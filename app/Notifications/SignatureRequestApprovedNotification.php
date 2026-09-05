<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\SignatureRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent when a target user approves a signature request so the requester knows to replace it in the editor.
 */
class SignatureRequestApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public SignatureRequest $signatureRequest,
        public Document $document,
        public string $approverName,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $isStamp = $this->signatureRequest->isStamp();
        $title = $isStamp ? __('STEMPEL PERUSAHAAN DISETUJUI') : __('TANDA TANGAN DISETUJUI');
        $itemLabel = $isStamp ? __('STEMPEL PERUSAHAAN') : __('TANDA TANGAN');

        return [
            'type'                 => $isStamp ? 'stamp_request_approved' : 'signature_request_approved',
            'title'                => $title,
            'message'              => __(':name TELAH MENYETUJUI PERMINTAAN :item PADA DOKUMEN ":doc".', [
                'name' => strtoupper($this->approverName),
                'item' => $itemLabel,
                'doc'  => strtoupper($this->document->title),
            ]),
            'url'                  => route('documents.edit', $this->document, false),
            'icon'                 => $isStamp ? 'stamp' : 'signature',
            'document_id'          => $this->document->id,
            'signature_request_id' => $this->signatureRequest->id,
            'request_type'         => $isStamp ? 'stamp' : 'signature',
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
