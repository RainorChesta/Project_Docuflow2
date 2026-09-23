<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\SignatureRequest;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class DocumentProcessorService
{
    public function __construct(
        protected PdfSignatureProcessorService $pdfProcessor,
        protected ?OnlyOfficeService $onlyOfficeService = null
    ) {
        $this->onlyOfficeService = $onlyOfficeService ?? app(OnlyOfficeService::class);
    }

    /**
     * Replace or stamp a signature onto a document version.
     * Automatically handles .docx (via PHPWord) and .pdf (via FPDI).
     */
    public function processSignature(
        Document $document,
        DocumentVersion $version,
        int $requestId,
        string $signaturePath,
        ?SignatureRequest $signatureRequest = null
    ): bool {
        try {
            $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
            $filePath = $version->file_path;

            if (!$disk->exists($filePath)) {
                Log::error("DocumentProcessorService: File does not exist at path: {$filePath}");
                return false;
            }

            if (!file_exists($signaturePath)) {
                Log::error("DocumentProcessorService: Signature image does not exist at path: {$signaturePath}");
                return false;
            }

            // Determine if the file is PDF or DOCX
            $isPdf = false;
            if ($filePath && str_ends_with(strtolower($filePath), '.pdf')) {
                $isPdf = true;
            } elseif ($version->file_mime && str_contains(strtolower($version->file_mime), 'pdf')) {
                $isPdf = true;
            }

            if ($isPdf) {
                $pageNumber = $signatureRequest?->page_number ?? 1;
                $posX = $signatureRequest?->pos_x;
                $posY = $signatureRequest?->pos_y;
                $width = $signatureRequest?->width ?? 24.0;
                $height = $signatureRequest?->height ?? 24.0;
                $preset = $signatureRequest?->preset_position ?? PdfSignatureProcessorService::PRESET_BOTTOM_RIGHT;

                return $this->pdfProcessor->processPdfSignature(
                    $document,
                    $version,
                    $signaturePath,
                    $pageNumber,
                    $posX,
                    $posY,
                    $width,
                    $height,
                    $preset
                );
            }

            // Handle DOCX via PHPWord TemplateProcessor
            $tempDocxPath = storage_path('app/temp_doc_' . uniqid() . '.docx');
            
            // Get file content and save to local temp path
            $fileContent = $disk->get($filePath);
            file_put_contents($tempDocxPath, $fileContent);

            // Pre-process Word OpenXML to ensure content controls, text badges, or inserted placeholder images are handled
            $mediaReplaced = $this->prepareDocxContentControls($tempDocxPath, $requestId, $signaturePath, $signatureRequest);

            // If media was not replaced in word/media/, process the document using PHPWord TemplateProcessor
            if (!$mediaReplaced) {
                $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($tempDocxPath);
                
                // Search for ${PENDING_SIG_123} and replace it with the image
                $macroName = "PENDING_SIG_{$requestId}";
                
                // The image is replaced directly (medium size ~18-20mm)
                $templateProcessor->setImageValue($macroName, [
                    'path' => $signaturePath,
                    'width' => 60,
                    'height' => 60,
                    'ratio' => false
                ]);

                // Save the processed document
                $templateProcessor->saveAs($tempDocxPath);
            }

            // Overwrite the original file in storage
            $modifiedContent = file_get_contents($tempDocxPath);
            $disk->put($filePath, $modifiedContent);
            @unlink($tempDocxPath);

            // Touch version and document and rotate ONLYOFFICE cache key
            $version->touch();
            $document->touch();
            $this->onlyOfficeService?->rotateDocumentKey($document, $version);

            Log::info("DocumentProcessorService: Successfully processed DOCX signature for document version ID: {$version->id}");
            return true;

        } catch (\Exception $e) {
            Log::error("DocumentProcessorService: Error processing document: " . $e->getMessage());
            
            if (isset($tempDocxPath) && file_exists($tempDocxPath)) {
                @unlink($tempDocxPath);
            }
            
            return false;
        }
    }

    /**
     * Remove any inserted signature placeholder images, content controls, or text badges for a rejected signature request.
     */
    public function removeSignaturePlaceholder(
        Document $document,
        DocumentVersion $version,
        int $requestId
    ): bool {
        try {
            $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
            $filePath = $version->file_path;

            if (!$disk->exists($filePath)) {
                Log::error("DocumentProcessorService: File does not exist at path: {$filePath}");
                return false;
            }

            // Determine if the file is PDF or DOCX
            $isPdf = false;
            if ($filePath && str_ends_with(strtolower($filePath), '.pdf')) {
                $isPdf = true;
            } elseif ($version->file_mime && str_contains(strtolower($version->file_mime), 'pdf')) {
                $isPdf = true;
            }

            if ($isPdf) {
                // PDFs are not stamped with placeholders during request creation, so we only rotate keys
                $version->touch();
                $document->touch();
                $this->onlyOfficeService?->rotateDocumentKey($document, $version);
                return true;
            }

            // Handle DOCX removal
            $tempDocxPath = storage_path('app/temp_rm_sig_' . uniqid() . '.docx');
            $fileContent = $disk->get($filePath);
            file_put_contents($tempDocxPath, $fileContent);

            $this->purgeDocxSignaturePlaceholder($tempDocxPath, $requestId);

            $modifiedContent = file_get_contents($tempDocxPath);
            $disk->put($filePath, $modifiedContent);
            @unlink($tempDocxPath);

            // Touch version and document and rotate ONLYOFFICE cache key
            $version->touch();
            $document->touch();
            $this->onlyOfficeService?->rotateDocumentKey($document, $version);

            Log::info("DocumentProcessorService: Successfully purged rejected signature placeholder for document version ID: {$version->id}, request ID: {$requestId}");
            return true;

        } catch (\Throwable $e) {
            Log::error("DocumentProcessorService: Error removing signature placeholder: " . $e->getMessage(), [
                'exception' => $e,
            ]);

            if (isset($tempDocxPath) && file_exists($tempDocxPath)) {
                @unlink($tempDocxPath);
            }

            return false;
        }
    }

    /**
     * Revert any previously stamped signatures on this document version back to their pending placeholder badges.
     * Used when initializing a new revision (e.g. V2) from a rejected or unfinalized version.
     */
    public function revertSignaturesToPlaceholders(Document $document, DocumentVersion $version): bool
    {
        try {
            $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
            $filePath = $version->file_path;

            // 1. Handle HTML content
            if ($version->content) {
                // Revert any rendered signature img tags back to [ttd:Name] or [stamp:Name] tags
                $newContent = preg_replace_callback('/<img\b[^>]*?data-ttd-user="([^"]+)"[^>]*\/?>/i', function ($m) {
                    return '[ttd:' . $m[1] . ']';
                }, $version->content);

                $newContent = preg_replace('/<span\b[^>]*?class="doku-signature-badge[^>]*>.*?\[(?:TTD|Stempel)\s+Ditolak:\s*([^\]]+)\].*?<\/span>/is', '[ttd:$1]', $newContent);

                if ($newContent !== $version->content) {
                    $version->update(['content' => $newContent]);
                }
            }

            if (!$filePath || !$disk->exists($filePath)) {
                return true;
            }

            // Determine if the file is PDF or DOCX
            if (str_ends_with(strtolower($filePath), '.pdf') || ($version->file_mime && str_contains(strtolower($version->file_mime), 'pdf'))) {
                return true;
            }

            // 2. Handle DOCX
            $tempDocxPath = storage_path('app/temp_rev_sig_' . uniqid() . '.docx');
            $fileContent = $disk->get($filePath);
            file_put_contents($tempDocxPath, $fileContent);

            $zip = new \ZipArchive();
            if ($zip->open($tempDocxPath) !== true) {
                @unlink($tempDocxPath);
                return false;
            }

            // Fetch all signature requests for this document
            $sigRequests = SignatureRequest::where('document_id', $document->id)
                ->with(['targetUser', 'requestedSignature'])
                ->get();

            if ($sigRequests->isEmpty()) {
                $zip->close();
                @unlink($tempDocxPath);
                return true;
            }

            // Scan word/media/ and map each signature/placeholder to its signature request
            $replacedAny = false;
            $usedRequestIds = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if (!str_starts_with($entryName, 'word/media/')) {
                    continue;
                }

                $imgBytes = $zip->getFromIndex($i);
                if (!$imgBytes) {
                    continue;
                }

                // Identify which SignatureRequest this image corresponds to
                $matchedRequest = null;
                $extractedId = $this->extractRequestIdFromImage($imgBytes);
                if ($extractedId) {
                    $matchedRequest = $sigRequests->firstWhere('id', $extractedId);
                }

                // If matched, replace this media entry with a fresh pending placeholder PNG
                if ($matchedRequest) {
                    $signerName = $matchedRequest->targetUser?->name ?? 'Approver';
                    $placeholderBytes = $this->onlyOfficeService
                        ? $this->onlyOfficeService->generatePlaceholderPngBytes($signerName, $matchedRequest->id, $matchedRequest->isStamp())
                        : '';

                    if (!empty($placeholderBytes)) {
                        $zip->addFromString($entryName, $placeholderBytes);
                        $replacedAny = true;
                        $usedRequestIds[] = $matchedRequest->id;
                    }
                }
            }

            // Fallback: If some 400x400 square images had no metadata marker, map remaining requests sequentially
            $remainingRequests = $sigRequests->filter(fn($r) => !in_array($r->id, $usedRequestIds, true))->values();
            if ($remainingRequests->isNotEmpty()) {
                $remIndex = 0;
                for ($i = 0; $i < $zip->numFiles && $remIndex < $remainingRequests->count(); $i++) {
                    $entryName = $zip->getNameIndex($i);
                    if (!str_starts_with($entryName, 'word/media/')) {
                        continue;
                    }
                    $imgBytes = $zip->getFromIndex($i);
                    $size = $imgBytes ? @getimagesizefromstring($imgBytes) : null;
                    if ($size && $size[0] >= 350 && $size[0] <= 450 && $size[1] >= 350 && $size[1] <= 450) {
                        // Check if not already a freshly replaced placeholder
                        if (!str_contains($imgBytes, "MENUNGGU PERSETUJUAN")) {
                            $req = $remainingRequests[$remIndex++];
                            $signerName = $req->targetUser?->name ?? 'Approver';
                            $placeholderBytes = $this->onlyOfficeService
                                ? $this->onlyOfficeService->generatePlaceholderPngBytes($signerName, $req->id, $req->isStamp())
                                : '';
                            if (!empty($placeholderBytes)) {
                                $zip->addFromString($entryName, $placeholderBytes);
                                $replacedAny = true;
                            }
                        }
                    }
                }
            }

            $zip->close();

            if ($replacedAny) {
                $modifiedContent = file_get_contents($tempDocxPath);
                $disk->put($filePath, $modifiedContent);
            }
            @unlink($tempDocxPath);

            $this->onlyOfficeService?->rotateDocumentKey($document, $version);
            Log::info("DocumentProcessorService: Reverted signatures to placeholders for document ID {$document->id}, version ID {$version->id}");
            return true;

        } catch (\Throwable $e) {
            Log::error("DocumentProcessorService: Error reverting signatures to placeholders: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            if (isset($tempDocxPath) && file_exists($tempDocxPath)) {
                @unlink($tempDocxPath);
            }
            return false;
        }
    }

    /**
     * Extract embedded DocuFlow signature request ID from an image binary (via text chunks or steganographic pixel blocks).
     */
    public function extractRequestIdFromImage(string $imgBytes): ?int
    {
        if (empty($imgBytes)) {
            return null;
        }

        // 1. Precise PNG Chunk Parser (100% immune to CRC byte collisions)
        if (str_starts_with($imgBytes, "\x89PNG\r\n\x1a\n")) {
            $offset = 8;
            $len = strlen($imgBytes);
            while ($offset + 8 <= $len) {
                $chunkLen = unpack('N', substr($imgBytes, $offset, 4))[1];
                $chunkType = substr($imgBytes, $offset + 4, 4);
                $offset += 8;

                if ($offset + $chunkLen > $len) {
                    break;
                }

                if ($chunkType === 'tEXt' || $chunkType === 'iTXt') {
                    $chunkData = substr($imgBytes, $offset, $chunkLen);
                    $nullPos = strpos($chunkData, "\0");
                    if ($nullPos !== false) {
                        $keyword = substr($chunkData, 0, $nullPos);
                        $text = substr($chunkData, $nullPos + 1);
                        if (in_array(strtolower($keyword), ['docuflowsigreq', 'df-req'], true)) {
                            if (preg_match('/(\d+)/', $text, $m)) {
                                return (int) $m[1];
                            }
                        }
                    }
                }

                $offset += $chunkLen + 4; // Skip chunk data + 4 bytes CRC
                if ($chunkType === 'IEND') {
                    break;
                }
            }
        }

        // 2. Safe bounded delimiter regex on binary string
        if (preg_match('/DocuFlowSigReq(?:\0|:|#)+(\d+)(?:\0|#|\s|\]|$)/i', $imgBytes, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/\[DF-REQ:(?:#)?(\d+)(?:#)?\]/i', $imgBytes, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/DF-REQ(?:\0|:|#)+(\d+)(?:\0|#|\s|\]|$)/i', $imgBytes, $m)) {
            return (int) $m[1];
        }
        if (preg_match('/(?:PENDING_SIG_|request_id=)(\d+)/i', $imgBytes, $m)) {
            return (int) $m[1];
        }

        // 3. Fallback steganographic inspection
        $size = @getimagesizefromstring($imgBytes);
        if ($size && $size[0] >= 100 && $size[1] >= 100) {
            $im = @imagecreatefromstring($imgBytes);
            if ($im) {
                $w = imagesx($im);
                $h = imagesy($im);

                // Check 8x8 block steganography at (12, 12)
                if ($w >= 48 && $h >= 20) {
                    $magicRgb = imagecolorat($im, 12, 12);
                    $mr = ($magicRgb >> 16) & 0xFF;
                    $mg = ($magicRgb >> 8) & 0xFF;
                    $mb = $magicRgb & 0xFF;

                    if (abs($mr - 222) <= 18 && abs($mg - 173) <= 18 && abs($mb - 190) <= 18) {
                        $b0 = (imagecolorat($im, 20, 12) >> 16) & 0xFF;
                        $b1 = (imagecolorat($im, 28, 12) >> 16) & 0xFF;
                        $b2 = (imagecolorat($im, 36, 12) >> 16) & 0xFF;
                        $embeddedId = $b0 | ($b1 << 8) | ($b2 << 16);
                        imagedestroy($im);
                        if ($embeddedId > 0) {
                            return $embeddedId;
                        }
                    }
                }

                // Check single-pixel steganography at (8, 8)
                if ($w >= 11 && $h >= 10) {
                    $magicRgb = imagecolorat($im, 8, 8);
                    $mr = ($magicRgb >> 16) & 0xFF;
                    $mg = ($magicRgb >> 8) & 0xFF;
                    $mb = $magicRgb & 0xFF;

                    if (abs($mr - 222) <= 18 && abs($mg - 173) <= 18 && abs($mb - 190) <= 18) {
                        $reqRgb = imagecolorat($im, 9, 8);
                        $rr = ($reqRgb >> 16) & 0xFF;
                        $rg = ($reqRgb >> 8) & 0xFF;
                        $rb = $reqRgb & 0xFF;
                        $embeddedId = $rr | ($rg << 8) | ($rb << 16);
                        imagedestroy($im);
                        if ($embeddedId > 0) {
                            return $embeddedId;
                        }
                    }
                }

                imagedestroy($im);
            }
        }

        return null;
    }

    /**
     * Remove any placeholder images in word/media/, their drawing references in word/document.xml,
     * relationships in word/_rels/document.xml.rels, Content Controls, or text badges for the rejected request ID.
     */
    protected function purgeDocxSignaturePlaceholder(string $docxPath, int $requestId): bool
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($docxPath) !== true) {
                return false;
            }

            $targetMediaEntries = [];

            // 1. Scan word/media/ for matching placeholder images
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                if (str_starts_with($entryName, 'word/media/')) {
                    $imgBytes = $zip->getFromIndex($i);
                    if ($imgBytes) {
                        $extractedId = $this->extractRequestIdFromImage($imgBytes);
                        if ($extractedId !== null && $extractedId === $requestId) {
                            $targetMediaEntries[] = $entryName;
                        }
                    }
                }
            }

            // 2. Map target media filenames to Relationship IDs in word/_rels/document.xml.rels
            $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
            $targetRIds = [];
            if ($relsXml !== false) {
                foreach ($targetMediaEntries as $mediaEntry) {
                    $baseMediaName = basename($mediaEntry);
                    if (preg_match_all('/<Relationship\b[^>]*?Id="([^"]+)"[^>]*?Target="[^"]*' . preg_quote($baseMediaName, '/') . '"[^>]*\/?>/i', $relsXml, $matches)) {
                        foreach ($matches[1] as $rId) {
                            $targetRIds[] = $rId;
                        }
                    }
                }
            }

            // 3. Remove drawings, shapes, content controls, and text badges in word/document.xml
            $documentXml = $zip->getFromName('word/document.xml');
            if ($documentXml !== false) {
                $dom = new \DOMDocument();
                $prevEntityLoader = libxml_use_internal_errors(true);
                if ($dom->loadXML($documentXml)) {
                    $xpath = new \DOMXPath($dom);
                    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');
                    $xpath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
                    $xpath->registerNamespace('r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
                    $xpath->registerNamespace('v', 'urn:schemas-microsoft-com:vml');

                    // Remove drawing/shape elements referencing the target rIds
                    foreach ($targetRIds as $rId) {
                        $nodes = $xpath->query("//*[@r:embed='{$rId}' or @r:id='{$rId}' or @r:link='{$rId}' or @id='{$rId}']");
                        if ($nodes && $nodes->length > 0) {
                            foreach ($nodes as $node) {
                                $curr = $node;
                                while ($curr && !in_array($curr->localName, ['drawing', 'pict', 'r', 'p', 'sdt'], true)) {
                                    $curr = $curr->parentNode;
                                }
                                if ($curr) {
                                    if ($curr->localName === 'drawing' || $curr->localName === 'pict') {
                                        $parentRun = $curr->parentNode;
                                        if ($parentRun && $parentRun->localName === 'r') {
                                            $parentParagraph = $parentRun->parentNode;
                                            $parentRun->removeChild($curr);
                                            if (!$parentRun->hasChildNodes() && $parentParagraph) {
                                                $parentParagraph->removeChild($parentRun);
                                                if (!$parentParagraph->hasChildNodes() && $parentParagraph->parentNode) {
                                                    $parentParagraph->parentNode->removeChild($parentParagraph);
                                                }
                                            }
                                        } else {
                                            $curr->parentNode?->removeChild($curr);
                                        }
                                    } elseif ($curr->localName === 'r') {
                                        $parentParagraph = $curr->parentNode;
                                        $parentParagraph?->removeChild($curr);
                                    }
                                }
                            }
                        }
                    }

                    // Remove Content Controls matching pending_sig_{$requestId}
                    $sdtNodes = $xpath->query("//w:sdt[.//w:tag[@w:val='pending_sig_{$requestId}'] or .//w:alias[@w:val='pending_sig_{$requestId}'] or contains(., 'pending_sig_{$requestId}') or contains(., 'PENDING_SIG_{$requestId}')]");
                    if ($sdtNodes && $sdtNodes->length > 0) {
                        foreach ($sdtNodes as $sdtNode) {
                            $sdtNode->parentNode?->removeChild($sdtNode);
                        }
                    }

                    $documentXml = $dom->saveXML();
                }
                libxml_clear_errors();
                libxml_use_internal_errors($prevEntityLoader);

                // Remove text badge paragraph
                $badgePattern = '/<w:p\b[^>]*>(?:(?!<\/w:p>).)*?(?:MENUNGGU.*?PENDING_SIG_' . $requestId . '|MENUNGGU.*?#' . $requestId . '|MENUNGGU.*?' . $requestId . ')(?:(?!<\/w:p>).)*?<\/w:p>/is';
                $documentXml = preg_replace($badgePattern, '', $documentXml);

                // Remove standalone macro text
                $pattern = '/(?:\$\{)?(?:PENDING_SIG_|pending_sig_)' . $requestId . '\}?/i';
                $documentXml = preg_replace($pattern, '', $documentXml);

                $zip->addFromString('word/document.xml', $documentXml);
            }

            // 4. Delete target media entries from the zip
            foreach ($targetMediaEntries as $mediaEntry) {
                $zip->deleteName($mediaEntry);
            }

            // 5. Clean up relationships in word/_rels/document.xml.rels
            if ($relsXml !== false && !empty($targetRIds)) {
                foreach ($targetRIds as $rId) {
                    $relsXml = preg_replace('/<Relationship\b[^>]*?Id="' . preg_quote($rId, '/') . '"[^>]*\/?>/i', '', $relsXml);
                }
                $zip->addFromString('word/_rels/document.xml.rels', $relsXml);
            }

            $zip->close();
            return true;
        } catch (\Throwable $e) {
            Log::warning("DocumentProcessorService: Failed to purge docx signature placeholder: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Convert any Word OpenXML Content Controls (<w:sdt>) or pending text badges matching pending_sig_{$requestId}
     * into a standard ${PENDING_SIG_{$requestId}} placeholder macro so TemplateProcessor can replace it with an image.
     * Also replaces any inserted placeholder PNGs in word/media/ with the approved signature image.
     * If no placeholder exists in the document, appends a dedicated signature paragraph at the bottom.
     */
    protected function prepareDocxContentControls(string $docxPath, int $requestId, ?string $signaturePath = null, ?SignatureRequest $signatureRequest = null): bool
    {
        try {
            $zip = new \ZipArchive();
            if ($zip->open($docxPath) !== true) {
                return false;
            }

            $mediaReplaced = false;

            // Direct in-place replacement of placeholder images in word/media/
            if ($signaturePath && file_exists($signaturePath)) {
                $rawSigBytes = file_get_contents($signaturePath);
                $isStamp = $signatureRequest ? $signatureRequest->isStamp() : false;
                $sigBytes = $this->onlyOfficeService ? $this->onlyOfficeService->formatSquareSignature($rawSigBytes, 400, 24, $requestId, $isStamp) : $rawSigBytes;
                
                $unidentifiedMedia = [];

                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entryName = $zip->getNameIndex($i);
                    if (str_starts_with($entryName, 'word/media/')) {
                        $imgBytes = $zip->getFromIndex($i);
                        if ($imgBytes) {
                            $extractedId = $this->extractRequestIdFromImage($imgBytes);
                            
                            // Exact match on request ID
                            if ($extractedId !== null && $extractedId === $requestId) {
                                $zip->addFromString($entryName, $sigBytes);
                                $mediaReplaced = true;
                                break; // Stop! Replaced only the placeholder belonging to this request
                            } elseif ($extractedId === null) {
                                $size = @getimagesizefromstring($imgBytes);
                                if ($size && $size[0] >= 350 && $size[0] <= 450 && $size[1] >= 350 && $size[1] <= 450) {
                                    $unidentifiedMedia[] = $entryName;
                                }
                            }
                        }
                    }
                }

                // Fallback: If no exact ID match was found, BUT there is EXACTLY 1 unidentified square placeholder image in the entire document
                // (e.g. single-signatory document where PNG chunks/steganography were stripped), replace that single image.
                if (!$mediaReplaced && count($unidentifiedMedia) === 1) {
                    $zip->addFromString($unidentifiedMedia[0], $sigBytes);
                    $mediaReplaced = true;
                }
            }

            $documentXml = $zip->getFromName('word/document.xml');
            if ($documentXml === false) {
                $zip->close();
                return $mediaReplaced;
            }

            $macroName = 'PENDING_SIG_' . $requestId;
            $macroText = '${' . $macroName . '}';
            $modified = false;

            // 1. Check for text badges or strings containing MENUNGGU and the requestId or PENDING_SIG_{$requestId}
            // and replace the entire enclosing paragraph so no badge brackets/text remain around the image.
            $badgePattern = '/<w:p\b[^>]*>(?:(?!<\/w:p>).)*?(?:MENUNGGU.*?PENDING_SIG_' . $requestId . '|MENUNGGU.*?#' . $requestId . '|MENUNGGU.*?' . $requestId . ')(?:(?!<\/w:p>).)*?<\/w:p>/is';
            if (preg_match($badgePattern, $documentXml)) {
                $replacement = $mediaReplaced ? '' : ('<w:p><w:r><w:t>' . $macroText . '</w:t></w:r></w:p>');
                $documentXml = preg_replace($badgePattern, $replacement, $documentXml, 1);
                $modified = true;
            }

            // 2. Check for Content Controls (<w:sdt>) with pending_sig_ or label
            if (str_contains($documentXml, 'pending_sig_' . $requestId) || str_contains($documentXml, '<w:sdt')) {
                $dom = new \DOMDocument();
                $prevEntityLoader = libxml_use_internal_errors(true);
                if ($dom->loadXML($documentXml)) {
                    $xpath = new \DOMXPath($dom);
                    $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

                    $sdtNodes = $xpath->query("//w:sdt[.//w:tag[@w:val='pending_sig_{$requestId}'] or .//w:alias[@w:val='pending_sig_{$requestId}'] or contains(., 'pending_sig_{$requestId}') or contains(., 'PENDING_SIG_{$requestId}')]");

                    if ($sdtNodes && $sdtNodes->length > 0) {
                        foreach ($sdtNodes as $sdtNode) {
                            $parent = $sdtNode->parentNode;
                            if (!$parent) continue;

                            if ($mediaReplaced) {
                                $parent->removeChild($sdtNode);
                            } else {
                                $rNode = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:r');
                                $tNode = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:t', $macroText);
                                $rNode->appendChild($tNode);

                                if ($parent->nodeName === 'w:p') {
                                    $parent->replaceChild($rNode, $sdtNode);
                                } else {
                                    $pNode = $dom->createElementNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'w:p');
                                    $pNode->appendChild($rNode);
                                    $parent->replaceChild($pNode, $sdtNode);
                                }
                            }
                            $modified = true;
                        }
                        if ($modified) {
                            $documentXml = $dom->saveXML();
                        }
                    }
                }
                libxml_clear_errors();
                libxml_use_internal_errors($prevEntityLoader);
            }

            // 3. Check if text runs were split across multiple <w:t> tags for ${PENDING_SIG_{$requestId}}
            if (!$mediaReplaced && (str_contains($documentXml, 'PENDING_SIG_' . $requestId) || str_contains($documentXml, 'pending_sig_' . $requestId))) {
                $pattern = '/(?:\$\{)?(?:PENDING_SIG_|pending_sig_)' . $requestId . '\}?/i';
                $documentXml = preg_replace($pattern, $macroText, $documentXml);
                $modified = true;
            }

            if ($modified) {
                $zip->addFromString('word/document.xml', $documentXml);
            }

            $zip->close();
            return $mediaReplaced;
        } catch (\Throwable $e) {
            Log::warning("DocumentProcessorService: Failed to prepare docx content controls: " . $e->getMessage());
            return false;
        }
    }
}
