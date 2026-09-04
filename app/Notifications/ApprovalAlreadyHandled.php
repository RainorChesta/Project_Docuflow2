<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to other Admin/Direktur approvers when a peer has already
 * approved or rejected the document, so they know no further action
 * is needed on their part.
 */
class ApprovalAlreadyHandled extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $handlerName,   // name of the approver who acted
        public string $action,        // 'approved' | 'rejected'
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $actionLabel = $this->action === 'approved' ? __('disetujui') : __('ditolak');

        return [
            'type'        => 'approval_already_handled',
            'title'       => __('Approval Sudah Ditangani'),
            'message'     => __('Dokumen ":doc" sudah :action oleh :handler. Tidak perlu tindakan lagi.', [
                'doc'     => $this->document->title,
                'action'  => $actionLabel,
                'handler' => $this->handlerName,
            ]),
            'url'         => route('documents.show', $this->document, false),
            'icon'        => 'info',
            'document_id' => $this->document->id,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
