<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('document_templates', function (Blueprint $table) {
            // Drop old category FK
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');

            // Add document_type_id FK — sync templates with existing document types
            $table->foreignId('document_type_id')
                ->after('file_mime')
                ->constrained('document_types')
                ->cascadeOnDelete();
        });

        // Drop the now-unused template_categories table
        Schema::dropIfExists('template_categories');
    }

    public function down(): void
    {
        Schema::create('template_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->timestamps();
        });

        Schema::table('document_templates', function (Blueprint $table) {
            $table->dropForeign(['document_type_id']);
            $table->dropColumn('document_type_id');
            $table->foreignId('category_id')->after('file_mime')->constrained('template_categories')->cascadeOnDelete();
        });
    }
};
