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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('unit_kerja_id')->nullable()->after('division_id')->constrained('unit_kerjas')->nullOnDelete();
        });

        Schema::create('unit_kerja_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_kerja_id')->constrained('unit_kerjas')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'unit_kerja_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_kerja_user');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('unit_kerja_id');
        });
    }
};
