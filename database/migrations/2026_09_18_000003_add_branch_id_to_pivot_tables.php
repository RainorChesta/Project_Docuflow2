<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. unit_kerja_user table
        Schema::table('unit_kerja_user', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('unit_kerja_id')->constrained('branches')->cascadeOnDelete();
        });

        // Drop previous unique index if exists and create composite unique with branch_id
        try {
            DB::statement('DROP INDEX IF EXISTS unit_kerja_user_user_id_unit_kerja_id_unique');
        } catch (\Throwable $e) {
            // Index might not exist or handled differently by driver
        }

        Schema::table('unit_kerja_user', function (Blueprint $table) {
            $table->unique(['user_id', 'unit_kerja_id', 'branch_id'], 'uk_user_branch_unique');
        });

        // 2. division_user table
        Schema::table('division_user', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('user_id')->constrained('branches')->cascadeOnDelete();
        });

        try {
            DB::statement('DROP INDEX IF EXISTS division_user_division_id_user_id_unique');
        } catch (\Throwable $e) {
            // Index might not exist or handled differently by driver
        }

        Schema::table('division_user', function (Blueprint $table) {
            $table->unique(['user_id', 'division_id', 'branch_id'], 'div_user_branch_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('unit_kerja_user', function (Blueprint $table) {
            $table->dropUnique('uk_user_branch_unique');
            $table->dropConstrainedForeignId('branch_id');
            $table->unique(['user_id', 'unit_kerja_id']);
        });

        Schema::table('division_user', function (Blueprint $table) {
            $table->dropUnique('div_user_branch_unique');
            $table->dropConstrainedForeignId('branch_id');
            $table->unique(['division_id', 'user_id']);
        });
    }
};
