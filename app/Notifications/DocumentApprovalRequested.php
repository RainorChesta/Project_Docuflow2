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
        return [
            'type'    => 'approval_request',
            'title'   => __('Permintaan Persetujuan Dokumen'),
            'message' => __(':author mengajukan dokumen ":doc" (v:ver) untuk persetujuan.', [
                'author' => $this->authorName,
                'doc'    => $this->document->title,
                'ver'    => $this->version->version_number,
            ]),
            'url'     => route('approvals.index'),
            'icon'    => 'approval',
            'document_id' => $this->document->id
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}


