<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->time('schedule_clock_in')->default('08:00:00')->after('salary_type');
            $table->time('schedule_clock_out')->default('17:00:00')->after('schedule_clock_in');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['schedule_clock_in', 'schedule_clock_out']);
        });
    }
};
