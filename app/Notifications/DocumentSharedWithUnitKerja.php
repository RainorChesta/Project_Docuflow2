<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSharedWithUnitKerja extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $unitKerjaName,
        public string $role,
        public string $sharedByName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $roleLabel = match ($this->role) {
            'editor' => __('Editor (Dapat mengedit)'),
            default  => __('Viewer (Hanya lihat)'),
        };

        return [
            'type'            => 'document_shared',
            'document_id'     => $this->document->id,
            'document_number' => $this->document->document_number,
            'title'           => __('Dokumen Dibagikan ke Unit Kerja :unit', ['unit' => $this->unitKerjaName]),
            'message'         => __(':name membagikan dokumen ":title" (:number) kepada seluruh anggota unit kerja :unit sebagai :role.', [
                'name'   => $this->sharedByName,
                'title'  => $this->document->title,
                'number' => $this->document->document_number,
                'unit'   => $this->unitKerjaName,
                'role'   => $roleLabel,
            ]),
            'url'             => route('documents.show', $this->document),
            'role'            => $this->role,
            'shared_by'       => $this->sharedByName,
            'unit_kerja'      => $this->unitKerjaName,
        ];
    }
}
