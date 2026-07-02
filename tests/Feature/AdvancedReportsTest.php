<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\MasterProduct;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdvancedReportsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Customer $customer;
    protected MasterProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->tenant = Tenant::create([
            'name' => 'Report Test Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'General Manager',
            'email' => 'gm@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->customer = Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Reseller VIP',
            'phone' => '0811223344',
            'balance' => 500000,
            'is_active' => true,
        ]);

        $this->product = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-REP-88',
            'name' => 'Produk Laporan',
            'price' => 100000,
            'cost_price' => 40000,
            'stock' => 50,
            'is_active' => true,
        ]);
    }

    public function test_can_view_store_sales_report(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.store_sales'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Penjualan Toko & Saluran');
        $response->assertSee('POS Offline (Toko Fisik)');
    }

    public function test_can_view_reseller_receivables_report(): void
    {
        // Create offline credit sale (piutang = 100000 - 20000 = 80000)
        $sale = OfflineSale::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'sale_number' => 'OS-REP-1',
            'status' => 'completed',
            'payment_method' => 'piutang',
            'total_amount' => 100000,
            'grand_total' => 100000,
            'paid_amount' => 20000,
            'change_amount' => 0,
            'sold_at' => now(),
        ]);

        OfflineSaleItem::create([
            'offline_sale_id' => $sale->id,
            'master_product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_price' => 100000,
            'quantity' => 1,
            'subtotal' => 100000,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.reseller_receivables'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Saldo & Piutang Reseller');
        $response->assertSee('Reseller VIP');
        $response->assertViewHas('totalResellerBalance', 500000.0);
    }

    public function test_can_view_inventory_turnover_report(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.inventory_turnover'));

        $response->assertStatus(200);
        $response->assertSee('Laporan Laju Perputaran Stok Gudang');
        $response->assertSee('SKU-REP-88');
    }
}
