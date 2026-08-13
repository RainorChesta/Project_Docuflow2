<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('general_access')->default('restricted');
            $table->string('link_role')->nullable();
            $table->string('share_token')->nullable()->unique();
        });

        // Backfill: give every existing document a share token.
        DB::table('documents')->select('id')->orderBy('id')->chunkById(500, function ($documents) {
            foreach ($documents as $document) {
                DB::table('documents')
                    ->where('id', $document->id)
                    ->update(['share_token' => Str::random(32)]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropUnique(['share_token']);
            $table->dropColumn(['general_access', 'link_role', 'share_token']);
        });
    }
};