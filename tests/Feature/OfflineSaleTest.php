<?php

namespace Tests\Feature;

use App\Models\MasterProduct;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineSaleTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected MasterProduct $masterProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->tenant = Tenant::create([
            'name'   => 'Offline Sales Tenant',
            'status' => 'active',
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Admin User',
            'email'     => 'admin@offlinesaletest.com',
            'password'  => bcrypt('password'),
            'role'      => 'admin',
        ]);

        $this->masterProduct = MasterProduct::create([
            'tenant_id'  => $this->tenant->id,
            'sku'        => 'SKU-OFFLINE-01',
            'name'       => 'Produk Offline Test',
            'price'      => 10000,
            'cost_price' => 5000,
            'stock'      => 10,
            'is_active'  => true,
        ]);
    }

    public function test_offline_sale_index_page_is_accessible_for_admin(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('offline_sales.index'));

        $response->assertStatus(200);
        $response->assertViewIs('offline_sales.index');
    }

    public function test_offline_sale_create_page_lists_available_products(): void
    {
        // Non-active product shouldn't show
        MasterProduct::create([
            'tenant_id'  => $this->tenant->id,
            'sku'        => 'SKU-OFFLINE-INACTIVE',
            'name'       => 'Produk Inactive',
            'price'      => 10000,
            'cost_price' => 5000,
            'stock'      => 10,
            'is_active'  => false,
        ]);

        // Out of stock product shouldn't show
        MasterProduct::create([
            'tenant_id'  => $this->tenant->id,
            'sku'        => 'SKU-OFFLINE-OUTOFSTOCK',
            'name'       => 'Produk Kosong',
            'price'      => 10000,
            'cost_price' => 5000,
            'stock'      => 0,
            'is_active'  => true,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('offline_sales.create'));

        $response->assertStatus(200);
        $response->assertViewIs('offline_sales.create');
        $response->assertSee('Produk Offline Test');
        $response->assertDontSee('Produk Inactive');
        $response->assertDontSee('Produk Kosong');
    }

    public function test_offline_sale_store_creates_sale_and_reduces_stock(): void
    {
        $payload = [
            'items' => [
                [
                    'master_product_id' => $this->masterProduct->id,
                    'quantity'          => 3,
                    'unit_price'        => 10000,
                ]
            ],
            'payment_method' => 'tunai',
            'paid_amount'    => 30000,
            'discount_amount'=> 0,
            'buyer_name'     => 'Budi',
            'buyer_phone'    => '08123456789',
            'notes'          => 'Catatan penjualan offline',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('offline_sales.store'), $payload);

        $response->assertRedirect(route('offline_sales.index'));
        $response->assertSessionHas('success');

        // Check if OfflineSale record was created
        $this->assertDatabaseHas('offline_sales', [
            'tenant_id'      => $this->tenant->id,
            'buyer_name'     => 'Budi',
            'payment_method' => 'tunai',
            'total_amount'   => 30000,
            'grand_total'    => 30000,
            'paid_amount'    => 30000,
            'status'         => OfflineSale::STATUS_COMPLETED,
        ]);

        // Check if OfflineSaleItem was created
        $this->assertDatabaseHas('offline_sale_items', [
            'master_product_id' => $this->masterProduct->id,
            'quantity'          => 3,
            'unit_price'        => 10000,
            'subtotal'          => 30000,
        ]);

        // Check if stock was reduced
        $this->masterProduct->refresh();
        $this->assertEquals(7, $this->masterProduct->stock);
    }

    public function test_offline_sale_store_fails_if_insufficient_stock(): void
    {
        $payload = [
            'items' => [
                [
                    'master_product_id' => $this->masterProduct->id,
                    'quantity'          => 12, // Stock is only 10
                    'unit_price'        => 10000,
                ]
            ],
            'payment_method' => 'tunai',
            'paid_amount'    => 120000,
            'discount_amount'=> 0,
            'buyer_name'     => 'Budi',
            'buyer_phone'    => '08123456789',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('offline_sales.store'), $payload);

        // Under DB transaction, the response is an abort status code 422
        $response->assertStatus(422);

        // Check stock is unchanged
        $this->masterProduct->refresh();
        $this->assertEquals(10, $this->masterProduct->stock);
    }

    public function test_offline_sale_cancel_restores_stock(): void
    {
        // 1. Create a completed sale first
        $sale = OfflineSale::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $this->user->id,
            'sale_number'     => 'SL-OFFLINE-TEST',
            'status'          => OfflineSale::STATUS_COMPLETED,
            'buyer_name'      => 'Asep',
            'payment_method'  => 'qris',
            'total_amount'    => 20000,
            'grand_total'     => 20000,
            'paid_amount'     => 20000,
            'change_amount'   => 0,
            'sold_at'         => now(),
        ]);

        $item = $sale->items()->create([
            'master_product_id' => $this->masterProduct->id,
            'product_name'      => $this->masterProduct->name,
            'sku'               => $this->masterProduct->sku,
            'quantity'          => 2,
            'unit_price'        => 10000,
            'subtotal'          => 20000,
        ]);

        // Manually decrease the stock to simulate creation
        $this->masterProduct->decrement('stock', 2);
        $this->assertEquals(8, $this->masterProduct->stock);

        // 2. Cancel the sale
        $response = $this->actingAs($this->user)
            ->post(route('offline_sales.cancel', $sale));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $sale->refresh();
        $this->assertEquals(OfflineSale::STATUS_CANCELLED, $sale->status);

        // Stock should be restored to 10
        $this->masterProduct->refresh();
        $this->assertEquals(10, $this->masterProduct->stock);
    }

    public function test_offline_sale_receipt_page_is_accessible(): void
    {
        $sale = OfflineSale::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $this->user->id,
            'sale_number'     => 'SL-OFFLINE-TEST',
            'status'          => OfflineSale::STATUS_COMPLETED,
            'payment_method'  => 'tunai',
            'total_amount'    => 10000,
            'grand_total'     => 10000,
            'paid_amount'     => 10000,
            'change_amount'   => 0,
            'sold_at'         => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('offline_sales.print', $sale));

        $response->assertStatus(200);
        $response->assertViewIs('offline_sales.receipt');
        $response->assertSee($sale->sale_number);
    }

    public function test_offline_sale_store_creates_customer_with_address_if_manual_and_not_exists(): void
    {
        $payload = [
            'items' => [
                [
                    'master_product_id' => $this->masterProduct->id,
                    'quantity'          => 1,
                    'unit_price'        => 10000,
                ]
            ],
            'payment_method' => 'tunai',
            'paid_amount'    => 10000,
            'discount_amount'=> 0,
            'buyer_name'     => 'Customer Baru POS',
            'buyer_phone'    => '08987654321',
            'buyer_address'  => 'Jalan POS Baru No. 123',
            'notes'          => 'Catatan test customer baru',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('offline_sales.store'), $payload);

        $response->assertRedirect(route('offline_sales.index'));

        // Check if customer was created in the database with address
        $this->assertDatabaseHas('customers', [
            'tenant_id' => $this->tenant->id,
            'name'      => 'Customer Baru POS',
            'phone'     => '08987654321',
            'address'   => 'Jalan POS Baru No. 123',
        ]);
    }

    public function test_offline_sale_store_with_piutang_payment_method(): void
    {
        $payload = [
            'items' => [
                [
                    'master_product_id' => $this->masterProduct->id,
                    'quantity'          => 2,
                    'unit_price'        => 10000,
                ]
            ],
            'payment_method' => 'piutang',
            'paid_amount'    => 0, // Down payment can be 0 for piutang
            'discount_amount'=> 0,
            'buyer_name'     => 'Customer Piutang POS',
            'buyer_phone'    => '08999999999',
            'buyer_address'  => 'Alamat Piutang',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('offline_sales.store'), $payload);

        $response->assertRedirect(route('offline_sales.index'));

        // Check if offline sale was created with piutang method and 0 paid amount
        $this->assertDatabaseHas('offline_sales', [
            'tenant_id'      => $this->tenant->id,
            'buyer_name'     => 'Customer Piutang POS',
            'payment_method' => 'piutang',
            'total_amount'   => 20000,
            'grand_total'    => 20000,
            'paid_amount'    => 0,
        ]);
    }

    public function test_offline_sale_store_with_reseller_balance_sufficient(): void
    {
        $customer = \App\Models\Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Reseller Test',
            'phone' => '0812345678',
            'balance' => 50000,
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'master_product_id' => $this->masterProduct->id,
                    'quantity'          => 2,
                    'unit_price'        => 10000,
                ]
            ],
            'payment_method' => 'reseller_balance',
            'paid_amount'    => 20000,
            'discount_amount'=> 0,
            'buyer_name'     => 'Buyer Name dropship',
            'buyer_phone'    => '0822222',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('offline_sales.store'), $payload);

        $response->assertRedirect(route('offline_sales.index'));

        $customer->refresh();
        $this->assertEquals(30000, (float)$customer->balance);

        $this->assertDatabaseHas('reseller_balance_transactions', [
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer->id,
            'type' => 'out',
            'amount' => 20000,
        ]);
    }

    public function test_offline_sale_store_with_reseller_balance_insufficient(): void
    {
        $customer = \App\Models\Customer::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Reseller Test Low',
            'phone' => '0812345678',
            'balance' => 5000,
        ]);

        $payload = [
            'customer_id' => $customer->id,
            'items' => [
                [
                    'master_product_id' => $this->masterProduct->id,
                    'quantity'          => 2,
                    'unit_price'        => 10000,
                ]
            ],
            'payment_method' => 'reseller_balance',
            'paid_amount'    => 20000,
            'discount_amount'=> 0,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('offline_sales.store'), $payload);

        $response->assertStatus(422);

        $customer->refresh();
        $this->assertEquals(5000, (float)$customer->balance);
    }
}
