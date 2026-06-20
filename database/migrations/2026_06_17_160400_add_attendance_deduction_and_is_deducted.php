<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->boolean('is_deducted')->default(false)->after('status');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('attendance_deduction', 15, 2)->default(0)->after('cash_advance_deduction');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('attendance_deduction');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn('is_deducted');
        });
    }
};
