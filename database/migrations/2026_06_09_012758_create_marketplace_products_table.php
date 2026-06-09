<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->foreignId('master_product_id')->nullable()->constrained()->onDelete('set null');
            $table->string('marketplace_product_id', 100); // ID produk dari marketplace
            $table->string('marketplace_sku', 100)->nullable(); // SKU yang tertulis di marketplace
            $table->string('name');
            $table->decimal('price', 15, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->boolean('sync_stock')->default(true); // Apakah stok disinkronkan
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_products');
    }
};
