<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Link-based access now always grants Viewer; the role is set per-user
     * in the share list, never at link generation time. Reset any existing
     * editor links to the new default.
     */
    public function up(): void
    {
        DB::table('documents')
            ->where('general_access', 'anyone_with_link')
            ->update(['link_role' => 'viewer']);
    }

    public function down(): void
    {
        // Irreversible data change; nothing to restore.
    }
};
