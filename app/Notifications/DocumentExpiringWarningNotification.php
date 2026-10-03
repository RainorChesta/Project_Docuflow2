<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentExpiringWarningNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Document $document,
        public int $daysRemaining,
        public string $warningType = '30days' // 30days, 7days, 1day
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $urgency = match($this->warningType) {
            '1day' => $this->daysRemaining <= 0 ? __('hari ini') : __('besok'),
            '7days' => __(':days hari', ['days' => $this->daysRemaining]),
            default => __(':days hari', ['days' => $this->daysRemaining]),
        };

        return [
            'type'            => 'document_expiring_soon',
            'document_id'     => $this->document->id,
            'document_title'  => $this->document->title,
            'document_number' => $this->document->document_number,
            'title'           => __('Masa Berlaku Dokumen Segera Berakhir'),
            'message'         => __('Dokumen Anda ":title" (:number) akan kadaluwarsa dalam :urgency (pada :date). Segera lakukan peninjauan atau revisi.', [
                'title'   => $this->document->title,
                'number'  => $this->document->document_number ?? '-',
                'urgency' => $urgency,
                'date'    => $this->document->expiration_date?->translatedFormat('d F Y') ?? '-',
            ]),
            'url'             => route('documents.show', $this->document),
            'action_text'     => __('Lihat Dokumen'),
            'days_remaining'  => $this->daysRemaining,
            'warning_type'    => $this->warningType,
        ];
    }
}
