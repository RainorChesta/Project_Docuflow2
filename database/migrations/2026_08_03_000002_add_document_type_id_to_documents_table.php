<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Hapus data lama - format document_number lama tidak kompatibel
        // dengan format baru dan tidak akan punya document_type_id
        DB::table('document_versions')->delete();

        if (Schema::hasTable('document_access_links')) {
            DB::table('document_access_links')->delete();
        }

        DB::table('documents')->delete();

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('document_type_id')
                ->after('division_id')
                ->constrained()
                ->restrictOnDelete();

            $table->index(['document_type_id', 'division_id']);
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['document_type_id']);
            $table->dropIndex(['document_type_id', 'division_id']);
            $table->dropColumn('document_type_id');
        });
    }
};
