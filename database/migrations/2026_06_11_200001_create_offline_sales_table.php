<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id');                    // kasir yang melayani
            $table->unsignedBigInteger('customer_id')->nullable();     // opsional: pelanggan terdaftar

            $table->string('sale_number')->unique();                   // contoh: OS-20240611-001
            $table->string('status')->default('completed');            // completed | cancelled

            // Info pembeli manual
            $table->string('buyer_name')->nullable();
            $table->string('buyer_phone')->nullable();

            // Pembayaran
            $table->string('payment_method')->default('tunai');        // tunai | transfer | qris | kartu
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('grand_total', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);        // uang yang dibayarkan
            $table->decimal('change_amount', 15, 2)->default(0);      // kembalian

            $table->text('notes')->nullable();
            $table->timestamp('sold_at')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('set null');
        });

        Schema::create('offline_sale_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('offline_sale_id');
            $table->unsignedBigInteger('master_product_id')->nullable(); // null jika produk sudah dihapus
            $table->string('product_name');
            $table->string('sku')->nullable();
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            $table->timestamps();

            $table->foreign('offline_sale_id')->references('id')->on('offline_sales')->onDelete('cascade');
            $table->foreign('master_product_id')->references('id')->on('master_products')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sale_items');
        Schema::dropIfExists('offline_sales');
    }
};
