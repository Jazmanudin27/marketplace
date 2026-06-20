<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Drop legacy schedule columns — now handled by employee_schedules table.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'schedule_clock_in',
                'schedule_clock_out',
                'schedule_clock_in_saturday',
                'schedule_clock_out_saturday',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->time('schedule_clock_in')->default('08:00:00')->nullable()->after('salary_type');
            $table->time('schedule_clock_out')->default('16:00:00')->nullable()->after('schedule_clock_in');
            $table->time('schedule_clock_in_saturday')->default('07:00:00')->nullable()->after('schedule_clock_out');
            $table->time('schedule_clock_out_saturday')->default('12:00:00')->nullable()->after('schedule_clock_in_saturday');
        });
    }
};
