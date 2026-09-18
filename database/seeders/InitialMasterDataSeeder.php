<?php

namespace Database\Seeders;

use App\Models\Division;
use App\Models\UnitKerja;
use Illuminate\Database\Seeder;

class InitialMasterDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Master Divisi (digunakan untuk Cabang Pusat)
        $divisions = [
            ['code' => 'SKRT', 'name' => 'Sekretariat'],
            ['code' => 'IT',   'name' => 'Information Technology'],
            ['code' => 'HRD',  'name' => 'Human Resources Department'],
            ['code' => 'FIN',  'name' => 'Finance & Accounting'],
            ['code' => 'OPS',  'name' => 'Operations'],
            ['code' => 'LEG',  'name' => 'Legal & Compliance'],
            ['code' => 'MKT',  'name' => 'Marketing & Communication'],
        ];

        foreach ($divisions as $div) {
            Division::firstOrCreate(
                ['code' => $div['code']],
                ['name' => $div['name']]
            );
        }

        // 2. Master Unit Kerja Global (digunakan untuk Cabang PT)
        $unitKerjas = [
            ['kode_unit_kerja' => '01', 'nama_unit_kerja' => 'Tim Manajemen Mutu'],
            ['kode_unit_kerja' => '02', 'nama_unit_kerja' => 'Tim Audit Internal'],
            ['kode_unit_kerja' => '03', 'nama_unit_kerja' => 'Tim PPI'],
            ['kode_unit_kerja' => '04', 'nama_unit_kerja' => 'Tim K3'],
            ['kode_unit_kerja' => '05', 'nama_unit_kerja' => 'Tim Pelayanan Medis'],
            ['kode_unit_kerja' => '06', 'nama_unit_kerja' => 'Tim Penunjang Medis'],
        ];

        foreach ($unitKerjas as $uk) {
            UnitKerja::firstOrCreate(
                ['kode_unit_kerja' => $uk['kode_unit_kerja']],
                ['nama_unit_kerja' => $uk['nama_unit_kerja']]
            );
        }
    }
}
