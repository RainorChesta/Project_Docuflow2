<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\SignatureRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use setasign\Fpdi\Fpdi;

class PdfSignatureProcessorService
{
    /**
     * Preset positions mapping to coordinate calculation.
     */
    public const PRESET_BOTTOM_RIGHT  = 'bottom-right';
    public const PRESET_BOTTOM_LEFT   = 'bottom-left';
    public const PRESET_BOTTOM_CENTER = 'bottom-center';
    public const PRESET_TOP_RIGHT     = 'top-right';
    public const PRESET_TOP_LEFT      = 'top-left';

    /**
     * Stamp signature PNG onto a PDF document version.
     *
     * @param Document $document
     * @param DocumentVersion $version
     * @param string $signaturePath Local absolute file path to signature image
     * @param int|null $pageNumber 1-indexed, or null/-1 for last page
     * @param float|null $x X coordinate in mm (if custom)
     * @param float|null $y Y coordinate in mm (if custom)
     * @param float $width Width in mm (default 40mm)
     * @param float $height Height in mm (default 25mm)
     * @param string|null $preset Position preset (e.g. 'bottom-right')
     * @param string|null $signerName Optional signer name to print under signature
     * @return bool
     */
    public function processPdfSignature(
        Document $document,
        DocumentVersion $version,
        string $signaturePath,
        ?int $pageNumber = 1,
        ?float $x = null,
        ?float $y = null,
        float $width = 24.0,
        float $height = 24.0,
        ?string $preset = self::PRESET_BOTTOM_RIGHT,
        ?string $signerName = null
    ): bool {
        try {
            $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
            $filePath = $version->file_path;

            if (!$disk->exists($filePath)) {
                Log::error("PdfSignatureProcessorService: PDF file not found at: {$filePath}");
                return false;
            }

            if (!file_exists($signaturePath)) {
                Log::error("PdfSignatureProcessorService: Signature image not found at: {$signaturePath}");
                return false;
            }

            // Create temp paths
            $tempSourcePdf = storage_path('app/temp_src_' . uniqid() . '.pdf');
            $tempOutputPdf = storage_path('app/temp_out_' . uniqid() . '.pdf');
            $tempSigFile   = storage_path('app/temp_sig_' . uniqid() . '.png');

            $basePath = $this->getBaseNoRequestsFilePath($filePath);
            $sourceStoragePath = $disk->exists($basePath) ? $basePath : $filePath;

            file_put_contents($tempSourcePdf, $disk->get($sourceStoragePath));

            // Trim signature image tightly to ink strokes so it fits the bounding box with zero padding
            $rawSigBytes = file_get_contents($signaturePath);
            $onlyOfficeService = app(\App\Services\OnlyOfficeService::class);
            $trimmedSigBytes = $onlyOfficeService->trimSignatureImage($rawSigBytes);
            file_put_contents($tempSigFile, $trimmedSigBytes);

            $oldKey = $onlyOfficeService->generateDocumentKey($document, $version);
            Cache::put('ignore_onlyoffice_key_' . $oldKey, true, now()->addMinutes(2));

            $pdf = new Fpdi();
            $pdf->SetAutoPageBreak(false);
            $pageCount = $pdf->setSourceFile($tempSourcePdf);

            if ($pageCount < 1) {
                Log::error("PdfSignatureProcessorService: PDF has no pages.");
                @unlink($tempSourcePdf);
                @unlink($tempSigFile);
                return false;
            }

            // If pageNumber is negative or greater than pageCount, default to last page
            $targetPage = ($pageNumber === null || $pageNumber < 1 || $pageNumber > $pageCount)
                ? $pageCount
                : $pageNumber;

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tplId);

                // Add page preserving exact size and orientation
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);

