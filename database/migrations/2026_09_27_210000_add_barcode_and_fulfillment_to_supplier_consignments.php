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
        // 1. Tambah qty_sold pada supplier_consignment_items untuk melacak jumlah barang titipan yang terjual/dikemas
        Schema::table('supplier_consignment_items', function (Blueprint $table) {
            if (!Schema::hasColumn('supplier_consignment_items', 'qty_sold')) {
                $table->integer('qty_sold')->default(0)->after('qty_received');
            }
        });

        // 2. Tambah relasi ke supplier_consignment_item dan sumber pemenuhan pada order_items
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'supplier_consignment_item_id')) {
                $table->unsignedBigInteger('supplier_consignment_item_id')->nullable()->after('master_product_id');
                $table->foreign('supplier_consignment_item_id', 'fk_oi_consignment_item')
                    ->references('id')
                    ->on('supplier_consignment_items')
                    ->onDelete('set null');
            }
            if (!Schema::hasColumn('order_items', 'fulfillment_source')) {
                $table->string('fulfillment_source', 50)->default('warehouse')->after('supplier_consignment_item_id'); // warehouse, consignment, spk
            }
        });

        // 3. Buat tabel audit log potongan stok konsinyasi saat dikemas
        if (!Schema::hasTable('supplier_consignment_deductions')) {
            Schema::create('supplier_consignment_deductions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('supplier_consignment_item_id');
                $table->unsignedBigInteger('order_id');
                $table->unsignedBigInteger('order_item_id')->nullable();
                $table->integer('quantity')->default(1);
                $table->string('scanned_barcode')->nullable();
                $table->unsignedBigInteger('user_id')->nullable();
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
                $table->foreign('supplier_consignment_item_id', 'fk_scd_cons_item_id')
                    ->references('id')
                    ->on('supplier_consignment_items')
                    ->onDelete('cascade');
                $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
                $table->foreign('order_item_id')->references('id')->on('order_items')->onDelete('set null');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');

                $table->index(['tenant_id', 'supplier_consignment_item_id'], 'idx_scd_tenant_cons_item');
                $table->index(['tenant_id', 'order_id'], 'idx_scd_tenant_order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplier_consignment_deductions');

        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'supplier_consignment_item_id')) {
                $table->dropForeign('fk_oi_consignment_item');
                $table->dropColumn('supplier_consignment_item_id');
            }
            if (Schema::hasColumn('order_items', 'fulfillment_source')) {
                $table->dropColumn('fulfillment_source');
            }
        });

        Schema::table('supplier_consignment_items', function (Blueprint $table) {
            if (Schema::hasColumn('supplier_consignment_items', 'qty_sold')) {
                $table->dropColumn('qty_sold');
            }
        });
    }
};
