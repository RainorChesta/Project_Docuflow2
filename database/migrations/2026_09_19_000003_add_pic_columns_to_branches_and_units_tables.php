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
        Schema::table('branches', function (Blueprint $table) {
            if (!Schema::hasColumn('branches', 'pic_klinik_id')) {
                $table->foreignId('pic_klinik_id')->nullable()->after('is_pusat')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('unit_kerjas', function (Blueprint $table) {
            if (!Schema::hasColumn('unit_kerjas', 'pic_user_id')) {
                $table->foreignId('pic_user_id')->nullable()->after('nama_unit_kerja')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_kerjas', function (Blueprint $table) {
            if (Schema::hasColumn('unit_kerjas', 'pic_user_id')) {
                $table->dropForeign(['pic_user_id']);
                $table->dropColumn('pic_user_id');
            }
        });

        Schema::table('branches', function (Blueprint $table) {
            if (Schema::hasColumn('branches', 'pic_klinik_id')) {
                $table->dropForeign(['pic_klinik_id']);
                $table->dropColumn('pic_klinik_id');
            }
        });
    }
};
