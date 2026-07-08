<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel retur pembelian (header)
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('return_number')->unique(); // RTN-YYYYMMDD-XXXX
            $table->date('return_date');
            $table->string('reason'); // cacat, salah kirim, kelebihan, dll
            $table->enum('status', ['draft', 'approved', 'sent'])->default('draft');
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // 2. Item retur pembelian
        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_return_id');
            $table->unsignedBigInteger('inventory_item_id')->nullable();
            $table->unsignedBigInteger('master_product_id')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('set null');
            $table->foreign('master_product_id')->references('id')->on('master_products')->onDelete('set null');
        });

        // 3. Tabel transfer stok antar departemen (header)
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('transfer_number')->unique(); // TRF-YYYYMMDD-XXXX
            $table->unsignedBigInteger('from_department_id')->nullable();
            $table->unsignedBigInteger('to_department_id')->nullable();
            $table->date('transfer_date');
            $table->enum('status', ['draft', 'confirmed'])->default('draft');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('from_department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('to_department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('set null');
        });

        // 4. Item transfer stok
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('stock_transfer_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
        });

        // 5. Tambah kolom purchase_return_id ke stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('purchase_return_id')->nullable()->after('purchase_order_id');
            $table->unsignedBigInteger('stock_transfer_id')->nullable()->after('purchase_return_id');
            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->onDelete('set null');
            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['purchase_return_id']);
            $table->dropForeign(['stock_transfer_id']);
            $table->dropColumn(['purchase_return_id', 'stock_transfer_id']);
        });

        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
