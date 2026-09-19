<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to a user (or unit kerja members) when their access to a document is revoked.
 */
class DocumentAccessRevoked extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $revokedByName,
        public ?string $unitKerjaName = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $message = $this->unitKerjaName
            ? __(':user mencabut akses unit kerja :unit ke dokumen ":doc".', [
                'user' => $this->revokedByName,
                'unit' => $this->unitKerjaName,
                'doc'  => $this->document->title,
            ])
            : __(':user mencabut akses Anda ke dokumen ":doc".', [
                'user' => $this->revokedByName,
                'doc'  => $this->document->title,
            ]);

        return [
            'type'        => 'document_access_revoked',
            'title'       => __('Akses Dokumen Dicabut'),
            'message'     => $message,
            'url'         => route('documents.index', [], false),
            'icon'        => 'rejected',
            'document_id' => $this->document->id,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
