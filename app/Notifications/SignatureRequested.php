<?php

namespace App\Notifications;

use App\Models\Document;
use App\Models\SignatureRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Sent when a user requests someone's signature or stamp on a document.
 */
class SignatureRequested extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $requesterName,
        public ?SignatureRequest $signatureRequest = null,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        $isStamp = $this->signatureRequest?->isStamp() ?? false;
        $companyName = $this->signatureRequest?->requestedSignature?->company?->name;

        if ($isStamp) {
            $title = __('Permintaan Stempel Perusahaan');
            $message = $companyName
                ? __(':name meminta stempel perusahaan (:company) Anda pada dokumen ":doc"', [
                    'name' => $this->requesterName,
                    'company' => $companyName,
                    'doc' => $this->document->title,
                ])
                : __(':name meminta stempel perusahaan Anda pada dokumen ":doc"', [
                    'name' => $this->requesterName,
                    'doc' => $this->document->title,
                ]);
        } else {
            $title = __('Permintaan Tanda Tangan');
            $message = __(':name meminta tanda tangan Anda pada dokumen ":doc"', [
                'name' => $this->requesterName,
                'doc'  => $this->document->title,
            ]);
        }

        return [
            'type'         => $isStamp ? 'stamp_request' : 'signature_request',
            'title'        => $title,
            'message'      => $message,
            'url'          => route('signatures.requests.index', [], false),
            'icon'         => $isStamp ? 'stamp' : 'signature',
            'document_id'  => $this->document->id,
            'signature_request_id' => $this->signatureRequest?->id,
            'request_type' => $isStamp ? 'stamp' : 'signature',
        ];
    }

    public function toBroadcast(object $notifiable): array
    {
        return $this->toArray($notifiable);
    }
}


