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
        if (Schema::hasTable('document_approval_steps')) {
            Schema::table('document_approval_steps', function (Blueprint $table) {
                if (!Schema::hasColumn('document_approval_steps', 'signature_request_id')) {
                    $table->foreignId('signature_request_id')->nullable()->after('version_id')->constrained('signature_requests')->nullOnDelete();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('document_approval_steps')) {
            Schema::table('document_approval_steps', function (Blueprint $table) {
                if (Schema::hasColumn('document_approval_steps', 'signature_request_id')) {
                    $table->dropForeign(['signature_request_id']);
                    $table->dropColumn('signature_request_id');
                }
            });
        }
    }
};
