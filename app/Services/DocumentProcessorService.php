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
        protected PdfSignatureProcessorService $pdfProcessor
    ) {}

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
                $width = $signatureRequest?->width ?? 40.0;
                $height = $signatureRequest?->height ?? 25.0;
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

            // Process the document using PHPWord TemplateProcessor
            $templateProcessor = new \PhpOffice\PhpWord\TemplateProcessor($tempDocxPath);
            
            // Search for ${PENDING_SIG_123} and replace it with the image
            $macroName = "PENDING_SIG_{$requestId}";
            
            // The image is replaced directly
            $templateProcessor->setImageValue($macroName, [
                'path' => $signaturePath,
                'width' => 140,
                'height' => 140,
                'ratio' => false
            ]);

            // Save the processed document
            $templateProcessor->saveAs($tempDocxPath);

            // Overwrite the original file in storage
            $modifiedContent = file_get_contents($tempDocxPath);
            $disk->put($filePath, $modifiedContent);

            // Touch version and document to rotate ONLYOFFICE cache key
            $version->touch();
            $document->touch();

            // Force OnlyOffice to fetch the new modified file by clearing its cache key
            \Illuminate\Support\Facades\Cache::forget('onlyoffice_doc_key_' . $document->id);

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
}
