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
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->integer('page_number')->default(1)->nullable()->after('is_used');
            $table->double('pos_x')->nullable()->after('page_number');
            $table->double('pos_y')->nullable()->after('pos_x');
            $table->double('width')->default(40.0)->nullable()->after('pos_y');
            $table->double('height')->default(25.0)->nullable()->after('width');
            $table->string('preset_position')->nullable()->after('height'); // e.g. bottom-right, bottom-left, top-right, custom
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signature_requests', function (Blueprint $table) {
            $table->dropColumn(['page_number', 'pos_x', 'pos_y', 'width', 'height', 'preset_position']);
        });
    }
};
