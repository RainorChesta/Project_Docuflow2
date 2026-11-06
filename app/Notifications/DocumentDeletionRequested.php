<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Sent to Administrators when an active document owner requests deletion.
 */
class DocumentDeletionRequested extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Document $document,
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
            'type'            => 'deletion_request',
            'title'           => __('Permintaan Penghapusan Dokumen Aktif'),
            'message'         => __(':requester mengajukan permohonan penghapusan dokumen aktif ":title" (:number).', [
                'requester' => $this->requesterName,
                'title'     => $this->document->title,
                'number'    => $this->document->document_number ?: 'DRAFT',
            ]),
            'url'             => route('documents.show', $this->document->id, false),
            'icon'            => 'trash',
            'document_id'     => $this->document->id,
            'document_title'  => $this->document->title,
            'document_number' => $this->document->document_number,
            'actor_name'      => $this->requesterName,
            'notes'           => $this->notes,
            'reason'          => $this->notes,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}

