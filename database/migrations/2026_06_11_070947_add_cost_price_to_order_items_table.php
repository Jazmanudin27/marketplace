<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'cost_price')) {
                $table->decimal('cost_price', 15, 2)->nullable()->default(0)->after('total_price')
                      ->comment('HPP per unit saat pesanan dibuat (snapshot)');
            }
            if (!Schema::hasColumn('order_items', 'hpp_subtotal')) {
                $table->decimal('hpp_subtotal', 15, 2)->nullable()->default(0)->after('cost_price')
                      ->comment('cost_price × quantity');
            }
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['cost_price', 'hpp_subtotal']);
        });
    }
};
