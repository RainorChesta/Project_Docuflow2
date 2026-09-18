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
        Schema::table('documents', function (Blueprint $table) {
            if (!Schema::hasColumn('documents', 'director_notified_at')) {
                $table->timestamp('director_notified_at')->nullable()->after('approver_role');
            }
            if (!Schema::hasColumn('documents', 'director_read_at')) {
                $table->timestamp('director_read_at')->nullable()->after('director_notified_at');
            }
            if (!Schema::hasColumn('documents', 'director_acknowledged_by_id')) {
                $table->foreignId('director_acknowledged_by_id')->nullable()->after('director_read_at')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'director_acknowledged_by_id')) {
                $table->dropForeign(['director_acknowledged_by_id']);
                $table->dropColumn('director_acknowledged_by_id');
            }
            if (Schema::hasColumn('documents', 'director_read_at')) {
                $table->dropColumn('director_read_at');
            }
            if (Schema::hasColumn('documents', 'director_notified_at')) {
                $table->dropColumn('director_notified_at');
            }
        });
    }
};
