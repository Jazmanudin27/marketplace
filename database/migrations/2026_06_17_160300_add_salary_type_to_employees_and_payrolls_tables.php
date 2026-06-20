<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->enum('salary_type', ['monthly', 'hourly'])->default('monthly')->after('is_active');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->enum('salary_type', ['monthly', 'hourly'])->default('monthly')->after('period');
            $table->decimal('hours_worked', 8, 2)->default(0)->after('basic_salary');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['salary_type', 'hours_worked']);
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('salary_type');
        });
    }
};
