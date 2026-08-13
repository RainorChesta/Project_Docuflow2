<?php

namespace App\Services;

use App\Models\Document;
use App\Models\SignatureRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SignatureResolverService
{
    /**
     * Resolve signature placeholders [ttd:username] or [ttd.me] in document HTML.
     *
     * @param string $content HTML content of document
     * @param Document|null $document Active document context (if available)
     * @param User|null $currentUser User viewing/rendering the document
     * @param bool $forPdfExport Whether rendering for PDF export (uses base64/absolute path for images)
     * @return string Processed HTML content
     */
    public function resolve(string $content, ?Document $document = null, ?User $currentUser = null, bool $forPdfExport = false): string
    {
        if (!trim($content)) {
            return $content;
        }

        $currentUser = $currentUser ?? auth()->user();
        $author = $document ? $document->owner : $currentUser;

        // Pattern matches [ttd:username], [ttd:self], [ttd.me]
        $pattern = '/\[ttd(?::|\.)([a-zA-Z0-9_\-\.\@]+)\]/i';

        return preg_replace_callback($pattern, function ($matches) use ($document, $currentUser, $author, $forPdfExport) {
            $identifier = strtolower(trim($matches[1]));

            // Identify target user
            if (in_array($identifier, ['me', 'self'])) {
                $targetUser = $author ?? $currentUser;
            } else {
                $targetUser = User::where('name', 'LIKE', $identifier)
                    ->orWhere('email', 'LIKE', $identifier . '%')
                    ->orWhere('id', is_numeric($identifier) ? $identifier : 0)
                    ->first();
            }

            if (!$targetUser) {
                return '<span class="doku-signature-badge inline-block text-xs font-semibold px-2 py-1 bg-gray-200 text-gray-700 rounded border border-gray-300">[TTD: User ' . htmlspecialchars($identifier) . ' tidak ditemukan]</span>';
            }

            $targetSignature = $targetUser->signature;

            if (!$targetSignature || !file_exists($targetSignature->absolute_path)) {
                return '<span class="doku-signature-badge inline-block text-xs font-semibold px-2 py-1 bg-yellow-100 text-yellow-800 rounded border border-yellow-300">[TTD ' . htmlspecialchars($targetUser->name) . ': Belum Memiliki TTD]</span>';
            }

            $requesterId = $author ? $author->id : ($currentUser ? $currentUser->id : null);

            // Case 1: Target user is the requester themselves (Self signature)
            if ($requesterId && $requesterId === $targetUser->id) {
                return $this->renderSignatureImage($targetSignature, $targetUser, $forPdfExport);
            }

            // Case 2: Cross-user signature usage (Requester uses someone else's signature)
            if ($requesterId && $document) {
                $requestRecord = SignatureRequest::where('requester_id', $requesterId)
                    ->where('target_user_id', $targetUser->id)
                    ->where('document_id', $document->id)
                    ->first();

                // If request record does not exist yet, auto-create it as pending
                if (!$requestRecord) {
                    $requestRecord = SignatureRequest::create([
                        'requester_id' => $requesterId,
                        'target_user_id' => $targetUser->id,
                        'document_id' => $document->id,
                        'status' => 'pending',
                        'requested_at' => now(),
                    ]);
                }

                if ($requestRecord->isApproved()) {
                    return $this->renderSignatureImage($targetSignature, $targetUser, $forPdfExport);
                } elseif ($requestRecord->isRejected()) {
                    return '<span class="doku-signature-badge inline-block text-xs font-semibold px-2 py-1 bg-red-100 text-red-800 rounded border border-red-300" title="Permintaan penggunaan TTD ditolak oleh ' . htmlspecialchars($targetUser->name) . '">❌ [TTD Ditolak: ' . htmlspecialchars($targetUser->name) . ']</span>';
                } else {
                    return '<span class="doku-signature-badge inline-block text-xs font-semibold px-2 py-1 bg-amber-100 text-amber-800 rounded border border-amber-300" title="Menunggu persetujuan TTD dari ' . htmlspecialchars($targetUser->name) . '">⏳ [TTD Menunggu Approval: ' . htmlspecialchars($targetUser->name) . ']</span>';
                }
            }

            // Fallback for previews without document instance (e.g. live editor preview)
            return $this->renderSignatureImage($targetSignature, $targetUser, $forPdfExport);

        }, $content);
    }

    /**
     * Render the HTML img element for a valid signature.
     */
    private function renderSignatureImage($signature, User $user, bool $forPdfExport = false): string
    {
        // Ensure the image URL is absolute and bust cache on updates for web preview pages
        $imgSrc = $forPdfExport ? $signature->base64 : asset('storage/' . $signature->file_path) . '?cb=' . $signature->updated_at->timestamp;

        return sprintf(
            '<img src="%s" alt="TTD %s" class="doku-signature-img inline-block" style="max-height: 80px; width: auto; vertical-align: middle; object-fit: contain; margin: 4px;" data-ttd-user="%s" />',
            $imgSrc,
            htmlspecialchars($user->name),
            htmlspecialchars($user->name)
        );
    }
}
