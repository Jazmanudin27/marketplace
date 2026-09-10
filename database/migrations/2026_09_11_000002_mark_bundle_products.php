<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('master_products') && Schema::hasColumn('master_products', 'is_bundle')) {
            // Update products that have child components in master_product_bundles
            if (Schema::hasTable('master_product_bundles')) {
                DB::table('master_products')
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('master_product_bundles')
                            ->whereColumn('master_product_bundles.parent_id', 'master_products.id');
                    })
                    ->update(['is_bundle' => true]);
            }

            // Update products with bundle SKU conventions
            DB::table('master_products')
                ->where(function ($q) {
                    $q->where('sku', 'like', 'SET-%')
                      ->orWhere('sku', 'like', 'PAKET-%')
                      ->orWhere('sku', 'like', 'BUNDLE-%');
                })
                ->update(['is_bundle' => true]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No rollback needed
    }
};
