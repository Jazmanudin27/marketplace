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

        // Active products with 0 stock are listed for PO (Pre-Order / SPK) feature
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
        $response->assertSee('Produk Kosong');
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
            'status'         => OfflineSale::STATUS_PENDING_APPROVAL,
        ]);

        $sale = OfflineSale::where('tenant_id', $this->tenant->id)->first();
        // Approve sale to reduce stock
        $this->actingAs($this->user)->post(route('offline_sales.approve', $sale), [
            'payment_destination' => 'kas_besar',
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
            ->post(route('offline_sales.cancel', $sale), [
                'cancellation_reason' => 'Customer membatalkan pembelian',
            ]);

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

    public function test_offline_sale_store_without_payment_details_defaults_to_piutang(): void
    {
        $payload = [
            'items' => [
                [
                    'master_product_id' => $this->masterProduct->id,
                    'quantity'          => 2,
                    'unit_price'        => 10000,
                ]
            ],
            'discount_amount'=> 0,
            'buyer_name'     => 'Pembeli Cicilan Tanpa DP',
            'buyer_phone'    => '08123456789',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('offline_sales.store'), $payload);

        $response->assertRedirect(route('offline_sales.index'));

        $this->assertDatabaseHas('offline_sales', [
            'tenant_id'      => $this->tenant->id,
            'buyer_name'     => 'Pembeli Cicilan Tanpa DP',
            'payment_method' => 'piutang',
            'paid_amount'    => 0,
            'grand_total'    => 20000,
        ]);
    }

    public function test_offline_sale_can_be_paid_in_installments(): void
    {
        $bank = \App\Models\BankAccount::create([
            'tenant_id'       => $this->tenant->id,
            'bank_name'       => 'BCA Operasional',
            'account_number'  => '1234567890',
            'account_holder'  => 'Toko Kita',
            'current_balance' => 100000,
        ]);

        $sale = OfflineSale::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $this->user->id,
            'sale_number'     => 'SL-CICILAN-001',
            'status'          => OfflineSale::STATUS_COMPLETED,
            'buyer_name'      => 'Customer Cicilan',
            'payment_method'  => 'piutang',
            'total_amount'    => 100000,
            'grand_total'     => 100000,
            'paid_amount'     => 0,
            'change_amount'   => 0,
            'sold_at'         => now(),
        ]);

        $this->assertEquals(100000, $sale->remaining_amount);
        $this->assertEquals('Belum Lunas', $sale->payment_status_label);

        // Cicilan ke-1: 40.000
        $response1 = $this->actingAs($this->user)
            ->post(route('offline_sales.payments.store', $sale), [
                'amount'              => 40000,
                'payment_method'      => 'transfer',
                'payment_destination' => 'BCA Operasional',
                'payment_date'        => now()->toDateString(),
                'notes'               => 'Cicilan 1 DP',
            ]);

        $response1->assertRedirect();
        $response1->assertSessionHas('success');

        $sale->refresh();
        $this->assertEquals(40000, (float)$sale->paid_amount);
        $this->assertEquals(60000, (float)$sale->remaining_amount);
        $this->assertEquals('Dicicil', $sale->payment_status_label);

        $bank->refresh();
        $this->assertEquals(140000, (float)$bank->current_balance);

        $this->assertDatabaseHas('offline_sale_payments', [
            'offline_sale_id'     => $sale->id,
            'amount'              => 40000,
            'payment_destination' => 'BCA Operasional',
            'payment_method'      => 'transfer',
        ]);

        $this->assertDatabaseHas('incomes', [
            'tenant_id'           => $this->tenant->id,
            'amount'              => 40000,
            'payment_destination' => 'BCA Operasional',
        ]);

        // Cicilan ke-2 (Pelunasan): 60.000
        $response2 = $this->actingAs($this->user)
            ->post(route('offline_sales.payments.store', $sale), [
                'amount'              => 60000,
                'payment_method'      => 'transfer',
                'payment_destination' => 'BCA Operasional',
                'payment_date'        => now()->toDateString(),
                'notes'               => 'Pelunasan Cicilan 2',
            ]);

        $response2->assertRedirect();
        $response2->assertSessionHas('success');

        $sale->refresh();
        $this->assertEquals(100000, (float)$sale->paid_amount);
        $this->assertEquals(0, (float)$sale->remaining_amount);
        $this->assertEquals('Lunas', $sale->payment_status_label);
        $this->assertTrue($sale->is_paid);

        $bank->refresh();
        $this->assertEquals(200000, (float)$bank->current_balance);

        // Hapus cicilan ke-2 dan verifikasi rollback
        $payment2 = $sale->payments()->where('amount', 60000)->first();
        $this->assertNotNull($payment2);

        $responseDelete = $this->actingAs($this->user)
            ->delete(route('offline_sales.payments.destroy', [$sale, $payment2]));

        $responseDelete->assertRedirect();
        $responseDelete->assertSessionHas('success');

        $sale->refresh();
        $this->assertEquals(40000, (float)$sale->paid_amount);
        $this->assertEquals(60000, (float)$sale->remaining_amount);
        $this->assertEquals('Dicicil', $sale->payment_status_label);

        $bank->refresh();
        $this->assertEquals(140000, (float)$bank->current_balance);

        $this->assertDatabaseMissing('offline_sale_payments', [
            'id' => $payment2->id,
        ]);
    }

    public function test_po_offline_sale_waiting_dp_and_follow_up(): void
    {
        $followUpDate = now()->addDays(3)->toDateString();

        $payload = [
            'items' => [
                [
                    'master_product_id' => $this->masterProduct->id,
                    'quantity'          => 2,
                    'unit_price'        => 50000,
                ]
            ],
            'is_po'          => 1,
            'deadline'       => now()->addDays(10)->toDateString(),
            'follow_up_date' => $followUpDate,
            'buyer_name'     => 'Customer PO Test',
            'buyer_phone'    => '081299887766',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('offline_sales.store'), $payload);

        $response->assertRedirect(route('offline_sales.index'));
        $response->assertSessionHas('success');

        // Check if OfflineSale record was created with status menunggu_dp and follow_up_date
        $sale = OfflineSale::where('tenant_id', $this->tenant->id)
            ->where('buyer_name', 'Customer PO Test')
            ->first();

        $this->assertNotNull($sale);
        $this->assertEquals(OfflineSale::STATUS_WAITING_DP, $sale->status);
        $this->assertEquals('Menunggu DP Masuk', $sale->status_label);
        $this->assertEquals($followUpDate, $sale->follow_up_date->toDateString());
        $this->assertEquals(0, (float)$sale->paid_amount);
        $this->assertEquals(100000, (float)$sale->grand_total);
        $this->assertFalse($sale->needs_follow_up); // Not overdue yet

        // Pastikan SPK BELUM dibuat saat input penjualan
        $this->assertEquals(0, \App\Models\Spk::where('tenant_id', $this->tenant->id)->count());

        // Set follow_up_date to past date to test overdue follow-up alert
        $sale->update(['follow_up_date' => now()->subDay()->toDateString()]);
        $sale->refresh();
        $this->assertTrue($sale->needs_follow_up); // Now it needs follow up!

        // Record DP payment
        $bank = \App\Models\BankAccount::create([
            'tenant_id'       => $this->tenant->id,
            'bank_name'       => 'BCA PO',
            'account_number'  => '1234567890',
            'account_name'    => 'Tenant Account',
            'current_balance' => 0,
            'is_active'       => true,
        ]);

        $payResponse = $this->actingAs($this->user)
            ->post(route('offline_sales.payments.store', $sale), [
                'amount'              => 30000, // DP 30.000
                'payment_method'      => 'transfer',
                'payment_destination' => 'BCA PO',
                'payment_date'        => now()->toDateString(),
                'notes'               => 'Pembayaran DP',
            ]);

        $payResponse->assertRedirect();
        $payResponse->assertSessionHas('success');

        $sale->refresh();
        // After DP payment, status automatically transitions to STATUS_PENDING_SPK ("Belum dibuat SPK")
        $this->assertEquals(OfflineSale::STATUS_PENDING_SPK, $sale->status);
        $this->assertEquals('Belum dibuat SPK', $sale->status_label);
        $this->assertEquals(30000, (float)$sale->paid_amount);
        $this->assertEquals(70000, (float)$sale->remaining_amount);
        $this->assertFalse($sale->needs_follow_up); // Because DP has been paid!

        // Terbitkan SPK
        $spkResponse = $this->actingAs($this->user)
            ->post(route('offline_sales.create_spk', $sale), [
                'deadline' => now()->addDays(7)->toDateString(),
            ]);

        $spkResponse->assertRedirect();
        $spkResponse->assertSessionHas('success');

        $this->assertEquals(1, \App\Models\Spk::where('tenant_id', $this->tenant->id)->count());
        $sale->refresh();
        $this->assertEquals(OfflineSale::STATUS_PENDING_APPROVAL, $sale->status);
        $this->assertEquals('Menunggu Approval', $sale->status_label);
    }

    public function test_delete_dp_payment_reverts_status_to_waiting_dp_and_deletes_income(): void
    {
        $bank = \App\Models\BankAccount::create([
            'tenant_id'       => $this->tenant->id,
            'bank_name'       => 'Mandiri PO',
            'account_number'  => '9876543210',
            'account_name'    => 'Tenant Mandiri',
            'current_balance' => 0,
            'is_active'       => true,
        ]);

        $sale = OfflineSale::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $this->user->id,
            'sale_number'     => 'SL-PO-DP-DEL-01',
            'status'          => OfflineSale::STATUS_WAITING_DP,
            'buyer_name'      => 'Customer Hapus DP',
            'payment_method'  => 'piutang',
            'total_amount'    => 200000,
            'grand_total'     => 200000,
            'paid_amount'     => 0,
            'change_amount'   => 0,
            'sold_at'         => now(),
            'is_po'           => true,
            'follow_up_date'  => now()->addDays(2)->toDateString(),
        ]);

        $this->assertEquals(OfflineSale::STATUS_WAITING_DP, $sale->status);

        // 1. Input Pembayaran DP
        $payResponse = $this->actingAs($this->user)
            ->post(route('offline_sales.payments.store', $sale), [
                'amount'              => 50000,
                'payment_method'      => 'transfer',
                'payment_destination' => 'Mandiri PO',
                'payment_date'        => now()->toDateString(),
                'notes'               => 'Pembayaran DP',
            ]);

        $payResponse->assertRedirect();
        $payResponse->assertSessionHas('success');

        $sale->refresh();
        $this->assertEquals(OfflineSale::STATUS_PENDING_SPK, $sale->status);
        $this->assertEquals('Belum dibuat SPK', $sale->status_label);
        $this->assertEquals(50000, (float)$sale->paid_amount);

        $bank->refresh();
        $this->assertEquals(50000, (float)$bank->current_balance);

        $payment = $sale->payments()->first();
        $this->assertNotNull($payment);
        $this->assertNotNull($payment->income_id);

        $this->assertDatabaseHas('incomes', [
            'id'                  => $payment->income_id,
            'tenant_id'           => $this->tenant->id,
            'amount'              => 50000,
            'payment_destination' => 'Mandiri PO',
        ]);

        // 2. Hapus Pembayaran DP
        $delResponse = $this->actingAs($this->user)
            ->delete(route('offline_sales.payments.destroy', [$sale, $payment]));

        $delResponse->assertRedirect();
        $delResponse->assertSessionHas('success');

        $sale->refresh();
        // Verifikasi: Status kembali ke Menunggu DP Masuk
        $this->assertEquals(OfflineSale::STATUS_WAITING_DP, $sale->status);
        $this->assertEquals('Menunggu DP Masuk', $sale->status_label);
        $this->assertEquals(0, (float)$sale->paid_amount);

        // Verifikasi: Record pembayaran terhapus
        $this->assertDatabaseMissing('offline_sale_payments', [
            'id' => $payment->id,
        ]);

        // Verifikasi: Mutasi keuangan (incomes) terhapus
        $this->assertDatabaseMissing('incomes', [
            'id' => $payment->income_id,
        ]);

        // Verifikasi: Saldo bank berkurang kembali
        $bank->refresh();
        $this->assertEquals(0, (float)$bank->current_balance);
    }

    public function test_po_sale_cannot_be_cancelled_if_dp_paid_and_can_be_cancelled_if_no_dp(): void
    {
        // 1. PO sale with DP paid cannot be cancelled
        $saleWithDp = OfflineSale::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $this->user->id,
            'sale_number'     => 'SL-PO-NO-CANCEL',
            'status'          => OfflineSale::STATUS_PENDING_SPK,
            'buyer_name'      => 'Customer PO Ada DP',
            'payment_method'  => 'transfer',
            'total_amount'    => 100000,
            'grand_total'     => 100000,
            'paid_amount'     => 50000,
            'sold_at'         => now(),
            'is_po'           => true,
        ]);

        $responseWithDp = $this->actingAs($this->user)
            ->post(route('offline_sales.cancel', $saleWithDp), [
                'cancellation_reason' => 'Mau dibatalkan padahal ada DP',
            ]);

        $responseWithDp->assertRedirect();
        $responseWithDp->assertSessionHas('error');
        $saleWithDp->refresh();
        $this->assertEquals(OfflineSale::STATUS_PENDING_SPK, $saleWithDp->status);

        // 2. PO sale with 0 DP (waiting_dp) CAN be cancelled
        $saleNoDp = OfflineSale::create([
            'tenant_id'       => $this->tenant->id,
            'user_id'         => $this->user->id,
            'sale_number'     => 'SL-PO-CAN-CANCEL',
            'status'          => OfflineSale::STATUS_WAITING_DP,
            'buyer_name'      => 'Customer PO Belum DP',
            'payment_method'  => 'piutang',
            'total_amount'    => 100000,
            'grand_total'     => 100000,
            'paid_amount'     => 0,
            'sold_at'         => now(),
            'is_po'           => true,
        ]);

        $responseNoDp = $this->actingAs($this->user)
            ->post(route('offline_sales.cancel', $saleNoDp), [
                'cancellation_reason' => 'Batal sebelum bayar DP',
            ]);

        $responseNoDp->assertRedirect();
        $responseNoDp->assertSessionHas('success');
        $saleNoDp->refresh();
        $this->assertEquals(OfflineSale::STATUS_CANCELLED, $saleNoDp->status);
    }
}




