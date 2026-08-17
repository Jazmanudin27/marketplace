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
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'paid_at')) {
                $table->timestamp('paid_at')->nullable()->after('completed_at');
            }
            if (!Schema::hasColumn('orders', 'payment_method')) {
                $table->string('payment_method', 100)->nullable()->after('buyer_phone');
            }
            if (!Schema::hasColumn('orders', 'buyer_email')) {
                $table->string('buyer_email', 191)->nullable()->after('buyer_name');
            }
            if (!Schema::hasColumn('orders', 'buyer_message')) {
                $table->text('buyer_message')->nullable()->after('shipping_address');
            }
            if (!Schema::hasColumn('orders', 'seller_note')) {
                $table->text('seller_note')->nullable()->after('buyer_message');
            }
            if (!Schema::hasColumn('orders', 'package_id')) {
                $table->string('package_id', 100)->nullable()->after('tracking_number');
            }
        });

        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'seller_sku')) {
                $table->string('seller_sku', 191)->nullable()->after('sku');
            }
            if (!Schema::hasColumn('order_items', 'sku_id')) {
                $table->string('sku_id', 100)->nullable()->after('seller_sku');
            }
            if (!Schema::hasColumn('order_items', 'sku_name')) {
                $table->string('sku_name', 255)->nullable()->after('product_name');
            }
            if (!Schema::hasColumn('order_items', 'original_price')) {
                $table->decimal('original_price', 15, 2)->default(0)->after('price');
            }
            if (!Schema::hasColumn('order_items', 'seller_discount')) {
                $table->decimal('seller_discount', 15, 2)->default(0)->after('original_price');
            }
            if (!Schema::hasColumn('order_items', 'platform_discount')) {
                $table->decimal('platform_discount', 15, 2)->default(0)->after('seller_discount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['paid_at', 'payment_method', 'buyer_email', 'buyer_message', 'seller_note', 'package_id']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['seller_sku', 'sku_id', 'sku_name', 'original_price', 'seller_discount', 'platform_discount']);
        });
    }
};
