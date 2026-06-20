<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create allowance_types table
        Schema::create('allowance_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->timestamps();

            $table->index('tenant_id');
        });

        // 2. Create employee_allowances table
        Schema::create('employee_allowances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('allowance_type_id');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('employee_id');
            $table->index('allowance_type_id');

            $table->foreign('employee_id')->references('id')->on('employees')->onDelete('cascade');
            $table->foreign('allowance_type_id')->references('id')->on('allowance_types')->onDelete('cascade');
        });

        // 3. Create payroll_allowances table
        Schema::create('payroll_allowances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('payroll_id');
            $table->string('name');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            $table->index('tenant_id');
            $table->index('payroll_id');

            $table->foreign('payroll_id')->references('id')->on('payrolls')->onDelete('cascade');
        });

        // 4. Drop obsolete columns from employees
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['allowance_meal', 'allowance_transport', 'allowance_position', 'allowance_other']);
        });

        // 5. Drop obsolete columns from payrolls
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn(['allowance_meal', 'allowance_transport', 'allowance_position', 'allowance_other']);
        });
    }

    public function down(): void
    {
        // Re-add columns to payrolls
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('allowance_meal', 15, 2)->default(0)->after('allowance');
            $table->decimal('allowance_transport', 15, 2)->default(0)->after('allowance_meal');
            $table->decimal('allowance_position', 15, 2)->default(0)->after('allowance_transport');
            $table->decimal('allowance_other', 15, 2)->default(0)->after('allowance_position');
        });

        // Re-add columns to employees
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('allowance_meal', 15, 2)->default(0)->after('basic_salary');
            $table->decimal('allowance_transport', 15, 2)->default(0)->after('allowance_meal');
            $table->decimal('allowance_position', 15, 2)->default(0)->after('allowance_transport');
            $table->decimal('allowance_other', 15, 2)->default(0)->after('allowance_position');
        });

        Schema::dropIfExists('payroll_allowances');
        Schema::dropIfExists('employee_allowances');
        Schema::dropIfExists('allowance_types');
    }
};
