<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offline_sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('offline_sale_id')->constrained('offline_sales')->onDelete('cascade');
            $table->string('return_number', 50)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->decimal('total_return_amount', 15, 2)->default(0);
            $table->string('refund_method', 50)->default('cash'); // cash, bank, customer_balance, no_refund
            $table->string('payment_destination', 100)->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('offline_sale_return_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offline_sale_return_id')->constrained('offline_sale_returns')->onDelete('cascade');
            $table->foreignId('offline_sale_item_id')->constrained('offline_sale_items')->onDelete('cascade');
            $table->foreignId('master_product_id')->nullable()->constrained('master_products')->onDelete('set null');
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offline_sale_return_items');
        Schema::dropIfExists('offline_sale_returns');
    }
};
