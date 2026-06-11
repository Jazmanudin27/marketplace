<?php

namespace Database\Seeders;

use App\Models\Channel;
use App\Models\MasterProduct;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        // ==========================
        // Perusahaan A - Demo
        // ==========================
        $tenantA = Tenant::create([
            'name'   => 'Perusahaan A (Demo)',
            'status' => 'active',
        ]);

        // Users for Tenant A
        User::create([
            'tenant_id' => $tenantA->id,
            'name'      => 'Admin Perusahaan A',
            'email'     => 'admin@perusahaan-a.com',
            'password'  => Hash::make('password'),
            'role'      => 'admin',
        ]);

        User::create([
            'tenant_id' => $tenantA->id,
            'name'      => 'Staff Gudang Perusahaan A',
            'email'     => 'warehouse@perusahaan-a.com',
            'password'  => Hash::make('password'),
            'role'      => 'warehouse',
        ]);

        User::create([
            'tenant_id' => $tenantA->id,
            'name'      => 'Finance Perusahaan A',
            'email'     => 'finance@perusahaan-a.com',
            'password'  => Hash::make('password'),
            'role'      => 'finance',
        ]);

        $shopee   = Channel::where('code', 'shopee')->first();
        $tiktok   = Channel::where('code', 'tiktok')->first();
        $tokoped  = Channel::where('code', 'tokopedia')->first();

        // 3 Toko Shopee Perusahaan A
        for ($i = 1; $i <= 3; $i++) {
            Store::create([
                'tenant_id'            => $tenantA->id,
                'channel_id'           => $shopee->id,
                'store_name'           => "Shopee Toko A-{$i}",
                'marketplace_store_id' => "SHOPEE_A_{$i}_DEMO",
                'status'               => 'connected',
            ]);
        }

        // 2 Toko TikTok Perusahaan A
        for ($i = 1; $i <= 2; $i++) {
            Store::create([
                'tenant_id'            => $tenantA->id,
                'channel_id'           => $tiktok->id,
                'store_name'           => "TikTok Shop A-{$i}",
                'marketplace_store_id' => "TIKTOK_A_{$i}_DEMO",
                'status'               => 'connected',
            ]);
        }

        // Categories & Brands for Perusahaan A
        $catFashion = Category::create([
            'tenant_id' => $tenantA->id,
            'name'      => 'Fashion',
        ]);

        $brandX = Brand::create([
            'tenant_id' => $tenantA->id,
            'name'      => 'BrandX',
        ]);

        $brandY = Brand::create([
            'tenant_id' => $tenantA->id,
            'name'      => 'BrandY',
        ]);

        // Produk Master Perusahaan A
        $products = [
            ['sku' => 'SKU-A-001', 'name' => 'Sepatu Sneakers Premium', 'price' => 350000, 'cost_price' => 180000, 'stock' => 150, 'category_id' => $catFashion->id, 'brand_id' => $brandX->id],
            ['sku' => 'SKU-A-002', 'name' => 'Tas Ransel Outdoor',      'price' => 250000, 'cost_price' => 120000, 'stock' => 80,  'category_id' => $catFashion->id, 'brand_id' => $brandX->id],
            ['sku' => 'SKU-A-003', 'name' => 'Topi Baseball Polos',     'price' => 85000,  'cost_price' => 35000,  'stock' => 200, 'category_id' => $catFashion->id, 'brand_id' => $brandY->id],
        ];

        foreach ($products as $p) {
            MasterProduct::create(array_merge($p, ['tenant_id' => $tenantA->id]));
        }

        // ==========================
        // Perusahaan B - Demo
        // ==========================
        $tenantB = Tenant::create([
            'name'   => 'Perusahaan B (Demo)',
            'status' => 'active',
        ]);

        // Users for Tenant B
        User::create([
            'tenant_id' => $tenantB->id,
            'name'      => 'Admin Perusahaan B',
            'email'     => 'admin@perusahaan-b.com',
            'password'  => Hash::make('password'),
            'role'      => 'admin',
        ]);

        User::create([
            'tenant_id' => $tenantB->id,
            'name'      => 'Staff Gudang Perusahaan B',
            'email'     => 'warehouse@perusahaan-b.com',
            'password'  => Hash::make('password'),
            'role'      => 'warehouse',
        ]);

        User::create([
            'tenant_id' => $tenantB->id,
            'name'      => 'Finance Perusahaan B',
            'email'     => 'finance@perusahaan-b.com',
            'password'  => Hash::make('password'),
            'role'      => 'finance',
        ]);

        // 2 Toko Tokopedia Perusahaan B
        for ($i = 1; $i <= 2; $i++) {
            Store::create([
                'tenant_id'            => $tenantB->id,
                'channel_id'           => $tokoped->id,
                'store_name'           => "Tokopedia Toko B-{$i}",
                'marketplace_store_id' => "TOKPED_B_{$i}_DEMO",
                'status'               => 'connected',
            ]);
        }

        // 1 Toko Shopee Perusahaan B
        Store::create([
            'tenant_id'            => $tenantB->id,
            'channel_id'           => $shopee->id,
            'store_name'           => 'Shopee Toko B-Official',
            'marketplace_store_id' => 'SHOPEE_B_1_DEMO',
            'status'               => 'connected',
        ]);
    }
}
