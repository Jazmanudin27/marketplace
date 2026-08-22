<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'order_marketplace_id')) {
            try {
                // 1. Rapikan whitespace pada order_marketplace_id
                DB::statement("UPDATE orders SET order_marketplace_id = TRIM(order_marketplace_id) WHERE order_marketplace_id IS NOT NULL AND order_marketplace_id != ''");

                // 2. Bersihkan duplikat berlebih jika ada sebelum menambahkan constraint unik
                $duplicates = DB::table('orders')
                    ->select('tenant_id', 'order_marketplace_id', DB::raw('MIN(id) as keep_id'))
                    ->whereNotNull('order_marketplace_id')
                    ->where('order_marketplace_id', '!=', '')
                    ->groupBy('tenant_id', 'order_marketplace_id')
                    ->havingRaw('COUNT(*) > 1')
                    ->get();

                foreach ($duplicates as $dup) {
                    $deleteOrderIds = DB::table('orders')
                        ->where('tenant_id', $dup->tenant_id)
                        ->where('order_marketplace_id', $dup->order_marketplace_id)
                        ->where('id', '!=', $dup->keep_id)
                        ->pluck('id')
                        ->toArray();

                    if (!empty($deleteOrderIds)) {
                        DB::table('order_items')->whereIn('order_id', $deleteOrderIds)->delete();
                        DB::table('orders')->whereIn('id', $deleteOrderIds)->delete();
                    }
                }

                // 3. Buat UNIQUE Index di level database MySQL
                Schema::table('orders', function (Blueprint $table) {
                    $table->unique(['tenant_id', 'order_marketplace_id'], 'unique_tenant_order_marketplace');
                });
            } catch (\Throwable $e) {
                // Prevent migration failure if index already exists
                \Illuminate\Support\Facades\Log::info('[Migration] Unique order index info: ' . $e->getMessage());
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('orders')) {
            try {
                Schema::table('orders', function (Blueprint $table) {
                    $table->dropUnique('unique_tenant_order_marketplace');
                });
            } catch (\Throwable $e) {}
        }
    }
};
