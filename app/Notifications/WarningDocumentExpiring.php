<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class WarningDocumentExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Document $document, public int $days)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $timeText = $this->days === 0 ? 'hari ini' : ($this->days === 1 ? 'besok' : "dalam {$this->days} hari");

        return [
            'type' => 'document_expiring_warning',
            'title' => 'Dokumen akan kedaluwarsa ' . $timeText,
            'message' => '"' . $this->document->title . '" akan kedaluwarsa pada ' . $this->document->expires_at?->format('d F Y') . '.',
            'url' => route('documents.show', $this->document),
            'icon' => 'document'
        ];
    }
}
