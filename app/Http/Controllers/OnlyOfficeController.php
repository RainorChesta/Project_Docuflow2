<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\AuditService;
use App\Services\OnlyOfficeService;
use App\Services\VersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OnlyOfficeController extends Controller
{
    public function __construct(
        protected OnlyOfficeService $onlyOfficeService,
        protected VersionService $versionService,
        protected AuditService $auditService,
    ) {}

    /**
     * Serve the document file to ONLYOFFICE Docs Document Server.
     */
    public function file(Document $document, DocumentVersion $version): BinaryFileResponse|StreamedResponse
    {
        abort_unless($version->document_id === $document->id, 404);

        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        if (!$version->file_path || !$disk->exists($version->file_path)) {
            abort(404, 'File not found in storage.');
        }

        $mime = $version->file_mime ?? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $fileName = $version->file_original_name ?? ($document->title . '.docx');

        return $disk->response($version->file_path, $fileName, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
        ]);
    }

    /**
     * Handle the save / status callback from ONLYOFFICE Document Server.
     */
    public function callback(Request $request, Document $document): JsonResponse
    {
        $rawPayload = $request->all();
        Log::info("ONLYOFFICE callback received for document {$document->id}", [
            'status' => $rawPayload['status'] ?? null,
            'users' => $rawPayload['users'] ?? null,
        ]);

        $payload = $this->onlyOfficeService->validateCallbackToken($rawPayload, $request->bearerToken());

        if ($payload === null) {
            return response()->json(['error' => 1, 'message' => 'Invalid JWT token'], 403);
        }

        $status = (int) ($payload['status'] ?? 0);

        // Status 2 = Ready for saving (user closed editor or autosave interval reached)
        // Status 6 = Mustsave / ForceSave
        if (in_array($status, [2, 6], true)) {
            $downloadUrl = $payload['url'] ?? null;

            if (!$downloadUrl) {
                Log::warning("ONLYOFFICE callback status {$status} missing download URL.", $payload);
                return response()->json(['error' => 1, 'message' => 'Missing download URL'], 400);
            }

            try {
                // Download the updated DOCX file from ONLYOFFICE
                $response = Http::timeout(60)->get($downloadUrl);

                if (!$response->successful()) {
                    Log::error("Failed to download updated DOCX from ONLYOFFICE URL: {$downloadUrl}", [
                        'http_status' => $response->status(),
                    ]);
                    return response()->json(['error' => 1, 'message' => 'Failed to download file'], 500);
                }

                $fileContent = $response->body();

                // Determine author: either from callback users or document owner
                $userId = null;
                if (!empty($payload['users']) && is_array($payload['users'])) {
                    $userId = (int) $payload['users'][0];
                } elseif (!empty($payload['actions']) && is_array($payload['actions'])) {
                    $userId = (int) ($payload['actions'][0]['userid'] ?? null);
                }

                $author = ($userId ? User::find($userId) : null) ?? $document->owner;

                // Save new or updated pending DOCX version
                $version = $this->versionService->savePendingDocx($document, $fileContent, $author);

                $this->auditService->log($author, 'document.saved_onlyoffice', 'document', $document->id, [
                    'version_number' => $version->version_number,
                    'status' => $status,
                ]);

                Log::info("Document {$document->id} saved successfully from ONLYOFFICE to v{$version->version_number}.");

                return response()->json(['error' => 0]);
            } catch (\Throwable $e) {
                Log::error("Exception processing ONLYOFFICE callback: " . $e->getMessage(), [
                    'exception' => $e,
                ]);
                return response()->json(['error' => 1, 'message' => $e->getMessage()], 500);
            }
        }

        // For other statuses (1 = editing, 3 = saving error, 4 = closed without changes, 7 = corrupt):
        return response()->json(['error' => 0]);
    }
}
