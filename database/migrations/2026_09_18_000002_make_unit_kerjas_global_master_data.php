<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('unit_kerjas', function (Blueprint $table) {
            // Drop foreign key and unique constraint if existing
            $table->dropForeign(['cabang_id']);
            $table->dropUnique(['cabang_id', 'kode_unit_kerja']);
            $table->dropColumn('cabang_id');

            $table->unique('kode_unit_kerja');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_kerjas', function (Blueprint $table) {
            $table->dropUnique(['kode_unit_kerja']);
            $table->foreignId('cabang_id')->nullable()->constrained('branches')->cascadeOnDelete();
            $table->unique(['cabang_id', 'kode_unit_kerja']);
        });
    }
};
