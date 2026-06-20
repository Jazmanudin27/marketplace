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
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('basic_salary', 15, 2)->default(0)->after('join_date');
            $table->decimal('allowance', 15, 2)->default(0)->after('basic_salary');
            $table->decimal('overtime_rate', 15, 2)->default(0)->after('allowance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['basic_salary', 'allowance', 'overtime_rate']);
        });
    }
};
