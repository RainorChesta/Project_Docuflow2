<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Drop the unique constraint on (requester_id, target_user_id, document_id)
     * so that users can submit multiple signature requests for the same
     * target user and document (strict 1-to-1: each approval = 1 insertion).
     */
    public function up(): void
    {
        // SQLite doesn't support DROP INDEX natively via Blueprint,
        // so we use a raw approach that works for both SQLite and MySQL/Postgres.
        $driver = Schema::getConnection()->getDriverName();

        // Add a non-unique index first so MySQL foreign key constraints on requester_id remain satisfied
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->index(['requester_id', 'target_user_id', 'document_id'], 'idx_req_target_doc');
        });

        // Drop the unique constraint index
        if ($driver === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS unique_req_target_doc');
        } else {
            Schema::table('signature_requests', function (Blueprint $table) {
                $table->dropUnique('unique_req_target_doc');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX unique_req_target_doc ON signature_requests (requester_id, target_user_id, document_id)');
        } else {
            Schema::table('signature_requests', function (Blueprint $table) {
                $table->unique(['requester_id', 'target_user_id', 'document_id'], 'unique_req_target_doc');
            });
        }

        Schema::table('signature_requests', function (Blueprint $table) {
            $table->dropIndex('idx_req_target_doc');
        });
    }
};
