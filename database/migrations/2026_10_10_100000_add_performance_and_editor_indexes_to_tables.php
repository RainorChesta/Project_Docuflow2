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
        // 1. Document Versions
        // Accelerates editor draft/pending checks and version numbering lookups per document
        if (Schema::hasTable('document_versions')) {
            $existing = collect(Schema::getIndexes('document_versions'))->pluck('name')->all();

            Schema::table('document_versions', function (Blueprint $table) use ($existing) {
                if (!in_array('idx_doc_versions_doc_status_discarded', $existing)) {
                    $table->index(['document_id', 'status', 'discarded_at'], 'idx_doc_versions_doc_status_discarded');
                }
                if (!in_array('idx_doc_versions_doc_ver_num', $existing)) {
                    $table->index(['document_id', 'version_number'], 'idx_doc_versions_doc_ver_num');
                }
            });
        }

        // 2. Documents
        // Accelerates approval queue queries (pending renames and rollbacks) and expiration management
        if (Schema::hasTable('documents')) {
            $existing = collect(Schema::getIndexes('documents'))->pluck('name')->all();

            Schema::table('documents', function (Blueprint $table) use ($existing) {
                if (!in_array('idx_documents_pending_title', $existing)) {
                    $table->index('pending_title', 'idx_documents_pending_title');
                }
                if (!in_array('idx_documents_pending_rollback', $existing)) {
                    $table->index('pending_rollback_version_id', 'idx_documents_pending_rollback');
                }
                if (!in_array('idx_documents_expired_date_deleted', $existing)) {
                    $table->index(['is_expired', 'expiration_date', 'deleted_at'], 'idx_documents_expired_date_deleted');
                }
            });
        }

        // 3. Document Approval Steps
        // Accelerates step resolution and traversal by version and order
        if (Schema::hasTable('document_approval_steps')) {
            $existing = collect(Schema::getIndexes('document_approval_steps'))->pluck('name')->all();

            Schema::table('document_approval_steps', function (Blueprint $table) use ($existing) {
                if (!in_array('idx_doc_app_steps_ver_order', $existing)) {
                    $table->index(['version_id', 'step_order'], 'idx_doc_app_steps_ver_order');
                }
            });
        }

        // 4. Corporate Soft Files
        // Accelerates template and soft file filtering by active status
        if (Schema::hasTable('corporate_soft_files')) {
            $existing = collect(Schema::getIndexes('corporate_soft_files'))->pluck('name')->all();

            Schema::table('corporate_soft_files', function (Blueprint $table) use ($existing) {
                if (!in_array('idx_corp_sf_status', $existing)) {
                    $table->index('status', 'idx_corp_sf_status');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('document_versions')) {
            $existing = collect(Schema::getIndexes('document_versions'))->pluck('name')->all();
            Schema::table('document_versions', function (Blueprint $table) use ($existing) {
                if (in_array('idx_doc_versions_doc_status_discarded', $existing)) {
                    $table->dropIndex('idx_doc_versions_doc_status_discarded');
                }
                if (in_array('idx_doc_versions_doc_ver_num', $existing)) {
                    $table->dropIndex('idx_doc_versions_doc_ver_num');
                }
            });
        }

        if (Schema::hasTable('documents')) {
            $existing = collect(Schema::getIndexes('documents'))->pluck('name')->all();
            Schema::table('documents', function (Blueprint $table) use ($existing) {
                if (in_array('idx_documents_pending_title', $existing)) {
                    $table->dropIndex('idx_documents_pending_title');
                }
                if (in_array('idx_documents_pending_rollback', $existing)) {
                    $table->dropIndex('idx_documents_pending_rollback');
                }
                if (in_array('idx_documents_expired_date_deleted', $existing)) {
                    $table->dropIndex('idx_documents_expired_date_deleted');
                }
            });
        }

        if (Schema::hasTable('document_approval_steps')) {
            $existing = collect(Schema::getIndexes('document_approval_steps'))->pluck('name')->all();
            Schema::table('document_approval_steps', function (Blueprint $table) use ($existing) {
                if (in_array('idx_doc_app_steps_ver_order', $existing)) {
                    $table->dropIndex('idx_doc_app_steps_ver_order');
                }
            });
        }

        if (Schema::hasTable('corporate_soft_files')) {
            $existing = collect(Schema::getIndexes('corporate_soft_files'))->pluck('name')->all();
            Schema::table('corporate_soft_files', function (Blueprint $table) use ($existing) {
                if (in_array('idx_corp_sf_status', $existing)) {
                    $table->dropIndex('idx_corp_sf_status');
                }
            });
        }
    }
};
