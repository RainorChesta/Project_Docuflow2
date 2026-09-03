<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
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
        float $width = 40.0,
        float $height = 25.0,
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

            file_put_contents($tempSourcePdf, $disk->get($filePath));

            $pdf = new Fpdi();
            $pdf->SetAutoPageBreak(false);
            $pageCount = $pdf->setSourceFile($tempSourcePdf);

            if ($pageCount < 1) {
                Log::error("PdfSignatureProcessorService: PDF has no pages.");
                @unlink($tempSourcePdf);
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
                    $pdf->Image($signaturePath, $finalX, $finalY, $width, $height, 'PNG');
                }
            }

            // Preserve original PDF backup if it doesn't already exist
            $backupPath = $this->getBackupFilePath($filePath);
            if (!$disk->exists($backupPath)) {
                $disk->put($backupPath, $disk->get($filePath));
            }

            // Push current state into undo stack for reverting the most recent signature
            $undoHistory = Cache::get('pdf_sig_undo_stack_' . $version->id, []);
            $stepFile = preg_replace('/\.pdf$/i', '.undo_step_' . microtime(true) . '.pdf', $filePath);
            $disk->put($stepFile, $disk->get($filePath));
            $undoHistory[] = $stepFile;
            Cache::put('pdf_sig_undo_stack_' . $version->id, $undoHistory, now()->addDays(30));

            $pdf->Output('F', $tempOutputPdf);

            // Write modified PDF back to storage
            $modifiedContent = file_get_contents($tempOutputPdf);
            $disk->put($filePath, $modifiedContent);

            // Cleanup temp files
            @unlink($tempSourcePdf);
            @unlink($tempOutputPdf);

            // Invalidate caches and touch timestamps
            $version->touch();
            $document->touch();
            Cache::forget('onlyoffice_doc_key_' . $document->id);

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

            $undoHistory = Cache::get('pdf_sig_undo_stack_' . $version->id, []);

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
                $originalContent = $disk->get($backupPath);
                $disk->put($filePath, $originalContent);
            } else {
                Log::warning("PdfSignatureProcessorService: No undo snapshot or original backup found for {$filePath}");
                return false;
            }

            // Invalidate caches and touch timestamps
            $version->touch();
            $document->touch();
            Cache::forget('onlyoffice_doc_key_' . $document->id);

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
