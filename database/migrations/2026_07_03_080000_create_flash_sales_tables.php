<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('store_id')->nullable(); // null = semua toko / global
            $table->string('title');
            $table->string('banner_url')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->enum('status', ['DRAFT', 'UPCOMING', 'ACTIVE', 'ENDED', 'CANCELLED'])->default('DRAFT');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('store_id')->references('id')->on('stores')->onDelete('set null');
        });

        Schema::create('flash_sale_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flash_sale_id')->constrained('flash_sales')->onDelete('cascade');
            $table->foreignId('master_product_id')->constrained('master_products')->onDelete('cascade');
            $table->decimal('original_price', 15, 2);
            $table->decimal('flash_sale_price', 15, 2);
            $table->decimal('discount_percentage', 5, 2)->default(0);
            $table->integer('quota')->default(10);
            $table->integer('sold_count')->default(0);
            $table->integer('max_purchase_per_user')->default(0); // 0 = unlimited
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_items');
        Schema::dropIfExists('flash_sales');
    }
};
