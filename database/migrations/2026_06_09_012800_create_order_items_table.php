<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('marketplace_product_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('master_product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('sku', 100)->nullable();
            $table->string('product_name');
            $table->string('product_image')->nullable();
            $table->decimal('price', 15, 2);
            $table->integer('quantity');
            $table->decimal('total_price', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
