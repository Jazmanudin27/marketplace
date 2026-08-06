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
        Schema::table('master_products', function (Blueprint $table) {
            if (!Schema::hasColumn('master_products', 'shopee_price')) {
                $table->decimal('shopee_price', 15, 2)->nullable()->after('reseller_price');
            }
            if (!Schema::hasColumn('master_products', 'tiktok_price')) {
                $table->decimal('tiktok_price', 15, 2)->nullable()->after('shopee_price');
            }
            if (!Schema::hasColumn('master_products', 'lazada_price')) {
                $table->decimal('lazada_price', 15, 2)->nullable()->after('tiktok_price');
            }
            if (!Schema::hasColumn('master_products', 'shopee_dropship_price')) {
                $table->decimal('shopee_dropship_price', 15, 2)->nullable()->after('lazada_price');
            }
            if (!Schema::hasColumn('master_products', 'tiktok_dropship_price')) {
                $table->decimal('tiktok_dropship_price', 15, 2)->nullable()->after('shopee_dropship_price');
            }
            if (!Schema::hasColumn('master_products', 'lazada_dropship_price')) {
                $table->decimal('lazada_dropship_price', 15, 2)->nullable()->after('tiktok_dropship_price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $cols = ['shopee_price', 'tiktok_price', 'lazada_price', 'shopee_dropship_price', 'tiktok_dropship_price', 'lazada_dropship_price'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('master_products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
