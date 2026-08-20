<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to a user when a document is shared with them.
 */
class DocumentSharedWithUser extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $role,
        public string $sharedByName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'document_shared',
            'title'   => __('Dokumen Dibagikan'),
            'message' => __(':user membagikan dokumen ":doc" kepada Anda sebagai :role.', [
                'user' => $this->sharedByName,
                'doc'  => $this->document->title,
                'role' => $this->role,
            ]),
            'url'     => route('documents.show', $this->document),
            'icon'    => 'document',
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
