<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->time('schedule_clock_in_saturday')->default('07:00:00')->after('schedule_clock_out');
            $table->time('schedule_clock_out_saturday')->default('12:00:00')->after('schedule_clock_in_saturday');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['schedule_clock_in_saturday', 'schedule_clock_out_saturday']);
        });
    }
};
