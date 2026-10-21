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
        if (Schema::hasTable('supplier_consignment_deductions')) {
            Schema::table('supplier_consignment_deductions', function (Blueprint $table) {
                if (!Schema::hasColumn('supplier_consignment_deductions', 'system_stock')) {
                    $table->integer('system_stock')->nullable()->after('quantity');
                }
                if (!Schema::hasColumn('supplier_consignment_deductions', 'actual_stock')) {
                    $table->integer('actual_stock')->nullable()->after('system_stock');
                }
                if (!Schema::hasColumn('supplier_consignment_deductions', 'difference_qty')) {
                    $table->integer('difference_qty')->nullable()->after('actual_stock');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('supplier_consignment_deductions')) {
            Schema::table('supplier_consignment_deductions', function (Blueprint $table) {
                if (Schema::hasColumn('supplier_consignment_deductions', 'difference_qty')) {
                    $table->dropColumn('difference_qty');
                }
                if (Schema::hasColumn('supplier_consignment_deductions', 'actual_stock')) {
                    $table->dropColumn('actual_stock');
                }
                if (Schema::hasColumn('supplier_consignment_deductions', 'system_stock')) {
                    $table->dropColumn('system_stock');
                }
            });
        }
    }
};
