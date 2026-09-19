<?php

namespace Database\Seeders;

use App\Models\UnitKerja;
use Illuminate\Database\Seeder;

class InitialMasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 22 Master Unit Kerja Global (digunakan di seluruh cabang & pusat)
        $unitKerjas = [
            ['kode_unit_kerja' => '01', 'nama_unit_kerja' => 'Tim Manajemen Mutu'],
            ['kode_unit_kerja' => '02', 'nama_unit_kerja' => 'Tim Audit Internal'],
            ['kode_unit_kerja' => '03', 'nama_unit_kerja' => 'Tim PPI'],
            ['kode_unit_kerja' => '04', 'nama_unit_kerja' => 'Tim K3'],
            ['kode_unit_kerja' => '05', 'nama_unit_kerja' => 'Tim Keselamatan Pasien'],
            ['kode_unit_kerja' => '06', 'nama_unit_kerja' => 'Tim Manajemen Risiko'],
            ['kode_unit_kerja' => '07', 'nama_unit_kerja' => 'Tim Manajemen Komplain dan Survei Kepuasan Pasien'],
            ['kode_unit_kerja' => '08', 'nama_unit_kerja' => 'Kesekretariatan'],
            ['kode_unit_kerja' => '09', 'nama_unit_kerja' => 'Administrasi Manajemen Keuangan'],
            ['kode_unit_kerja' => '10', 'nama_unit_kerja' => 'Administrasi Manajemen Kepegawaian'],
            ['kode_unit_kerja' => '11', 'nama_unit_kerja' => 'Administrasi Manajemen Sarpras'],
            ['kode_unit_kerja' => '12', 'nama_unit_kerja' => 'Administrasi Manajemen Sistem Informasi dan Pelaporan'],
            ['kode_unit_kerja' => '13', 'nama_unit_kerja' => 'Administrasi Manajemen Audit'],
            ['kode_unit_kerja' => '14', 'nama_unit_kerja' => 'Pelayanan Pendaftaran'],
            ['kode_unit_kerja' => '15', 'nama_unit_kerja' => 'Pelayanan Kesehatan Gigi dan Mulut'],
            ['kode_unit_kerja' => '16', 'nama_unit_kerja' => 'Pelayanan Kegawatdaruratan / Tindakan'],
            ['kode_unit_kerja' => '17', 'nama_unit_kerja' => 'Pelayanan KIA - KB & Imunisasi'],
            ['kode_unit_kerja' => '18', 'nama_unit_kerja' => 'Pelayanan Farmasi'],
            ['kode_unit_kerja' => '19', 'nama_unit_kerja' => 'Pelayanan Laboratorium'],
            ['kode_unit_kerja' => '20', 'nama_unit_kerja' => 'Pelayanan Kecantikan'],
            ['kode_unit_kerja' => '21', 'nama_unit_kerja' => 'Pelayanan Fisio'],
            ['kode_unit_kerja' => '22', 'nama_unit_kerja' => 'Pelayanan Hiperbank'],
        ];

        foreach ($unitKerjas as $uk) {
            UnitKerja::updateOrCreate(
                ['kode_unit_kerja' => $uk['kode_unit_kerja']],
                ['nama_unit_kerja' => $uk['nama_unit_kerja']]
            );
        }
    }
}
