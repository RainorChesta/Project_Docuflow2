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
            if (!Schema::hasColumn('documents', 'deletion_requested_by_id')) {
                $table->foreignId('deletion_requested_by_id')->nullable()->constrained('users')->nullOnDelete();
            }
            if (!Schema::hasColumn('documents', 'deletion_requested_at')) {
                $table->timestamp('deletion_requested_at')->nullable();
            }
            if (!Schema::hasColumn('documents', 'deletion_request_notes')) {
                $table->text('deletion_request_notes')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            if (Schema::hasColumn('documents', 'deletion_requested_by_id')) {
                $table->dropForeign(['deletion_requested_by_id']);
                $table->dropColumn(['deletion_requested_by_id', 'deletion_requested_at', 'deletion_request_notes']);
            }
        });
    }
};

