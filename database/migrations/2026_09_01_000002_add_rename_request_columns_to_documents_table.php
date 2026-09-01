<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('pending_title')->nullable()->after('title');
            $table->foreignId('rename_requested_by_id')->nullable()
                ->after('pending_title')
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('rename_requested_at')->nullable()->after('rename_requested_by_id');
            $table->text('rename_request_notes')->nullable()->after('rename_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropForeign(['rename_requested_by_id']);
            $table->dropColumn([
                'pending_title',
                'rename_requested_by_id',
                'rename_requested_at',
                'rename_request_notes',
            ]);
        });
    }
};
