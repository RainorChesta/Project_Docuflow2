<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Sent to the requester when an administrator approves or rejects the deletion request.
 */
class DocumentDeletionResult extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $status, // 'approved' or 'rejected'
        public string $actorName,
        public ?string $notes = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $isApproved = $this->status === 'approved';

        $title = $isApproved
            ? __('Permohonan Penghapusan Disetujui')
            : __('Permohonan Penghapusan Ditolak');

        $message = $isApproved
            ? __('Administrator :actor menyetujui penghapusan dokumen ":title" (:number). Dokumen dipindahkan ke tempat sampah.', [
                'actor'  => $this->actorName,
                'title'  => $this->document->title,
                'number' => $this->document->document_number ?: 'DRAFT',
            ])
            : __('Administrator :actor menolak permohonan penghapusan dokumen ":title" (:number).', [
                'actor'  => $this->actorName,
                'title'  => $this->document->title,
                'number' => $this->document->document_number ?: 'DRAFT',
            ]);

        return [
            'type'            => 'deletion_result',
            'status'          => $this->status,
            'title'           => $title,
            'message'         => $message,
            'url'             => $isApproved ? route('trash.index', [], false) : route('documents.show', $this->document->id, false),
            'icon'            => $isApproved ? 'trash' : 'document',
            'document_id'     => $this->document->id,
            'document_title'  => $this->document->title,
            'document_number' => $this->document->document_number,
            'actor_name'      => $this->actorName,
            'notes'           => $this->notes,
            'reason'          => $this->notes,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}

