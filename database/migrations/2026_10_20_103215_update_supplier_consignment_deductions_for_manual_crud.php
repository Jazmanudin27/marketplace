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
        try {
            \Illuminate\Support\Facades\DB::statement("ALTER TABLE `supplier_consignment_deductions` MODIFY `order_id` BIGINT UNSIGNED NULL");
        } catch (\Throwable $e) {
            try {
                Schema::table('supplier_consignment_deductions', function (Blueprint $table) {
                    $table->unsignedBigInteger('order_id')->nullable()->change();
                });
            } catch (\Throwable $e2) {}
        }

        Schema::table('supplier_consignment_deductions', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_consignment_deductions', 'reason')) {
                $table->string('reason', 50)->default('unscanned_order')->after('scanned_barcode');
            }
            if (!Schema::hasColumn('supplier_consignment_deductions', 'reference_no')) {
                $table->string('reference_no', 100)->nullable()->after('reason');
            }
            if (!Schema::hasColumn('supplier_consignment_deductions', 'notes')) {
                $table->text('notes')->nullable()->after('reference_no');
            }
            if (!Schema::hasColumn('supplier_consignment_deductions', 'deduction_date')) {
                $table->dateTime('deduction_date')->nullable()->after('notes');
            }
            if (!Schema::hasColumn('supplier_consignment_deductions', 'deduct_master_stock')) {
                $table->boolean('deduct_master_stock')->default(false)->after('deduction_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('supplier_consignment_deductions', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_consignment_deductions', 'deduct_master_stock')) {
                $table->dropColumn('deduct_master_stock');
            }
            if (Schema::hasColumn('supplier_consignment_deductions', 'deduction_date')) {
                $table->dropColumn('deduction_date');
            }
            if (Schema::hasColumn('supplier_consignment_deductions', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('supplier_consignment_deductions', 'reference_no')) {
                $table->dropColumn('reference_no');
            }
            if (Schema::hasColumn('supplier_consignment_deductions', 'reason')) {
                $table->dropColumn('reason');
            }
        });
    }
};
