<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to Division Head when a document version is submitted for approval.
 */
class DocumentApprovalRequested extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public DocumentVersion $version,
        public string $authorName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $isRename = $this->version->isRename();
        $sigReq = \App\Models\SignatureRequest::with('requestedSignature.company')
            ->where('document_id', $this->document->id)
            ->where('target_user_id', $notifiable->id)
            ->where('status', 'pending')
            ->first();
        $hasSignature = !is_null($sigReq);
        $isStamp = $sigReq?->isStamp() ?? false;
        $companyName = $sigReq?->requestedSignature?->company?->name;

        if ($isRename) {
            $title = __('Permintaan Persetujuan Perubahan Nama Dokumen');
            $message = $this->version->old_title
                ? __(':author mengajukan perubahan nama dokumen dari ":old" menjadi ":doc" (v:ver) untuk persetujuan.', [
                    'author' => $this->authorName,
                    'old'    => $this->version->old_title,
                    'doc'    => $this->document->title,
                    'ver'    => $this->version->version_number,
                ])
                : __(':author mengajukan perubahan nama dokumen ":doc" (v:ver) untuk persetujuan.', [
                    'author' => $this->authorName,
                    'doc'    => $this->document->title,
                    'ver'    => $this->version->version_number,
                ]);
        } elseif ($isStamp) {
            $title = __('Permintaan Persetujuan & Stempel Perusahaan');
            $message = $companyName
                ? __(':author mengajukan dokumen ":doc" (v:ver) untuk persetujuan dan stempel perusahaan (:company) Anda.', [
                    'author'  => $this->authorName,
                    'company' => $companyName,
                    'doc'     => $this->document->title,
                    'ver'     => $this->version->version_number,
                ])
                : __(':author mengajukan dokumen ":doc" (v:ver) untuk persetujuan dan stempel perusahaan Anda.', [
                    'author' => $this->authorName,
                    'doc'    => $this->document->title,
                    'ver'    => $this->version->version_number,
                ]);
        } elseif ($hasSignature) {
            $title = __('Permintaan Persetujuan & Tanda Tangan');
            $message = __(':author mengajukan dokumen ":doc" (v:ver) untuk persetujuan dan tanda tangan Anda.', [
                'author' => $this->authorName,
                'doc'    => $this->document->title,
                'ver'    => $this->version->version_number,
            ]);
        } else {
            $title = __('Permintaan Persetujuan Dokumen');
            $message = __(':author mengajukan dokumen ":doc" (v:ver) untuk review dan persetujuan Anda.', [
                'author' => $this->authorName,
                'doc'    => $this->document->title,
                'ver'    => $this->version->version_number,
            ]);
        }

        return [
            'type'                 => 'approval_request',
            'title'                => $title,
            'message'              => $message,
            'is_rename'            => $isRename,
            'has_signature'        => $hasSignature,
            'is_stamp'             => $isStamp,
            'request_type'         => $isStamp ? 'stamp' : ($hasSignature ? 'signature' : 'approval'),
            'signature_request_id' => $sigReq?->id,
            'company_name'         => $companyName,
            'old_title'            => $this->version->old_title,
            'url'                  => route('documents.show', $this->document->id, false),
            'icon'                 => $isStamp ? 'stamp' : ($hasSignature ? 'signature' : 'approval'),
            'document_id'          => $this->document->id,
            'version_id'           => $this->version->id,
            'version_number'       => $this->version->version_number,
            'document_title'       => $this->document->title,
            'document_number'      => $this->document->document_number,
            'actor_name'           => $this->authorName,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}


