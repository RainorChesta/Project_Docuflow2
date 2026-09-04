<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the document owner when a user who was granted access opens that document.
 */
class DocumentOpenedByGrantedUser extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $viewerName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'document_opened',
            'title'       => __('Dokumen Dibuka'),
            'message'     => __(':viewer telah membuka dokumen ":doc" yang Anda bagikan.', [
                'viewer' => $this->viewerName,
                'doc'    => $this->document->title,
            ]),
            'url'         => route('documents.show', $this->document, false),
            'icon'        => 'document',
            'document_id' => $this->document->id,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
