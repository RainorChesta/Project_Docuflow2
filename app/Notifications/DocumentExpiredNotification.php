<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'document_expired',
            'document_id'     => $this->document->id,
            'document_title'  => $this->document->title,
            'document_number' => $this->document->document_number,
            'title'           => __('Dokumen Telah Kadaluwarsa'),
            'message'         => __('Masa berlaku dokumen Anda ":title" (:number) telah berakhir pada :date. Harap segera periksa dan buat versi revisi jika diperlukan.', [
                'title'  => $this->document->title,
                'number' => $this->document->document_number ?? '-',
                'date'   => $this->document->expiration_date?->translatedFormat('d F Y') ?? '-',
            ]),
            'url'             => route('documents.show', $this->document),
            'action_text'     => __('Lihat Dokumen'),
        ];
    }
}
