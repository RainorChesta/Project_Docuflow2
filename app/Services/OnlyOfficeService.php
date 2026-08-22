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
        $cacheKey = 'onlyoffice_doc_key_' . $document->id;
        
        return \Illuminate\Support\Facades\Cache::remember($cacheKey, now()->addDays(1), function () use ($document, $version) {
            $timeKey = uniqid() . '_' . microtime(true);
            
            $raw = sprintf(
                'doc_%d_v%d_%s',
                $document->id,
                $version->version_number,
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
                    'autosave' => (bool) config('onlyoffice.autosave', true),
                    'forcesave' => (bool) config('onlyoffice.forcesave', true),
                    'chat' => false,
                    'comments' => false,
                    'compactHeader' => false,
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
