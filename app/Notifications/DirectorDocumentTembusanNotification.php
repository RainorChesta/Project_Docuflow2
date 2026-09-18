<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\DocumentVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to Director when a document is finalized and released without Director's signature box (Only To Know / Tembusan).
 */
class DirectorDocumentTembusanNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public DocumentVersion $version,
        public string $finalApproverName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'director_tembusan',
            'title' => __('Tembusan Dokumen Rilis'),
            'message' => __('Dokumen ":doc" (v:ver) telah disahkan oleh :approver dan rilis untuk operasional.', [
                'doc' => $this->document->title,
                'ver' => $this->version->version_number,
                'approver' => $this->finalApproverName,
            ]),
            'document_id' => $this->document->id,
            'version_id' => $this->version->id,
            'url' => route('documents.show', $this->document),
        ];
    }
}
