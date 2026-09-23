<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->foreignId('corporate_soft_file_id')
                ->nullable()
                ->after('template_id')
                ->constrained('corporate_soft_files')
                ->nullOnDelete();
        });

        Schema::table('document_templates', function (Blueprint $table) {
            $table->foreignId('corporate_soft_file_id')
                ->nullable()
                ->after('file_path')
                ->constrained('corporate_soft_files')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['corporate_soft_file_id']);
            $table->dropColumn('corporate_soft_file_id');
        });

        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropForeign(['corporate_soft_file_id']);
            $table->dropColumn('corporate_soft_file_id');
        });
    }
};
