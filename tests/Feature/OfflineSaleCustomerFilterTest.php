<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Customer;
use App\Models\MasterProduct;
use App\Models\OfflineSale;
use App\Models\Order;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineSaleCustomerFilterTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->tenant = Tenant::create([
            'name'   => 'Offline Customer Filter Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin POS',
            'email'     => 'admin_pos@test.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);
    }

    public function test_offline_sale_create_only_shows_offline_customers(): void
    {
        // 1. Offline customer (Umum)
        $offlineCustUmum = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Budi Offline Umum',
            'category'  => 'umum',
            'phone'     => '0811111111',
        ]);

        // 2. Offline customer (Dropship)
        $offlineCustDropship = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Citra Dropshipper',
            'category'  => 'dropship',
            'phone'     => '0822222222',
        ]);

        // 3. Marketplace customer by category
        $marketplaceCust = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Shopee Buyer By Category',
            'category'  => 'marketplace',
            'phone'     => '0833333333',
        ]);

        // 4. Marketplace customer by marketplace_username
        $marketplaceCustUsername = Customer::create([
            'tenant_id'            => $this->tenant->id,
            'name'                 => 'TikTok Buyer With Username',
            'category'             => 'umum',
            'marketplace_username' => 'tiktok_user_99',
            'phone'                => '0844444444',
        ]);

        // 5. Customer with marketplace order (synced from marketplace without explicit category)
        $channel = Channel::create(['name' => 'Shopee', 'code' => 'shopee']);
        $store = Store::create([
            'tenant_id'            => $this->tenant->id,
            'channel_id'           => $channel->id,
            'store_name'           => 'Toko Shopee Test',
            'marketplace_store_id' => '12345678',
            'status'               => 'connected',
        ]);

        $orderBuyer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Synced Shopee Buyer Only Orders',
            'category'  => 'umum',
            'phone'     => '0855555555',
        ]);

        Order::create([
            'tenant_id'            => $this->tenant->id,
            'store_id'             => $store->id,
            'customer_id'          => $orderBuyer->id,
            'order_marketplace_id' => 'ORD-SHOPEE-999',
            'order_status'         => 'COMPLETED',
            'buyer_name'           => 'Synced Shopee Buyer Only Orders',
            'total_amount'         => 50000,
            'net_amount'           => 45000,
            'order_date'           => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('offline_sales.create'));

        $response->assertStatus(200);

        // Offline customers MUST be present
        $response->assertSee('Budi Offline Umum');
        $response->assertSee('Citra Dropshipper');

        // Marketplace customers MUST NOT be present
        $response->assertDontSee('Shopee Buyer By Category');
        $response->assertDontSee('TikTok Buyer With Username');
        $response->assertDontSee('Synced Shopee Buyer Only Orders');
    }

    public function test_quick_customer_modal_does_not_contain_marketplace_option(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('offline_sales.create'));

        $response->assertStatus(200);

        // Offline options must be available
        $response->assertSee('<option value="umum">Pelanggan Umum</option>', false);
        $response->assertSee('<option value="biasa">Pelanggan Biasa</option>', false);
        $response->assertSee('<option value="dropship">Pelanggan Dropship</option>', false);

        // Marketplace option must not be available in offline sales modal
        $response->assertDontSee('<option value="marketplace">Pelanggan Marketplace</option>', false);
        $response->assertDontSee('<option value="marketplace">', false);
    }

    public function test_offline_sale_create_does_not_display_bundle_products(): void
    {
        // 1. Single product - SHOULD be visible
        MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Baju Putih SD L',
            'sku'       => 'BP-SD-L',
            'price'     => 45000,
            'is_bundle' => false,
            'is_active' => true,
        ]);

        // 2. Product marked as is_bundle = true - SHOULD NOT be visible
        MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Set Seragam SD Lengkap',
            'sku'       => 'SET-SD-LENGKAP',
            'price'     => 95000,
            'is_bundle' => true,
            'is_active' => true,
        ]);

        // 3. Product with SET- prefix even if is_bundle = false - SHOULD NOT be visible
        MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Set SMP Putih Biru',
            'sku'       => 'SET-SMP-PUTIH',
            'price'     => 110000,
            'is_bundle' => false,
            'is_active' => true,
        ]);

        // 4. Product with PAKET- prefix even if is_bundle = false - SHOULD NOT be visible
        MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Paket Pramuka Siaga',
            'sku'       => 'PAKET-PRAMUKA',
            'price'     => 120000,
            'is_bundle' => false,
            'is_active' => true,
        ]);

        // 5. Product with BUNDLE- prefix - SHOULD NOT be visible
        MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Bundle Hemat Aksesoris',
            'sku'       => 'BUNDLE-HEMAT',
            'price'     => 30000,
            'is_bundle' => false,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('offline_sales.create'));

        $response->assertStatus(200);

        // Single product must appear
        $response->assertSee('Baju Putih SD L');
        $response->assertSee('BP-SD-L');

        // Bundle products must NOT appear
        $response->assertDontSee('Set Seragam SD Lengkap');
        $response->assertDontSee('SET-SD-LENGKAP');
        $response->assertDontSee('Set SMP Putih Biru');
        $response->assertDontSee('SET-SMP-PUTIH');
        $response->assertDontSee('Paket Pramuka Siaga');
        $response->assertDontSee('PAKET-PRAMUKA');
        $response->assertDontSee('Bundle Hemat Aksesoris');
        $response->assertDontSee('BUNDLE-HEMAT');
    }
}
