<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. product_recipes
        Schema::create('product_recipes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('master_product_id');
            $table->string('name');
            $table->integer('batch_qty')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('master_product_id')->references('id')->on('master_products')->onDelete('cascade');
        });

        // 2. product_recipe_items
        Schema::create('product_recipe_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_recipe_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->decimal('quantity', 15, 4);
            $table->timestamps();

            $table->foreign('product_recipe_id')->references('id')->on('product_recipes')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
        });

        // 3. product_recipe_labors
        Schema::create('product_recipe_labors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_recipe_id');
            $table->string('service_name');
            $table->decimal('default_cost', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('product_recipe_id')->references('id')->on('product_recipes')->onDelete('cascade');
        });

        // 4. production_actual_labors
        Schema::create('production_actual_labors', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->string('service_name');
            $table->decimal('actual_cost', 15, 2)->default(0);
            $table->timestamps();

            $table->foreign('production_order_id')->references('id')->on('production_orders')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_actual_labors');
        Schema::dropIfExists('product_recipe_labors');
        Schema::dropIfExists('product_recipe_items');
        Schema::dropIfExists('product_recipes');
    }
};
