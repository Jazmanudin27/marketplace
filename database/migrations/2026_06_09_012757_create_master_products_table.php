<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('sku', 100);        // SKU utama ERP
            $table->string('name');
            $table->string('image_url')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('cost_price', 15, 2)->default(0); // Harga Modal
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0); // Safety Stock
            $table->string('unit', 50)->nullable(); // pcs, kg, box
            $table->string('category')->nullable();
            $table->string('brand')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['tenant_id', 'sku']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_products');
    }
};
