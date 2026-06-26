<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Batas waktu pengiriman dari marketplace (Shopee: ship_by_date, TikTok: ship_deadline_time)
            $table->timestamp('ship_before_date')->nullable()->after('order_date');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('ship_before_date');
        });
    }
};

