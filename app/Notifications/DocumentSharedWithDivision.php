<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to members of a division when a document is shared with their division.
 */
class DocumentSharedWithDivision extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $divisionName,
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
            'type'        => 'document_shared',
            'title'       => __('Akses Dokumen untuk Divisi'),
            'message'     => __(':user membagikan dokumen ":doc" kepada divisi :division sebagai :role.', [
                'user'     => $this->sharedByName,
                'doc'      => $this->document->title,
                'division' => $this->divisionName,
                'role'     => $this->role,
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
