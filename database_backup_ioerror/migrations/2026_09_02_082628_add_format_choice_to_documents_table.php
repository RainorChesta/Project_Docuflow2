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
            $table->enum('format_choice', ['lama', 'baru'])->default('baru')->after('document_number');
            $table->string('numbering_scheme')->default('new_format')->after('format_choice');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['format_choice', 'numbering_scheme']);
        });
    }
};
