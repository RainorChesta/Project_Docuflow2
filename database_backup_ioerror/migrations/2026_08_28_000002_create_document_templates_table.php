<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('file_path');
            $table->string('file_original_name');
            $table->string('file_mime')->default('application/vnd.openxmlformats-officedocument.wordprocessingml.document');
            $table->foreignId('category_id')->constrained('template_categories')->cascadeOnDelete();
            $table->string('status', 20)->default('active'); // active | archived
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
