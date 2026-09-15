<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->index(['tenant_id', 'is_bundle', 'name'], 'idx_mp_tenant_bundle_name');
            $table->index(['tenant_id', 'is_active'], 'idx_mp_tenant_active');
            $table->index(['tenant_id', 'category_id'], 'idx_mp_tenant_cat');
            $table->index(['tenant_id', 'brand_id'], 'idx_mp_tenant_brand');
        });

        if (Schema::hasTable('marketplace_products')) {
            Schema::table('marketplace_products', function (Blueprint $table) {
                $table->index(['master_product_id', 'store_id'], 'idx_mpp_master_store');
            });
        }

        if (Schema::hasTable('master_product_bundles')) {
            Schema::table('master_product_bundles', function (Blueprint $table) {
                $table->index(['parent_id', 'child_id'], 'idx_mpb_parent_child');
            });
        }
    }

    public function down(): void
    {
        Schema::table('master_products', function (Blueprint $table) {
            $table->dropIndex('idx_mp_tenant_bundle_name');
            $table->dropIndex('idx_mp_tenant_active');
            $table->dropIndex('idx_mp_tenant_cat');
            $table->dropIndex('idx_mp_tenant_brand');
        });

        if (Schema::hasTable('marketplace_products')) {
            Schema::table('marketplace_products', function (Blueprint $table) {
                $table->dropIndex('idx_mpp_master_store');
            });
        }

        if (Schema::hasTable('master_product_bundles')) {
            Schema::table('master_product_bundles', function (Blueprint $table) {
                $table->dropIndex('idx_mpb_parent_child');
            });
        }
    }
};
