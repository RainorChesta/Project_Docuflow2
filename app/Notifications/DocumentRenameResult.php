<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the rename requester when their document rename request is approved or rejected.
 */
class DocumentRenameResult extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $oldTitle,
        public string $newTitle,
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
        $title = $isApproved ? __('Perubahan Nama Disetujui') : __('Perubahan Nama Ditolak');
        $message = $isApproved
            ? __('Permintaan perubahan nama dokumen ":old" menjadi ":new" telah disetujui oleh :reviewer.', [
                'old'      => $this->oldTitle,
                'new'      => $this->newTitle,
                'reviewer' => $this->reviewerName,
            ])
            : __('Permintaan perubahan nama dokumen ":old" ditolak oleh :reviewer.', [
                'old'      => $this->oldTitle,
                'reviewer' => $this->reviewerName,
            ]);

        return [
            'type'        => $isApproved ? 'rename_approved' : 'rename_rejected',
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
