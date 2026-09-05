<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\SignatureRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent when a target user rejects a signature request so the requester knows.
 */
class SignatureRequestRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SignatureRequest $signatureRequest,
        public Document $document,
        public string $rejecterName,
        public ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $isStamp = $this->signatureRequest->isStamp();
        $title = $isStamp ? __('STEMPEL PERUSAHAAN DITOLAK') : __('TANDA TANGAN DITOLAK');
        $itemLabel = $isStamp ? __('STEMPEL PERUSAHAAN') : __('TANDA TANGAN');

        return [
            'type'                 => $isStamp ? 'stamp_request_rejected' : 'signature_request_rejected',
            'title'                => $title,
            'message'              => __(':name TELAH MENOLAK PERMINTAAN :item PADA DOKUMEN ":doc".', [
                'name' => strtoupper($this->rejecterName),
                'item' => $itemLabel,
                'doc'  => strtoupper($this->document->title),
            ]),
            'url'                  => route('documents.edit', $this->document, false),
            'icon'                 => 'rejected',
            'document_id'          => $this->document->id,
            'signature_request_id' => $this->signatureRequest->id,
            'request_type'         => $isStamp ? 'stamp' : 'signature',
            'reason'               => $this->reason,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
