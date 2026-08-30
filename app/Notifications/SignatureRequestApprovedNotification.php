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
        return [
            'type'                 => 'signature_request_approved',
            'title'                => __('TANDA TANGAN DISETUJUI'),
            'message'              => __(':name TELAH MENYETUJUI PERMINTAAN TANDA TANGAN PADA DOKUMEN ":doc". SILAKAN BUKA EDITOR DAN LAKUKAN "REPLACE SIGNATURE" UNTUK MENAMPILKANNYA.', [
                'name' => strtoupper($this->approverName),
                'doc'  => strtoupper($this->document->title),
            ]),
            'url'                  => route('documents.edit', $this->document),
            'icon'                 => 'signature',
            'document_id'          => $this->document->id,
            'signature_request_id' => $this->signatureRequest->id,
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}
