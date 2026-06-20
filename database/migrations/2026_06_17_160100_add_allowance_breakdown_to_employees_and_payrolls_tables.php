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
            $table->decimal('allowance_meal', 15, 2)->default(0)->after('basic_salary');
            $table->decimal('allowance_transport', 15, 2)->default(0)->after('allowance_meal');
            $table->decimal('allowance_position', 15, 2)->default(0)->after('allowance_transport');
            $table->decimal('allowance_other', 15, 2)->default(0)->after('allowance_position');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('allowance_meal', 15, 2)->default(0)->after('allowance');
            $table->decimal('allowance_transport', 15, 2)->default(0)->after('allowance_meal');
            $table->decimal('allowance_position', 15, 2)->default(0)->after('allowance_transport');
            $table->decimal('allowance_other', 15, 2)->default(0)->after('allowance_position');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['allowance_meal', 'allowance_transport', 'allowance_position', 'allowance_other']);
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['allowance_meal', 'allowance_transport', 'allowance_position', 'allowance_other']);
        });
    }
};
