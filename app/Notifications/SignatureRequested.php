<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent when a user requests someone's signature on a document.
 */
class SignatureRequested extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $requesterName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'     => 'signature_request',
            'title'    => __('Permintaan Tanda Tangan'),
            'message'  => __(':name meminta tanda tangan Anda pada dokumen ":doc"', [
                'name' => $this->requesterName,
                'doc'  => $this->document->title,
            ]),
            'url'      => route('signatures.requests.index'),
            'icon'     => 'signature',
            'document_id' => $this->document->id
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}


