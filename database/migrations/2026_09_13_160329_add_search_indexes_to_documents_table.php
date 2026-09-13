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
            $table->index('title', 'idx_documents_title');
            $table->index(['company_id', 'branch_id', 'division_id'], 'idx_documents_company_branch_div');
            $table->index(['branch_id', 'division_id'], 'idx_documents_branch_div');
            $table->index(['document_type_id', 'format_choice'], 'idx_documents_type_format');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex('idx_documents_title');
            $table->dropIndex('idx_documents_company_branch_div');
            $table->dropIndex('idx_documents_branch_div');
            $table->dropIndex('idx_documents_type_format');
        });
    }
};
