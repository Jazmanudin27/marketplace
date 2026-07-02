<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Order;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\Payroll;
use App\Models\MasterProduct;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Store;
use App\Models\Channel;
use App\Models\AdsAccount;
use App\Models\AdsCampaign;
use App\Models\AdsPerformanceLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetProfitCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected Store $store;
    protected MasterProduct $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->tenant = Tenant::create([
            'name' => 'Finance Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Financial Controller',
            'email' => 'finance@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $channel = Channel::create([
            'name' => 'Shopee',
            'code' => 'shopee',
        ]);

        $this->store = Store::create([
            'tenant_id' => $this->tenant->id,
            'channel_id' => $channel->id,
            'store_name' => 'Shopee Store',
            'marketplace_store_id' => '112233',
            'status' => 'connected',
        ]);

        $this->product = MasterProduct::create([
            'tenant_id' => $this->tenant->id,
            'sku' => 'SKU-FIN-01',
            'name' => 'Produk Finansial',
            'price' => 50000,
            'cost_price' => 20000, // HPP = 20.000
            'stock' => 100,
            'is_active' => true,
        ]);
    }

    public function test_profit_index_calculates_correct_real_net_profit(): void
    {
        $today = now()->toDateString();

        // 1. Create an online order (gross profit = net_amount - hpp_total)
        // net_amount = 50000, hpp = 20000 -> gross profit = 30000
        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'store_id' => $this->store->id,
            'order_marketplace_id' => 'ORD-FIN-1',
            'invoice_number' => 'INV-FIN-1',
            'order_status' => 'COMPLETED',
            'order_date' => now(),
            'total_amount' => 50000,
            'net_amount' => 50000,
            'is_dropship' => false,
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $order->id,
            'master_product_id' => $this->product->id,
            'sku' => $this->product->sku,
            'product_name' => $this->product->name,
            'price' => 50000,
            'quantity' => 1,
            'total_price' => 50000,
            'hpp_subtotal' => 20000,
        ]);

        // 2. Create an offline sale (gross profit = grand_total - hpp_total)
        // grand_total = 100000, hpp = 40000 -> gross profit = 60000
        $offlineSale = OfflineSale::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->user->id,
            'sale_number' => 'OS-FIN-1',
            'status' => 'completed',
            'total_amount' => 100000,
            'grand_total' => 100000,
            'sold_at' => now(),
        ]);

        OfflineSaleItem::create([
            'offline_sale_id' => $offlineSale->id,
            'master_product_id' => $this->product->id,
            'product_name' => $this->product->name,
            'unit_price' => 50000,
            'quantity' => 2,
            'subtotal' => 100000,
        ]);

        // Total gross revenue = 50000 + 100000 = 150000
        // Total gross HPP = 20000 + 40000 = 60000
        // Total gross profit = 150000 - 60000 = 90000

        // 3. Create General Expense (Category: rent = 10000)
        Expense::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Sewa Toko',
            'category' => 'rent',
            'amount' => 10000,
            'expense_date' => $today,
        ]);

        // 4. Create Paid Payroll (salary = 15000)
        $employee = \App\Models\Employee::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Karyawan Keuangan',
            'email' => 'employee@finance.com',
            'phone' => '08123',
            'position' => 'Staff',
            'basic_salary' => 15000,
            'status' => 'active',
        ]);

        Payroll::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $employee->id,
            'period' => now()->format('Y-m'),
            'basic_salary' => 15000,
            'net_salary' => 15000,
            'status' => 'paid',
            'payment_date' => $today,
        ]);

        // 5. Create Ad Spend (ad spend = 25000)
        $adsAccount = AdsAccount::create([
            'tenant_id' => $this->tenant->id,
            'platform' => 'meta',
            'account_name' => 'Akun Meta Finance',
            'is_active' => true,
        ]);

        $adsCampaign = AdsCampaign::create([
            'tenant_id' => $this->tenant->id,
            'ads_account_id' => $adsAccount->id,
            'name' => 'Campaign Finance Meta',
            'is_active' => true,
        ]);

        AdsPerformanceLog::create([
            'tenant_id' => $this->tenant->id,
            'ads_campaign_id' => $adsCampaign->id,
            'date' => $today,
            'ad_spend' => 25000,
            'clicks' => 100,
            'impressions' => 1000,
        ]);

        // Total Deductions = 10000 (rent) + 15000 (payroll) + 25000 (ad spend) = 50000
        // Real Net Profit = 90000 (gross profit) - 50000 = 40000
        // Real Net Margin = 40000 / 150000 * 100 = 26.67%

        $response = $this->actingAs($this->user)
            ->get(route('profit.index', [
                'date_from' => $today,
                'date_to' => $today,
            ]));

        $response->assertStatus(200);

        // Assert view context variable calculations
        $response->assertViewHas('totalExpenses', 10000.0);
        $response->assertViewHas('totalPayroll', 15000.0);
        $response->assertViewHas('totalAdSpend', 25000.0);
        $response->assertViewHas('totalDeductions', 50000.0);
        $response->assertViewHas('realNetProfit', 40000.0);
        $response->assertViewHas('realNetMargin', 26.67);
    }
}
