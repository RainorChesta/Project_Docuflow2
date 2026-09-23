<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Log;

class OnlyOfficeService
{
    /**
     * Generate a unique document key for ONLYOFFICE caching/versioning.
     * ONLYOFFICE uses this key to identify whether a document has changed.
     * Characters allowed: 0-9, a-z, A-Z, -._=, max 128 chars.
     */
    public function generateDocumentKey(Document $document, DocumentVersion $version): string
    {
        $disk = \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'));
        $fileHash = '';
        if ($version->file_path && $disk->exists($version->file_path)) {
            $fileHash = md5($disk->get($version->file_path));
        } else {
            $fileHash = md5($document->id . '_' . $version->id . '_' . ($version->updated_at ? $version->updated_at->timestamp : time()));
        }

        $raw = sprintf(
            'doc_%d_v%d_%s',
            $document->id,
            $version->id,
            substr($fileHash, 0, 24)
        );

        return substr(preg_replace('/[^0-9a-zA-Z_\-]/', '_', $raw), 0, 128);
    }

    /**
     * Rotate / clear cached ONLYOFFICE document keys for a document so the next session opens cleanly.
     */
    public function rotateDocumentKey(Document $document, ?DocumentVersion $version = null): void
    {
        if ($version) {
            \Illuminate\Support\Facades\Cache::forget('onlyoffice_doc_session_key_' . $document->id . '_v' . $version->id);
        }
        foreach ($document->versions as $v) {
            \Illuminate\Support\Facades\Cache::forget('onlyoffice_doc_session_key_' . $document->id . '_v' . $v->id);
        }
    }

    /**
     * Get the URL ONLYOFFICE uses to fetch the DOCX document file from Laravel.
     */
    public function getDocumentFileUrl(Document $document, DocumentVersion $version): string
    {
        $internalBase = rtrim(config('onlyoffice.internal_url'), '/');
        
        return $internalBase . route('onlyoffice.file', [
            'document' => $document->id,
            'version' => $version->id,
        ], false);
    }

    /**
     * Get the URL ONLYOFFICE uses to fetch a user's signature image.
     */
    public function getSignatureFileUrl(User $user): ?string
    {
        if (!$user->hasSignature()) {
            return null;
        }

        $internalBase = rtrim(config('onlyoffice.internal_url'), '/');

        return $internalBase . route('onlyoffice.signature', [
            'user' => $user->id,
        ], false);
    }

    /**
     * Get the URL ONLYOFFICE uses to fetch a specific signature image.
     */
    public function getSignatureFileUrlForSignature(\App\Models\Signature $signature): ?string
    {
        $internalBase = rtrim(config('onlyoffice.internal_url'), '/');

        return $internalBase . route('onlyoffice.signature.image', [
            'signature' => $signature->id,
        ], false);
    }

    /**
     * Get the URL ONLYOFFICE uses to fetch a placeholder badge image for pending signatures.
     */
    public function getPlaceholderImageUrl(?string $text = null, ?int $requestId = null, bool $isStamp = false): string
    {
        $internalBase = rtrim(config('onlyoffice.internal_url'), '/');

        $params = [];
        if ($text) {
            $params['text'] = $text;
        }
        if ($requestId) {
            $params['request_id'] = $requestId;
        }
        if ($isStamp) {
            $params['is_stamp'] = 1;
        }

        return $internalBase . route('onlyoffice.signature.placeholder', $params, false);
    }

    /**
     * Get the URL ONLYOFFICE uses to fetch the document's QR code PNG image.
     */
    public function getQrCodeFileUrl(Document $document): string
    {
        $internalBase = rtrim(config('onlyoffice.internal_url'), '/');

        return $internalBase . route('onlyoffice.qrcode', [
            'document' => $document->id,
        ], false);
    }

    /**
     * Generate a signed JWT token for the ONLYOFFICE insertImage Docs API command.
     */
    public function generateInsertImageToken(string $imageUrl): ?string
    {
        if (!config('onlyoffice.jwt_enabled') || empty(config('onlyoffice.jwt_secret'))) {
            return null;
        }

        $payload = [
            'c' => 'add',
            'images' => [
                [
                    'fileType' => 'png',
                    'url' => $imageUrl,
                ],
            ],
            'fileType' => 'png',
            'url' => $imageUrl,
        ];

        return JWT::encode($payload, config('onlyoffice.jwt_secret'), 'HS256');
    }

