<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent to the document requester (author) to inform them who will
 * review their document, including fallback context if the primary
 * Head approver is unavailable.
 */
class ApprovalRouteResolved extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $approverRole,    // head, admin, direktur
        public string $approverNames,   // comma-separated names
        public string $routingMessage,  // human-readable explanation
        public bool $isFallback = false,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $title = $this->isFallback
            ? __('Approver Dokumen (Fallback)')
            : __('Approver Dokumen');

        return [
            'type'           => 'approval_route_resolved',
            'title'          => $title,
            'message'        => $this->routingMessage,
            'url'            => route('documents.show', $this->document, false),
            'icon'           => 'approval',
            'document_id'    => $this->document->id,
            'approver_role'  => $this->approverRole,
            'approver_names' => $this->approverNames,
            'is_fallback'    => $this->isFallback,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
