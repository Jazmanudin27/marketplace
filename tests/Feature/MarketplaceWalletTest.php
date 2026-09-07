<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Order;
use App\Models\MarketplaceWalletTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceWalletTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $user;
    protected $shopeeChannel;
    protected $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Wallet Tenant',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);

        $this->shopeeChannel = Channel::create([
            'name' => 'Shopee',
            'code' => 'shopee',
        ]);

        $this->store = Store::create([
            'tenant_id' => $this->tenant->id,
            'channel_id' => $this->shopeeChannel->id,
            'store_name' => 'Toko Shopee Official',
            'marketplace_store_id' => '123456',
            'status' => 'connected',
        ]);
    }

    public function test_marketplace_wallet_page_loads_instantly_with_correct_balances()
    {
        // 1. Catatan saldo dompet di DB
        MarketplaceWalletTransaction::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'transaction_id' => 'TX1001',
            'transaction_date' => now()->subHours(2),
            'type' => 'Pelepasan Dana Pesanan',
            'description' => 'Dana pesanan selesai',
            'amount' => 1500000,
            'direction' => 'in',
            'current_balance' => 4500000,
        ]);

        // 2. Pesanan aktif pending (harus dihitung di Saldo Pending)
        Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'SHP-PENDING-01',
            'order_status' => 'SHIPPED',
            'total_amount' => 200000,
            'marketplace_fee' => 30000,
            'net_amount' => 170000,
            'order_date' => now()->subDays(2),
        ]);

        Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'SHP-PENDING-02',
            'order_status' => 'READY_TO_SHIP',
            'total_amount' => 100000,
            'marketplace_fee' => 15000,
            'net_amount' => 85000,
            'order_date' => now()->subDay(),
        ]);

        // 3. Pesanan yang TIDAK boleh masuk ke Pending:
        // - COMPLETED (sudah cair)
        Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'SHP-COMPLETED-01',
            'order_status' => 'COMPLETED',
            'total_amount' => 500000,
            'net_amount' => 400000,
            'order_date' => now()->subDays(5),
        ]);

        // - CANCELLED (batal)
        Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'SHP-CANCELLED-01',
            'order_status' => 'CANCELLED',
            'total_amount' => 300000,
            'net_amount' => 250000,
            'order_date' => now()->subDays(3),
        ]);

        // - UNPAID (belum bayar)
        Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'SHP-UNPAID-01',
            'order_status' => 'UNPAID',
            'total_amount' => 150000,
            'net_amount' => 120000,
            'order_date' => now()->subHour(),
        ]);

        $response = $this->actingAs($this->user)->get(route('finance.marketplace_wallets.index'));

        $response->assertStatus(200);
        $response->assertSee('Toko Shopee Official');
        // Total Wallet Balance = 4.500.000
        $response->assertSee('4.500.000');
        // Pending Balance = 170.000 + 85.000 = 255.000
        $response->assertSee('255.000');
        // Total Estimated = 4.755.000
        $response->assertSee('4.755.000');
    }

    public function test_marketplace_wallet_pending_page_loads_with_breakdown_orders()
    {
        Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'SHP-PENDING-DETAIL-01',
            'order_status' => 'SHIPPED',
            'buyer_name' => 'Budi Santoso',
            'courier' => 'J&T Express',
            'tracking_number' => 'JT123456789',
            'total_amount' => 250000,
            'marketplace_fee' => 35000,
            'net_amount' => 215000,
            'order_date' => now()->subDay(),
        ]);

        $response = $this->actingAs($this->user)->get(route('finance.marketplace_wallets.pending', $this->store));

        $response->assertStatus(200);
        $response->assertSee('Rincian Saldo Tertahan');
        $response->assertSee('Toko Shopee Official');
        $response->assertSee('SHP-PENDING-DETAIL-01');
        $response->assertSee('Budi Santoso');
        $response->assertSee('215.000');
    }
}