    /**
     * Format any signature or stamp PNG cropped and centered on a 1:1 true square
     * transparent canvas with balanced margins, ensuring a consistent square aspect ratio
     * and crisp rendering across ONLYOFFICE and PDF viewers.
     */
    public function formatSquareSignature(string $rawPngBytes, int $targetSize = 400, int $padding = 24): string
    {
        if (!extension_loaded('gd') || empty($rawPngBytes)) {
            return $rawPngBytes;
        }

        $src = @imagecreatefromstring($rawPngBytes);
        if (!$src) {
            return $rawPngBytes;
        }

        // Convert palette/indexed images (PNG-8, etc.) to truecolor immediately.
        // On indexed images, imagecolorat() returns palette indices instead of ARGB values,
        // which causes transparent/white backgrounds to be mistakenly treated as solid blue/black opaque pixels.
        if (!imageistruecolor($src)) {
            imagepalettetotruecolor($src);
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);

        // Detect if the source image already has a transparent background by sampling border pixels
        $totalBorderPixels = 0;
        $transparentBorderPixels = 0;
        $stepX = max(1, (int)($srcW / 25));
        $stepY = max(1, (int)($srcH / 25));
        for ($x = 0; $x < $srcW; $x += $stepX) {
            foreach ([0, $srcH - 1] as $y) {
                $c = imagecolorat($src, $x, $y);
                $totalBorderPixels++;
                if ((($c >> 24) & 0x7F) >= 80) $transparentBorderPixels++;
            }
        }
        for ($y = 0; $y < $srcH; $y += $stepY) {
            foreach ([0, $srcW - 1] as $x) {
                $c = imagecolorat($src, $x, $y);
                $totalBorderPixels++;
                if ((($c >> 24) & 0x7F) >= 80) $transparentBorderPixels++;
            }
        }
        $hasTransparentBg = ($totalBorderPixels > 0 && ($transparentBorderPixels / $totalBorderPixels) > 0.3);

        // Find bounding box of signature / stamp content
        $minX = $srcW;
        $minY = $srcH;
        $maxX = 0;
        $maxY = 0;
        $hasStroke = false;

        for ($y = 0; $y < $srcH; $y++) {
            for ($x = 0; $x < $srcW; $x++) {
                $rgba = imagecolorat($src, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F; // 0 = opaque, 127 = fully transparent
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                // Pixel is considered ink stroke / stamp content if not transparent and not white background
                $isNotTransparent = ($alpha < 110);
                $isNotWhite = ($r < 240 || $g < 240 || $b < 240);

                if ($isNotTransparent && ($hasTransparentBg || $isNotWhite)) {
                    $hasStroke = true;
                    if ($x < $minX) $minX = $x;
                    if ($x > $maxX) $maxX = $x;
                    if ($y < $minY) $minY = $y;
                    if ($y > $maxY) $maxY = $y;
                }
            }
        }

        if (!$hasStroke) {
            $minX = 0;
            $minY = 0;
            $maxX = $srcW - 1;
            $maxY = $srcH - 1;
        }

        $cropW = max(1, $maxX - $minX + 1);
        $cropH = max(1, $maxY - $minY + 1);

        $targetSize = max(100, $targetSize);
        $padding = max(4, min((int)($targetSize * 0.2), $padding));
        $innerSize = max(20, $targetSize - ($padding * 2));

        // Scale proportionally to fit within innerSize of the square canvas
        $scale = min($innerSize / $cropW, $innerSize / $cropH);
        $drawW = (int) round($cropW * $scale);
        $drawH = (int) round($cropH * $scale);

        // Create an intermediate cropped & transparent truecolor image
        $cropped = imagecreatetruecolor($cropW, $cropH);
        imagealphablending($cropped, false);
        imagesavealpha($cropped, true);
        $cropTrans = imagecolorallocatealpha($cropped, 0, 0, 0, 127);
        imagefilledrectangle($cropped, 0, 0, $cropW, $cropH, $cropTrans);

        for ($cy = 0; $cy < $cropH; $cy++) {
            for ($cx = 0; $cx < $cropW; $cx++) {
                $sx = $minX + $cx;
                $sy = $minY + $cy;
                $rgba = imagecolorat($src, $sx, $sy);
                $alpha = ($rgba >> 24) & 0x7F;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                // If already transparent in source, leave as transparent
                if ($alpha >= 120) {
                    continue;
                }

                // If white or near-white background on an opaque image, make transparent
                if (!$hasTransparentBg && $r >= 238 && $g >= 238 && $b >= 238) {
                    continue;
                }

                // Smooth edge anti-aliasing for light pixels on opaque scans to eliminate white fringe/halo
                if (!$hasTransparentBg && $r > 200 && $g > 200 && $b > 200) {
                    $lightness = ($r + $g + $b) / (3 * 255.0);
                    $extraAlpha = (int) round(($lightness - 0.78) / (1.0 - 0.78) * 127);
                    $newAlpha = min(127, max($alpha, $extraAlpha));
                    $color = imagecolorallocatealpha($cropped, $r, $g, $b, $newAlpha);
                } else {
                    $color = imagecolorallocatealpha($cropped, $r, $g, $b, $alpha);
                }

                imagesetpixel($cropped, $cx, $cy, $color);
            }
        }

        // Center within square canvas
        $destX = (int) round(($targetSize - $drawW) / 2);
        $destY = (int) round(($targetSize - $drawH) / 2);

        // Create 1:1 true square image with transparent background
        $dest = imagecreatetruecolor($targetSize, $targetSize);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
        imagefilledrectangle($dest, 0, 0, $targetSize, $targetSize, $transparent);

        // Resample cropped signature neatly into the center of the square canvas
        imagecopyresampled($dest, $cropped, $destX, $destY, 0, 0, $drawW, $drawH, $cropW, $cropH);

        ob_start();
        imagepng($dest);
        $result = ob_get_clean();

        imagedestroy($src);
        imagedestroy($cropped);
        imagedestroy($dest);

        return $result ?: $rawPngBytes;
    }

    /**
     * Crop/trim any signature or stamp PNG tightly to ink strokes and center on a 1:1 square canvas.
     */
    public function trimSignatureImage(string $rawPngBytes, int $padding = 24): string
    {
        return $this->formatSquareSignature($rawPngBytes, 400, $padding);
    }

    /**
     * Get the callback URL ONLYOFFICE calls to save the document.
     */
    public function getCallbackUrl(Document $document): string
    {
        $internalBase = rtrim(config('onlyoffice.internal_url'), '/');

        return $internalBase . route('onlyoffice.callback', [
            'document' => $document->id,
        ], false);
    }

    /**
     * Generate a unique template key for ONLYOFFICE caching/versioning.
     */
    public function generateTemplateKey(\App\Models\DocumentTemplate $template): string
    {
        $sessionCacheKey = 'onlyoffice_template_session_key_' . $template->id;

        return \Illuminate\Support\Facades\Cache::remember($sessionCacheKey, now()->addHours(2), function () use ($template) {
            $timeKey = uniqid();
            $updatedAt = $template->updated_at ? $template->updated_at->timestamp : ($template->created_at ? $template->created_at->timestamp : time());

            $raw = sprintf(
                'tpl_%d_%d_%s',
                $template->id,
                $updatedAt,
                $timeKey
            );

            return substr(preg_replace('/[^0-9a-zA-Z_\-]/', '_', $raw), 0, 128);
        });
    }

    /**
     * Rotate / clear cached ONLYOFFICE template keys for a template so the next session opens cleanly.
     */
    public function rotateTemplateKey(\App\Models\DocumentTemplate $template): void
    {
        \Illuminate\Support\Facades\Cache::forget('onlyoffice_template_session_key_' . $template->id);
        \Illuminate\Support\Facades\Cache::forget('onlyoffice_template_key_' . $template->id);

        if ($template->updated_at) {
            \Illuminate\Support\Facades\Cache::forget('onlyoffice_template_key_' . $template->id . '_' . $template->updated_at->timestamp);
        }
    }

    /**
     * Send a forcesave command to ONLYOFFICE Command Service to flush in-memory changes immediately.
     */
    public function sendForcesaveCommand(string $documentKey): bool
    {
        try {
            $onlyOfficeBase = rtrim(config('onlyoffice.url'), '/');
            $commandUrl = $onlyOfficeBase . '/coauthoring/CommandService.ashx';

            $payload = [
                'c' => 'forcesave',
                'key' => $documentKey,
            ];

            if (config('onlyoffice.jwt_enabled') && config('onlyoffice.jwt_secret')) {
                $payload['token'] = JWT::encode($payload, config('onlyoffice.jwt_secret'), 'HS256');
            }

            $response = \Illuminate\Support\Facades\Http::timeout(5)->post($commandUrl, $payload);
            if ($response->successful()) {
                $data = $response->json();
                return isset($data['error']) && $data['error'] === 0;
            }
        } catch (\Throwable $e) {
            Log::debug('ONLYOFFICE forcesave command skipped/failed: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Trigger forcesave for a template.
     */
    public function forceSaveTemplate(\App\Models\DocumentTemplate $template): bool
    {
        $key = $this->generateTemplateKey($template);
        return $this->sendForcesaveCommand($key);
    }

    /**
     * Trigger forcesave for a document version.
     */
    public function forceSaveDocument(Document $document, DocumentVersion $version): bool
    {
        $key = $this->generateDocumentKey($document, $version);
        return $this->sendForcesaveCommand($key);
    }

    /**
     * Get the URL ONLYOFFICE uses to fetch the DOCX template file from Laravel.
     */
    public function getTemplateFileUrl(\App\Models\DocumentTemplate $template): string
    {
        $internalBase = rtrim(config('onlyoffice.internal_url'), '/');
        
        return $internalBase . route('onlyoffice.templates.file', [
            'template' => $template->id,
        ], false);
    }

    /**
     * Get the callback URL ONLYOFFICE calls to save the template.
     */
    public function getTemplateCallbackUrl(\App\Models\DocumentTemplate $template): string
    {
        $internalBase = rtrim(config('onlyoffice.internal_url'), '/');

        return $internalBase . route('onlyoffice.templates.callback', [
            'template' => $template->id,
        ], false);
    }

    /**
     * Generate the complete config payload for the ONLYOFFICE Docs API editor.
     */
    public function generateEditorConfig(
        Document $document,
        DocumentVersion $version,
        User $user,
        string $mode = 'edit'
    ): array {
        $fileUrl = $this->getDocumentFileUrl($document, $version);
        $callbackUrl = $this->getCallbackUrl($document);
        $documentKey = $this->generateDocumentKey($document, $version);

        // Detect extension and file type
        $extension = 'docx';
        if ($version->file_path) {
            $ext = strtolower(pathinfo($version->file_path, PATHINFO_EXTENSION));
            if (in_array($ext, ['docx', 'pdf', 'doc', 'txt', 'rtf'], true)) {
                $extension = $ext;
            }
        } elseif ($version->file_mime) {
            if (str_contains($version->file_mime, 'pdf')) {
                $extension = 'pdf';
            }
        }

        $documentType = match ($extension) {
            'pdf' => 'pdf',
            default => 'word',
        };

        $canEdit = ($mode === 'edit');
        if ($extension === 'pdf') {
            // ONLYOFFICE PDF mode supports editing PDF forms/annotations
            $canEdit = true;
        }

        $fileName = $version->file_original_name ?? $document->title;
        if (!str_ends_with(strtolower($fileName), '.' . $extension)) {
            $fileName .= '.' . $extension;
        }

        $config = [
            'documentType' => $documentType,
            'document' => [
                'title' => $fileName,
                'url' => $fileUrl,
                'fileType' => $extension,
                'key' => $documentKey,
                'permissions' => [
                    'edit' => $canEdit,
                    'download' => true,
                    'print' => true,
                    'review' => true,
                    'comment' => true,
                    'copy' => true,
                    'modifyFilter' => true,
                    'modifyContentControl' => true,
                    'fillForms' => true,
                ],
            ],
            'editorConfig' => [
                'mode' => $canEdit ? 'edit' : 'view',
                'lang' => app()->getLocale() === 'id' ? 'id-ID' : 'en-US',
                'callbackUrl' => $callbackUrl,
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                ],
                'customization' => [
                    'autoFocus' => false,
                    'zoom' => 100,
                    'compactHeader' => true,
                    'autosave' => (bool) config('onlyoffice.autosave', true),
                    'forcesave' => (bool) config('onlyoffice.forcesave', true),
                    'chat' => false,
                    'comments' => false,
                    'toolbarNoTabs' => false,
                    'feedback' => false,
                    'goback' => [
                        'url' => route('documents.show', $document),
                        'text' => __('Kembali ke Detail Dokumen'),
                    ],
                ],
            ],
            'height' => '100%',
            'width' => '100%',
            'type' => 'desktop',
        ];

        if (config('onlyoffice.jwt_enabled') && config('onlyoffice.jwt_secret')) {
            $config['token'] = JWT::encode($config, config('onlyoffice.jwt_secret'), 'HS256');
        }

        return $config;
    }

    /**
     * Generate the complete config payload for the ONLYOFFICE Docs API editor for Templates.
     */
    public function generateTemplateEditorConfig(
        \App\Models\DocumentTemplate $template,
        User $user,
        string $mode = 'edit'
    ): array {
        $fileUrl = $this->getTemplateFileUrl($template);
        $callbackUrl = $this->getTemplateCallbackUrl($template);
        $documentKey = $this->generateTemplateKey($template);

        $extension = 'docx';
        if ($template->file_path) {
            $ext = strtolower(pathinfo($template->file_path, PATHINFO_EXTENSION));
            if (in_array($ext, ['docx'], true)) {
                $extension = $ext;
            }
        }

        $canEdit = ($mode === 'edit');
        $fileName = $template->file_original_name ?? $template->title;
        if (!str_ends_with(strtolower($fileName), '.' . $extension)) {
            $fileName .= '.' . $extension;
        }

        $config = [
            'documentType' => 'word',
            'document' => [
                'title' => $fileName,
                'url' => $fileUrl,
                'fileType' => $extension,
                'key' => $documentKey,
                'permissions' => [
                    'edit' => $canEdit,
                    'download' => true,
                    'print' => true,
                    'review' => true,
                    'comment' => true,
                    'copy' => true,
                    'modifyFilter' => true,
                    'modifyContentControl' => true,
                    'fillForms' => true,
                ],
            ],
            'editorConfig' => [
                'mode' => $canEdit ? 'edit' : 'view',
                'lang' => app()->getLocale() === 'id' ? 'id-ID' : 'en-US',
                'callbackUrl' => $callbackUrl,
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                ],
                'customization' => [
                    'autoFocus' => false,
                    'zoom' => 100,
                    'compactHeader' => true,
                    'autosave' => (bool) config('onlyoffice.autosave', true),
                    'forcesave' => (bool) config('onlyoffice.forcesave', true),
                    'chat' => false,
                    'comments' => false,
                    'toolbarNoTabs' => false,
                    'feedback' => false,
                    'goback' => [
                        'url' => route('admin.templates.index'),
                        'text' => __('Kembali ke Daftar Template'),
                    ],
                ],
            ],
            'height' => '100%',
            'width' => '100%',
            'type' => 'desktop',
        ];

        if (config('onlyoffice.jwt_enabled') && config('onlyoffice.jwt_secret')) {
            $config['token'] = JWT::encode($config, config('onlyoffice.jwt_secret'), 'HS256');
        }

        return $config;
    }

    /**
     * Generate a unique soft file key for ONLYOFFICE caching/versioning.
     */
    public function generateCorporateSoftFileKey(\App\Models\CorporateSoftFile $file): string
    {
        $sessionCacheKey = 'onlyoffice_softfile_session_key_' . $file->id;

        return \Illuminate\Support\Facades\Cache::remember($sessionCacheKey, now()->addHours(2), function () use ($file) {
            $timeKey = uniqid();
            $updatedAt = $file->updated_at ? $file->updated_at->timestamp : ($file->created_at ? $file->created_at->timestamp : time());

            $raw = sprintf(
                'csf_%d_%d_%s',
                $file->id,
                $updatedAt,
                $timeKey
            );

            return substr(preg_replace('/[^0-9a-zA-Z_\-]/', '_', $raw), 0, 128);
        });
    }

    /**
     * Generate the complete config payload for ONLYOFFICE Docs viewer for Corporate Soft Files.
     */
    public function generateCorporateSoftFileEditorConfig(
        \App\Models\CorporateSoftFile $file,
        User $user,
        string $mode = 'view'
    ): array {
        $fileUrl = $this->getCorporateSoftFileUrl($file);
        $documentKey = $this->generateCorporateSoftFileKey($file);

        $extension = 'docx';
        if ($file->file_path) {
            $ext = strtolower(pathinfo($file->file_path, PATHINFO_EXTENSION));
            if (in_array($ext, ['docx', 'doc', 'pdf'], true)) {
                $extension = $ext;
            }
        }

        $documentType = ($extension === 'pdf') ? 'pdf' : 'word';
        $fileName = $file->file_original_name ?? $file->title;
        if (!str_ends_with(strtolower($fileName), '.' . $extension)) {
            $fileName .= '.' . $extension;
        }

        $config = [
            'documentType' => $documentType,
            'document' => [
                'title' => $fileName,
                'url' => $fileUrl,
                'fileType' => $extension,
                'key' => $documentKey,
                'permissions' => [
                    'edit' => false,
                    'download' => true,
                    'print' => true,
                    'review' => false,
                    'comment' => false,
                    'copy' => true,
                    'modifyFilter' => false,
                    'modifyContentControl' => false,
                    'fillForms' => false,
                ],
            ],
            'editorConfig' => [
                'mode' => 'view',
                'canEdit' => false,
                'lang' => app()->getLocale() === 'id' ? 'id-ID' : 'en-US',
                'user' => [
                    'id' => (string) $user->id,
                    'name' => $user->name,
                ],
                'customization' => [
                    'autoFocus' => false,
                    'zoom' => -2,
                    'compactHeader' => true,
                    'toolbarNoTabs' => true,
                    'toolbarHideFileName' => false,
                    'autosave' => false,
                    'forcesave' => false,
                    'chat' => false,
                    'comments' => false,
                    'feedback' => false,
                    'leftMenu' => false,
                    'rightMenu' => false,
                    'embedded' => [
                        'toolbarDockPosition' => 'bottom',
                    ],
                    'goback' => [
                        'url' => route('admin.corporate-soft-files.index'),
                        'text' => __('Kembali ke Daftar Soft File'),
                    ],
                ],
            ],
            'height' => '100%',
            'width' => '100%',
            'type' => 'embedded',
        ];

        if (config('onlyoffice.jwt_enabled') && config('onlyoffice.jwt_secret')) {
            $config['token'] = JWT::encode($config, config('onlyoffice.jwt_secret'), 'HS256');
        }

        return $config;
    }

    /**
     * Validate and decode the JWT token from ONLYOFFICE callback if JWT is enabled.
     */
    public function validateCallbackToken(array $data, ?string $token): ?array
    {
        if (!config('onlyoffice.jwt_enabled') || empty(config('onlyoffice.jwt_secret'))) {
            return $data;
        }

        $jwt = $token ?? ($data['token'] ?? null);

        if (!$jwt) {
            // If raw payload is already provided in request body and no token sent
            if (!empty($data['status'])) {
                return $data;
            }
            Log::warning('ONLYOFFICE callback missing JWT token.');
            return null;
        }

        try {
            $decoded = (array) JWT::decode($jwt, new Key(config('onlyoffice.jwt_secret'), 'HS256'));
            
            // ONLYOFFICE may wrap payload in 'payload' key
            if (isset($decoded['payload']) && is_object($decoded['payload'])) {
                return array_merge($data, (array) $decoded['payload']);
            }

            return array_merge($data, $decoded);
        } catch (\Throwable $e) {
            Log::error('ONLYOFFICE callback JWT verification failed: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a JWT token for ONLYOFFICE requests/payloads.
     */
    public function generateJwtToken(array $payload): string
    {
        $secret = config('onlyoffice.jwt_secret');
        if (empty($secret)) {
            return '';
        }

        return JWT::encode($payload, $secret, 'HS256');
    }

    /**
     * Get the URL ONLYOFFICE uses to fetch a corporate soft file.
     */
    public function getCorporateSoftFileUrl(\App\Models\CorporateSoftFile $file): string
    {
        $internalBase = rtrim(config('onlyoffice.internal_url'), '/');
        
        return $internalBase . route('onlyoffice.corporate-soft-files.file', [
            'corporateSoftFile' => $file->id,
        ], false);
    }

    /**
     * Convert a PDF file (e.g. corporate soft file) to editable DOCX.
     * Uses ONLYOFFICE's native x2t engine (via Docker container if available),
     * falling back to ConvertService.ashx HTTP API, and then PhpWord/PdfParser.
     */
    public function convertPdfToDocx($fileOrPath, ?string $fileUrl = null): ?string
    {
        // 1. Resolve local absolute file path
        $localPath = null;
        if ($fileOrPath instanceof \App\Models\CorporateSoftFile) {
            $disk = \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'));
            if ($disk->exists($fileOrPath->file_path)) {
                $localPath = $disk->path($fileOrPath->file_path);
            }
            if (!$fileUrl) {
                $fileUrl = $this->getCorporateSoftFileUrl($fileOrPath);
            }
        } elseif (is_string($fileOrPath) && file_exists($fileOrPath)) {
            $localPath = $fileOrPath;
        }

        // 2. Try native x2t conversion via ONLYOFFICE Docker container (instant & offline)
        if ($localPath && file_exists($localPath)) {
            $convertedDocx = $this->convertPdfUsingDockerX2t($localPath);
            if ($convertedDocx && substr($convertedDocx, 0, 2) === "PK") {
                return $this->enforceF4PageSize($convertedDocx);
            }
        }

        // 3. Try ConvertService.ashx via HTTP API
        if ($fileUrl) {
            $convertedDocx = $this->convertDocument($fileUrl, 'pdf', 'docx');
            if ($convertedDocx && substr($convertedDocx, 0, 2) === "PK") {
                return $this->enforceF4PageSize($convertedDocx);
            }
        }

        // 4. Fallback: Smalot PdfParser + PhpWord
        if ($localPath && file_exists($localPath)) {
            $convertedDocx = $this->convertPdfUsingPhpWordFallback($localPath);
            if ($convertedDocx && substr($convertedDocx, 0, 2) === "PK") {
                return $this->enforceF4PageSize($convertedDocx);
            }
        }

        return null;
    }

    /**
     * Convert a PDF file to DOCX using ONLYOFFICE's native x2t binary inside Docker container.
     * Extracts the corporate letterhead directly into the Word Header (word/header1.xml)
     * and Footer (word/footer1.xml), with clean typing body in F4 dimensions.
     */
    protected function convertPdfUsingDockerX2t(string $localPdfPath): ?string
    {
        try {
            $container = env('ONLYOFFICE_DOCKER_CONTAINER', 'dokuflow-onlyoffice');
            $uid = uniqid('conv_', true);
            $containerIn = "/var/www/onlyoffice/Data/in_{$uid}.pdf";
            $containerPngOut = "/var/www/onlyoffice/Data/out_{$uid}.png";
            $containerDocxOut = "/var/www/onlyoffice/Data/out_{$uid}.docx";
            $tempLocalPng = storage_path("app/temp_png_{$uid}.png");
            $tempLocalDocx = storage_path("app/temp_docx_{$uid}.docx");

            // 1. Copy PDF into container
            $cpInCmd = sprintf('docker cp %s %s:%s 2>&1', escapeshellarg($localPdfPath), escapeshellarg($container), escapeshellarg($containerIn));
            @exec($cpInCmd, $outIn, $codeIn);
            if ($codeIn !== 0) {
                return null;
            }

            // 2. Render to high-res PNG for header extraction into word/header1.xml
            $x2tPngCmd = sprintf(
                'docker exec %s /var/www/onlyoffice/documentserver/server/FileConverter/bin/x2t %s %s 2>&1',
                escapeshellarg($container),
                escapeshellarg($containerIn),
                escapeshellarg($containerPngOut)
            );
            @exec($x2tPngCmd, $outPng, $codePng);

            if ($codePng === 0) {
                $cpPngCmd = sprintf('docker cp %s:%s %s 2>&1', escapeshellarg($container), escapeshellarg($containerPngOut), escapeshellarg($tempLocalPng));
                @exec($cpPngCmd, $outCpPng, $codeCpPng);

                if ($codeCpPng === 0 && file_exists($tempLocalPng) && filesize($tempLocalPng) > 0) {
                    $pngBytes = file_get_contents($tempLocalPng);
                    @unlink($tempLocalPng);
                    @exec(sprintf('docker exec %s rm -f %s %s 2>&1', escapeshellarg($container), escapeshellarg($containerIn), escapeshellarg($containerPngOut)));

                    return $this->createDocxFromImageBytes($pngBytes, 'png');
                }
            }
            if (file_exists($tempLocalPng)) {
                @unlink($tempLocalPng);
            }

            // 3. Fallback: direct x2t DOCX conversion
            $x2tDocxCmd = sprintf(
                'docker exec %s /var/www/onlyoffice/documentserver/server/FileConverter/bin/x2t %s %s 2>&1',
                escapeshellarg($container),
                escapeshellarg($containerIn),
                escapeshellarg($containerDocxOut)
            );
            @exec($x2tDocxCmd, $outDocx, $codeDocx);

            $cpDocxCmd = sprintf('docker cp %s:%s %s 2>&1', escapeshellarg($container), escapeshellarg($containerDocxOut), escapeshellarg($tempLocalDocx));
            @exec($cpDocxCmd, $outCpDocx, $codeCpDocx);

            // Cleanup container files
            @exec(sprintf('docker exec %s rm -f %s %s %s 2>&1', escapeshellarg($container), escapeshellarg($containerIn), escapeshellarg($containerPngOut), escapeshellarg($containerDocxOut)));

            if ($codeCpDocx === 0 && file_exists($tempLocalDocx) && filesize($tempLocalDocx) > 1000) {
                $content = file_get_contents($tempLocalDocx);
                @unlink($tempLocalDocx);
                return $this->enforceF4PageSize($content);
            }
            if (file_exists($tempLocalDocx)) {
                @unlink($tempLocalDocx);
            }
        } catch (\Throwable $e) {
            Log::warning('convertPdfUsingDockerX2t failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Fallback conversion for PDF to DOCX using Smalot\PdfParser and PhpWord.
     */
    protected function convertPdfUsingPhpWordFallback(string $localPdfPath): string
    {
        $pdfText = '';
        try {
            $parser = new \Smalot\PdfParser\Parser();
            $pdfObj = $parser->parseFile($localPdfPath);
            $pdfText = $pdfObj->getText();
        } catch (\Throwable $e) {
            $pdfText = '';
        }

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 18709,
            'marginTop' => 1440,
            'marginBottom' => 1440,
            'marginLeft' => 1440,
            'marginRight' => 1440,
        ]);

        if (trim($pdfText)) {
            $lines = explode("\n", trim($pdfText));
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $section->addText(htmlspecialchars($line), ['size' => 11]);
                } else {
                    $section->addText('');
                }
            }
        } else {
            $section->addText('');
        }

        $tempDocx = tempnam(sys_get_temp_dir(), 'pdf_fallback_') . '.docx';
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempDocx);

        $content = file_get_contents($tempDocx);
        @unlink($tempDocx);

        return $content;
    }

    /**
     * Convert a document (e.g. PDF to editable DOCX) via ONLYOFFICE ConvertService API.
     * Returns the binary content of the converted DOCX file, or null on failure.
     */
    public function convertDocument(string $fileUrl, string $fileType = 'pdf', string $outputType = 'docx'): ?string
    {
        try {
            $onlyOfficeUrl = rtrim(config('onlyoffice.url'), '/');
            $convertUrl = $onlyOfficeUrl . '/ConvertService.ashx';
            $key = 'conv_' . md5($fileUrl . '_' . time());

            $payload = [
                'async' => false,
                'filetype' => strtolower($fileType),
                'key' => $key,
                'outputtype' => strtolower($outputType),
                'title' => 'converted_' . $key . '.' . $outputType,
                'url' => $fileUrl,
            ];

            if (config('onlyoffice.jwt_enabled') && config('onlyoffice.jwt_secret')) {
                $payload['token'] = JWT::encode($payload, config('onlyoffice.jwt_secret'), 'HS256');
            }

            $headers = [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ];
            if (!empty($payload['token'])) {
                $headers['Authorization'] = 'Bearer ' . $payload['token'];
            }

            $response = \Illuminate\Support\Facades\Http::timeout(10)->withHeaders($headers)->post($convertUrl, $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (!empty($data['fileUrl'])) {
                    $downloadHeaders = [];
                    if (!empty($payload['token'])) {
                        $downloadHeaders['Authorization'] = 'Bearer ' . $payload['token'];
                    }
                    $downloadRes = \Illuminate\Support\Facades\Http::timeout(15)->withHeaders($downloadHeaders)->get($data['fileUrl']);
                    if ($downloadRes->successful()) {
                        return $downloadRes->body();
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('OnlyOfficeService convertDocument failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Enforce F4 paper size (210 x 330 mm = 11906 x 18709 twips) in a DOCX binary.
     */
    public function enforceF4PageSize(string $docxBinary): string
    {
        if (substr($docxBinary, 0, 2) !== "PK") {
            return $docxBinary;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'docx_f4_');
        file_put_contents($tempFile, $docxBinary);

        $zip = new \ZipArchive();
        if ($zip->open($tempFile) === true) {
            $xmlContent = $zip->getFromName('word/document.xml');
            if ($xmlContent !== false) {
                // Set all <w:pgSz> elements to F4 size (11906 x 18709 twips)
                if (preg_match('/<w:pgSz\b[^>]*\/?>/', $xmlContent)) {
                    $xmlContent = preg_replace(
                        '/<w:pgSz\b([^>]*?)(?:w:w="\d+")?([^>]*?)(?:w:h="\d+")?([^>]*?)\/?>/',
                        '<w:pgSz$1 w:w="11906" w:h="18709"$2$3/>',
                        $xmlContent
                    );
                    // Normalize any duplicate attributes
                    $xmlContent = preg_replace('/<w:pgSz\b[^>]*\/?>/', '<w:pgSz w:w="11906" w:h="18709"/>', $xmlContent);
                } else {
                    $xmlContent = preg_replace(
                        '/<w:sectPr\b([^>]*)>/',
                        '<w:sectPr$1><w:pgSz w:w="11906" w:h="18709"/>',
                        $xmlContent
                    );
                }
                $zip->addFromString('word/document.xml', $xmlContent);
            }
            $zip->close();
            $docxBinary = file_get_contents($tempFile);
        }
        @unlink($tempFile);

        return $docxBinary;
    }

    /**
     * Enforce A4 paper size (210 x 297 mm = 11906 x 16838 twips) in a DOCX binary.
     */
    public function enforceA4PageSize(string $docxBinary): string
    {
        if (substr($docxBinary, 0, 2) !== "PK") {
            return $docxBinary;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'docx_a4_');
        file_put_contents($tempFile, $docxBinary);

        $zip = new \ZipArchive();
        if ($zip->open($tempFile) === true) {
            $xmlContent = $zip->getFromName('word/document.xml');
            if ($xmlContent !== false) {
                // Set all <w:pgSz> elements to A4 size (11906 x 16838 twips)
                if (preg_match('/<w:pgSz\b[^>]*\/?>/', $xmlContent)) {
                    $xmlContent = preg_replace(
                        '/<w:pgSz\b([^>]*?)(?:w:w="\d+")?([^>]*?)(?:w:h="\d+")?([^>]*?)\/?>/',
                        '<w:pgSz$1 w:w="11906" w:h="16838"$2$3/>',
                        $xmlContent
                    );
                    $xmlContent = preg_replace('/<w:pgSz\b[^>]*\/?>/', '<w:pgSz w:w="11906" w:h="16838"/>', $xmlContent);
                } else {
                    $xmlContent = preg_replace(
                        '/<w:sectPr\b([^>]*)>/',
                        '<w:sectPr$1><w:pgSz w:w="11906" w:h="16838"/>',
                        $xmlContent
                    );
                }
                $zip->addFromString('word/document.xml', $xmlContent);
            }
            $zip->close();
            $docxBinary = file_get_contents($tempFile);
        }
        @unlink($tempFile);

        return $docxBinary;
    }

    /**
     * Remove all headers, footers, and letterhead references from a DOCX binary,
     * resetting paper size to A4 (210 x 297 mm) and margins to standard defaults.
     */
    public function removeHeaderAndFooterFromDocx(string $docxBinary): string
    {
        if (substr($docxBinary, 0, 2) !== 'PK') {
            return $docxBinary;
        }

        $tempFile = tempnam(sys_get_temp_dir(), 'rm_hdr_');
        file_put_contents($tempFile, $docxBinary);

        $zip = new \ZipArchive();
        if ($zip->open($tempFile) === true) {
            // 1. Remove all header and footer files and associated media from zip
            $filesToDelete = [];
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (
                    preg_match('#^word/(header|footer)\d*\.xml#i', $name) ||
                    preg_match('#^word/_rels/(header|footer)\d*\.xml\.rels#i', $name) ||
                    preg_match('#^word/media/(header|footer)\d*_image\d*\.#i', $name)
                ) {
                    $filesToDelete[] = $name;
                }
            }
            foreach ($filesToDelete as $file) {
                $zip->deleteName($file);
            }

            // 2. Clean word/document.xml
            $docXml = $zip->getFromName('word/document.xml');
            if ($docXml !== false) {
                // Remove headerReference and footerReference
                $docXml = preg_replace('/<w:headerReference\b[^>]*\/?>/', '', $docXml);
                $docXml = preg_replace('/<w:footerReference\b[^>]*\/?>/', '', $docXml);

                // Reset top and bottom margins to standard (top 1440 twips = 2.5cm, bottom 1134 twips = 2.0cm, left 1440, right 1134)
                if (preg_match('/<w:pgMar\b[^>]*\/>/', $docXml)) {
                    $docXml = preg_replace('/w:top="\d+"/', 'w:top="1440"', $docXml);
                    $docXml = preg_replace('/w:bottom="\d+"/', 'w:bottom="1134"', $docXml);
                    $docXml = preg_replace('/w:left="\d+"/', 'w:left="1440"', $docXml);
                    $docXml = preg_replace('/w:right="\d+"/', 'w:right="1134"', $docXml);
                }
                $zip->addFromString('word/document.xml', $docXml);
            }

            // 3. Clean word/_rels/document.xml.rels
            $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
            if ($relsXml !== false) {
                $relsXml = preg_replace('/<Relationship\b[^>]*Type="http:\/\/schemas\.openxmlformats\.org\/officeDocument\/2006\/relationships\/(header|footer)"[^>]*\/?>/i', '', $relsXml);
                $zip->addFromString('word/_rels/document.xml.rels', $relsXml);
            }

            // 4. Clean [Content_Types].xml
            $ctXml = $zip->getFromName('[Content_Types].xml');
            if ($ctXml !== false) {
                $ctXml = preg_replace('/<Override\b[^>]*PartName="\/word\/(header|footer)\d*\.xml"[^>]*\/?>/i', '', $ctXml);
                $zip->addFromString('[Content_Types].xml', $ctXml);
            }

            $zip->close();
            $docxBinary = file_get_contents($tempFile);
        }
        @unlink($tempFile);

        return $this->enforceA4PageSize($docxBinary);
    }

    /**
     * Apply corporate soft file (Kop Surat & Footer) to an existing DOCX binary,
     * preserving all existing paragraphs, tables, and text written by the user.
     */
    public function applyCorporateSoftFileToDocx(string $targetDocxBinary, \App\Models\CorporateSoftFile $corporateSoftFile): string
    {
        $disk = \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'));

        // Generate or get the kop DOCX binary
        if ($corporateSoftFile->isPdf()) {
            $kopDocx = $this->convertPdfToDocx($corporateSoftFile);
        } elseif ($corporateSoftFile->isImage()) {
            $kopDocx = $this->createDocxFromCorporateSoftFileImage($corporateSoftFile);
        } else {
            $kopDocx = $disk->exists($corporateSoftFile->file_path) ? $disk->get($corporateSoftFile->file_path) : null;
            if ($kopDocx) {
                $kopDocx = $this->enforceF4PageSize($kopDocx);
            }
        }

        if (!$kopDocx) {
            return $targetDocxBinary;
        }

        if (substr($kopDocx, 0, 2) !== 'PK' || substr($targetDocxBinary, 0, 2) !== 'PK') {
            return $kopDocx;
        }

        // 1. Clean any old headers/footers from target docx
        $cleanTargetDoc = $this->removeHeaderAndFooterFromDocx($targetDocxBinary);

        // 2. Read all header, footer, media files and sectPr from kopDocx
        $tmpKop = tempnam(sys_get_temp_dir(), 'kop_');
        file_put_contents($tmpKop, $kopDocx);
        $zipKop = new \ZipArchive();
        if ($zipKop->open($tmpKop) !== true) {
            @unlink($tmpKop);
            return $targetDocxBinary;
        }

        $kopFiles = [];
        for ($i = 0; $i < $zipKop->numFiles; $i++) {
            $name = $zipKop->getNameIndex($i);
            if (
                preg_match('#^word/(header|footer)\d*\.xml#i', $name) ||
                preg_match('#^word/_rels/(header|footer)\d*\.xml\.rels#i', $name) ||
                preg_match('#^word/media/#i', $name)
            ) {
                $kopFiles[$name] = $zipKop->getFromIndex($i);
            }
        }

        $kopDocXml = $zipKop->getFromName('word/document.xml');
        $kopRelsXml = $zipKop->getFromName('word/_rels/document.xml.rels');
        $kopCtXml = $zipKop->getFromName('[Content_Types].xml');
        $zipKop->close();
        @unlink($tmpKop);

        // 3. Inject kop components into target DOCX without touching existing body elements
        $tmpTarget = tempnam(sys_get_temp_dir(), 'tgt_');
        file_put_contents($tmpTarget, $cleanTargetDoc);
        $zipTarget = new \ZipArchive();
        if ($zipTarget->open($tmpTarget) !== true) {
            @unlink($tmpTarget);
            return $targetDocxBinary;
        }

        // Add headers, footers, media
        foreach ($kopFiles as $name => $content) {
            $zipTarget->addFromString($name, $content);
        }

        // Merge [Content_Types].xml
        if ($kopCtXml) {
            $targetCtXml = $zipTarget->getFromName('[Content_Types].xml');
            if ($targetCtXml !== false) {
                preg_match_all('/<Override\b[^>]*PartName="\/word\/(header|footer)\d*\.xml"[^>]*\/?>/i', $kopCtXml, $ctMatches);
                if (!empty($ctMatches[0])) {
                    foreach ($ctMatches[0] as $override) {
                        if (strpos($targetCtXml, $override) === false) {
                            $targetCtXml = str_replace('</Types>', $override . '</Types>', $targetCtXml);
                        }
                    }
                    $zipTarget->addFromString('[Content_Types].xml', $targetCtXml);
                }
            }
        }

        // Merge word/_rels/document.xml.rels
        if ($kopRelsXml) {
            $targetRelsXml = $zipTarget->getFromName('word/_rels/document.xml.rels');
            if ($targetRelsXml !== false) {
                preg_match_all('/<Relationship\b[^>]*Type="http:\/\/schemas\.openxmlformats\.org\/officeDocument\/2006\/relationships\/(header|footer)"[^>]*\/?>/i', $kopRelsXml, $relMatches);
                if (!empty($relMatches[0])) {
                    foreach ($relMatches[0] as $rel) {
                        if (strpos($targetRelsXml, $rel) === false) {
                            $targetRelsXml = str_replace('</Relationships>', $rel . '</Relationships>', $targetRelsXml);
                        }
                    }
                    $zipTarget->addFromString('word/_rels/document.xml.rels', $targetRelsXml);
                }
            }
        }

        // Merge word/document.xml sectPr (headerReferences, footerReferences, pgSz, pgMar)
        $targetDocXml = $zipTarget->getFromName('word/document.xml');
        if ($targetDocXml !== false && $kopDocXml) {
            // Extract headerReferences & footerReferences from kopDocXml
            preg_match_all('/<w:(headerReference|footerReference)\b[^>]*\/?>/', $kopDocXml, $refMatches);
            $headerFooterRefs = implode('', $refMatches[0] ?? []);

            // Extract pgMar from kopDocXml
            $pgMar = '';
            if (preg_match('/<w:pgMar\b[^>]*\/?>/', $kopDocXml, $pgMarMatch)) {
                $pgMar = $pgMarMatch[0];
            }

            if (preg_match('/<w:sectPr\b[^>]*>(.*?)<\/w:sectPr>/s', $targetDocXml, $sectMatch)) {
                $sectContent = $sectMatch[1];
                $sectContent = preg_replace('/<w:(headerReference|footerReference)\b[^>]*\/?>/', '', $sectContent);
                $sectContent = $headerFooterRefs . $sectContent;
                if ($pgMar) {
                    if (preg_match('/<w:pgMar\b[^>]*\/?>/', $sectContent)) {
                        $sectContent = preg_replace('/<w:pgMar\b[^>]*\/?>/', $pgMar, $sectContent);
                    } else {
                        $sectContent .= $pgMar;
                    }
                }
                if (preg_match('/<w:pgSz\b[^>]*\/?>/', $sectContent)) {
                    $sectContent = preg_replace('/<w:pgSz\b[^>]*\/?>/', '<w:pgSz w:w="11906" w:h="18709"/>', $sectContent);
                } else {
                    $sectContent .= '<w:pgSz w:w="11906" w:h="18709"/>';
                }
                $targetDocXml = preg_replace('/<w:sectPr\b[^>]*>.*?<\/w:sectPr>/s', '<w:sectPr>' . $sectContent . '</w:sectPr>', $targetDocXml);
            } else {
                $sectPr = '<w:sectPr>' . $headerFooterRefs . ($pgMar ?: '<w:pgMar w:top="2200" w:bottom="1440" w:left="1440" w:right="1134"/>') . '<w:pgSz w:w="11906" w:h="18709"/></w:sectPr>';
                $targetDocXml = str_replace('</w:body>', $sectPr . '</w:body>', $targetDocXml);
            }

            $zipTarget->addFromString('word/document.xml', $targetDocXml);
        }

        $zipTarget->close();
        $resultBinary = file_get_contents($tmpTarget);
        @unlink($tmpTarget);

        return $this->enforceF4PageSize($resultBinary);
    }




    /**
     * Generate an editable DOCX document from an image corporate soft file (JPEG/PNG kop surat).
     * Performs intelligent letterhead detection:
     * - Landscape / Banner Kop: Places kop at top of document body with clean paragraph spacing below.
     * - Portrait / Full Page scan: Detects and crops Top Kop and Bottom Footer, placing Kop in body and Footer in footer,
     *   leaving the middle body 100% clean and editable for the user in ONLYOFFICE.
     */
    public function createDocxFromCorporateSoftFileImage(\App\Models\CorporateSoftFile $corporateSoftFile): string
    {
        $disk = \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'));
        $imageBytes = $disk->get($corporateSoftFile->file_path);
        $ext = pathinfo($corporateSoftFile->file_path, PATHINFO_EXTENSION) ?: 'png';

        return $this->createDocxFromImageBytes($imageBytes, $ext);
    }

    /**
     * Generate an editable DOCX from raw image bytes with smart letterhead layout.
     */
    public function createDocxFromImageBytes(string $imageBytes, string $ext = 'png'): string
    {
        $tempImagePath = storage_path('app/temp_gen_in_' . uniqid() . '.' . $ext);
        file_put_contents($tempImagePath, $imageBytes);

        $im = @imagecreatefromstring($imageBytes);
        if (!$im) {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection([
                'pageSizeW' => 11906,
                'pageSizeH' => 18709,
                'marginTop' => 850,
                'marginBottom' => 1134,
                'marginLeft' => 1417,
                'marginRight' => 1134,
            ]);
            $section->addImage($tempImagePath, [
                'width' => 450,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            ]);
            $section->addTextBreak(1);
            $section->addText('', ['name' => 'Calibri', 'size' => 11]);

            $tempDocx = storage_path('app/temp_gen_out_' . uniqid() . '.docx');
            $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
            $writer->save($tempDocx);
            $res = file_get_contents($tempDocx);
            @unlink($tempImagePath);
            @unlink($tempDocx);

            return $this->enforceF4PageSize($res);
        }

        $w = imagesx($im);
        $h = imagesy($im);
        $ratio = ($h > 0) ? ($w / $h) : 1;

        $headerTempFile = null;
        $footerTempFile = null;
        $headerHeightPx = $h;
        $footerHeightPx = 0;

        // Printable width in points for F4 (210mm wide with 25mm left, 20mm right margins = 165mm = ~468 pt)
        $maxWidthPt = 460;

        if ($ratio >= 1.5) {
            // Landscape / Banner Kop only
            $headerTempFile = $tempImagePath;
            $headerHeightPx = $h;
        } else {
            // Portrait / Full Page scan: detect Top Kop and Bottom Footer
            $topEnd = 0;
            $emptyStreak = 0;
            $foundAnyTop = false;

            $scanLimitY = (int)($h * 0.45);
            for ($y = 0; $y < $scanLimitY; $y++) {
                $rowDark = 0;
                for ($x = 0; $x < $w; $x += 4) {
                    $rgb = imagecolorat($im, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    if ($r < 220 || $g < 220 || $b < 220) {
                        $rowDark++;
                    }
                }
                if ($rowDark > 3) {
                    $foundAnyTop = true;
                    $emptyStreak = 0;
                    $topEnd = $y;
                } else {
                    if ($foundAnyTop) {
                        $emptyStreak++;
                        if ($emptyStreak > 30) {
                            break;
                        }
                    }
                }
            }
            $topEnd = min($h, $topEnd + 15);

            // Detect Footer if any
            $bottomStart = $h;
            $emptyStreak = 0;
            $foundAnyBottom = false;
            $scanBottomLimitY = (int)($h * 0.65);

            for ($y = $h - 1; $y > $scanBottomLimitY; $y--) {
                $rowDark = 0;
                for ($x = 0; $x < $w; $x += 4) {
                    $rgb = imagecolorat($im, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    if ($r < 220 || $g < 220 || $b < 220) {
                        $rowDark++;
                    }
                }
                if ($rowDark > 3) {
                    $foundAnyBottom = true;
                    $emptyStreak = 0;
                    $bottomStart = $y;
                } else {
                    if ($foundAnyBottom) {
                        $emptyStreak++;
                        if ($emptyStreak > 30) {
                            break;
                        }
                    }
                }
            }
            $bottomStart = max(0, $bottomStart - 15);

            if ($foundAnyTop && $topEnd > 20 && $topEnd < (int)($h * 0.5)) {
                // Crop Header Kop
                $headerIm = imagecreatetruecolor($w, $topEnd);
                imagecopy($headerIm, $im, 0, 0, 0, 0, $w, $topEnd);
                $headerTempFile = storage_path('app/temp_crop_h_' . uniqid() . '.png');
                imagepng($headerIm, $headerTempFile);
                imagedestroy($headerIm);
                $headerHeightPx = $topEnd;

                // Crop Footer if present
                if ($foundAnyBottom && ($h - $bottomStart) > 20 && $bottomStart > (int)($h * 0.6)) {
                    $footerH = $h - $bottomStart;
                    $footerIm = imagecreatetruecolor($w, $footerH);
                    imagecopy($footerIm, $im, 0, 0, 0, $bottomStart, $w, $footerH);
                    $footerTempFile = storage_path('app/temp_crop_f_' . uniqid() . '.png');
                    imagepng($footerIm, $footerTempFile);
                    imagedestroy($footerIm);
                    $footerHeightPx = $footerH;
                }
            } else {
                // Whole image
                $headerTempFile = $tempImagePath;
                $headerHeightPx = $h;
            }
        }

        // Detect content bounds (left/right margins) directly from uploaded file
        $minX = $w;
        $maxX = 0;
        for ($y = 0; $y < $h; $y += 2) {
            for ($x = 0; $x < $w; $x += 2) {
                $rgb = imagecolorat($im, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                if ($r < 230 || $g < 230 || $b < 230) {
                    if ($x < $minX) $minX = $x;
                    if ($x > $maxX) $maxX = $x;
                }
            }
        }
        if ($minX >= $maxX) {
            $minX = 0;
            $maxX = $w;
        }

        $leftRatio = $minX / $w;
        $rightRatio = ($w - $maxX) / $w;

        // Map original file margin proportions to F4 page dimensions (11906 twips)
        $marginLeft = max(720, min(2500, (int)round(11906 * $leftRatio)));
        $marginRight = max(720, min(2500, (int)round(11906 * $rightRatio)));

        imagedestroy($im);

        // Build Word document with F4 dimensions (210 x 330 mm = 11906 x 18709 twips)
        $printableWidthPt = (11906 - ($marginLeft + $marginRight)) / 20.0;
        $maxWidthPt = max(400, $printableWidthPt);
        $scale = min(1.0, $maxWidthPt / ($w * 0.75));
        $headerWidthPt = ($w * 0.75) * $scale;
        $headerHeightPt = ($headerHeightPx * 0.75) * $scale;

        $headerHeightTwips = (int)(($headerHeightPt / 72.0) * 1440);
        $footerHeightTwips = 0;
        if ($footerTempFile && $footerHeightPx > 0) {
            $footerHeightPt = ($footerHeightPx * 0.75) * $scale;
            $footerHeightTwips = (int)(($footerHeightPt / 72.0) * 1440);
        }

        $marginTop = max(1440, $headerHeightTwips + 280);
        $marginBottom = max(1134, $footerHeightTwips + 280);

        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection([
            'pageSizeW' => 11906,
            'pageSizeH' => 18709,
            'marginTop' => $marginTop,
            'marginBottom' => $marginBottom,
            'marginLeft' => $marginLeft,
            'marginRight' => $marginRight,
            'headerHeight' => 720,
            'footerHeight' => 720,
        ]);

        // Place Kop Surat inside the Word Header (word/header1.xml)
        $header = $section->addHeader();
        $header->addImage($headerTempFile, [
            'width' => $headerWidthPt,
            'height' => $headerHeightPt,
            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
        ]);

        // Place Footer inside the Word Footer (word/footer1.xml) if present
        if ($footerTempFile && $footerHeightPx > 0) {
            $footer = $section->addFooter();
            $footer->addImage($footerTempFile, [
                'width' => $headerWidthPt,
                'height' => $footerHeightPt,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER,
            ]);
        }

        // Clean default editable paragraph in the document body
        $section->addText('', ['name' => 'Calibri', 'size' => 11]);

        $tempDocx = storage_path('app/temp_gen_out_' . uniqid() . '.docx');
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempDocx);

        $resultBytes = file_get_contents($tempDocx);

        @unlink($tempImagePath);
        if ($headerTempFile && $headerTempFile !== $tempImagePath) @unlink($headerTempFile);
        if ($footerTempFile) @unlink($footerTempFile);
        @unlink($tempDocx);

        return $this->enforceF4PageSize($resultBytes);
    }
}