                if ($pageNo === $targetPage) {
                    $pageWidth = $size['width'];
                    $pageHeight = $size['height'];

                    // Calculate Coordinates if not explicitly provided
                    [$finalX, $finalY] = $this->calculateCoordinates(
                        $pageWidth,
                        $pageHeight,
                        $width,
                        $height,
                        $x,
                        $y,
                        $preset
                    );

                    // Overlay signature image only (do not print signer name)
                    $pdf->Image($tempSigFile, $finalX, $finalY, $width, $height, 'PNG');
                }
            }

            // Preserve original PDF backup if it doesn't already exist
            $backupPath = $this->getBackupFilePath($filePath);
            if (!$disk->exists($backupPath)) {
                $disk->put($backupPath, $disk->get($sourceStoragePath));
            }

            // Push current state into undo stack for reverting the most recent signature
            $undoHistory = Cache::get('pdf_sig_undo_stack_' . $version->id, []);
            $stepFile = preg_replace('/\.pdf$/i', '.undo_step_' . microtime(true) . '.pdf', $filePath);
            $disk->put($stepFile, $disk->get($sourceStoragePath));
            $undoHistory[] = $stepFile;
            Cache::put('pdf_sig_undo_stack_' . $version->id, $undoHistory, now()->addDays(30));

            $pdf->Output('F', $tempOutputPdf);

            // Write modified PDF back to storage
            $modifiedContent = file_get_contents($tempOutputPdf);
            $disk->put($filePath, $modifiedContent);
            if ($disk->exists($basePath)) {
                $disk->put($basePath, $modifiedContent);
            }

            // Cleanup temp files
            @unlink($tempSourcePdf);
            @unlink($tempOutputPdf);
            @unlink($tempSigFile);

            // Re-apply any pending/approved signature requests on top of the updated base PDF
            if ($disk->exists($basePath) || SignatureRequest::where('document_id', $document->id)->whereIn('status', ['pending', 'approved'])->exists()) {
                $this->syncPdfSignatureRequests($document, $version);
            } else {
                // Invalidate caches and touch timestamps
                $version->touch();
                $document->touch();
                Cache::forget('onlyoffice_doc_key_' . $document->id);
                $onlyOfficeService->rotateDocumentKey($document, $version);
            }

            Log::info("PdfSignatureProcessorService: Successfully stamped signature on Document #{$document->id} (Page {$targetPage})");
            return true;

        } catch (\Throwable $e) {
            Log::error("PdfSignatureProcessorService: Error stamping PDF signature: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            if (isset($tempSourcePdf) && file_exists($tempSourcePdf)) {
                @unlink($tempSourcePdf);
            }
            if (isset($tempOutputPdf) && file_exists($tempOutputPdf)) {
                @unlink($tempOutputPdf);
            }
            if (isset($tempSigFile) && file_exists($tempSigFile)) {
                @unlink($tempSigFile);
            }

            return false;
        }
    }

    /**
     * Calculate absolute X and Y coordinates in mm on a page.
     */
    protected function calculateCoordinates(
        float $pageWidth,
        float $pageHeight,
        float $width,
        float $height,
        ?float $x,
        ?float $y,
        ?string $preset
    ): array {
        if ($x !== null && $y !== null) {
            return [
                max(0, min($x, max(0, $pageWidth - $width))),
                max(0, min($y, max(0, $pageHeight - $height))),
            ];
        }

        $marginRight = 20.0;
        $marginLeft = 20.0;
        $marginBottom = 25.0;
        $marginTop = 20.0;

        return match ($preset) {
            self::PRESET_BOTTOM_LEFT => [
                $marginLeft,
                max(0, $pageHeight - $marginBottom - $height),
            ],
            self::PRESET_BOTTOM_CENTER => [
                max(0, ($pageWidth - $width) / 2),
                max(0, $pageHeight - $marginBottom - $height),
            ],
            self::PRESET_TOP_RIGHT => [
                max(0, $pageWidth - $marginRight - $width),
                $marginTop,
            ],
            self::PRESET_TOP_LEFT => [
                $marginLeft,
                $marginTop,
            ],
            default => [ // PRESET_BOTTOM_RIGHT
                max(0, $pageWidth - $marginRight - $width),
                max(0, $pageHeight - $marginBottom - $height),
            ],
        };
    }

    /**
     * Get backup file path for preserving the clean original PDF.
     */
    public function getBackupFilePath(string $filePath): string
    {
        return preg_replace('/\.pdf$/i', '.original_pre_sig.pdf', $filePath);
    }

    /**
     * Get file path for preserving the base PDF without SignatureRequest overlays (pending placeholders or approved request signatures).
     */
    public function getBaseNoRequestsFilePath(string $filePath): string
    {
        return preg_replace('/\.pdf$/i', '.base_no_req.pdf', $filePath);
    }

    /**
     * Synchronize all active SignatureRequest overlays (pending placeholder badges and approved signatures/stamps)
     * onto the PDF document version cleanly from the base PDF.
     */
    public function syncPdfSignatureRequests(Document $document, DocumentVersion $version): bool
    {
        $tempFiles = [];
        try {
            $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
            $filePath = $version->file_path;

            if (!$filePath || !$disk->exists($filePath)) {
                Log::error("PdfSignatureProcessorService::syncPdfSignatureRequests: PDF file not found at: {$filePath}");
                return false;
            }

            if (!str_ends_with(strtolower($filePath), '.pdf') && !($version->file_mime && str_contains(strtolower($version->file_mime), 'pdf'))) {
                return false;
            }

            $onlyOfficeService = app(\App\Services\OnlyOfficeService::class);
            $oldKey = $onlyOfficeService->generateDocumentKey($document, $version);
            Cache::put('ignore_onlyoffice_key_' . $oldKey, true, now()->addMinutes(2));

            $basePath = $this->getBaseNoRequestsFilePath($filePath);
            if (!$disk->exists($basePath)) {
                $disk->put($basePath, $disk->get($filePath));
            }

            $backupPath = $this->getBackupFilePath($filePath);
            if (!$disk->exists($backupPath)) {
                $disk->put($backupPath, $disk->get($basePath));
            }

            // Fetch all signature requests for this document ordered by ID so newer requests take precedence
            $allRequests = SignatureRequest::where('document_id', $document->id)
                ->with(['targetUser.signatures', 'requestedSignature.company'])
                ->orderBy('id', 'asc')
                ->get();

            // Keep latest active request per (target_user_id, requested_signature_id)
            $activeRequestsMap = [];
            foreach ($allRequests as $req) {
                $key = $req->target_user_id . '_' . ($req->requested_signature_id ?? 'orig');
                if ($req->status === 'approved' || ($req->status === 'pending' && !$req->is_used)) {
                    $activeRequestsMap[$key] = $req;
                } elseif ($req->status === 'rejected') {
                    unset($activeRequestsMap[$key]);
                }
            }

            $activeRequests = array_values($activeRequestsMap);

            // If no active requests exist, restore clean base PDF directly
            if (empty($activeRequests)) {
                $disk->put($filePath, $disk->get($basePath));
                $version->touch();
                $document->touch();
                Cache::forget('onlyoffice_doc_key_' . $document->id);
                $onlyOfficeService->rotateDocumentKey($document, $version);
                return true;
            }

            // Prepare overlay image files for each active request
            $overlays = [];
            foreach ($activeRequests as $req) {
                $imgBytes = null;
                $isStamp = $req->isStamp();

                if ($req->status === 'pending') {
                    $badgeText = $isStamp
                        ? ($req->requestedSignature?->company?->name ?? $req->targetUser?->name ?? 'Stempel')
                        : ($req->targetUser?->name ?? 'Approver');
                    $imgBytes = $onlyOfficeService->generatePlaceholderPngBytes($badgeText, $req->id, $isStamp);
                } elseif ($req->status === 'approved') {
                    $sig = null;
                    if ($isStamp && $req->requestedSignature) {
                        $sig = $req->requestedSignature;
                    } elseif ($req->requestedSignature) {
                        $sig = $req->requestedSignature;
                    } else {
                        $sig = $req->targetUser?->signatures?->firstWhere('type', 'original')
                            ?? $req->targetUser?->signatures?->first();
                    }

                    $signaturePath = null;
                    if ($sig && $sig->file_path) {
                        if (Storage::disk('public')->exists($sig->file_path)) {
                            $signaturePath = Storage::disk('public')->path($sig->file_path);
                        } elseif (file_exists(storage_path('app/public/' . ltrim($sig->file_path, '/')))) {
                            $signaturePath = storage_path('app/public/' . ltrim($sig->file_path, '/'));
                        } elseif (file_exists(public_path('storage/' . ltrim($sig->file_path, '/')))) {
                            $signaturePath = public_path('storage/' . ltrim($sig->file_path, '/'));
                        } elseif (file_exists($sig->file_path)) {
                            $signaturePath = $sig->file_path;
                        }
                    }

                    if ($signaturePath && file_exists($signaturePath)) {
                        $rawBytes = file_get_contents($signaturePath);
                        $imgBytes = $onlyOfficeService->trimSignatureImage($rawBytes);
                    }
                }

                if (!empty($imgBytes)) {
                    $tempImg = storage_path('app/temp_req_sig_' . $req->id . '_' . uniqid() . '.png');
                    file_put_contents($tempImg, $imgBytes);
                    $tempFiles[] = $tempImg;

                    $overlays[] = [
                        'file' => $tempImg,
                        'page_number' => $req->page_number,
                        'pos_x' => $req->pos_x !== null ? (float) $req->pos_x : null,
                        'pos_y' => $req->pos_y !== null ? (float) $req->pos_y : null,
                        'width' => (float) ($req->width ?? 24.0),
                        'height' => (float) ($req->height ?? 24.0),
                        'preset' => $req->preset_position ?? self::PRESET_BOTTOM_RIGHT,
                    ];
                }
            }

            if (empty($overlays)) {
                $disk->put($filePath, $disk->get($basePath));
                $version->touch();
                $document->touch();
                Cache::forget('onlyoffice_doc_key_' . $document->id);
                $onlyOfficeService->rotateDocumentKey($document, $version);
                return true;
            }

            $tempSourcePdf = storage_path('app/temp_sync_src_' . uniqid() . '.pdf');
            $tempOutputPdf = storage_path('app/temp_sync_out_' . uniqid() . '.pdf');
            $tempFiles[] = $tempSourcePdf;
            $tempFiles[] = $tempOutputPdf;

            file_put_contents($tempSourcePdf, $disk->get($basePath));

            $pdf = new Fpdi();
            $pdf->SetAutoPageBreak(false);
            $pageCount = $pdf->setSourceFile($tempSourcePdf);

            if ($pageCount < 1) {
                foreach ($tempFiles as $tf) {
                    if (file_exists($tf)) @unlink($tf);
                }
                return false;
            }

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $tplId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($tplId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($tplId);

                $pageWidth = $size['width'];
                $pageHeight = $size['height'];

                foreach ($overlays as $ov) {
                    $ovPage = $ov['page_number'];
                    $targetPage = ($ovPage === null || $ovPage < 1 || $ovPage > $pageCount)
                        ? $pageCount
                        : (int) $ovPage;

                    if ($pageNo === $targetPage) {
                        [$finalX, $finalY] = $this->calculateCoordinates(
                            $pageWidth,
                            $pageHeight,
                            $ov['width'],
                            $ov['height'],
                            $ov['pos_x'],
                            $ov['pos_y'],
                            $ov['preset']
                        );

                        $pdf->Image($ov['file'], $finalX, $finalY, $ov['width'], $ov['height'], 'PNG');
                    }
                }
            }

            $pdf->Output('F', $tempOutputPdf);

            $modifiedContent = file_get_contents($tempOutputPdf);
            $disk->put($filePath, $modifiedContent);

            foreach ($tempFiles as $tf) {
                if (file_exists($tf)) @unlink($tf);
            }

            $version->touch();
            $document->touch();
            Cache::forget('onlyoffice_doc_key_' . $document->id);
            $onlyOfficeService->rotateDocumentKey($document, $version);

            Log::info("PdfSignatureProcessorService: Synced " . count($overlays) . " signature request overlay(s) on Document #{$document->id}");
            return true;
        } catch (\Throwable $e) {
            Log::error("PdfSignatureProcessorService: Error syncing PDF signature requests: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            foreach ($tempFiles as $tf) {
                if (file_exists($tf)) @unlink($tf);
            }
            return false;
        }
    }

    /**
     * Check if an original clean backup or undo step exists for the version.
     */
    public function hasOriginalBackup(DocumentVersion $version): bool
    {
        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
        $undoHistory = Cache::get('pdf_sig_undo_stack_' . $version->id, []);
        if (!empty($undoHistory)) {
            return true;
        }
        return $disk->exists($this->getBackupFilePath($version->file_path));
    }

    /**
     * Revert the most recently added signature step on the PDF document.
     */
    public function revertPdfSignature(Document $document, DocumentVersion $version): bool
    {
        try {
            $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
            $filePath = $version->file_path;
            $backupPath = $this->getBackupFilePath($filePath);
            $basePath = $this->getBaseNoRequestsFilePath($filePath);

            $undoHistory = Cache::get('pdf_sig_undo_stack_' . $version->id, []);
            $restoredContent = null;

            if (!empty($undoHistory)) {
                // Pop the most recent signature step
                $lastStepFile = array_pop($undoHistory);
                Cache::put('pdf_sig_undo_stack_' . $version->id, $undoHistory, now()->addDays(30));

                if ($disk->exists($lastStepFile)) {
                    $restoredContent = $disk->get($lastStepFile);
                    $disk->put($filePath, $restoredContent);
                    $disk->delete($lastStepFile);
                } elseif ($disk->exists($backupPath)) {
                    $restoredContent = $disk->get($backupPath);
                    $disk->put($filePath, $restoredContent);
                }
            } elseif ($disk->exists($backupPath)) {
                // Fallback to original pristine backup
                $restoredContent = $disk->get($backupPath);
                $disk->put($filePath, $restoredContent);
            } else {
                Log::warning("PdfSignatureProcessorService: No undo snapshot or original backup found for {$filePath}");
                return false;
            }

            if ($restoredContent !== null && $disk->exists($basePath)) {
                $disk->put($basePath, $restoredContent);
                $this->syncPdfSignatureRequests($document, $version);
                return true;
            }

            // Invalidate caches and touch timestamps
            $version->touch();
            $document->touch();
            Cache::forget('onlyoffice_doc_key_' . $document->id);
            app(\App\Services\OnlyOfficeService::class)->rotateDocumentKey($document, $version);

            Log::info("PdfSignatureProcessorService: Successfully reverted most recent signature on PDF Document #{$document->id}");
            return true;
        } catch (\Throwable $e) {
            Log::error("PdfSignatureProcessorService: Error reverting PDF signature: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            return false;
        }
    }
}
