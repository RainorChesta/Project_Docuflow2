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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique();
            $table->string('title');
            $table->foreignId('division_id')->constrained();
            $table->foreignId('owner_id')->constrained('users');
            $table->boolean('is_public')->default(false);
            $table->unsignedBigInteger('current_version_id')->nullable();
            $table->timestamps();

            $table->index(['division_id', 'is_public']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
