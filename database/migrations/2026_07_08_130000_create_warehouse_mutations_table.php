<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create warehouse_mutations table
        Schema::create('warehouse_mutations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->string('mutation_number')->unique(); // WMI-YYYYMMDD-XXXX or WMO-YYYYMMDD-XXXX
            $table->enum('type', ['in', 'out']);
            $table->unsignedBigInteger('goods_receipt_id')->nullable(); // if from purchase receipt approval
            $table->unsignedBigInteger('from_department_id')->nullable(); // source department
            $table->unsignedBigInteger('to_department_id')->nullable(); // destination department
            $table->date('mutation_date');
            $table->string('status')->default('approved'); // pending, approved
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->onDelete('set null');
            $table->foreign('from_department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('to_department_id')->references('id')->on('departments')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // 2. Create warehouse_mutation_items table
        Schema::create('warehouse_mutation_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('warehouse_mutation_id');
            $table->unsignedBigInteger('inventory_item_id');
            $table->integer('quantity');
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('warehouse_mutation_id')->references('id')->on('warehouse_mutations')->onDelete('cascade');
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->onDelete('cascade');
        });

        // 3. Add warehouse_mutation_id to stock_movements
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->unsignedBigInteger('warehouse_mutation_id')->nullable()->after('goods_receipt_id');
            $table->foreign('warehouse_mutation_id')->references('id')->on('warehouse_mutations')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table) {
            $table->dropForeign(['warehouse_mutation_id']);
            $table->dropColumn('warehouse_mutation_id');
        });

        Schema::dropIfExists('warehouse_mutation_items');
        Schema::dropIfExists('warehouse_mutations');
    }
};
