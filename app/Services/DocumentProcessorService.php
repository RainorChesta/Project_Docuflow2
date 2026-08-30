<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentProcessorService
{
    /**
     * Replace a pending signature text macro with the actual signature image using PHPWord.
     */
    public function processSignature(Document $document, DocumentVersion $version, int $requestId, string $signaturePath): bool
    {
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

            // Create a temporary path for the local file because PhpWord needs a real file path
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

            // Clean up temp files
            unlink($tempDocxPath);
            
            // Force OnlyOffice to fetch the new modified file by clearing its cache key
            \Illuminate\Support\Facades\Cache::forget('onlyoffice_doc_key_' . $document->id);

            Log::info("DocumentProcessorService: Successfully processed signature for document version ID: {$version->id}");
            return true;

        } catch (\Exception $e) {
            Log::error("DocumentProcessorService: Error processing document: " . $e->getMessage());
            
            if (isset($tempDocxPath) && file_exists($tempDocxPath)) {
                unlink($tempDocxPath);
            }
            
            return false;
        }
    }
}
