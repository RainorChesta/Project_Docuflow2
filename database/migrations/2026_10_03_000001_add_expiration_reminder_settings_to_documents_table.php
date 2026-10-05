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
        Schema::table('documents', function (Blueprint $table) {
            $table->unsignedSmallInteger('expiration_reminder_days')->nullable()->after('expiration_notif_status');
            $table->timestamp('expiration_notified_at')->nullable()->after('expiration_reminder_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['expiration_reminder_days', 'expiration_notified_at']);
        });
    }
};
