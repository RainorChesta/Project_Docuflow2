<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to a user (or division members) when their access to a document is revoked.
 */
class DocumentAccessRevoked extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $revokedByName,
        public ?string $divisionName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $message = $this->divisionName
            ? __(':user mencabut akses divisi :division ke dokumen ":doc".', [
                'user'     => $this->revokedByName,
                'division' => $this->divisionName,
                'doc'      => $this->document->title,
            ])
            : __(':user mencabut akses Anda ke dokumen ":doc".', [
                'user' => $this->revokedByName,
                'doc'  => $this->document->title,
            ]);

        return [
            'type'        => 'document_access_revoked',
            'title'       => __('Akses Dokumen Dicabut'),
            'message'     => $message,
            'url'         => route('documents.index'),
            'icon'        => 'rejected',
            'document_id' => $this->document->id,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
