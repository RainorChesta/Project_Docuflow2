<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CrossBranchDocumentReceived extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $targetBranchName,
        public string $senderName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'        => 'document_cross_branch_received',
            'title'       => __('New Cross-Branch Document'),
            'message'     => __(':user from :origin sent a document to :target.', [
                'user'   => $this->senderName,
                'origin' => $this->document->branch?->name ?? 'Unknown',
                'target' => $this->targetBranchName,
            ]),
            'url'         => route('documents.show', $this->document, false),
            'icon'        => 'document-text',
            'document_id' => $this->document->id,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
