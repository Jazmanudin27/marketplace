<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('order_marketplace_id', 100); // ID Pesanan dari marketplace
            $table->string('invoice_number', 100)->nullable();
            $table->string('order_status', 100);
            // Status: UNPAID, READY_TO_SHIP, SHIPPED, DELIVERED, CANCELLED, RETURN
            $table->string('buyer_name')->nullable();
            $table->string('buyer_phone', 50)->nullable();
            $table->text('shipping_address')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('shipping_fee', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('marketplace_fee', 15, 2)->default(0); // Komisi marketplace
            $table->decimal('net_amount', 15, 2)->default(0);       // Pendapatan bersih
            $table->string('courier', 100)->nullable();             // J&T, JNE, SiCepat
            $table->string('tracking_number', 100)->nullable();     // Nomor resi
            $table->timestamp('order_date');
            $table->timestamps();
            $table->index(['tenant_id', 'order_status']);
            $table->index(['tenant_id', 'order_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
