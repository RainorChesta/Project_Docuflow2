<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the rollback requester when their rollback request is approved or rejected.
 */
class DocumentRollbackResult extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public ?DocumentVersion $targetVersion,
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
        $verNum = $this->targetVersion?->version_number ?? '?';
        $title = $isApproved ? __('Rollback Disetujui') : __('Rollback Ditolak');
        $message = $isApproved
            ? __('Permintaan rollback dokumen ":doc" (v:ver) telah disetujui oleh :reviewer.', [
                'doc'      => $this->document->title,
                'ver'      => $verNum,
                'reviewer' => $this->reviewerName,
            ])
            : __('Permintaan rollback dokumen ":doc" ditolak oleh :reviewer.', [
                'doc'      => $this->document->title,
                'reviewer' => $this->reviewerName,
            ]);

        return [
            'type'        => $isApproved ? 'rollback_approved' : 'rollback_rejected',
            'title'       => $title,
            'message'     => $message,
            'url'         => route('documents.show', $this->document),
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
