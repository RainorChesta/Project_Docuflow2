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
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->timestamp('notified_at')->nullable()->after('requested_at');
        });

        // Backfill existing records so all past requests remain marked as notified
        DB::table('signature_requests')->whereNull('notified_at')->update([
            'notified_at' => DB::raw('requested_at')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->dropColumn('notified_at');
        });
    }
};
