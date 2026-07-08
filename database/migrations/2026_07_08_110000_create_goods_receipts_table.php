<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Header Penerimaan Barang Langsung (tanpa PO)
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('supplier_id')->nullable(); // bisa tanpa supplier tetap
            $table->unsignedBigInteger('department_id')->nullable();
            $table->string('receipt_number')->unique(); // GR-YYYYMMDD-XXXX
            $table->date('receipt_date');
            $table->string('source')->default('direct'); // direct, emergency, walk_in
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('set null');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Item Penerimaan Langsung
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_receipt_id');
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('set null');
            $table->foreign('master_product_id')->references('id')->on('master_products')->onDelete('set null');
        });

        // Tambah goods_receipt_id ke stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('goods_receipt_id')->nullable()->after('stock_transfer_id');
            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['goods_receipt_id']);
            $table->dropColumn('goods_receipt_id');
        });
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
