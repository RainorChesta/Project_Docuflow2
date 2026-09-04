<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the document owner when another user opens their document via a shared link.
 */
class DocumentOpenedViaLink extends Notification
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
            'type'    => 'document_opened',
            'title'   => __('Dokumen Dibuka via Link'),
            'message' => __(':viewer telah membuka dokumen ":doc" melalui link yang dibagikan.', [
                'viewer' => $this->viewerName,
                'doc'    => $this->document->title,
            ]),
            'url'     => route('documents.show', $this->document, false),
            'icon'    => 'document',
            'document_id' => $this->document->id
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}


