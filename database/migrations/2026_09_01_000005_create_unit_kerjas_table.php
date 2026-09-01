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
        Schema::create('unit_kerjas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cabang_id')->constrained('branches')->cascadeOnDelete();
            $table->string('kode_unit_kerja', 50);
            $table->string('nama_unit_kerja', 255);
            $table->timestamps();

            $table->unique(['cabang_id', 'kode_unit_kerja']);
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('unit_kerja_id')->nullable()->after('branch_id')->constrained('unit_kerjas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['unit_kerja_id']);
            $table->dropColumn('unit_kerja_id');
        });

        Schema::dropIfExists('unit_kerjas');
    }
};
