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
        // 1. Create departments table
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('name');
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Create materials table
        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('type'); // bahan / kemasan
            $table->string('unit')->default('PCS');
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 3. Create inventory_items table
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('sku')->nullable();
            $table->string('name');
            $table->string('unit')->default('PCS');
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);
            $table->decimal('cost_price', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 4. Modify purchase_orders to add department_id
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('department_id')->nullable()->after('supplier_id');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });

        // 5. Modify purchase_order_items to add material_id, inventory_item_id and make master_product_id nullable
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('master_product_id')->nullable()->change();
            $table->unsignedBigInteger('material_id')->nullable()->after('master_product_id');
            $table->unsignedBigInteger('inventory_item_id')->nullable()->after('material_id');

            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
        });

        // 6. Modify stock_movements to add material_id, inventory_item_id, department_id and make master_product_id nullable
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('master_product_id')->nullable()->change();
            $table->unsignedBigInteger('material_id')->nullable()->after('master_product_id');
            $table->unsignedBigInteger('inventory_item_id')->nullable()->after('material_id');
            $table->unsignedBigInteger('department_id')->nullable()->after('inventory_item_id');

            $table->foreign('material_id')->references('id')->on('materials')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
            $table->foreign('department_id')->references('id')->on('departments')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropForeign(['inventory_item_id']);
            $table->dropForeign(['material_id']);
            $table->dropColumn(['department_id', 'inventory_item_id', 'material_id']);
            $table->unsignedBigInteger('master_product_id')->nullable(false)->change();
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropForeign(['inventory_item_id']);
            $table->dropForeign(['material_id']);
            $table->dropColumn(['inventory_item_id', 'material_id']);
            $table->unsignedBigInteger('master_product_id')->nullable(false)->change();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropForeign(['department_id']);
            $table->dropColumn('department_id');
        });

        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('departments');
    }
};
