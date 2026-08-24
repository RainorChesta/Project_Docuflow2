<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent when a new document is added to a division.
 */
class DocumentAddedToDivision extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $divisionName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'document_added',
            'title'   => __('Dokumen Baru di Divisi'),
            'message' => __('Dokumen baru ":doc" telah ditambahkan ke divisi :division', [
                'doc'      => $this->document->title,
                'division' => $this->divisionName,
            ]),
            'url'     => route('documents.show', $this->document),
            'icon'    => 'document',
            'document_id' => $this->document->id
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}


