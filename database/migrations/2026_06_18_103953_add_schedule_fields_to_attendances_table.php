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
        Schema::table('attendances', function (Blueprint $table) {
            $table->time('schedule_clock_in')->nullable()->after('clock_out');
            $table->time('schedule_clock_out')->nullable()->after('schedule_clock_in');
            $table->boolean('schedule_is_off')->default(false)->after('schedule_clock_out');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['schedule_clock_in', 'schedule_clock_out', 'schedule_is_off']);
        });
    }
};
