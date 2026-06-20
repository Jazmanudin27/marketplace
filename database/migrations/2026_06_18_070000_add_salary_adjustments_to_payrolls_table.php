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
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('salary_adjustment_addition', 15, 2)->default(0)->after('other_deductions');
            $table->decimal('salary_adjustment_deduction', 15, 2)->default(0)->after('salary_adjustment_addition');
            $table->text('salary_adjustment_notes')->nullable()->after('salary_adjustment_deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['salary_adjustment_addition', 'salary_adjustment_deduction', 'salary_adjustment_notes']);
        });
    }
};
