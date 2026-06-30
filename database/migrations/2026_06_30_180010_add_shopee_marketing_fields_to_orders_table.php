<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('voucher_code')->nullable()->after('tiktok_live_session_id')
                ->comment('Kode voucher diskon yang digunakan pembeli');
            $table->string('shopee_utm_keyword')->nullable()->after('voucher_code')
                ->comment('Parameter UTM / Sub-ID Shopee Affiliate untuk tracking influencer');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['voucher_code', 'shopee_utm_keyword']);
        });
    }
};
