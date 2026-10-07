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
        Schema::table('unit_kerja_user', function (Blueprint $table) {
            // Drop old (user_id, unit_kerja_id) unique index to allow the same user
            // and unit kerja across different branches, while keeping uk_user_branch_unique intact.
            $table->dropUnique('unit_kerja_user_user_id_unit_kerja_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_kerja_user', function (Blueprint $table) {
            // Restore the old unique index on (user_id, unit_kerja_id)
            $table->unique(['user_id', 'unit_kerja_id'], 'unit_kerja_user_user_id_unit_kerja_id_unique');
        });
    }
};
