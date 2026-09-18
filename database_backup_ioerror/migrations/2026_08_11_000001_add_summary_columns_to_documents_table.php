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
            $table->longText('summary')->nullable()->after('title');
            $table->string('summary_status')->default('pending')->after('summary');
            $table->text('summary_error')->nullable()->after('summary_status');
            $table->timestamp('summary_started_at')->nullable()->after('summary_error');
            $table->timestamp('summary_completed_at')->nullable()->after('summary_started_at');

            $table->index('owner_id');
            $table->index(['owner_id', 'summary_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropIndex(['owner_id', 'summary_status']);
            $table->dropIndex(['owner_id']);
            $table->dropColumn([
                'summary', 'summary_status', 'summary_error',
                'summary_started_at', 'summary_completed_at',
            ]);
        });
    }
};
