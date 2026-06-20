<?php

namespace Tests\Feature;

use App\Models\Channel;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MasterProduct;
use App\Models\ProductionOrder;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreOrderTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $gudangUser;
    protected User $produksiUser;
    protected Channel $channel;
    protected Store $store;
    protected MasterProduct $poProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'PO Test Tenant',
            'status' => 'active',
        ]);

        $this->gudangUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Gudang User',
            'email' => 'gudang@potest.com',
            'password' => bcrypt('password'),
            'role' => 'warehouse',
        ]);

        $this->produksiUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produksi User',
            'email' => 'produksi@potest.com',
            'password' => bcrypt('password'),
            'role' => 'production',
        ]);

        $this->channel = Channel::create([
            'name' => 'Shopee',
            'code' => 'shopee',
        ]);

        $this->store = Store::create([
            'tenant_id' => $this->tenant->id,
            'channel_id' => $this->channel->id,
            'store_name' => 'Shopee Store',
            'marketplace_store_id' => 'SHOPEE_STORE_123',
            'status' => 'connected',
        ]);

        $this->poProduct = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-PO-SHOE',
            'name' => 'Pre-Order Sneaker Premium',
            'price' => 200000,
            'cost_price' => 80000,
            'stock' => 0, // Awalnya stok 0
            'min_stock' => 5,
            'is_active' => true,
            'is_preorder' => true, // Menandakan produk Pre-Order
            'preorder_days' => 7,
        ]);
    }

    public function test_preorder_does_not_deduct_insufficient_stock(): void
    {
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-PO-001',
            'invoice_number' => 'INV-PO-001',
            'order_status' => Order::STATUS_READY_TO_SHIP,
            'buyer_name' => 'Rian Buyer',
            'total_amount' => 200000,
            'order_date' => now(),
            'is_stock_deducted' => false,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'master_product_id' => $this->poProduct->id,
            'sku' => 'SKU-PO-SHOE',
            'product_name' => 'Pre-Order Sneaker Premium',
            'price' => 200000,
            'quantity' => 2,
            'total_price' => 400000,
        ]);

        // Jalankan proses pemotongan stok
        $order->processStockDeduction();

        // Segarkan data produk dan order
        $this->poProduct->refresh();
        $order->refresh();

        // Stok tidak boleh terpotong (harus tetap 0, tidak minus)
        $this->assertEquals(0, $this->poProduct->stock);
        // Status order is_stock_deducted harus tetap false
        $this->assertFalse($order->is_stock_deducted);
    }

    public function test_preorder_auto_allocates_when_production_completed(): void
    {
        // 1. Buat pesanan PO
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-PO-002',
            'invoice_number' => 'INV-PO-002',
            'order_status' => Order::STATUS_READY_TO_SHIP,
            'buyer_name' => 'Rian Buyer',
            'total_amount' => 200000,
            'order_date' => now(),
            'is_stock_deducted' => false,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'master_product_id' => $this->poProduct->id,
            'sku' => 'SKU-PO-SHOE',
            'product_name' => 'Pre-Order Sneaker Premium',
            'price' => 200000,
            'quantity' => 2,
            'total_price' => 400000,
        ]);

        // Coba kurangi stok (akan skip karena stok 0)
        $order->processStockDeduction();
        $this->assertFalse($order->refresh()->is_stock_deducted);

        // 2. Buat Production Order untuk memenuhi kebutuhan order
        $productionOrder = ProductionOrder::create([
            'tenant_id' => $this->tenant->id,
            'master_product_id' => $this->poProduct->id,
            'quantity' => 5, // Divisi produksi memproduksi 5 unit
            'status' => 'producing',
            'requested_by' => $this->gudangUser->id,
        ]);

        // Login sebagai divisi produksi
        $this->actingAs($this->produksiUser);

        // Selesaikan produksi
        $response = $this->post(route('mobile.produksi.complete', $productionOrder));
        $response->assertSessionHas('success');

        // Segarkan data produk dan order
        $this->poProduct->refresh();
        $order->refresh();

        // 3. Verifikasi alokasi otomatis
        // Awalnya diproduksi 5 unit.
        // Pesanan PO membutuhkan 2 unit.
        // Setelah dialokasikan otomatis, stok tersisa di gudang harus = 3 unit.
        $this->assertEquals(3, $this->poProduct->stock);

        // Pesanan harus sudah ditandai terpotong stoknya
        $this->assertTrue($order->is_stock_deducted);
    }

    public function test_marketplace_product_auto_links_when_matching_sku_saved(): void
    {
        // 1. Buat produk marketplace dengan SKU yang sama dengan poProduct ('SKU-PO-SHOE')
        $mpProduct = \App\Models\MarketplaceProduct::create([
            'store_id' => $this->store->id,
            'marketplace_product_id' => 'MP-PROD-999',
            'marketplace_variant_id' => 'MP-VAR-999',
            'marketplace_sku' => 'SKU-PO-SHOE',
            'name' => 'Shopee Sneaker Premium Variation',
            'price' => 200000,
            'stock' => 10,
        ]);

        // 2. Verifikasi penautan otomatis
        // master_product_id harus otomatis terisi dengan ID poProduct
        $this->assertEquals($this->poProduct->id, $mpProduct->master_product_id);
        
        // sync_stock harus otomatis bernilai true
        $this->assertTrue($mpProduct->sync_stock);
    }
}
