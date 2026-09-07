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
            $mediaReplaced = $this->prepareDocxContentControls($tempDocxPath, $requestId, $signaturePath);

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

            // Touch version and document to rotate ONLYOFFICE cache key
            $version->touch();
            $document->touch();

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
     * Convert any Word OpenXML Content Controls (<w:sdt>) or pending text badges matching pending_sig_{$requestId}
     * into a standard ${PENDING_SIG_{$requestId}} placeholder macro so TemplateProcessor can replace it with an image.
     * Also replaces any inserted placeholder PNGs in word/media/ with the approved signature image.
     * If no placeholder exists in the document, appends a dedicated signature paragraph at the bottom.
     */
    protected function prepareDocxContentControls(string $docxPath, int $requestId, ?string $signaturePath = null): bool
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
                $sigBytes = $this->onlyOfficeService ? $this->onlyOfficeService->formatSquareSignature($rawSigBytes, 400, 24) : $rawSigBytes;
                
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entryName = $zip->getNameIndex($i);
                    if (str_starts_with($entryName, 'word/media/')) {
                        $imgBytes = $zip->getFromIndex($i);
                        if ($imgBytes) {
                            $size = @getimagesizefromstring($imgBytes);
                            $isTargetPlaceholder = false;

                            // 1. Check if PNG contains the DocuFlowSigReq tag for this request
                            if (str_contains($imgBytes, "DocuFlowSigReq\0" . $requestId) || 
                                str_contains($imgBytes, "DocuFlowSigReq:" . $requestId) || 
                                str_contains($imgBytes, "request_id=" . $requestId) ||
                                str_contains($imgBytes, "PENDING_SIG_" . $requestId)) {
                                $isTargetPlaceholder = true;
                            }
                            // 2. If the image is a generic DocuFlow placeholder without specific request id tag (fallback)
                            elseif (str_contains($imgBytes, "DocuFlowSigReq") && !preg_match('/DocuFlowSigReq[^\d]*(\d+)/', $imgBytes)) {
                                $isTargetPlaceholder = true;
                            }

                            if ($isTargetPlaceholder) {
                                $zip->addFromString($entryName, $sigBytes);
                                $mediaReplaced = true;
                            }
                        }
                    }
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
