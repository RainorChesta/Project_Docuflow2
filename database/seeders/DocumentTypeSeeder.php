<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['code' => 'S.ED', 'name' => 'Surat Edaran'],
            ['code' => 'IM', 'name' => 'Internal Memo'],
            ['code' => 'ST', 'name' => 'Surat Tugas'],
            ['code' => 'I/S.KEL', 'name' => 'Surat Korespondensi Internal'],
            ['code' => 'E/S.KEL', 'name' => 'Surat Korespondensi Eksternal'],
            ['code' => 'PKWT', 'name' => 'Surat Perjanjian Internal'],
            ['code' => 'PKS', 'name' => 'Surat Perjanjian Eksternal'],
            ['code' => 'SP', 'name' => 'Surat Peringatan'],
            ['code' => 'S.KU', 'name' => 'Surat Kuasa'],
            ['code' => 'BA', 'name' => 'Berita Acara'],
            ['code' => 'S.KET', 'name' => 'Surat Keterangan'],
            ['code' => 'S.UND', 'name' => 'Surat Undangan'],
            ['code' => 'SPPD', 'name' => 'Surat Perintah Perjalanan Dinas'],
            ['code' => 'S.PENG', 'name' => 'Surat Pengantar'],
            ['code' => 'SB', 'name' => 'Surat Balasan'],
            ['code' => 'S.PMH', 'name' => 'Surat Permohonan'],
            ['code' => 'SR', 'name' => 'Surat Rekomendasi'],
            ['code' => 'S.PB', 'name' => 'Surat Pemberitahuan'],
            ['code' => 'S.PRT', 'name' => 'Surat Perintah'],
            ['code' => 'S.PWN', 'name' => 'Surat Penawaran'],
            ['code' => 'SK', 'name' => 'Surat Keterangan'],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(['code' => $type['code']], $type);
        }
    }
}
