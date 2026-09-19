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
        // 1. Add category to document_types
        if (!Schema::hasColumn('document_types', 'category')) {
            Schema::table('document_types', function (Blueprint $table) {
                $table->string('category', 50)->default('naskah_dinas')->after('name');
            });
        }

        // 2. Create document_unit_kerja_shares table
        if (!Schema::hasTable('document_unit_kerja_shares')) {
            Schema::create('document_unit_kerja_shares', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained()->cascadeOnDelete();
                $table->foreignId('unit_kerja_id')->constrained('unit_kerjas')->cascadeOnDelete();
                $table->string('role', 20)->default('viewer');
                $table->foreignId('invited_by')->constrained('users')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['document_id', 'unit_kerja_id']);
            });
        }

        // 3. Drop division references and indexes from documents
        if (Schema::hasColumn('documents', 'division_id')) {
            try {
                if (DB::getDriverName() === 'sqlite') {
                    $indexes = DB::select("SELECT name, sql FROM sqlite_master WHERE type='index' AND tbl_name='documents'");
                    foreach ($indexes as $idx) {
                        if (!empty($idx->sql) && str_contains($idx->sql, 'division_id')) {
                            DB::statement("DROP INDEX IF EXISTS \"{$idx->name}\"");
                        }
                    }
                }
            } catch (\Throwable $e) {}

            Schema::table('documents', function (Blueprint $table) {
                try {
                    $table->dropForeign(['division_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn('division_id');
            });
        }

        // 4. Drop division references from users
        if (Schema::hasColumn('users', 'division_id')) {
            try {
                if (DB::getDriverName() === 'sqlite') {
                    $indexes = DB::select("SELECT name, sql FROM sqlite_master WHERE type='index' AND tbl_name='users'");
                    foreach ($indexes as $idx) {
                        if (!empty($idx->sql) && str_contains($idx->sql, 'division_id')) {
                            DB::statement("DROP INDEX IF EXISTS \"{$idx->name}\"");
                        }
                    }
                }
            } catch (\Throwable $e) {}

            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropForeign(['division_id']);
                } catch (\Throwable $e) {}
                $table->dropColumn('division_id');
            });
        }

        // 5. Drop division_id from branches if still present
        if (Schema::hasTable('divisions') && Schema::hasColumn('divisions', 'branch_id')) {
            try {
                Schema::table('divisions', function (Blueprint $table) {
                    $table->dropForeign(['branch_id']);
                });
            } catch (\Throwable $e) {}
        }

        // 6. Drop division-related tables
        Schema::dropIfExists('document_division_shares');
        Schema::dropIfExists('division_user');
        Schema::dropIfExists('divisions');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reverse operations if needed
        if (!Schema::hasTable('divisions')) {
            Schema::create('divisions', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('documents', 'division_id')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('users', 'division_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('division_id')->nullable()->constrained('divisions')->nullOnDelete();
            });
        }

        Schema::dropIfExists('document_unit_kerja_shares');

        if (Schema::hasColumn('document_types', 'category')) {
            Schema::table('document_types', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }
};
