<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            // 1. Naskah Dinas (Official Correspondence)
            ['code' => 'S.ED',    'name' => 'Surat Edaran',                  'category' => 'naskah_dinas'],
            ['code' => 'ST',      'name' => 'Surat Tugas',                   'category' => 'naskah_dinas'],
            ['code' => 'IM',      'name' => 'Internal Memo',                 'category' => 'naskah_dinas'],
            ['code' => 'I/S.KEL', 'name' => 'Surat Korespondensi Internal',  'category' => 'naskah_dinas'],
            ['code' => 'E/S.KEL', 'name' => 'Surat Korespondensi Eksternal',  'category' => 'naskah_dinas'],
            ['code' => 'PKWT',    'name' => 'Surat Perjanjian Internal',     'category' => 'naskah_dinas'],
            ['code' => 'PKS',     'name' => 'Surat Perjanjian Eksternal',    'category' => 'naskah_dinas'],
            ['code' => 'SP',      'name' => 'Surat Peringatan',              'category' => 'naskah_dinas'],
            ['code' => 'S.KU',    'name' => 'Surat Kuasa',                   'category' => 'naskah_dinas'],
            ['code' => 'BA',      'name' => 'Berita Acara',                  'category' => 'naskah_dinas'],
            ['code' => 'S.KET',   'name' => 'Surat Keterangan',              'category' => 'naskah_dinas'],
            ['code' => 'S.UND',   'name' => 'Surat Undangan',                'category' => 'naskah_dinas'],

            // 2. Dokumen Akreditasi (Accreditation Documents)
            ['code' => 'SK',      'name' => 'Keputusan / Kebijakan',         'category' => 'akreditasi'],
            ['code' => 'PD',      'name' => 'Pedoman',                       'category' => 'akreditasi'],
            ['code' => 'PAN',     'name' => 'Panduan',                       'category' => 'akreditasi'],
            ['code' => 'MM',      'name' => 'Manual Mutu',                   'category' => 'akreditasi'],
            ['code' => 'SOP',     'name' => 'Standar Operasional Prosedur',  'category' => 'akreditasi'],
            ['code' => 'REN',     'name' => 'Rencana Lima Tahunan',          'category' => 'akreditasi'],
            ['code' => 'RET',     'name' => 'Rencana Tahunan',               'category' => 'akreditasi'],
            ['code' => 'KAK',     'name' => 'Kerangka Acuan Kegiatan',       'category' => 'akreditasi'],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
