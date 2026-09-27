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
        Schema::table('corporate_soft_files', function (Blueprint $table) {
            $table->string('paper_size', 10)->default('f4')->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('corporate_soft_files', function (Blueprint $table) {
            $table->dropColumn('paper_size');
        });
    }
};
