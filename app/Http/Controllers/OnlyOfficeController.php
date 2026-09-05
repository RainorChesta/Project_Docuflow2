<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use App\Services\AuditService;
use App\Services\OnlyOfficeService;
use App\Services\VersionService;
use App\Services\ApprovalRoutingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OnlyOfficeController extends Controller
{
    public function __construct(
        protected OnlyOfficeService $onlyOfficeService,
        protected VersionService $versionService,
        protected AuditService $auditService,
        protected ApprovalRoutingService $approvalRoutingService,
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
     * Serve user's signature image to ONLYOFFICE Docs Document Server.
     */
    public function signature(Request $request, User $user): \Illuminate\Http\Response
    {
        $signatureId = $request->query('signature_id');
        $signature = null;

        if ($signatureId) {
            $signature = $user->signatures()->find($signatureId);
        } else {
            $signature = $user->signature;
        }

        if (!$signature || !$signature->file_path) {
            abort(404, 'Signature not found.');
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($signature->file_path)) {
            abort(404, 'Signature file not found in storage.');
        }

        $rawBytes = $disk->get($signature->file_path);
        $squaredBytes = $this->onlyOfficeService->formatSquareSignature($rawBytes, 400, 24);

        return response($squaredBytes, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="signature_' . $user->id . '_' . $signature->id . '.png"',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Serve a specific signature/stamp image to ONLYOFFICE Docs Document Server.
     */
    public function signatureImage(Request $request, \App\Models\Signature $signature): \Illuminate\Http\Response
    {
        if (!$signature || !$signature->file_path) {
            abort(404, 'Signature not found.');
        }

        $disk = Storage::disk('public');
        if (!$disk->exists($signature->file_path)) {
            abort(404, 'Signature file not found in storage.');
        }

        $rawBytes = $disk->get($signature->file_path);
        $squaredBytes = $this->onlyOfficeService->formatSquareSignature($rawBytes, 400, 24);

        return response($squaredBytes, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="signature_' . $signature->user_id . '_' . $signature->id . '.png"',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Serve a placeholder image for pending signatures.
     */
    public function signaturePlaceholder(Request $request): \Illuminate\Http\Response
    {
        $width = 400;
        $height = 400;

        if (!extension_loaded('gd')) {
            return response('GD extension required', 500);
        }

        $image = imagecreatetruecolor($width, $height);
        
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefilledrectangle($image, 0, 0, $width, $height, $transparent);
        imagealphablending($image, true);
        
        $textColor = imagecolorallocate($image, 150, 150, 150);
        $font = 5;
        
        $lines = ["PENDING", "SIGNATURE", "APPROVAL"];
        $fh = imagefontheight($font);
        
        $totalHeight = count($lines) * $fh + (count($lines) - 1) * 10;
        $startY = ($height - $totalHeight) / 2;

        foreach ($lines as $i => $line) {
            $fw = imagefontwidth($font);
            $textWidth = $fw * strlen($line);
            $x = ($width - $textWidth) / 2;
            $y = $startY + ($i * ($fh + 10));
            imagestring($image, $font, (int) $x, (int) $y, $line, $textColor);
        }
        
        $borderColor = imagecolorallocate($image, 200, 200, 200);
        imagerectangle($image, 0, 0, $width - 1, $height - 1, $borderColor);
        
        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);
        
        return response($imageData, 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Serve document QR Code PNG to ONLYOFFICE Docs Document Server.
     */
    public function qrcode(Document $document, \App\Services\QrCodeService $qrCodeService): \Illuminate\Http\Response
    {
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        $qrPng = $qrCodeService->pngBytes($qrCodeService->qrcodeUrl($document));

        return response($qrPng, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="qrcode_' . $document->id . '.png"',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    /**
     * Handle the save / status callback from ONLYOFFICE Document Server.
     */
    public function callback(Request $request, Document $document): JsonResponse
    {
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

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
        $cacheKey = 'onlyoffice_active_' . $document->id;

        if ($status === 1) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(10));
        }


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

                // Automatically process any approved signatures that were just saved into the document
                $approvedRequests = \App\Models\SignatureRequest::where('document_id', $document->id)
                    ->where('status', 'approved')
                    ->with(['targetUser', 'requestedSignature'])
                    ->get();

                if ($approvedRequests->isNotEmpty()) {
                    $processor = app(\App\Services\DocumentProcessorService::class);
                    foreach ($approvedRequests as $req) {
                        $sig = $req->requestedSignature ?? $req->targetUser?->signature;
                        if ($sig && $sig->file_path && Storage::disk('public')->exists($sig->file_path)) {
                            $signaturePath = Storage::disk('public')->path($sig->file_path);
                            $processor->processSignature($document, $version, $req->id, $signaturePath, $req);
                        }
                    }
                }

                $this->auditService->log($author, 'document.saved_onlyoffice', 'document', $document->id, [
                    'version_number' => $version->version_number,
                    'status' => $status,
                ]);

                // Trigger approval routing and notifications if the version is pending
                if ($version->status === 'pending') {
                    $resolution = $this->approvalRoutingService->resolveApprover($document, $author);
                    $this->approvalRoutingService->applyToDocument($document, $resolution);

                    foreach ($resolution['approvers'] as $approver) {
                        $approver->notify(new \App\Notifications\DocumentApprovalRequested($document, $version, $author->name));
                    }

                    if ($resolution['role'] !== null) {
                        $author->notify(new \App\Notifications\ApprovalRouteResolved(
                            $document,
                            $resolution['role'],
                            $resolution['approvers']->pluck('name')->join(', '),
                            $resolution['message'],
                            $resolution['isFallback'],
                        ));
                    }
                }

                Log::info("Document {$document->id} saved successfully from ONLYOFFICE to v{$version->version_number}.");
            } catch (\Throwable $e) {
                Log::error("Exception processing ONLYOFFICE callback: " . $e->getMessage(), [
                    'exception' => $e,
                ]);
                return response()->json(['error' => 1, 'message' => $e->getMessage()], 500);
            }
        } elseif ($status === 4) {
            // Document closed without changes. We MUST touch the version so the documentKey changes 
            // for the next session, preventing ONLYOFFICE "Version Changed" cache collisions.
            $activeVersion = $document->versions()->whereIn('status', ['pending', 'draft'])
                ->whereNull('discarded_at')
                ->orderBy('version_number', 'desc')
                ->first();
            
            if ($activeVersion) {
                $activeVersion->touch();
            } else {
                $document->touch();
            }
            Log::info("ONLYOFFICE closed without changes for document {$document->id}. Version touched to rotate key.");
        }

        if (in_array($status, [2, 3, 4, 7], true)) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            \Illuminate\Support\Facades\Cache::forget('onlyoffice_doc_key_' . $document->id);
        }

        // For other statuses (1 = editing, 3 = saving error, 7 = corrupt):
        return response()->json(['error' => 0]);
    }

    /**
     * Serve the template file to ONLYOFFICE Docs Document Server.
     */
    public function templateFile(\App\Models\DocumentTemplate $template): BinaryFileResponse|StreamedResponse
    {
        $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));

        if (!$template->file_path || !$disk->exists($template->file_path)) {
            abort(404, 'File not found in storage.');
        }

        $mime = $template->file_mime ?? 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        $fileName = $template->file_original_name ?? ($template->title . '.docx');

        return $disk->response($template->file_path, $fileName, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . addslashes($fileName) . '"',
        ]);
    }

    /**
     * Handle the save / status callback from ONLYOFFICE Document Server for Templates.
     */
    public function templateCallback(Request $request, \App\Models\DocumentTemplate $template): JsonResponse
    {
        if (config('app.url')) {
            URL::forceRootUrl(config('app.url'));
        }

        $rawPayload = $request->all();
        Log::info("ONLYOFFICE callback received for template {$template->id}", [
            'status' => $rawPayload['status'] ?? null,
            'users' => $rawPayload['users'] ?? null,
        ]);

        $payload = $this->onlyOfficeService->validateCallbackToken($rawPayload, $request->bearerToken());

        if ($payload === null) {
            return response()->json(['error' => 1, 'message' => 'Invalid JWT token'], 403);
        }

        $status = (int) ($payload['status'] ?? 0);
        $cacheKey = 'onlyoffice_template_active_' . $template->id;

        if ($status === 1) {
            \Illuminate\Support\Facades\Cache::put($cacheKey, true, now()->addMinutes(10));
        }

        // Status 2 = Ready for saving, 6 = ForceSave
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

                // Save new template content
                $disk = Storage::disk(config('onlyoffice.storage_disk', 'local'));
                $disk->put($template->file_path, $fileContent);
                
                $template->touch(); // Update updated_at timestamp

                // Determine author
                $userId = null;
                if (!empty($payload['users']) && is_array($payload['users'])) {
                    $userId = (int) $payload['users'][0];
                } elseif (!empty($payload['actions']) && is_array($payload['actions'])) {
                    $userId = (int) ($payload['actions'][0]['userid'] ?? null);
                }
                $author = ($userId ? User::find($userId) : null) ?? $template->creator;

                $this->auditService->log($author, 'template.saved_onlyoffice', 'document_template', $template->id, [
                    'status' => $status,
                ]);

                Log::info("Template {$template->id} saved successfully from ONLYOFFICE.");
            } catch (\Throwable $e) {
                Log::error("Exception processing ONLYOFFICE template callback: " . $e->getMessage(), [
                    'exception' => $e,
                ]);
                return response()->json(['error' => 1, 'message' => $e->getMessage()], 500);
            }
        } elseif ($status === 4) {
            $template->touch(); // Rotate key
            Log::info("ONLYOFFICE closed without changes for template {$template->id}. Touched to rotate key.");
        }

        if (in_array($status, [2, 3, 4, 7], true)) {
            \Illuminate\Support\Facades\Cache::forget($cacheKey);
            \Illuminate\Support\Facades\Cache::forget('onlyoffice_template_key_' . $template->id);
        }

        return response()->json(['error' => 0]);
    }
}
