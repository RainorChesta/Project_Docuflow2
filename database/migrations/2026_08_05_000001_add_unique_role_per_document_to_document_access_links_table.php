<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforce one active share link per (document, role) at the DB level.
     * Existing duplicates are pruned (keep the oldest) before the index is added.
     */
    public function up(): void
    {
        // Prune duplicates: keep the oldest link per (document_id, role).
        $dupes = DB::table('document_access_links')
            ->select('document_id', 'role', DB::raw('MIN(id) as keep_id'))
            ->groupBy('document_id', 'role')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupes as $d) {
            DB::table('document_access_links')
                ->where('document_id', $d->document_id)
                ->where('role', $d->role)
                ->where('id', '!=', $d->keep_id)
                ->delete();
        }

        Schema::table('document_access_links', function (Blueprint $table) {
            $table->unique(['document_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::table('document_access_links', function (Blueprint $table) {
            $table->dropUnique(['document_id', 'role']);
        });
    }
};