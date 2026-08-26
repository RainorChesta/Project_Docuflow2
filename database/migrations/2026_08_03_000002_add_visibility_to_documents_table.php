<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add standalone index on division_id first in a separate query so that
        // the foreign key constraint remains satisfied when dropping the composite index.
        Schema::table('documents', function (Blueprint $table) {
            $table->index('division_id');
        });

        // 2. Add visibility column, drop legacy composite index, and add new composite index.
        Schema::table('documents', function (Blueprint $table) {
            // visibility: general (public) | division (division-specific) | personal (private to owner)
            $table->string('visibility')->default('division')->after('title');

            // division_id becomes nullable: general/personal documents have no division
            $table->foreignId('division_id')->nullable()->change();

            // Keep legacy is_public as a derived convenience flag
            $table->dropIndex(['division_id', 'is_public']);
            $table->index(['visibility', 'division_id']);
        });

        // Backfill: docs previously marked public become general scope.
        DB::table('documents')->where('is_public', true)->update(['visibility' => 'general']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('visibility');
            $table->foreignId('division_id')->nullable(false)->change();
            $table->dropIndex(['visibility', 'division_id']);
            $table->index(['division_id', 'is_public']);
            $table->dropIndex(['division_id']);
        });
    }
};
