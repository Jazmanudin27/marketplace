<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->integer('late_minutes')->default(0)->after('is_deducted');
            $table->decimal('late_penalty', 15, 2)->default(0)->after('late_minutes');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('late_deduction', 15, 2)->default(0)->after('attendance_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('late_deduction');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['late_minutes', 'late_penalty']);
        });
    }
};
