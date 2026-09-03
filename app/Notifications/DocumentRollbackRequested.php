<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to Division Head when a rollback is requested.
 */
class DocumentRollbackRequested extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public DocumentVersion $targetVersion,
        public string $requesterName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'    => 'rollback_request',
            'title'   => __('Permintaan Rollback Dokumen'),
            'message' => __(':requester mengajukan rollback dokumen ":doc" ke versi v:ver.', [
                'requester' => $this->requesterName,
                'doc'       => $this->document->title,
                'ver'       => $this->targetVersion->version_number,
            ]),
            'url'     => route('documents.show', $this->document->id),
            'icon'    => 'approval',
            'document_id' => $this->document->id
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}


