<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tiered_discounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->unsignedBigInteger('master_product_id')->nullable(); // null = berlaku untuk semua produk
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('master_product_id')->references('id')->on('master_products')->onDelete('set null');
        });

        Schema::create('tiered_discount_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tiered_discount_id')->constrained('tiered_discounts')->onDelete('cascade');
            $table->integer('min_qty');
            $table->integer('max_qty')->nullable(); // null = ke atas (misal >= 10)
            $table->enum('discount_type', ['percentage', 'fixed_amount'])->default('percentage');
            $table->decimal('discount_value', 15, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tiered_discount_tiers');
        Schema::dropIfExists('tiered_discounts');
    }
};
