<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('document_types') || app()->runningUnitTests()) {
            return;
        }

        // 1. Standard Dokumen Akreditasi (Accreditation Documents)
        $akreditasiTypes = [
            ['code' => 'SK',  'name' => 'Keputusan / Kebijakan'],
            ['code' => 'PD',  'name' => 'Pedoman'],
            ['code' => 'PAN', 'name' => 'Panduan'],
            ['code' => 'MM',  'name' => 'Manual Mutu'],
            ['code' => 'SOP', 'name' => 'Standar Operasional Prosedur'],
            ['code' => 'REN', 'name' => 'Rencana Lima Tahunan'],
            ['code' => 'RET', 'name' => 'Rencana Tahunan'],
            ['code' => 'KAK', 'name' => 'Kerangka Acuan Kegiatan'],
        ];

        $now = Carbon::now();

        foreach ($akreditasiTypes as $type) {
            $existing = DB::table('document_types')->where('code', $type['code'])->first();
            if ($existing) {
                $updateData = ['category' => 'akreditasi'];
                // Update legacy names if they were still old placeholders
                if (in_array($existing->name, ['Surat Keterangan', 'Standard Operating Procedure'], true)) {
                    $updateData['name'] = $type['name'];
                }
                DB::table('document_types')->where('id', $existing->id)->update($updateData);
            } else {
                DB::table('document_types')->insert([
                    'code' => $type['code'],
                    'name' => $type['name'],
                    'category' => 'akreditasi',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // 2. Standard Official Correspondence (Naskah Dinas)
        $naskahDinasCodes = [
            'S.ED', 'ST', 'IM', 'I/S.KEL', 'E/S.KEL', 'PKWT', 'PKS',
            'SP', 'S.KU', 'BA', 'S.KET', 'S.UND', 'SPPD', 'S.PENG',
            'SB', 'S.PMH', 'SR', 'S.PB', 'S.PRT', 'S.PWN',
        ];

        DB::table('document_types')
            ->whereIn('code', $naskahDinasCodes)
            ->update(['category' => 'naskah_dinas']);

        // Default any remaining null or empty category to naskah_dinas
        DB::table('document_types')
            ->whereNull('category')
            ->orWhere('category', '')
            ->update(['category' => 'naskah_dinas']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Safe rollback: no deletion of types to avoid breaking existing documents
    }
};
