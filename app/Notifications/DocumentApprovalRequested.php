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
        $title = $isRename
            ? __('Permintaan Persetujuan Perubahan Nama Dokumen')
            : __('Permintaan Persetujuan Dokumen');

        $message = $isRename && $this->version->old_title
            ? __(':author mengajukan perubahan nama dokumen dari ":old" menjadi ":doc" (v:ver) untuk persetujuan.', [
                'author' => $this->authorName,
                'old'    => $this->version->old_title,
                'doc'    => $this->document->title,
                'ver'    => $this->version->version_number,
            ])
            : __(':author mengajukan dokumen ":doc" (v:ver) untuk persetujuan.', [
                'author' => $this->authorName,
                'doc'    => $this->document->title,
                'ver'    => $this->version->version_number,
            ]);

        return [
            'type'            => 'approval_request',
            'title'           => $title,
            'message'         => $message,
            'is_rename'       => $isRename,
            'old_title'       => $this->version->old_title,
            'url'             => route('documents.show', $this->document->id, false),
            'icon'            => 'approval',
            'document_id'     => $this->document->id,
            'document_title'  => $this->document->title,
            'document_number' => $this->document->document_number,
            'actor_name'      => $this->authorName,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}


