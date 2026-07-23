<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->index(['tenant_id', 'order_status', 'packing_status'], 'orders_ts_status_packing_idx');
            $table->index(['tenant_id', 'order_status', 'is_printed'], 'orders_ts_status_printed_idx');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_ts_status_packing_idx');
            $table->dropIndex('orders_ts_status_printed_idx');
        });
    }
};
