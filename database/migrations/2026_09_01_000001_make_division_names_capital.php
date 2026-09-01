<?php

use App\Models\Division;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $divisions = DB::table('divisions')->get();
        foreach ($divisions as $division) {
            DB::table('divisions')
                ->where('id', $division->id)
                ->update(['name' => Division::formatCapitalName($division->name)]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
