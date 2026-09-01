<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $companies = DB::table('companies')->get();
        foreach ($companies as $company) {
            DB::table('companies')
                ->where('id', $company->id)
                ->update(['name' => mb_strtoupper($company->name)]);
        }

        $branches = DB::table('branches')->get();
        foreach ($branches as $branch) {
            DB::table('branches')
                ->where('id', $branch->id)
                ->update(['name' => mb_strtoupper($branch->name)]);
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
