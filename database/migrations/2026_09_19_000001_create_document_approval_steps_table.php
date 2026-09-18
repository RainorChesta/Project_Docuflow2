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
        if (!Schema::hasTable('document_approval_steps')) {
            Schema::create('document_approval_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
                $table->foreignId('version_id')->constrained('document_versions')->cascadeOnDelete();
                $table->foreignId('signature_request_id')->nullable()->constrained('signature_requests')->nullOnDelete();
                $table->unsignedInteger('step_order')->default(1);
                $table->string('step_type', 50); // peer_review, pic_unit_acknowledge, kadiv_approval, pic_klinik_approval, director_approval, system_review
                $table->string('step_name');
                $table->foreignId('assigned_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('assigned_role', 50)->nullable(); // staff, head, direktur, admin
                $table->string('status', 30)->default('waiting'); // waiting, pending, approved, rejected, bypassed, cancelled
                $table->foreignId('action_by_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('action_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['version_id', 'status']);
                $table->index(['assigned_user_id', 'status']);
                $table->index(['document_id', 'step_order']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_approval_steps');
    }
};
