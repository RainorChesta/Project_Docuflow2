<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('pending_rollback_version_id')->nullable()
                ->after('current_version_id')
                ->constrained('document_versions')
                ->nullOnDelete();
            $table->foreignId('rollback_requested_by_id')->nullable()
                ->after('pending_rollback_version_id')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('rollback_requested_at')->nullable()->after('rollback_requested_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['pending_rollback_version_id']);
            $table->dropForeign(['rollback_requested_by_id']);
            $table->dropColumn(['pending_rollback_version_id', 'rollback_requested_by_id', 'rollback_requested_at']);
        });
    }
};
