<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to Division Head when a document rename is requested.
 */
class DocumentRenameRequested extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $requestedTitle,
        public string $requesterName,
        public ?string $notes = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'rename_request',
            'title'           => __('Permintaan Ubah Nama Dokumen'),
            'message'         => __(':requester mengajukan perubahan nama dokumen ":old" menjadi ":new".', [
                'requester' => $this->requesterName,
                'old'       => $this->document->title,
                'new'       => $this->requestedTitle,
            ]),
            'url'             => route('documents.show', $this->document->id, false),
            'icon'            => 'approval',
            'document_id'     => $this->document->id,
            'requested_title' => $this->requestedTitle,
            'notes'           => $this->notes,
            'reason'          => $this->notes,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
