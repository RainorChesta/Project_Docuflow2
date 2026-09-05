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
        $updatedAt = $version->updated_at ? $version->updated_at->timestamp : ($version->created_at ? $version->created_at->timestamp : time());
        $cacheKey = 'onlyoffice_doc_key_' . $document->id . '_v' . $version->id . '_' . $updatedAt;
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDays(1), function () use ($document, $version, $updatedAt) {
            $timeKey = uniqid();
            
            $raw = sprintf(
                'doc_%d_v%d_%d_%d_%s',
                $document->id,
                $version->version_number,
                $version->id,
                $updatedAt,
                $timeKey
            );

            return substr(preg_replace('/[^0-9a-zA-Z_\-]/', '_', $raw), 0, 128);
        });
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

        $srcW = imagesx($src);
        $srcH = imagesy($src);

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

                if ($isNotTransparent && $isNotWhite) {
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

        // Center within square canvas
        $destX = (int) round(($targetSize - $drawW) / 2);
        $destY = (int) round(($targetSize - $drawH) / 2);

        // Create 1:1 true square image with transparent background
        $dest = imagecreatetruecolor($targetSize, $targetSize);
        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 255, 255, 255, 127);
        imagefilledrectangle($dest, 0, 0, $targetSize, $targetSize, $transparent);
        imagealphablending($dest, true);

        // Resample cropped signature neatly into the center of the square canvas
        imagecopyresampled($dest, $src, $destX, $destY, $minX, $minY, $drawW, $drawH, $cropW, $cropH);

        ob_start();
        imagepng($dest);
        $result = ob_get_clean();

        imagedestroy($src);
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
        $updatedAt = $template->updated_at ? $template->updated_at->timestamp : ($template->created_at ? $template->created_at->timestamp : time());
        $cacheKey = 'onlyoffice_template_key_' . $template->id . '_' . $updatedAt;
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDays(1), function () use ($template, $updatedAt) {
            $timeKey = uniqid();
            
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
}
