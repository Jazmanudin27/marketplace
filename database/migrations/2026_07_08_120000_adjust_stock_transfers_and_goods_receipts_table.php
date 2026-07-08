<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop foreign key constraint on stock_movements first
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['stock_transfer_id']);
            $table->dropColumn('stock_transfer_id');
        });

        // 2. Now it is safe to drop the tables
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');

        // 3. Modify goods_receipts to support status, approval and PO link
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_order_id')->nullable()->after('supplier_id');
            $table->string('status')->default('pending')->after('source'); // pending, approved, cancelled
            $table->unsignedBigInteger('approved_by')->nullable()->after('created_by');
            $table->timestamp('approved_at')->nullable()->after('approved_by');

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('goods_receipts', function (Blueprint $table) {
            $table->dropForeign(['purchase_order_id']);
            $table->dropForeign(['approved_by']);
            $table->dropColumn(['purchase_order_id', 'status', 'approved_by', 'approved_at']);
        });

        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('stock_transfer_id')->nullable()->after('purchase_return_id');
            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('set null');
        });

        // Recreate stock_transfers tables if rolled back
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('transfer_number')->unique();
            $table->unsignedBigInteger('from_department_id')->nullable();
            $table->unsignedBigInteger('to_department_id')->nullable();
            $table->date('transfer_date');
            $table->string('status')->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }
};
