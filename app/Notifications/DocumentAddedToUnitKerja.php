<?php

namespace App\Notifications;

use App\Models\Document;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DocumentAddedToUnitKerja extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Document $document,
        public string $unitKerjaName,
        public string $addedByName
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type'            => 'document_added_to_unit_kerja',
            'document_id'     => $this->document->id,
            'document_number' => $this->document->document_number,
            'title'           => __('Dokumen Baru di Unit Kerja :unit', ['unit' => $this->unitKerjaName]),
            'message'         => __(':name menambahkan dokumen baru ":title" (:number) ke unit kerja :unit.', [
                'name'   => $this->addedByName,
                'title'  => $this->document->title,
                'number' => $this->document->document_number,
                'unit'   => $this->unitKerjaName,
            ]),
            'url'             => route('documents.show', $this->document),
            'added_by'        => $this->addedByName,
            'unit_kerja'      => $this->unitKerjaName,
        ];
    }
}
