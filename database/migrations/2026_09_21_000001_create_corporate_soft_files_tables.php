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
        Schema::create('corporate_soft_files', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_original_name');
            $table->string('file_mime')->default('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('status')->default('active'); // active, archived
            $table->json('allowed_roles')->nullable(); // null or json array e.g. ["head", "staff", "direktur"]
            $table->boolean('is_all_companies')->default(true);
            $table->boolean('is_all_branches')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('company_corporate_soft_file', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporate_soft_file_id')->constrained('corporate_soft_files')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['corporate_soft_file_id', 'company_id'], 'comp_corp_sf_unique');
        });

        Schema::create('branch_corporate_soft_file', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporate_soft_file_id')->constrained('corporate_soft_files')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['corporate_soft_file_id', 'branch_id'], 'branch_corp_sf_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_corporate_soft_file');
        Schema::dropIfExists('company_corporate_soft_file');
        Schema::dropIfExists('corporate_soft_files');
    }
};
