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
        if (!Schema::hasColumn('orders', 'fee_platform_amount')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('fee_platform_amount', 12, 2)->default(0)->after('marketplace_fee')->comment('Biaya Platform (Shopee/TikTok)');
                $table->decimal('fee_free_shipping_amount', 12, 2)->default(0)->after('fee_platform_amount')->comment('Biaya Gratis Ongkir XTRA');
                $table->decimal('fee_service_amount', 12, 2)->default(0)->after('fee_free_shipping_amount')->comment('Biaya Layanan');
                $table->decimal('fee_promo_amount', 12, 2)->default(0)->after('fee_service_amount')->comment('Biaya Promosi (Voucher/Affiliate)');
                $table->decimal('fee_other_amount', 12, 2)->default(0)->after('fee_promo_amount')->comment('Biaya Lainnya (Proteksi/Coins/Asuransi)');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'fee_platform_amount',
                'fee_free_shipping_amount',
                'fee_service_amount',
                'fee_promo_amount',
                'fee_other_amount',
            ]);
        });
    }
};
