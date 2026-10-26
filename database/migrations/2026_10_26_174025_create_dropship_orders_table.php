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
        if (!Schema::hasTable('dropship_orders')) {
            Schema::create('dropship_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
                $table->string('order_number', 50)->unique();
                $table->string('channel_type', 30)->default('dropship')->comment('dropship, lazada');
                $table->date('order_date');
                $table->string('customer_name', 150)->comment('Nama Pelanggan input manual');
                $table->string('customer_phone', 50)->nullable();
                $table->text('notes')->nullable()->comment('Keterangan');
                $table->decimal('total_amount', 15, 2)->default(0);
                $table->integer('total_qty')->default(0);
                $table->string('status', 30)->default('completed')->comment('draft, pending, completed, cancelled');
                $table->timestamps();

                $table->index(['tenant_id', 'order_date']);
                $table->index(['tenant_id', 'channel_type']);
            });
        }

        if (!Schema::hasTable('dropship_order_items')) {
            Schema::create('dropship_order_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('dropship_order_id')->constrained('dropship_orders')->cascadeOnDelete();
                $table->foreignId('master_product_id')->nullable()->constrained('master_products')->nullOnDelete();
                $table->string('product_name', 255);
                $table->string('sku', 100)->nullable();
                $table->integer('quantity')->default(1);
                $table->decimal('unit_price', 15, 2)->default(0);
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->timestamps();

                $table->index(['dropship_order_id', 'master_product_id']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dropship_order_items');
        Schema::dropIfExists('dropship_orders');
    }
};
