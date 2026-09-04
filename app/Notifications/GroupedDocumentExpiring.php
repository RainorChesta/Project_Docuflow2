<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class GroupedDocumentExpiring extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $in30Days, public int $in7Days)
    {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $total = $this->in30Days + $this->in7Days;
        
        $msgParts = [];
        if ($this->in7Days > 0) $msgParts[] = "{$this->in7Days} dokumen akan kedaluwarsa dalam 7 hari";
        if ($this->in30Days > 0) $msgParts[] = "{$this->in30Days} dokumen dalam 30 hari";
        
        $message = implode(' dan ', $msgParts) . '.';

        return [
            'type' => 'document_expiring_grouped',
            'title' => "{$total} Dokumen Memerlukan Perhatian",
            'message' => $message,
            'url' => route('dashboard', [], false), // The dashboard has the modal
            'icon' => 'document'
        ];
    }
}

