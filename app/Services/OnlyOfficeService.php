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

        $sessionNonce = \Illuminate\Support\Facades\Cache::get('onlyoffice_doc_session_key_' . $document->id . '_v' . $version->id, '');
        $nonceSuffix = $sessionNonce ? ('_' . $sessionNonce) : '';

        $raw = sprintf(
            'doc_%d_v%d_%s%s',
            $document->id,
            $version->id,
            substr($fileHash, 0, 20),
            $nonceSuffix
        );

        return substr(preg_replace('/[^0-9a-zA-Z_\-]/', '_', $raw), 0, 128);
    }

    /**
     * Rotate / clear cached ONLYOFFICE document keys for a document so the next session opens cleanly.
     */
    public function rotateDocumentKey(Document $document, ?DocumentVersion $version = null): void
    {
        $newNonce = substr(md5(uniqid((string)mt_rand(), true)), 0, 8);
        if ($version) {
            \Illuminate\Support\Facades\Cache::put('onlyoffice_doc_session_key_' . $document->id . '_v' . $version->id, $newNonce, 86400);
        }
        foreach ($document->versions as $v) {
            \Illuminate\Support\Facades\Cache::put('onlyoffice_doc_session_key_' . $document->id . '_v' . $v->id, $newNonce, 86400);
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
     * Generate the raw PNG binary of a pending signature / stamp placeholder badge.
     */
    public function generatePlaceholderPngBytes(?string $text = null, ?int $requestId = null, bool $isStamp = false): string
    {
        if (!extension_loaded('gd')) {
            return '';
        }

        $cacheKey = 'oo_placeholder_png_' . md5(($text ?? '') . '_' . ($requestId ?? 0) . '_' . ($isStamp ? '1' : '0'));
        return \Illuminate\Support\Facades\Cache::rememberForever($cacheKey, function () use ($text, $requestId, $isStamp) {
            $width = 400;
            $height = 400;

            $image = imagecreatetruecolor($width, $height);
            
            imagealphablending($image, false);
            imagesavealpha($image, true);
            $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
            imagefilledrectangle($image, 0, 0, $width, $height, $transparent);
            imagealphablending($image, true);

        // Amber background card
        $bgColor = imagecolorallocate($image, 254, 243, 199);       // #FEF3C7
        $borderColor = imagecolorallocate($image, 245, 158, 11);     // #F59E0B
        $titleColor = imagecolorallocate($image, 180, 83, 9);        // #B45309
        $subtitleColor = imagecolorallocate($image, 146, 64, 14);    // #92400E
        $pillBg = imagecolorallocate($image, 253, 230, 138);         // #FDE68A
        $pillBorder = imagecolorallocate($image, 217, 119, 6);       // #D97706
        $dividerColor = imagecolorallocate($image, 252, 211, 77);    // #FCD34D
        $mutedColor = imagecolorallocate($image, 161, 98, 7);        // #A16207

        // Outer box with border
        imagefilledrectangle($image, 6, 6, $width - 7, $height - 7, $bgColor);
        imagerectangle($image, 6, 6, $width - 7, $height - 7, $borderColor);
        imagerectangle($image, 7, 7, $width - 8, $height - 8, $borderColor);

        $header = $isStamp ? "[ STEMPEL PERUSAHAAN ]" : "[ TANDA TANGAN DIGITAL ]";
        $subHeader = "MENUNGGU PERSETUJUAN:";
        $mainText = $text ? strtoupper($text) : ($isStamp ? "STEMPEL PERUSAHAAN" : "TANDA TANGAN RESMI");
        $pillText = "PENDING APPROVAL";
        $footnote = "OTOMATIS BERUBAH SETELAH DISETUJUI";

        // Section 1: Header
        $fontHeader = 4;
        $fwH = imagefontwidth($fontHeader);
        $xH = ($width - ($fwH * strlen($header))) / 2;
        imagestring($image, $fontHeader, max(12, (int) $xH), 40, $header, $titleColor);

        // Divider 1
        imageline($image, 30, 75, $width - 31, 75, $dividerColor);

        // Section 2: Subheader ("MENUNGGU PERSETUJUAN:")
        $fontSub = 3;
        $fwSub = imagefontwidth($fontSub);
        $xSub = ($width - ($fwSub * strlen($subHeader))) / 2;
        imagestring($image, $fontSub, max(12, (int) $xSub), 115, $subHeader, $subtitleColor);

        // Section 3: Main Name (Font 5)
        $fontLarge = 5;
        $fwL = imagefontwidth($fontLarge);
        $truncatedMain = strlen($mainText) > 26 ? (substr($mainText, 0, 24) . '..') : $mainText;
        $xL = ($width - ($fwL * strlen($truncatedMain))) / 2;
        imagestring($image, $fontLarge, max(12, (int) $xL), 165, $truncatedMain, $subtitleColor);

        // Divider 2
        imageline($image, 30, 225, $width - 31, 225, $dividerColor);

        // Section 4: Pill Badge
        $pillLeft = 50;
        $pillRight = $width - 51;
        $pillTop = 250;
        $pillBottom = 295;
        imagefilledrectangle($image, $pillLeft, $pillTop, $pillRight, $pillBottom, $pillBg);
        imagerectangle($image, $pillLeft, $pillTop, $pillRight, $pillBottom, $pillBorder);

        $fontPill = 4;
        $fwP = imagefontwidth($fontPill);
        $xP = ($width - ($fwP * strlen($pillText))) / 2;
        imagestring($image, $fontPill, max(12, (int) $xP), 265, $pillText, $titleColor);

        // Section 5: Footnote
        $fontFoot = 2;
        $fwF = imagefontwidth($fontFoot);
        $xF = ($width - ($fwF * strlen($footnote))) / 2;
        imagestring($image, $fontFoot, max(12, (int) $xF), 335, $footnote, $mutedColor);

        if ($requestId && $requestId > 0) {
            // Embed invisible identifier text using background color with safe delimiters
            imagestring($image, 1, 10, 380, "DocuFlowSigReq:#" . $requestId . "# [DF-REQ:#" . $requestId . "#]", $bgColor);
        }

        ob_start();
        imagepng($image);
        $imageData = ob_get_clean();
        imagedestroy($image);

        if ($requestId && $requestId > 0 && $imageData) {
            $keyword = "DocuFlowSigReq";
            $text = "#" . $requestId . "#";
            $chunkData = $keyword . "\0" . $text;
            $chunkLen = pack('N', strlen($chunkData));
            $chunkType = 'tEXt';
            $crc = pack('N', crc32($chunkType . $chunkData));
            $tExtChunk = $chunkLen . $chunkType . $chunkData . $crc;

            $iendPos = strrpos($imageData, "IEND");
            if ($iendPos !== false && $iendPos >= 4) {
                $insertPos = $iendPos - 4;
                $imageData = substr($imageData, 0, $insertPos) . $tExtChunk . substr($imageData, $insertPos);
            }
        }

        return $imageData ?: '';
    });
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
    public function formatSquareSignature(string $rawPngBytes, int $targetSize = 400, int $padding = 24, ?int $requestId = null, bool $isStamp = false): string
    {
        if (!extension_loaded('gd') || empty($rawPngBytes)) {
            return $rawPngBytes;
        }

        $cacheKey = 'oo_sq_sig_' . md5($rawPngBytes) . '_' . $targetSize . '_' . $padding . '_' . ($requestId ?? 0) . '_' . ($isStamp ? '1' : '0');
        return \Illuminate\Support\Facades\Cache::rememberForever($cacheKey, function () use ($rawPngBytes, $targetSize, $padding, $requestId, $isStamp) {
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

        // Calculate uniform scaling to fit inside the inner bounding box (targetSize - 2 * padding)
        $innerSize = max(1, $targetSize - (2 * $padding));
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

        if ($requestId && $requestId > 0) {
            // Embed invisible identifier text using transparent color
            imagestring($dest, 1, 10, $targetSize - 20, "DocuFlowSigReq:" . $requestId . " [DF-REQ:" . $requestId . "]", imagecolorallocatealpha($dest, 255, 255, 255, 127));
        }

        ob_start();
        imagepng($dest);
        $result = ob_get_clean();

        imagedestroy($src);
        imagedestroy($cropped);
        imagedestroy($dest);

        if ($requestId && $requestId > 0 && $result) {
            $keyword = "DocuFlowSigReq";
            $text = "#" . $requestId . "#";
            $chunkData = $keyword . "\0" . $text;
            $chunkLen = pack('N', strlen($chunkData));
            $chunkType = 'tEXt';
            $crc = pack('N', crc32($chunkType . $chunkData));
            $tExtChunk = $chunkLen . $chunkType . $chunkData . $crc;

            $iendPos = strrpos($result, "IEND");
            if ($iendPos !== false && $iendPos >= 4) {
                $insertPos = $iendPos - 4;
                $result = substr($result, 0, $insertPos) . $tExtChunk . substr($result, $insertPos);
            }
        }

        return $result ?: $rawPngBytes;
        });
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
                    'logo' => [
                        'url' => 'https://cmhgroup.id/',
                    ],
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
                    'logo' => [
                        'url' => 'https://cmhgroup.id/',
                    ],
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
                    'logo' => [
                        'url' => 'https://cmhgroup.id/',
                        'visible' => true,
                    ],
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
    public function convertPdfToDocx($fileOrPath, ?string $fileUrl = null, string $targetPaperSize = 'f4'): ?string
    {
        // 1. Resolve local absolute file path
        $localPath = null;
        if ($fileOrPath instanceof \App\Models\CorporateSoftFile) {
            $targetPaperSize = $fileOrPath->paper_size ?? 'f4';
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
            $convertedDocx = $this->convertPdfUsingDockerX2t($localPath, $targetPaperSize);
            if ($convertedDocx && substr($convertedDocx, 0, 2) === "PK") {
                return $targetPaperSize === 'a4' ? $this->enforceA4PageSize($convertedDocx) : $this->enforceF4PageSize($convertedDocx);
            }
        }

        // 3. Try ConvertService.ashx via HTTP API
        if ($fileUrl) {
            $convertedDocx = $this->convertDocument($fileUrl, 'pdf', 'docx');
            if ($convertedDocx && substr($convertedDocx, 0, 2) === "PK") {
                return $targetPaperSize === 'a4' ? $this->enforceA4PageSize($convertedDocx) : $this->enforceF4PageSize($convertedDocx);
            }
        }

        // 4. Fallback: Smalot PdfParser + PhpWord
        if ($localPath && file_exists($localPath)) {
            $convertedDocx = $this->convertPdfUsingPhpWordFallback($localPath);
            if ($convertedDocx && substr($convertedDocx, 0, 2) === "PK") {
                return $targetPaperSize === 'a4' ? $this->enforceA4PageSize($convertedDocx) : $this->enforceF4PageSize($convertedDocx);
            }
        }

        return null;
    }

    /**
     * Convert a PDF file to DOCX using ONLYOFFICE's native x2t binary inside Docker container.
     * Extracts the corporate letterhead directly into the Word Header (word/header1.xml)
     * and Footer (word/footer1.xml), with clean typing body in target dimensions.
     */
    protected function convertPdfUsingDockerX2t(string $localPdfPath, string $targetPaperSize = 'f4'): ?string
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

                    return $this->createDocxFromImageBytes($pngBytes, 'png', $targetPaperSize);
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
                return $targetPaperSize === 'a4' ? $this->enforceA4PageSize($content) : $this->enforceF4PageSize($content);
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
     * Convert a DOCX corporate soft file with body-level design to smart header/footer DOCX
     * using ONLYOFFICE's native x2t binary inside Docker container.
     */
    public function convertDocxUsingDockerX2t(string $localDocxPath, string $targetPaperSize = 'f4'): ?string
    {
        try {
            $container = env('ONLYOFFICE_DOCKER_CONTAINER', 'dokuflow-onlyoffice');
            $uid = uniqid('conv_docx_', true);
            $containerIn = "/var/www/onlyoffice/Data/in_{$uid}.docx";
            $containerPngOut = "/var/www/onlyoffice/Data/out_{$uid}.png";
            $tempLocalPng = storage_path("app/temp_png_{$uid}.png");

            // 1. Copy DOCX into container
            $cpInCmd = sprintf('docker cp %s %s:%s 2>&1', escapeshellarg($localDocxPath), escapeshellarg($container), escapeshellarg($containerIn));
            @exec($cpInCmd, $outIn, $codeIn);
            if ($codeIn !== 0) {
                return null;
            }

            // 2. Render to high-res PNG for header & footer extraction
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

                    return $this->createDocxFromImageBytes($pngBytes, 'png', $targetPaperSize);
                }
            }
            if (file_exists($tempLocalPng)) {
                @unlink($tempLocalPng);
            }
            @exec(sprintf('docker exec %s rm -f %s %s 2>&1', escapeshellarg($container), escapeshellarg($containerIn), escapeshellarg($containerPngOut)));
        } catch (\Throwable $e) {
            Log::warning('convertDocxUsingDockerX2t failed: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Check if a DOCX binary has both native explicit header and footer XML files.
     */
    public function docxHasExplicitHeaderAndFooter(string $docxBinary): bool
    {
        if (empty($docxBinary) || substr($docxBinary, 0, 2) !== 'PK') {
            return false;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'chk_docx_');
        file_put_contents($tmp, $docxBinary);
        $zip = new \ZipArchive();
        if ($zip->open($tmp) !== true) {
            @unlink($tmp);
            return false;
        }

        $hasHeader = false;
        $hasFooter = false;

        $isMeaningful = function (?string $xml): bool {
            if (!$xml) return false;
            if (preg_match('/<w:drawing\b|<w:pict\b|<v:shape\b|<v:imagedata\b|<a:blip\b|<pic:pic\b|<w:tbl\b/i', $xml)) {
                return true;
            }
            if (preg_match_all('/<w:t\b[^>]*>(.*?)<\/w:t>/is', $xml, $matches)) {
                foreach ($matches[1] as $text) {
                    if (trim(html_entity_decode(strip_tags($text))) !== '') {
                        return true;
                    }
                }
            }
            return false;
        };

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('#^word/header\d*\.xml$#i', $name)) {
                if ($isMeaningful($zip->getFromIndex($i))) {
                    $hasHeader = true;
                }
            } elseif (preg_match('#^word/footer\d*\.xml$#i', $name)) {
                if ($isMeaningful($zip->getFromIndex($i))) {
                    $hasFooter = true;
                }
            }
        }

        $zip->close();
        @unlink($tmp);

        return $hasHeader || $hasFooter;
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
     * Convert any PDF to F4 dimensions (210 x 330 mm = 595.28 x 935.43 pt).
     * Automatically scales and aligns letterhead to top.
     */
    public function convertPdfToF4(string $pdfBinary): string
    {
        if (empty($pdfBinary) || !str_starts_with($pdfBinary, '%PDF-')) {
            return $pdfBinary;
        }

        $tempIn = tempnam(sys_get_temp_dir(), 'pdf_in_');
        file_put_contents($tempIn, $pdfBinary);

        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($tempIn);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $isLandscape = $size['width'] > $size['height'];
                $pageFormat = $isLandscape ? [330, 210] : [210, 330];
                $orientation = $isLandscape ? 'L' : 'P';

                $pdf->AddPage($orientation, $pageFormat);

                $targetW = $isLandscape ? 330 : 210;
                $targetH = $isLandscape ? 210 : 330;

                if ($isLandscape) {
                    $scale = min(330 / max(1, $size['width']), 210 / max(1, $size['height']), 1.0);
                    $w = $size['width'] * $scale;
                    $h = $size['height'] * $scale;
                    $x = ($targetW - $w) / 2;
                    $y = ($targetH - $h) / 2;
                } else {
                    // Portrait: Scale width to fit 210mm (F4 width) and top align
                    $w = 210;
                    $h = ($size['height'] / max(1, $size['width'])) * 210;
                    if ($h > 330) {
                        $h = 330;
                        $w = ($size['width'] / max(1, $size['height'])) * 330;
                        $x = (210 - $w) / 2;
                        $y = 0;
                    } else {
                        $x = 0;
                        $y = 0;
                    }
                }

                $pdf->useTemplate($templateId, $x, $y, $w, $h);
            }

            $tempOut = tempnam(sys_get_temp_dir(), 'pdf_out_');
            $pdf->Output($tempOut, 'F');
            $result = file_get_contents($tempOut);
            @unlink($tempOut);

            return $result ?: $pdfBinary;
        } catch (\Throwable $e) {
            Log::warning('convertPdfToF4 failed: ' . $e->getMessage(), ['exception' => $e]);
            return $pdfBinary;
        } finally {
            @unlink($tempIn);
        }
    }

    /**
     * Convert any uploaded document (DOCX, DOC, or PDF) to standard F4 paper size.
     */
    public function convertFileToF4(string $binaryContent, string $extension): string
    {
        $ext = strtolower($extension);
        if (in_array($ext, ['docx', 'doc']) || substr($binaryContent, 0, 2) === 'PK') {
            return $this->enforceF4PageSize($binaryContent);
        }

        if ($ext === 'pdf' || str_starts_with($binaryContent, '%PDF-')) {
            return $this->convertPdfToF4($binaryContent);
        }

        return $binaryContent;
    }

    /**
     * Convert any PDF to A4 dimensions (210 x 297 mm = 595.28 x 841.89 pt).
     * Automatically scales and aligns letterhead to top.
     */
    public function convertPdfToA4(string $pdfBinary): string
    {
        if (empty($pdfBinary) || !str_starts_with($pdfBinary, '%PDF-')) {
            return $pdfBinary;
        }

        $tempIn = tempnam(sys_get_temp_dir(), 'pdf_in_');
        file_put_contents($tempIn, $pdfBinary);

        try {
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($tempIn);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $isLandscape = $size['width'] > $size['height'];
                $pageFormat = $isLandscape ? [297, 210] : [210, 297];
                $orientation = $isLandscape ? 'L' : 'P';

                $pdf->AddPage($orientation, $pageFormat);

                $targetW = $isLandscape ? 297 : 210;
                $targetH = $isLandscape ? 210 : 297;

                if ($isLandscape) {
                    $scale = min(297 / max(1, $size['width']), 210 / max(1, $size['height']), 1.0);
                    $w = $size['width'] * $scale;
                    $h = $size['height'] * $scale;
                    $x = ($targetW - $w) / 2;
                    $y = ($targetH - $h) / 2;
                } else {
                    // Portrait: Scale width to fit 210mm (A4 width) and top align
                    $w = 210;
                    $h = ($size['height'] / max(1, $size['width'])) * 210;
                    if ($h > 297) {
                        $h = 297;
                        $w = ($size['width'] / max(1, $size['height'])) * 297;
                        $x = (210 - $w) / 2;
                        $y = 0;
                    } else {
                        $x = 0;
                        $y = 0;
                    }
                }

                $pdf->useTemplate($templateId, $x, $y, $w, $h);
            }

            $tempOut = tempnam(sys_get_temp_dir(), 'pdf_out_');
            $pdf->Output($tempOut, 'F');
            $result = file_get_contents($tempOut);
            @unlink($tempOut);

            return $result ?: $pdfBinary;
        } catch (\Throwable $e) {
            Log::warning('convertPdfToA4 failed: ' . $e->getMessage(), ['exception' => $e]);
            return $pdfBinary;
        } finally {
            @unlink($tempIn);
        }
    }

    /**
     * Convert any uploaded document (DOCX, DOC, or PDF) to standard A4 paper size.
     */
    public function convertFileToA4(string $binaryContent, string $extension): string
    {
        $ext = strtolower($extension);
        if (in_array($ext, ['docx', 'doc']) || substr($binaryContent, 0, 2) === 'PK') {
            return $this->enforceA4PageSize($binaryContent);
        }

        if ($ext === 'pdf' || str_starts_with($binaryContent, '%PDF-')) {
            return $this->convertPdfToA4($binaryContent);
        }

        return $binaryContent;
    }

    /**
     * Convert any uploaded document (DOCX, DOC, or PDF) to specified target paper size (A4 or F4).
     */
    public function convertFileToPaperSize(string $binaryContent, string $extension, string $paperSize = 'f4'): string
    {
        if (strtolower($paperSize) === 'a4') {
            return $this->convertFileToA4($binaryContent, $extension);
        }

        return $this->convertFileToF4($binaryContent, $extension);
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
                // Remove headerReference and footerReference while preserving original margins (<w:pgMar>)
                $docXml = preg_replace('/<w:headerReference\b[^>]*\/?>/', '', $docXml);
                $docXml = preg_replace('/<w:footerReference\b[^>]*\/?>/', '', $docXml);

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
     * strictly preserving all existing paragraphs, tables, drawings, and text written by the user.
     */
    public function applyCorporateSoftFileToDocx(string $targetDocxBinary, \App\Models\CorporateSoftFile $corporateSoftFile): string
    {
        if (empty($targetDocxBinary) || substr($targetDocxBinary, 0, 2) !== 'PK') {
            return $targetDocxBinary;
        }

        $disk = \Illuminate\Support\Facades\Storage::disk(config('onlyoffice.storage_disk', 'local'));
        if (!$disk->exists($corporateSoftFile->file_path)) {
            return $targetDocxBinary;
        }

        $originalTargetBackup = $targetDocxBinary;

        try {
            // 1. Obtain the Letterhead DOCX components
            $isA4 = $corporateSoftFile->isA4();
            $targetPaperSize = $isA4 ? 'a4' : 'f4';
            $kopDocx = null;

            if ($corporateSoftFile->isPdf()) {
                $kopDocx = $this->convertPdfToDocx($corporateSoftFile, null, $targetPaperSize);
            } elseif ($corporateSoftFile->isImage()) {
                $kopDocx = $this->createDocxFromCorporateSoftFileImage($corporateSoftFile);
            } else {
                // DOCX corporate soft file
                $kopDocx = $disk->get($corporateSoftFile->file_path);
            }

            if (!$kopDocx || substr($kopDocx, 0, 2) !== 'PK') {
                return $targetDocxBinary;
            }

            // 2. Clean old letterhead headers/footers from target while preserving all body content and user media
            $cleanTargetDoc = $this->removeHeaderAndFooterFromDocx($targetDocxBinary);

            // 3. Extract header/footer files, media, relationships, and sectPr from kopDocx
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

            // Helper to determine if an XML part has visible text or graphics
            $isMeaningfulXml = function (?string $xml): bool {
                if (!$xml) {
                    return false;
                }
                if (preg_match('/<w:drawing\b|<w:pict\b|<v:shape\b|<v:imagedata\b|<a:blip\b|<pic:pic\b|<w:tbl\b/i', $xml)) {
                    return true;
                }
                if (preg_match_all('/<w:t\b[^>]*>(.*?)<\/w:t>/is', $xml, $matches)) {
                    foreach ($matches[1] as $text) {
                        if (trim(html_entity_decode(strip_tags($text))) !== '') {
                            return true;
                        }
                    }
                }
                return false;
            };

            // Inspect explicit headers and footers in kop
            $hasExplicitHeader = false;
            $meaningfulHeaderPath = null;
            foreach ($kopFiles as $path => $content) {
                if (preg_match('#^word/header\d*\.xml$#i', $path)) {
                    if ($isMeaningfulXml($content)) {
                        $hasExplicitHeader = true;
                        if (!$meaningfulHeaderPath) {
                            $meaningfulHeaderPath = $path;
                        }
                    }
                }
            }

            $hasExplicitFooter = false;
            $meaningfulFooterPath = null;
            foreach ($kopFiles as $path => $content) {
                if (preg_match('#^word/footer\d*\.xml$#i', $path)) {
                    if ($isMeaningfulXml($content)) {
                        $hasExplicitFooter = true;
                        if (!$meaningfulFooterPath) {
                            $meaningfulFooterPath = $path;
                        }
                    }
                }
            }

            // Ensure header1.xml and footer1.xml exist if meaningful headers/footers were defined under other filenames (e.g. header2.xml, footer2.xml)
            if ($hasExplicitHeader && $meaningfulHeaderPath && (!isset($kopFiles['word/header1.xml']) || !$isMeaningfulXml($kopFiles['word/header1.xml']))) {
                $kopFiles['word/header1.xml'] = $kopFiles[$meaningfulHeaderPath];
                $srcRelsPath = preg_replace('#^word/(header\d*\.xml)$#i', 'word/_rels/$1.rels', $meaningfulHeaderPath);
                if (isset($kopFiles[$srcRelsPath])) {
                    $kopFiles['word/_rels/header1.xml.rels'] = $kopFiles[$srcRelsPath];
                }
            }

            if ($hasExplicitFooter && $meaningfulFooterPath && (!isset($kopFiles['word/footer1.xml']) || !$isMeaningfulXml($kopFiles['word/footer1.xml']))) {
                $kopFiles['word/footer1.xml'] = $kopFiles[$meaningfulFooterPath];
                $srcRelsPath = preg_replace('#^word/(footer\d*\.xml)$#i', 'word/_rels/$1.rels', $meaningfulFooterPath);
                if (isset($kopFiles[$srcRelsPath])) {
                    $kopFiles['word/_rels/footer1.xml.rels'] = $kopFiles[$srcRelsPath];
                }
            }

            $createRelsForXml = function(string $xmlContent, ?string $sourceRelsXml): string {
                $matchedRels = [];
                if ($sourceRelsXml && preg_match_all('/\b(?:r:embed|r:id|r:href|id)="([^"]+)"/i', $xmlContent, $refMatches)) {
                    $referencedIds = array_unique($refMatches[1]);
                    foreach ($referencedIds as $rId) {
                        if (preg_match('/<Relationship\b[^>]*\bId="' . preg_quote($rId, '/') . '"[^>]*\/?>/i', $sourceRelsXml, $relMatch)) {
                            $matchedRels[$rId] = $relMatch[0];
                        }
                    }
                }
                return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
                    '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . "\n" .
                    implode("\n", $matchedRels) . "\n" .
                    '</Relationships>';
            };

            // If header or footer is missing, extract from document.xml body paragraphs / drawings
            if ((!$hasExplicitHeader || !$hasExplicitFooter) && $kopDocXml && preg_match('/<w:body\b[^>]*>(.*?)<\/w:body>/s', $kopDocXml, $bm)) {
                $body = $bm[1];
                $bodyWithoutSect = preg_replace('/<w:sectPr\b[^>]*>.*?<\/w:sectPr>/s', '', $body);
                preg_match_all('/<(w:p|w:tbl)\b[^>]*>.*?<\/\1>/s', $bodyWithoutSect, $pMatches);
                $elements = $pMatches[0];

                if (!empty($elements)) {
                    $isElemEmpty = function($elem) {
                        if (preg_match('/<w:drawing\b|<w:pict\b|<v:shape\b|<v:imagedata\b|<a:blip\b|<pic:pic\b|<w:tbl\b/i', $elem)) {
                            return false;
                        }
                        return (trim(html_entity_decode(strip_tags($elem))) === '');
                    };

                    $isBottomElem = function($elem) {
                        if (preg_match('/<wp:positionV[^>]*>.*?<wp:align>bottom<\/wp:align>.*?<\/wp:positionV>/s', $elem)) {
                            return true;
                        }
                        if (preg_match('/<wp:positionV[^>]*>.*?<wp:posOffset>(\d+)<\/wp:posOffset>.*?<\/wp:positionV>/s', $elem, $m)) {
                            if ((int)$m[1] > 4000000) {
                                return true;
                            }
                        }
                        return false;
                    };

                    $headerElems = [];
                    $footerElems = [];
                    $hasBottomAnchored = false;
                    foreach ($elements as $el) {
                        if ($isBottomElem($el)) {
                            $hasBottomAnchored = true;
                            break;
                        }
                    }

                    if ($hasBottomAnchored) {
                        foreach ($elements as $el) {
                            if ($isElemEmpty($el)) continue;
                            if ($isBottomElem($el)) {
                                $footerElems[] = $el;
                            } else {
                                $headerElems[] = $el;
                            }
                        }
                    } else {
                        $clusters = [];
                        $currentCluster = [];
                        foreach ($elements as $el) {
                            if ($isElemEmpty($el)) {
                                if (!empty($currentCluster)) {
                                    $clusters[] = $currentCluster;
                                    $currentCluster = [];
                                }
                            } else {
                                $currentCluster[] = $el;
                            }
                        }
                        if (!empty($currentCluster)) {
                            $clusters[] = $currentCluster;
                        }

                        if (count($clusters) === 1) {
                            $headerElems = $clusters[0];
                        } elseif (count($clusters) >= 2) {
                            $headerElems = $clusters[0];
                            for ($c = 1; $c < count($clusters); $c++) {
                                $footerElems = array_merge($footerElems, $clusters[$c]);
                            }
                        }
                    }

                    $nsAttributes = 'xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture" xmlns:v="urn:schemas-microsoft-com:vml"';
                    if (preg_match('/<w:document\b([^>]*)>/', $kopDocXml, $docTagMatch)) {
                        $nsAttributes = $docTagMatch[1];
                    }

                    if (!$hasExplicitHeader && !empty($headerElems)) {
                        $hXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
                            '<w:hdr ' . $nsAttributes . '>' . implode('', $headerElems) . '</w:hdr>';
                        $kopFiles['word/header1.xml'] = $hXml;
                        $kopFiles['word/_rels/header1.xml.rels'] = $createRelsForXml($hXml, $kopRelsXml);
                        $hasExplicitHeader = true;
                    }

                    if (!$hasExplicitFooter && !empty($footerElems)) {
                        $fXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
                            '<w:ftr ' . $nsAttributes . '>' . implode('', $footerElems) . '</w:ftr>';
                        $kopFiles['word/footer1.xml'] = $fXml;
                        $kopFiles['word/_rels/footer1.xml.rels'] = $createRelsForXml($fXml, $kopRelsXml);
                        $hasExplicitFooter = true;
                    }
                }
            }

            if (!$hasExplicitHeader && empty($kopFiles)) {
                return $targetDocxBinary;
            }

            // Namespace kop media filenames to avoid collision with target document media
            $mediaRenameMap = [];
            $updatedKopFiles = [];
            foreach ($kopFiles as $path => $content) {
                if (preg_match('#^word/media/([^/]+)$#i', $path, $m)) {
                    $oldName = $m[1];
                    $newName = 'kop_' . $oldName;
                    $mediaRenameMap[$oldName] = $newName;
                    $updatedKopFiles['word/media/' . $newName] = $content;
                } else {
                    $updatedKopFiles[$path] = $content;
                }
            }
            $kopFiles = $updatedKopFiles;

            // Update media references in header/footer XML and relationship files
            if (!empty($mediaRenameMap)) {
                foreach ($kopFiles as $path => $content) {
                    if (preg_match('#^word/(_rels/)?(header|footer)\d*\.xml(\.rels)?$#i', $path)) {
                        foreach ($mediaRenameMap as $oldName => $newName) {
                            $content = str_replace('Target="media/' . $oldName . '"', 'Target="media/' . $newName . '"', $content);
                            $content = str_replace('Target="../media/' . $oldName . '"', 'Target="../media/' . $newName . '"', $content);
                            $content = str_replace('Target="' . $oldName . '"', 'Target="' . $newName . '"', $content);
                            $content = str_replace('name="' . $oldName . '"', 'name="' . $newName . '"', $content);
                        }
                        $kopFiles[$path] = $content;
                    }
                }
            }

            // 4. Inject kop components into target DOCX
            $tmpTarget = tempnam(sys_get_temp_dir(), 'tgt_');
            file_put_contents($tmpTarget, $cleanTargetDoc);
            $zipTarget = new \ZipArchive();
            if ($zipTarget->open($tmpTarget) !== true) {
                @unlink($tmpTarget);
                return $targetDocxBinary;
            }

            // Add all header, footer, and media files to target ZIP
            foreach ($kopFiles as $name => $content) {
                $zipTarget->addFromString($name, $content);
            }

            // Update [Content_Types].xml
            $targetCtXml = $zipTarget->getFromName('[Content_Types].xml');
            if ($targetCtXml !== false) {
                foreach (array_keys($kopFiles) as $path) {
                    if (preg_match('#^word/(header\d*\.xml)$#i', $path, $hm)) {
                        $part = '/word/' . $hm[1];
                        if (!str_contains($targetCtXml, 'PartName="' . $part . '"')) {
                            $headerOverride = '<Override PartName="' . $part . '" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/>';
                            $targetCtXml = str_replace('</Types>', $headerOverride . '</Types>', $targetCtXml);
                        }
                    } elseif (preg_match('#^word/(footer\d*\.xml)$#i', $path, $fm)) {
                        $part = '/word/' . $fm[1];
                        if (!str_contains($targetCtXml, 'PartName="' . $part . '"')) {
                            $footerOverride = '<Override PartName="' . $part . '" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/>';
                            $targetCtXml = str_replace('</Types>', $footerOverride . '</Types>', $targetCtXml);
                        }
                    }
                }

                $extTypes = [
                    'png'  => 'image/png',
                    'jpeg' => 'image/jpeg',
                    'jpg'  => 'image/jpeg',
                    'emf'  => 'image/x-emf',
                    'wmf'  => 'image/x-wmf',
                    'gif'  => 'image/gif',
                    'svg'  => 'image/svg+xml',
                    'tif'  => 'image/tiff',
                    'tiff' => 'image/tiff',
                    'webp' => 'image/webp',
                    'xml'  => 'application/xml',
                    'rels' => 'application/vnd.openxmlformats-package.relationships+xml',
                ];
                foreach ($extTypes as $ext => $mime) {
                    if (!str_contains($targetCtXml, 'Extension="' . $ext . '"')) {
                        $targetCtXml = str_replace('</Types>', '<Default Extension="' . $ext . '" ContentType="' . $mime . '"/></Types>', $targetCtXml);
                    }
                }

                $zipTarget->addFromString('[Content_Types].xml', $targetCtXml);
            }

            // Update word/_rels/document.xml.rels
            $targetRelsXml = $zipTarget->getFromName('word/_rels/document.xml.rels');
            if ($targetRelsXml !== false) {
                // Remove any old header/footer relationships
                $targetRelsXml = preg_replace('/<Relationship\b[^>]*Type="http:\/\/schemas\.openxmlformats\.org\/officeDocument\/2006\/relationships\/(header|footer)"[^>]*\/?>/i', '', $targetRelsXml);

                foreach (array_keys($kopFiles) as $path) {
                    if (preg_match('#^word/(header\d*\.xml)$#i', $path, $hm)) {
                        $hdrFile = $hm[1];
                        $hdrRelId = 'rId_letterhead_' . str_replace('.xml', '', $hdrFile);
                        $hdrRel = '<Relationship Id="' . $hdrRelId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="' . $hdrFile . '"/>';
                        if (!str_contains($targetRelsXml, 'Id="' . $hdrRelId . '"')) {
                            $targetRelsXml = str_replace('</Relationships>', $hdrRel . '</Relationships>', $targetRelsXml);
                        }
                    } elseif (preg_match('#^word/(footer\d*\.xml)$#i', $path, $fm)) {
                        $ftrFile = $fm[1];
                        $ftrRelId = 'rId_letterhead_' . str_replace('.xml', '', $ftrFile);
                        $ftrRel = '<Relationship Id="' . $ftrRelId . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="' . $ftrFile . '"/>';
                        if (!str_contains($targetRelsXml, 'Id="' . $ftrRelId . '"')) {
                            $targetRelsXml = str_replace('</Relationships>', $ftrRel . '</Relationships>', $targetRelsXml);
                        }
                    }
                }

                $zipTarget->addFromString('word/_rels/document.xml.rels', $targetRelsXml);
            }

            // Update word/document.xml sectPr with 100% exact margins and header/footer references
            $targetDocXml = $zipTarget->getFromName('word/document.xml');
            if ($targetDocXml !== false) {
                if (!str_contains($targetDocXml, 'xmlns:r=')) {
                    $targetDocXml = preg_replace('/<w:document\b/', '<w:document xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"', $targetDocXml, 1);
                }

                // Build header/footer references for both default and first pages
                $headerFooterRefs = '';
                if (isset($kopFiles['word/header1.xml'])) {
                    $headerFooterRefs .= '<w:headerReference w:type="default" r:id="rId_letterhead_header1"/>';
                    $headerFooterRefs .= '<w:headerReference w:type="first" r:id="rId_letterhead_header1"/>';
                }
                if (isset($kopFiles['word/footer1.xml'])) {
                    $headerFooterRefs .= '<w:footerReference w:type="default" r:id="rId_letterhead_footer1"/>';
                    $headerFooterRefs .= '<w:footerReference w:type="first" r:id="rId_letterhead_footer1"/>';
                }

                // Extract exact original margins directly from uploaded Kop Soft File ($kopDocXml)
                $extractMarAttr = function (string $attrName, string $str, ?int $default = null): ?int {
                    if (preg_match('/(?:\bw:)?' . preg_quote($attrName, '/') . '="(\d+)"/i', $str, $m)) {
                        return (int) $m[1];
                    }
                    return $default;
                };

                $topMargin = null;
                $bottomMargin = null;
                $leftMargin = null;
                $rightMargin = null;
                $headerMargin = null;
                $footerMargin = null;
                $gutterMargin = null;

                // Priority 1: Exact margins directly from uploaded Kop Soft File
                if (!empty($kopDocXml) && preg_match_all('/<w:pgMar\b([^>]*)/i', $kopDocXml, $allKm)) {
                    $lastKopAttr = end($allKm[1]);
                    $topMargin = $extractMarAttr('top', $lastKopAttr);
                    $bottomMargin = $extractMarAttr('bottom', $lastKopAttr);
                    $leftMargin = $extractMarAttr('left', $lastKopAttr);
                    $rightMargin = $extractMarAttr('right', $lastKopAttr);
                    $headerMargin = $extractMarAttr('header', $lastKopAttr);
                    $footerMargin = $extractMarAttr('footer', $lastKopAttr);
                    $gutterMargin = $extractMarAttr('gutter', $lastKopAttr);
                }

                // Priority 2: Target document existing margins fallback if softfile has no pgMar
                if (preg_match('/<w:pgMar\b([^>]*)/i', $targetDocXml, $tm)) {
                    $tgtAttr = $tm[1];
                    if ($topMargin === null) $topMargin = $extractMarAttr('top', $tgtAttr);
                    if ($bottomMargin === null) $bottomMargin = $extractMarAttr('bottom', $tgtAttr);
                    if ($leftMargin === null) $leftMargin = $extractMarAttr('left', $tgtAttr);
                    if ($rightMargin === null) $rightMargin = $extractMarAttr('right', $tgtAttr);
                    if ($headerMargin === null) $headerMargin = $extractMarAttr('header', $tgtAttr);
                    if ($footerMargin === null) $footerMargin = $extractMarAttr('footer', $tgtAttr);
                    if ($gutterMargin === null) $gutterMargin = $extractMarAttr('gutter', $tgtAttr);
                }

                $topMargin = $topMargin ?? 1440;
                $bottomMargin = $bottomMargin ?? 1440;
                $leftMargin = $leftMargin ?? 1440;
                $rightMargin = $rightMargin ?? 1440;
                $headerMargin = $headerMargin ?? 720;
                $footerMargin = $footerMargin ?? 720;

                // Target paper size: A4 (11906 x 16838 twips) or F4 (11906 x 18709 twips)
                $pageW = 11906;
                $pageH = $isA4 ? 16838 : 18709;

                $gutterAttr = ($gutterMargin !== null) ? ' w:gutter="' . $gutterMargin . '"' : '';
                $pgMarTag = '<w:pgMar w:top="' . $topMargin . '" w:bottom="' . $bottomMargin . '" w:left="' . $leftMargin . '" w:right="' . $rightMargin . '" w:header="' . $headerMargin . '" w:footer="' . $footerMargin . '"' . $gutterAttr . '/>';
                $pgSzTag = '<w:pgSz w:w="' . $pageW . '" w:h="' . $pageH . '"/>';

                if (preg_match('/<w:sectPr\b[^>]*>(.*?)<\/w:sectPr>/s', $targetDocXml, $sectMatch)) {
                    $sectContent = $sectMatch[1];
                    // Strip old header/footer references
                    $sectContent = preg_replace('/<w:(headerReference|footerReference)\b[^>]*\/?>/', '', $sectContent);
                    $sectContent = $headerFooterRefs . $sectContent;

                    // Apply pgMar
                    if (preg_match('/<w:pgMar\b[^>]*\/?>/', $sectContent)) {
                        $sectContent = preg_replace('/<w:pgMar\b[^>]*\/?>/', $pgMarTag, $sectContent);
                    } else {
                        $sectContent .= $pgMarTag;
                    }

                    // Apply target pgSz
                    if (preg_match('/<w:pgSz\b[^>]*\/?>/', $sectContent)) {
                        $sectContent = preg_replace('/<w:pgSz\b[^>]*\/?>/', $pgSzTag, $sectContent);
                    } else {
                        $sectContent .= $pgSzTag;
                    }

                    $targetDocXml = preg_replace('/<w:sectPr\b[^>]*>.*?<\/w:sectPr>/s', '<w:sectPr>' . $sectContent . '</w:sectPr>', $targetDocXml);
                } else {
                    $sectPr = '<w:sectPr>' . $headerFooterRefs . $pgMarTag . $pgSzTag . '</w:sectPr>';
                    $targetDocXml = str_replace('</w:body>', $sectPr . '</w:body>', $targetDocXml);
                }

                $zipTarget->addFromString('word/document.xml', $targetDocXml);
            }

            $zipTarget->close();
            $resultBinary = file_get_contents($tmpTarget);
            @unlink($tmpTarget);

            if (empty($resultBinary) || substr($resultBinary, 0, 2) !== 'PK') {
                return $originalTargetBackup;
            }

            return $isA4 ? $this->enforceA4PageSize($resultBinary) : $this->enforceF4PageSize($resultBinary);
        } catch (\Throwable $e) {
            \Log::error('applyCorporateSoftFileToDocx failed: ' . $e->getMessage(), ['exception' => $e]);
            return $originalTargetBackup;
        }
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

        return $this->createDocxFromImageBytes($imageBytes, $ext, $corporateSoftFile->paper_size ?? 'f4');
    }

    /**
     * Generate an editable DOCX from raw image bytes with smart letterhead layout.
     */
    public function createDocxFromImageBytes(string $imageBytes, string $ext = 'png', string $paperSize = 'f4'): string
    {
        $isA4 = strtolower($paperSize) === 'a4';
        $pageW_Twips = 11906;
        $pageH_Twips = $isA4 ? 16838 : 18709;
        $pageW_Emu = 7560000;
        $pageH_Emu = $isA4 ? 10692000 : 11880000;

        $tempImagePath = storage_path('app/temp_gen_in_' . uniqid() . '.' . $ext);
        file_put_contents($tempImagePath, $imageBytes);

        $im = @imagecreatefromstring($imageBytes);
        if (!$im) {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection([
                'pageSizeW' => $pageW_Twips,
                'pageSizeH' => $pageH_Twips,
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

            return $isA4 ? $this->enforceA4PageSize($res) : $this->enforceF4PageSize($res);
        }

        $w = imagesx($im);
        $h = imagesy($im);
        $ratio = ($h > 0) ? ($w / $h) : 1;

        $headerTempFile = null;
        $footerTempFile = null;
        $headerHeightPx = $h;
        $footerHeightPx = 0;

        if ($ratio >= 1.5) {
            // Landscape / Banner Kop only
            $headerTempFile = $tempImagePath;
            $headerHeightPx = $h;
        } else {
            // Portrait / Full Page scan: detect Top Kop and Bottom Footer (fully dynamic without limits)
            $topEnd = 0;
            $emptyStreak = 0;
            $foundAnyTop = false;

            $scanLimitY = (int)($h * 0.75);
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

            // Detect Footer if any (fully dynamic without artificial height limits)
            $bottomStart = $h;
            $emptyStreak = 0;
            $foundAnyBottom = false;
            $scanBottomLimitY = max(0, $topEnd + 20);

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

            if ($foundAnyTop && $topEnd > 20 && $topEnd < $bottomStart) {
                // Crop Header Kop
                $headerIm = imagecreatetruecolor($w, $topEnd);
                imagecopy($headerIm, $im, 0, 0, 0, 0, $w, $topEnd);
                $headerTempFile = storage_path('app/temp_crop_h_' . uniqid() . '.png');
                imagepng($headerIm, $headerTempFile);
                imagedestroy($headerIm);
                $headerHeightPx = $topEnd;

                // Crop Footer if present (any size)
                if ($foundAnyBottom && ($h - $bottomStart) > 10 && $bottomStart > ($topEnd + 10)) {
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

        $isBanner = ($ratio >= 1.5);
        if ($isBanner) {
            $marginLeft = 1440; // 1 inch (2.54 cm)
            $marginRight = 1134; // ~2.0 cm
            $headerMarginTwips = 450; // ~0.8 cm from top of paper
            $footerMarginTwips = 450;
        } else {
            $leftRatio = $minX / $w;
            $rightRatio = ($w - $maxX) / $w;
            $marginLeft = max(720, min(2500, (int)round(11906 * $leftRatio)));
            $marginRight = max(720, min(2500, (int)round(11906 * $rightRatio)));
            $headerMarginTwips = 450;
            $footerMarginTwips = 450;
        }

        imagedestroy($im);

        $headerH_Emu = (int)round(($headerHeightPx / $h) * $pageH_Emu);
        $headerHTwips = (int)round($headerH_Emu / 635);
        $marginTopTwips = max(1440, $headerHTwips + 300);

        $footerH_Emu = 0;
        $footerHTwips = 0;
        $footerTopOffset_Emu = $pageH_Emu;
        $marginBottomTwips = 1440;

        if ($footerTempFile && $footerHeightPx > 0) {
            $footerH_Emu = (int)round(($footerHeightPx / $h) * $pageH_Emu);
            $footerHTwips = (int)round($footerH_Emu / 635);
            $footerTopOffset_Emu = $pageH_Emu - $footerH_Emu;
            $marginBottomTwips = max(1440, $footerHTwips + 300);
        }

        $marginLeftTwips = 1440; // 2.54 cm standard
        $marginRightTwips = 1134; // 2.0 cm standard

        // Build header1.xml using exact edge-to-edge page-relative anchor
        $headerXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<w:hdr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ' .
            'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" ' .
            'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" ' .
            'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" ' .
            'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">' .
            '<w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr><w:r><w:drawing>' .
            '<wp:anchor distT="0" distB="0" distL="0" distR="0" simplePos="0" relativeHeight="251658240" behindDoc="1" locked="1" layoutInCell="1" allowOverlap="1">' .
            '<wp:simplePos x="0" y="0"/>' .
            '<wp:positionH relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionH>' .
            '<wp:positionV relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionV>' .
            '<wp:extent cx="' . $pageW_Emu . '" cy="' . $headerH_Emu . '"/>' .
            '<wp:effectExtent l="0" t="0" r="0" b="0"/>' .
            '<wp:wrapNone/>' .
            '<wp:docPr id="1" name="Header Image"/>' .
            '<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>' .
            '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">' .
            '<pic:pic><pic:nvPicPr><pic:cNvPr id="1" name="Header Picture"/><pic:cNvPicPr/></pic:nvPicPr>' .
            '<pic:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>' .
            '<pic:spPr><a:xfrm><a:off x="0" y="0"/><a:ext cx="' . $pageW_Emu . '" cy="' . $headerH_Emu . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>' .
            '</pic:pic></a:graphicData></a:graphic>' .
            '</wp:anchor></w:drawing></w:r></w:p></w:hdr>';

        $headerRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
            '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
            '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/header1_image1.png"/>' .
            '</Relationships>';

        $footerXml = null;
        $footerRelsXml = null;
        if ($footerTempFile && $footerH_Emu > 0) {
            $footerXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
                '<w:ftr xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" ' .
                'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" ' .
                'xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" ' .
                'xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main" ' .
                'xmlns:pic="http://schemas.openxmlformats.org/drawingml/2006/picture">' .
                '<w:p><w:pPr><w:spacing w:before="0" w:after="0"/></w:pPr><w:r><w:drawing>' .
                '<wp:anchor distT="0" distB="0" distL="0" distR="0" simplePos="0" relativeHeight="251658240" behindDoc="1" locked="1" layoutInCell="1" allowOverlap="1">' .
                '<wp:simplePos x="0" y="0"/>' .
                '<wp:positionH relativeFrom="page"><wp:posOffset>0</wp:posOffset></wp:positionH>' .
                '<wp:positionV relativeFrom="page"><wp:posOffset>' . $footerTopOffset_Emu . '</wp:posOffset></wp:positionV>' .
                '<wp:extent cx="' . $pageW_Emu . '" cy="' . $footerH_Emu . '"/>' .
                '<wp:effectExtent l="0" t="0" r="0" b="0"/>' .
                '<wp:wrapNone/>' .
                '<wp:docPr id="2" name="Footer Image"/>' .
                '<wp:cNvGraphicFramePr><a:graphicFrameLocks noChangeAspect="1"/></wp:cNvGraphicFramePr>' .
                '<a:graphic><a:graphicData uri="http://schemas.openxmlformats.org/drawingml/2006/picture">' .
                '<pic:pic><pic:nvPicPr><pic:cNvPr id="2" name="Footer Picture"/><pic:cNvPicPr/></pic:nvPicPr>' .
                '<pic:blipFill><a:blip r:embed="rId1"/><a:stretch><a:fillRect/></a:stretch></pic:blipFill>' .
                '<pic:spPr><a:xfrm><a:off x="0" y="' . $footerTopOffset_Emu . '"/><a:ext cx="' . $pageW_Emu . '" cy="' . $footerH_Emu . '"/></a:xfrm><a:prstGeom prst="rect"><a:avLst/></a:prstGeom></pic:spPr>' .
                '</pic:pic></a:graphicData></a:graphic>' .
                '</wp:anchor></w:drawing></w:r></w:p></w:ftr>';

            $footerRelsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . "\n" .
                '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' .
                '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/image" Target="media/footer1_image1.png"/>' .
                '</Relationships>';
        }

        $pw = new \PhpOffice\PhpWord\PhpWord();
        $sec = $pw->addSection([
            'pageSizeW' => $pageW_Twips,
            'pageSizeH' => $pageH_Twips,
            'marginTop' => $marginTopTwips,
            'marginBottom' => $marginBottomTwips,
            'marginLeft' => $marginLeftTwips,
            'marginRight' => $marginRightTwips,
            'headerHeight' => 0,
            'footerHeight' => 0,
        ]);
        $sec->addText('', ['name' => 'Calibri', 'size' => 11]);

        $tempDocx = storage_path('app/temp_gen_out_' . uniqid() . '.docx');
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($pw, 'Word2007');
        $writer->save($tempDocx);

        $zip = new \ZipArchive();
        $zip->open($tempDocx);
        $zip->addFromString('word/header1.xml', $headerXml);
        $zip->addFromString('word/_rels/header1.xml.rels', $headerRelsXml);
        $zip->addFromString('word/media/header1_image1.png', file_get_contents($headerTempFile));

        if ($footerXml && $footerTempFile) {
            $zip->addFromString('word/footer1.xml', $footerXml);
            $zip->addFromString('word/_rels/footer1.xml.rels', $footerRelsXml);
            $zip->addFromString('word/media/footer1_image1.png', file_get_contents($footerTempFile));
        }

        $ct = $zip->getFromName('[Content_Types].xml');
        if (!str_contains($ct, 'header1.xml')) {
            $ct = str_replace('</Types>', '<Override PartName="/word/header1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.header+xml"/></Types>', $ct);
        }
        if ($footerXml && !str_contains($ct, 'footer1.xml')) {
            $ct = str_replace('</Types>', '<Override PartName="/word/footer1.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.footer+xml"/></Types>', $ct);
        }
        if (!str_contains($ct, 'Extension="png"')) {
            $ct = str_replace('</Types>', '<Default Extension="png" ContentType="image/png"/></Types>', $ct);
        }
        $zip->addFromString('[Content_Types].xml', $ct);

        $rels = $zip->getFromName('word/_rels/document.xml.rels');
        $rels = preg_replace('/<Relationship\b[^>]*Type="http:\/\/schemas\.openxmlformats\.org\/officeDocument\/2006\/relationships\/(header|footer)"[^>]*\/?>/i', '', $rels);
        $hdrRel = '<Relationship Id="rIdHeader1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/header" Target="header1.xml"/>';
        $rels = str_replace('</Relationships>', $hdrRel . '</Relationships>', $rels);
        if ($footerXml) {
            $ftrRel = '<Relationship Id="rIdFooter1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/footer" Target="footer1.xml"/>';
            $rels = str_replace('</Relationships>', $ftrRel . '</Relationships>', $rels);
        }
        $zip->addFromString('word/_rels/document.xml.rels', $rels);

        $docXml = $zip->getFromName('word/document.xml');
        $hRefs = '<w:headerReference w:type="default" r:id="rIdHeader1"/><w:headerReference w:type="first" r:id="rIdHeader1"/>';
        if ($footerXml) {
            $hRefs .= '<w:footerReference w:type="default" r:id="rIdFooter1"/><w:footerReference w:type="first" r:id="rIdFooter1"/>';
        }
        $docXml = preg_replace('/<w:(headerReference|footerReference)\b[^>]*\/?>/', '', $docXml);
        $docXml = preg_replace('/<w:sectPr\b[^>]*>/', '<w:sectPr>' . $hRefs, $docXml);
        $zip->addFromString('word/document.xml', $docXml);

        $zip->close();
        $resultBytes = file_get_contents($tempDocx);

        @unlink($tempImagePath);
        if ($headerTempFile && $headerTempFile !== $tempImagePath) @unlink($headerTempFile);
        if ($footerTempFile) @unlink($footerTempFile);
        @unlink($tempDocx);

        return $isA4 ? $this->enforceA4PageSize($resultBytes) : $this->enforceF4PageSize($resultBytes);
    }
}


