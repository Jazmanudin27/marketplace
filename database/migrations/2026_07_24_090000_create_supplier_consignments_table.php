<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabel Header Penitipan Barang Konsinyasi
        Schema::create('supplier_consignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('reference_number')->unique(); // KNS-YYYYMMDD-XXXX
            $table->date('consignment_date');
            $table->enum('status', ['pending', 'approved', 'cancelled'])->default('pending');
            $table->integer('total_qty_received')->default(0);
            $table->decimal('total_amount_hpp', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'supplier_id']);
        });

        // 2. Tabel Item Detail Penitipan Barang
        Schema::create('supplier_consignment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('supplier_consignment_id');
            $table->unsignedBigInteger('master_product_id');
            $table->integer('qty_received')->default(0);
            $table->decimal('unit_cost_price', 15, 2)->default(0); // HPP / Harga Titip (misal 80rb)
            $table->decimal('unit_selling_price', 15, 2)->default(0); // Harga Jual (misal 100rb)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('supplier_consignment_id')->references('id')->on('supplier_consignments')->onDelete('cascade');
            $table->foreign('master_product_id')->references('id')->on('master_products')->onDelete('cascade');
        });

        // 3. Tabel Header Setoran ke Supplier
        Schema::create('supplier_consignment_settlements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('supplier_id');
            $table->string('settlement_number')->unique(); // STR-YYYYMMDD-XXXX
            $table->date('settlement_date');
            $table->integer('total_qty_settled')->default(0);
            $table->decimal('total_amount_paid', 15, 2)->default(0); // Nominal yang disetor ke supplier
            $table->string('payment_method')->default('transfer'); // cash, transfer
            $table->unsignedBigInteger('bank_account_id')->nullable();
            $table->string('reference_number')->nullable();
            $table->enum('status', ['approved', 'cancelled'])->default('approved');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // 4. Tabel Detail Item Setoran
        Schema::create('supplier_consignment_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('settlement_id');
            $table->unsignedBigInteger('supplier_consignment_item_id');
            $table->unsignedBigInteger('master_product_id');
            $table->integer('qty_settled')->default(0);
            $table->decimal('unit_cost_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('settlement_id', 'fk_scs_item_settlement_id')->references('id')->on('supplier_consignment_settlements')->onDelete('cascade');
            $table->foreign('supplier_consignment_item_id', 'fk_scs_item_cons_item_id')->references('id')->on('supplier_consignment_items')->onDelete('cascade');
            $table->foreign('master_product_id', 'fk_scs_item_product_id')->references('id')->on('master_products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_consignment_settlement_items');
        Schema::dropIfExists('supplier_consignment_settlements');
        Schema::dropIfExists('supplier_consignment_items');
        Schema::dropIfExists('supplier_consignments');
    }
};
