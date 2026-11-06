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
        // 1. Archive Retention Policies (JRA - Jadwal Retensi Arsip)
        Schema::create('archive_retention_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->unsignedInteger('version')->default(1);
            $table->text('description')->nullable();
            $table->string('trigger_event', 40)->default('registration_date'); // registration_date, document_date, expiration_date, closure_date, manual_event
            $table->unsignedInteger('active_retention_years')->default(2);
            $table->unsignedInteger('inactive_retention_years')->default(3);
            $table->unsignedInteger('total_retention_days')->default(1825);
            $table->string('final_disposition', 30)->default('review'); // review, destroy, permanent
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'code', 'version'], 'arc_ret_pol_comp_code_ver_uq');
            $table->index(['company_id', 'is_active'], 'arc_ret_pol_comp_active_idx');
        });

        // 2. Archive Classifications (Klasifikasi Arsip)
        Schema::create('archive_classifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('archive_classifications')->nullOnDelete();
            $table->foreignId('default_retention_policy_id')->nullable()->constrained('archive_retention_policies')->nullOnDelete();
            $table->string('code', 50);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('confidentiality_level', 30)->default('internal'); // public, internal, confidential, restricted
            $table->boolean('requires_approval')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code'], 'arc_class_comp_code_uq');
            $table->index(['company_id', 'is_active'], 'arc_class_comp_active_idx');
        });

        // 3. Archive Numbering Schemes (Skema Penomoran Arsip)
        Schema::create('archive_numbering_schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->string('name');
            $table->string('prefix', 20)->default('ARC');
            $table->string('template')->default('ARC/{COMPANY}/{BRANCH}/{UNIT}/{CLASS}/{YYYY}/{SEQ:06}');
            $table->string('separator', 5)->default('/');
            $table->string('sequence_scope', 40)->default('company_year'); // company_year, branch_year, company_never
            $table->string('reset_period', 20)->default('yearly'); // yearly, monthly, never
            $table->unsignedTinyInteger('padding')->default(6);
            $table->unsignedBigInteger('start_value')->default(1);
            $table->string('timezone', 60)->default('Asia/Jakarta');
            $table->boolean('is_active')->default(true);
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'is_active'], 'arc_num_scheme_comp_act_idx');
        });

        // 4. Archive Number Counters (Atomic Sequence Counters)
        Schema::create('archive_number_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scheme_id')->constrained('archive_numbering_schemes')->cascadeOnDelete();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('scope_key', 120);
            $table->string('period_key', 40);
            $table->unsignedBigInteger('last_value')->default(0);
            $table->timestamps();

            $table->unique(['scheme_id', 'company_id', 'scope_key', 'period_key'], 'arc_num_cnt_unique');
        });

        // 5. Archive Requests (Intake & Approval Queue before Registration)
        Schema::create('archive_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('unit_kerja_id')->nullable()->constrained('unit_kerjas')->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('requested_version_id')->nullable()->constrained('document_versions')->nullOnDelete();
            $table->foreignId('predecessor_entry_id')->nullable();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('classification_id')->constrained('archive_classifications')->restrictOnDelete();
            $table->foreignId('retention_policy_id')->nullable()->constrained('archive_retention_policies')->nullOnDelete();
            $table->string('archive_title');
            $table->text('description')->nullable();
            $table->text('archive_reason')->nullable();
            $table->date('record_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->string('sensitivity_level', 30)->default('internal'); // public, internal, confidential, restricted
            $table->json('tags')->nullable();
            $table->json('storage_location')->nullable();
            $table->string('state', 40)->default('draft');
            // draft, pending_approval, approved, rejected, returned_for_correction, preparing_snapshot, ready_to_register, registered, registration_failed, canceled
            $table->boolean('requires_approval')->default(true);
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('approver_role', 40)->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->string('idempotency_key', 100)->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('registered_archive_entry_id')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'idempotency_key'], 'arc_req_comp_idemp_uq');
            $table->index(['company_id', 'state'], 'arc_req_comp_state_idx');
            $table->index(['document_id', 'requested_version_id'], 'arc_req_doc_ver_idx');
        });

        // 6. Archive Entries (Authoritative Immutable Archive Register)
        Schema::create('archive_entries', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('unit_kerja_id')->nullable()->constrained('unit_kerjas')->nullOnDelete();
            $table->foreignId('document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->foreignId('document_version_id')->nullable()->constrained('document_versions')->nullOnDelete();
            $table->foreignId('request_id')->nullable()->constrained('archive_requests')->nullOnDelete();
            $table->foreignId('predecessor_entry_id')->nullable()->constrained('archive_entries')->nullOnDelete();

            // Official Archive Numbering Fields (Separate from documents.document_number)
            $table->string('archive_number', 120);
            $table->string('normalized_number', 120);
            $table->foreignId('scheme_id')->nullable()->constrained('archive_numbering_schemes')->nullOnDelete();
            $table->unsignedInteger('scheme_version')->default(1);
            $table->unsignedBigInteger('sequence_number');
            $table->string('registration_period', 30);
            $table->timestamp('registered_at');
            $table->foreignId('registered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // Classification & Organization Snapshots
            $table->foreignId('classification_id')->nullable()->constrained('archive_classifications')->nullOnDelete();
            $table->string('classification_code_snapshot', 50);
            $table->string('classification_name_snapshot')->nullable();
            $table->string('company_code_snapshot', 30);
            $table->string('company_name_snapshot')->nullable();
            $table->string('branch_code_snapshot', 30);
            $table->string('branch_name_snapshot')->nullable();
            $table->string('unit_code_snapshot', 30);
            $table->string('unit_name_snapshot')->nullable();

            // Descriptive & Provenance Metadata
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('original_document_number', 120)->nullable();
            $table->unsignedInteger('version_number_snapshot')->default(1);
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('owner_name_snapshot')->nullable();
            $table->string('sensitivity_level', 30)->default('internal');
            $table->text('archive_reason')->nullable();
            $table->date('record_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->json('tags')->nullable();
            $table->json('storage_location')->nullable();

            // Lifecycle & Retention State
            $table->string('status', 40)->default('registered');
            // registered, under_review, eligible_for_disposition, disposition_pending, disposed, permanent, voided
            $table->foreignId('retention_policy_id')->nullable()->constrained('archive_retention_policies')->nullOnDelete();
            $table->date('retention_trigger_date')->nullable();
            $table->date('retention_active_until')->nullable();
            $table->date('retention_due_at')->nullable();
            $table->string('final_disposition_action', 30)->default('review'); // review, destroy, permanent
            $table->boolean('is_under_legal_hold')->default(false);

            // Verification & Snapshots
            $table->json('metadata_snapshot');
            $table->string('qr_token', 80)->unique();

            // Disposition & Void Tracking
            $table->timestamp('disposed_at')->nullable();
            $table->foreignId('disposed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disposition_certificate_number', 80)->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'normalized_number'], 'arc_ent_comp_norm_num_uq');
            $table->index(['company_id', 'status', 'registered_at'], 'arc_ent_comp_stat_reg_idx');
            $table->index(['company_id', 'classification_id', 'registered_at'], 'arc_ent_comp_class_reg_idx');
            $table->index(['company_id', 'branch_id', 'unit_kerja_id'], 'arc_ent_org_scope_idx');
            $table->index(['company_id', 'retention_due_at', 'status'], 'arc_ent_ret_due_idx');
            $table->index(['company_id', 'is_under_legal_hold'], 'arc_ent_hold_idx');
            $table->index('document_id', 'arc_ent_doc_idx');
        });

        // 7. Archive Snapshots (Immutable File & Manifest Verification)
        Schema::create('archive_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_entry_id')->nullable()->constrained('archive_entries')->cascadeOnDelete();
            $table->foreignId('archive_request_id')->nullable()->constrained('archive_requests')->nullOnDelete();
            $table->string('storage_disk', 40)->default('local');
            $table->string('object_key')->nullable();
            $table->string('original_filename')->nullable();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('content_hash_sha256', 64);
            $table->string('manifest_hash_sha256', 64);
            $table->json('manifest_payload');
            $table->boolean('is_html_snapshot')->default(false);
            $table->longText('html_content')->nullable();
            $table->timestamp('copied_at');
            $table->timestamp('validated_at')->nullable();
            $table->timestamp('last_verified_at')->nullable();
            $table->string('integrity_status', 30)->default('valid'); // valid, mismatch, missing, disposed
            $table->timestamps();

            $table->index('archive_entry_id', 'arc_snap_entry_idx');
            $table->index('content_hash_sha256', 'arc_snap_hash_idx');
        });

        // 8. Archive Retention Assignments (Schedule History & Extensions)
        Schema::create('archive_retention_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('archive_entry_id')->constrained('archive_entries')->cascadeOnDelete();
            $table->foreignId('retention_policy_id')->nullable()->constrained('archive_retention_policies')->nullOnDelete();
            $table->unsignedInteger('policy_version')->default(1);
            $table->string('trigger_event', 40)->default('registration_date');
            $table->date('trigger_date');
            $table->date('active_until');
            $table->date('eligible_from');
            $table->string('final_action', 30)->default('review');
            $table->json('calculation_snapshot')->nullable();
            $table->string('status', 30)->default('active'); // active, extended, superseded, completed
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['archive_entry_id', 'status'], 'arc_ret_asgn_entry_stat_idx');
        });

        // 9. Archive Legal Holds (Multiple Independent Legal/Audit Holds)
        Schema::create('archive_legal_holds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('archive_entry_id')->constrained('archive_entries')->cascadeOnDelete();
            $table->string('case_reference', 100);
            $table->string('title');
            $table->text('reason');
            $table->string('status', 30)->default('active'); // active, released
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at');
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('released_at')->nullable();
            $table->text('release_reason')->nullable();
            $table->timestamps();

            $table->index(['archive_entry_id', 'status'], 'arc_hold_entry_stat_idx');
            $table->index(['company_id', 'status'], 'arc_hold_comp_stat_idx');
        });

        // 10. Archive Disposition Requests & Destruction Certificates
        Schema::create('archive_disposition_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('archive_entry_id')->constrained('archive_entries')->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->string('action_type', 30); // destroy, permanent, transfer, extend
            $table->text('justification');
            $table->unsignedInteger('extension_days')->nullable();
            $table->string('status', 30)->default('pending'); // pending, approved, rejected, executed, failed
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->foreignId('executed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('executed_at')->nullable();
            $table->string('certificate_number', 80)->nullable()->unique();
            $table->json('certificate_snapshot')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'status'], 'arc_disp_comp_stat_idx');
            $table->index(['archive_entry_id', 'status'], 'arc_disp_entry_stat_idx');
        });

        // 11. Archive Relationships (Lineage: supersedes, reactivated_from, related)
        Schema::create('archive_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_entry_id')->constrained('archive_entries')->cascadeOnDelete();
            $table->foreignId('to_entry_id')->nullable()->constrained('archive_entries')->nullOnDelete();
            $table->foreignId('related_document_id')->nullable()->constrained('documents')->nullOnDelete();
            $table->string('relation_type', 40); // supersedes, reactivated_from, reactivated_to_document, related
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['from_entry_id', 'relation_type'], 'arc_rel_from_type_idx');
            $table->index(['to_entry_id', 'relation_type'], 'arc_rel_to_type_idx');
        });

        // 12. Archive Void Records (Permanent Tombstones so Numbers are Never Reused)
        Schema::create('archive_void_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('archive_entry_id')->nullable()->constrained('archive_entries')->nullOnDelete();
            $table->string('archive_number', 120);
            $table->string('normalized_number', 120);
            $table->text('void_reason');
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at');
            $table->json('metadata_snapshot')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'normalized_number'], 'arc_void_comp_norm_num_uq');
        });

        // 13. Archive Events (Append-Only Tamper-Evident Audit Log)
        Schema::create('archive_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->foreignId('archive_entry_id')->nullable()->constrained('archive_entries')->nullOnDelete();
            $table->foreignId('archive_request_id')->nullable()->constrained('archive_requests')->nullOnDelete();
            $table->string('event_type', 60);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('actor_name')->nullable();
            $table->string('actor_role', 40)->nullable();
            $table->timestamp('occurred_at');
            $table->text('reason')->nullable();
            $table->json('before_state')->nullable();
            $table->json('after_state')->nullable();
            $table->json('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('previous_hash', 64)->nullable();
            $table->string('event_hash', 64);

            $table->index(['archive_entry_id', 'occurred_at'], 'arc_evt_entry_time_idx');
            $table->index(['company_id', 'event_type', 'occurred_at'], 'arc_evt_comp_type_time_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archive_events');
        Schema::dropIfExists('archive_void_records');
        Schema::dropIfExists('archive_relationships');
        Schema::dropIfExists('archive_disposition_requests');
        Schema::dropIfExists('archive_legal_holds');
        Schema::dropIfExists('archive_retention_assignments');
        Schema::dropIfExists('archive_snapshots');
        Schema::dropIfExists('archive_entries');
        Schema::dropIfExists('archive_requests');
        Schema::dropIfExists('archive_number_counters');
        Schema::dropIfExists('archive_numbering_schemes');
        Schema::dropIfExists('archive_classifications');
        Schema::dropIfExists('archive_retention_policies');
    }
};

