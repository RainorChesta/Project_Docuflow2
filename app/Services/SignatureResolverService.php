<?php

namespace App\Services;

use App\Models\Document;
use App\Models\SignatureRequest;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class SignatureResolverService
{
    /**
     * Resolve signature/stamp placeholders [ttd:username], [stamp:username], [stempel:username], or [ttd.me] in document HTML.
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

        // Pattern matches [ttd:username], [ttd:self], [ttd.me], [stamp:username], [stempel:username]
        $pattern = '/\[(ttd|stamp|stempel)(?::|\.)([^\]]+)\]/i';

        return preg_replace_callback($pattern, function ($matches) use ($document, $currentUser, $author, $forPdfExport) {
            $tagType = strtolower(trim($matches[1]));
            $isStampTag = in_array($tagType, ['stamp', 'stempel'], true);
            $rawIdentifier = trim($matches[2]);
            $identifier = strtolower($rawIdentifier);

            // Identify target user
            if (in_array($identifier, ['me', 'self'])) {
                $targetUser = $author ?? $currentUser;
            } else {
                $targetUser = User::where('name', $rawIdentifier)
                    ->orWhere('name', 'LIKE', $identifier)
                    ->orWhere('name', 'LIKE', '%' . $identifier . '%')
                    ->orWhere('email', 'LIKE', $identifier . '%')
                    ->orWhere('id', is_numeric($identifier) ? $identifier : 0)
                    ->first();
            }

            if (!$targetUser) {
                $label = $isStampTag ? 'Stempel' : 'TTD';
                return '<span class="doku-signature-badge inline-block text-xs font-semibold px-2 py-1 bg-gray-200 text-gray-700 rounded border border-gray-300">[' . $label . ': User ' . htmlspecialchars($identifier) . ' tidak ditemukan]</span>';
            }

            $requesterId = $author ? $author->id : ($currentUser ? $currentUser->id : null);
            $requestRecord = null;

            if ($requesterId && $document) {
                $requestRecord = SignatureRequest::where('requester_id', $requesterId)
                    ->where('target_user_id', $targetUser->id)
                    ->where('document_id', $document->id)
                    ->latest('id')
                    ->first();
            }

            // Resolve target signature model
            if ($requestRecord && $requestRecord->isStamp() && $requestRecord->requestedSignature) {
                $targetSignature = $requestRecord->requestedSignature;
            } elseif ($isStampTag) {
                $docCompanyId = $document ? ($document->company_id ?? $document->branch?->company_id) : null;
                $targetSignature = $targetUser->signatures()
                    ->where('type', 'company_stamp')
                    ->when($docCompanyId, fn($q) => $q->where('company_id', $docCompanyId))
                    ->first()
                    ?? $targetUser->signatures()->where('type', 'company_stamp')->first();
            } elseif ($requestRecord && $requestRecord->requestedSignature) {
                $targetSignature = $requestRecord->requestedSignature;
            } else {
                $targetSignature = $targetUser->signatures()->where('type', 'original')->first() 
                    ?? $targetUser->signatures()->first();
            }

            if (!$targetSignature || !file_exists($targetSignature->absolute_path)) {
                $label = ($isStampTag || ($requestRecord && $requestRecord->isStamp())) ? 'Stempel' : 'TTD';
                return '<span class="doku-signature-badge inline-block text-xs font-semibold px-2 py-1 bg-yellow-100 text-yellow-800 rounded border border-yellow-300">[' . $label . ' ' . htmlspecialchars($targetUser->name) . ': Belum Memiliki ' . $label . ']</span>';
            }

            // Case 1: Target user is the requester themselves (Self signature / stamp)
            if ($requesterId && $requesterId === $targetUser->id && (!$requestRecord || $requestRecord->isApproved())) {
                return $this->renderSignatureImage($targetSignature, $targetUser, $forPdfExport);
            }

            // Case 2: Cross-user signature usage (Requester uses someone else's signature)
            if ($requesterId && $document) {
                // If request record does not exist yet, auto-create it as pending
                if (!$requestRecord) {
                    $requestRecord = SignatureRequest::create([
                        'requester_id' => $requesterId,
                        'target_user_id' => $targetUser->id,
                        'requested_signature_id' => $targetSignature->id,
                        'document_id' => $document->id,
                        'status' => 'pending',
                        'requested_at' => now(),
                    ]);
                }

                $itemLabel = $requestRecord->isStamp() ? 'Stempel' : 'TTD';

                if ($requestRecord->isApproved()) {
                    return $this->renderSignatureImage($targetSignature, $targetUser, $forPdfExport);
                } elseif ($requestRecord->isRejected()) {
                    return '<span class="doku-signature-badge inline-block text-xs font-semibold px-2 py-1 bg-red-100 text-red-800 rounded border border-red-300" title="Permintaan penggunaan ' . $itemLabel . ' ditolak oleh ' . htmlspecialchars($targetUser->name) . '">❌ [' . $itemLabel . ' Ditolak: ' . htmlspecialchars($targetUser->name) . ']</span>';
                } else {
                    return '<span class="doku-signature-badge inline-block text-xs font-semibold px-2 py-1 bg-amber-100 text-amber-800 rounded border border-amber-300" title="Menunggu persetujuan ' . $itemLabel . ' dari ' . htmlspecialchars($targetUser->name) . '">⏳ [' . $itemLabel . ' Menunggu Approval: ' . htmlspecialchars($targetUser->name) . ']</span>';
                }
            }

            // Fallback for previews without document instance (e.g. live editor preview)
            return $this->renderSignatureImage($targetSignature, $targetUser, $forPdfExport);

        }, $content);
    }

    /**
     * Render the HTML img element for a valid signature or stamp.
     */
    private function renderSignatureImage($signature, User $user, bool $forPdfExport = false): string
    {
        // Ensure the image URL is absolute and bust cache on updates for web preview pages
        $imgSrc = $forPdfExport ? $signature->base64 : asset('storage/' . $signature->file_path) . '?cb=' . ($signature->updated_at?->timestamp ?? time());
        $altText = $signature->type === 'company_stamp' ? 'Stempel ' . ($signature->company?->name ?? $user->name) : 'TTD ' . $user->name;

        return sprintf(
            '<img src="%s" alt="%s" class="doku-signature-img inline-block" style="max-height: 80px; width: auto; vertical-align: middle; object-fit: contain; margin: 4px;" data-ttd-user="%s" />',
            $imgSrc,
            htmlspecialchars($altText),
            htmlspecialchars($user->name)
        );
    }
}
