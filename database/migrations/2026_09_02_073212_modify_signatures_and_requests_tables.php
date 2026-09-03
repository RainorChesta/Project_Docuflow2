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
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->renameColumn('signature_type', 'created_via');
            $table->string('type')->default('original')->after('user_id');
            $table->foreignId('company_id')->nullable()->after('type')->constrained()->nullOnDelete();
        });

        Schema::table('signature_requests', function (Blueprint $table) {
            $table->foreignId('requested_signature_id')->nullable()->after('target_user_id')->constrained('signatures')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->dropForeign(['requested_signature_id']);
            $table->dropColumn('requested_signature_id');
        });

        Schema::table('signatures', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn('company_id');
            $table->dropColumn('type');
            $table->renameColumn('created_via', 'signature_type');
            $table->unique('user_id');
        });
    }
};
