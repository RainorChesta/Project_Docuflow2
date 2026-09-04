<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the document author when their version is approved or rejected.
 */
class DocumentApprovalResult extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public DocumentVersion $version,
        public string $status, // 'approved' | 'rejected'
        public string $reviewerName,
        public ?string $notes = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $isApproved = $this->status === 'approved';
        $title = $isApproved ? __('Dokumen Disetujui') : __('Dokumen Ditolak');
        $message = $isApproved
            ? __('Dokumen ":doc" (v:ver) Anda telah disetujui oleh :reviewer.', [
                'doc'      => $this->document->title,
                'ver'      => $this->version->version_number,
                'reviewer' => $this->reviewerName,
            ])
            : __('Dokumen ":doc" (v:ver) Anda ditolak oleh :reviewer.', [
                'doc'      => $this->document->title,
                'ver'      => $this->version->version_number,
                'reviewer' => $this->reviewerName,
            ]);

        return [
            'type'        => 'approval_result',
            'title'       => $title,
            'message'     => $message,
            'url'         => route('documents.show', $this->document, false),
            'icon'        => $isApproved ? 'approval' : 'rejected',
            'document_id' => $this->document->id,
            'notes'       => $this->notes,
            'reason'      => $this->notes,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}


