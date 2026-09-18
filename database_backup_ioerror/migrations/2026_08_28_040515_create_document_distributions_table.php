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
        Schema::create('document_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('source_branch_id')->constrained('branches');
            $table->foreignId('target_branch_id')->constrained('branches');
            $table->foreignId('target_user_id')->nullable()->constrained('users');
            $table->string('status')->default('unread'); // unread, read, opened
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['document_id', 'target_branch_id']);
            $table->index(['target_branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_distributions');
    }
};
