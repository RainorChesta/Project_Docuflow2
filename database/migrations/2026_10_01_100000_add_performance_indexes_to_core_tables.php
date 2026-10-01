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
        // 1. Pivot Table Reverse Lookups (Foreign keys where user_id or unit_kerja_id is queried alone)
        if (Schema::hasTable('company_user')) {
            Schema::table('company_user', function (Blueprint $table) {
                $table->index('user_id', 'idx_company_user_user_id');
            });
        }

        if (Schema::hasTable('branch_user')) {
            Schema::table('branch_user', function (Blueprint $table) {
                $table->index('user_id', 'idx_branch_user_user_id');
            });
        }

        if (Schema::hasTable('unit_kerja_user')) {
            Schema::table('unit_kerja_user', function (Blueprint $table) {
                $table->index('unit_kerja_id', 'idx_unit_kerja_user_uk_id');
            });
        }

        if (Schema::hasTable('company_corporate_soft_file')) {
            Schema::table('company_corporate_soft_file', function (Blueprint $table) {
                $table->index('company_id', 'idx_comp_corp_sf_comp_id');
            });
        }

        if (Schema::hasTable('branch_corporate_soft_file')) {
            Schema::table('branch_corporate_soft_file', function (Blueprint $table) {
                $table->index('branch_id', 'idx_branch_corp_sf_branch_id');
            });
        }

        if (Schema::hasTable('document_shares')) {
            Schema::table('document_shares', function (Blueprint $table) {
                $table->index('user_id', 'idx_document_shares_user_id');
            });
        }

        if (Schema::hasTable('document_unit_kerja_shares')) {
            Schema::table('document_unit_kerja_shares', function (Blueprint $table) {
                $table->index('unit_kerja_id', 'idx_doc_uk_shares_uk_id');
            });
        }

        // 2. User Signatures
        if (Schema::hasTable('signatures')) {
            Schema::table('signatures', function (Blueprint $table) {
                $table->index(['user_id', 'type'], 'idx_signatures_user_type');
            });
        }

        // 3. Signature Requests (Incoming approval queues and document auto-apply lookups)
        if (Schema::hasTable('signature_requests')) {
            Schema::table('signature_requests', function (Blueprint $table) {
                $table->index(['target_user_id', 'status', 'requested_at'], 'idx_sig_req_target_status_req');
                $table->index(['document_id', 'status', 'is_used'], 'idx_sig_req_doc_status_used');
            });
        }

        // 4. Document Versions (Approval pipeline queues and navbar badge counters)
        if (Schema::hasTable('document_versions')) {
            Schema::table('document_versions', function (Blueprint $table) {
                $table->index(['status', 'discarded_at'], 'idx_doc_versions_status_discarded');
            });
        }

        // 5. Documents (Branch / Unit Kerja browsing, active status, soft delete filters, and director unread tembusan)
        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->index(['branch_id', 'deleted_at', 'created_at'], 'idx_documents_branch_del_created');
                $table->index(['unit_kerja_id', 'deleted_at', 'created_at'], 'idx_documents_uk_del_created');
                $table->index(['director_notified_at', 'director_read_at', 'deleted_at'], 'idx_documents_director_tembusan');
                
                // Drop redundant single-column index on owner_id (covered by documents_owner_id_summary_status_index)
                $table->dropIndex('documents_owner_id_index');
            });
        }

        // 6. Notifications (Polymorphic user notifications feed and unread badge counts)
        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->index(['notifiable_type', 'notifiable_id', 'read_at', 'created_at'], 'idx_notif_target_read_created');
                
                // Drop redundant 2-column index (covered by idx_notif_target_read_created)
                $table->dropIndex('notifications_notifiable_type_notifiable_id_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('company_user')) {
            Schema::table('company_user', function (Blueprint $table) {
                $table->dropIndex('idx_company_user_user_id');
            });
        }

        if (Schema::hasTable('branch_user')) {
            Schema::table('branch_user', function (Blueprint $table) {
                $table->dropIndex('idx_branch_user_user_id');
            });
        }

        if (Schema::hasTable('unit_kerja_user')) {
            Schema::table('unit_kerja_user', function (Blueprint $table) {
                $table->dropIndex('idx_unit_kerja_user_uk_id');
            });
        }

        if (Schema::hasTable('company_corporate_soft_file')) {
            Schema::table('company_corporate_soft_file', function (Blueprint $table) {
                $table->dropIndex('idx_comp_corp_sf_comp_id');
            });
        }

        if (Schema::hasTable('branch_corporate_soft_file')) {
            Schema::table('branch_corporate_soft_file', function (Blueprint $table) {
                $table->dropIndex('idx_branch_corp_sf_branch_id');
            });
        }

        if (Schema::hasTable('document_shares')) {
            Schema::table('document_shares', function (Blueprint $table) {
                $table->dropIndex('idx_document_shares_user_id');
            });
        }

        if (Schema::hasTable('document_unit_kerja_shares')) {
            Schema::table('document_unit_kerja_shares', function (Blueprint $table) {
                $table->dropIndex('idx_doc_uk_shares_uk_id');
            });
        }

        if (Schema::hasTable('signatures')) {
            Schema::table('signatures', function (Blueprint $table) {
                $table->dropIndex('idx_signatures_user_type');
            });
        }

        if (Schema::hasTable('signature_requests')) {
            Schema::table('signature_requests', function (Blueprint $table) {
                $table->dropIndex('idx_sig_req_target_status_req');
                $table->dropIndex('idx_sig_req_doc_status_used');
            });
        }

        if (Schema::hasTable('document_versions')) {
            Schema::table('document_versions', function (Blueprint $table) {
                $table->dropIndex('idx_doc_versions_status_discarded');
            });
        }

        if (Schema::hasTable('documents')) {
            Schema::table('documents', function (Blueprint $table) {
                $table->dropIndex('idx_documents_branch_del_created');
                $table->dropIndex('idx_documents_uk_del_created');
                $table->dropIndex('idx_documents_director_tembusan');
                $table->index('owner_id', 'documents_owner_id_index');
            });
        }

        if (Schema::hasTable('notifications')) {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropIndex('idx_notif_target_read_created');
                $table->index(['notifiable_type', 'notifiable_id'], 'notifications_notifiable_type_notifiable_id_index');
            });
        }
    }
};
