<?php

namespace Tests\Feature;

use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\MasterProduct;
use App\Models\Spk;
use App\Models\SpkItem;
use App\Models\SpkItemExtra;
use App\Models\SpkPayment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpkPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $user;
    protected BankAccount $bank;
    protected Spk $spk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name'   => 'SPK Tenant Test',
            'status' => 'active',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role'      => 'admin',
        ]);

        $this->bank = BankAccount::create([
            'tenant_id'       => $this->tenant->id,
            'bank_name'       => 'BCA Operasional',
            'account_number'  => '987654321',
            'account_holder'  => 'PT Produksi Maju',
            'current_balance' => 10000000,
        ]);

        $this->spk = Spk::create([
            'tenant_id'   => $this->tenant->id,
            'no_produksi' => 'JN2609001',
            'no_spk'      => 'SPK-20260910-0001',
            'tanggal'     => now(),
            'deadline'    => now()->addDays(14),
            'pemesan'     => 'Distributor Jakarta',
        ]);

        $item = $this->spk->items()->create([
            'nama_produk' => 'Kemeja Casual',
            'sku'         => 'KMJ-01',
            'quantity'    => 100,
        ]);

        // Biaya Produksi: 500.000 (Jasa Jahit 400.000 + Jasa Potong 100.000)
        SpkItemExtra::create([
            'spk_item_id' => $item->id,
            'keterangan'  => 'Jasa Jahit (100 pcs)',
            'nominal'     => 400000,
        ]);
        SpkItemExtra::create([
            'spk_item_id' => $item->id,
            'keterangan'  => 'Jasa Potong (100 pcs)',
            'nominal'     => 100000,
        ]);
        // Bahan: 300.000 (tidak dihitung sebagai ongkos produksi)
        SpkItemExtra::create([
            'spk_item_id' => $item->id,
            'keterangan'  => 'Bahan: Katun Toyobo',
            'nominal'     => 300000,
        ]);
    }

    public function test_spk_payments_index_page_is_accessible_and_displays_kpi(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('spks.payments.index'));

        $response->assertStatus(200);
        $response->assertViewIs('inventory.spks.payments');
        $response->assertSee('Rekap Pembayaran Produksi SPK');
        $response->assertSee('JN2609001');
        $response->assertSee('Distributor Jakarta');
        $response->assertSee('Rp 500.000'); // Target ongkos produksi
        $response->assertSee('Belum Bayar');
    }

    public function test_can_record_first_installment_payment_for_spk_production(): void
    {
        $this->assertEquals(500000, $this->spk->total_biaya_produksi);
        $this->assertEquals(0, $this->spk->total_paid_production);
        $this->assertEquals(500000, $this->spk->remaining_production_cost);
        $this->assertEquals('Belum Bayar', $this->spk->production_payment_status_label);

        // Cicilan ke-1: DP 200.000 dibayarkan via BCA ke Penjahit
        $payload = [
            'amount'          => 200000,
            'payment_date'    => now()->toDateString(),
            'payment_source'  => (string) $this->bank->id,
            'recipient_name'  => 'Konveksi Berkah',
            'payment_type'    => 'biaya_produksi',
            'notes'           => 'DP Jahit 40%',
        ];

        $response = $this->actingAs($this->user)
            ->post(route('spks.payments.store', $this->spk), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->spk->refresh();
        $this->bank->refresh();

        // Bank terpotong 200.000
        $this->assertEquals(9800000, (float) $this->bank->current_balance);

        // Status SPK menjadi Dicicil
        $this->assertEquals(200000, (float) $this->spk->total_paid_production);
        $this->assertEquals(300000, (float) $this->spk->remaining_production_cost);
        $this->assertEquals('Dicicil', $this->spk->production_payment_status_label);

        // SpkPayment tercatat
        $this->assertDatabaseHas('spk_payments', [
            'tenant_id'       => $this->tenant->id,
            'spk_id'          => $this->spk->id,
            'amount'          => 200000,
            'bank_account_id' => $this->bank->id,
            'recipient_name'  => 'Konveksi Berkah',
            'notes'           => 'DP Jahit 40%',
        ]);

        // Jurnal Pengeluaran (Expense) tercatat
        $this->assertDatabaseHas('expenses', [
            'tenant_id' => $this->tenant->id,
            'amount'    => 200000,
            'category'  => 'salary',
        ]);
    }

    public function test_can_record_second_installment_payment_and_reaches_lunas(): void
    {
        // Cicilan 1: 200.000
        $this->actingAs($this->user)->post(route('spks.payments.store', $this->spk), [
            'amount'          => 200000,
            'payment_date'    => now()->toDateString(),
            'payment_source'  => 'kas_besar',
            'recipient_name'  => 'Konveksi Berkah',
        ]);

        // Cicilan 2 (Pelunasan sisa): 300.000
        $response = $this->actingAs($this->user)->post(route('spks.payments.store', $this->spk), [
            'amount'          => 300000,
            'payment_date'    => now()->toDateString(),
            'payment_source'  => 'kas_besar',
            'recipient_name'  => 'Konveksi Berkah',
            'notes'           => 'Pelunasan sisa ongkos jahit',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->spk->refresh();
        $this->assertEquals(500000, (float) $this->spk->total_paid_production);
        $this->assertEquals(0, (float) $this->spk->remaining_production_cost);
        $this->assertEquals('Lunas', $this->spk->production_payment_status_label);
        $this->assertEquals(100.0, $this->spk->production_payment_percentage);
    }

    public function test_cannot_pay_more_than_remaining_production_cost(): void
    {
        // Mencoba bayar 600.000 padahal tagihan hanya 500.000
        $response = $this->actingAs($this->user)->post(route('spks.payments.store', $this->spk), [
            'amount'         => 600000,
            'payment_date'   => now()->toDateString(),
            'payment_source' => 'kas_besar',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->spk->refresh();
        $this->assertEquals(0, (float) $this->spk->total_paid_production);
    }

    public function test_can_rollback_or_delete_spk_installment_payment(): void
    {
        // Bayar 250.000 via bank
        $this->actingAs($this->user)->post(route('spks.payments.store', $this->spk), [
            'amount'         => 250000,
            'payment_date'   => now()->toDateString(),
            'payment_source' => (string) $this->bank->id,
            'recipient_name' => 'Penjahit Rapi',
        ]);

        $this->spk->refresh();
        $this->bank->refresh();
        $this->assertEquals(9750000, (float) $this->bank->current_balance);
        $this->assertEquals(250000, (float) $this->spk->total_paid_production);

        $payment = $this->spk->payments()->first();
        $this->assertNotNull($payment);
        $expenseId = $payment->expense_id;

        // Hapus cicilan
        $response = $this->actingAs($this->user)
            ->delete(route('spks.payments.destroy', [$this->spk, $payment]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->spk->refresh();
        $this->bank->refresh();

        // Saldo bank kembali ke 10.000.000
        $this->assertEquals(10000000, (float) $this->bank->current_balance);
        $this->assertEquals(0, (float) $this->spk->total_paid_production);
        $this->assertEquals('Belum Bayar', $this->spk->production_payment_status_label);

        // Record terhapus
        $this->assertDatabaseMissing('spk_payments', ['id' => $payment->id]);
        $this->assertDatabaseMissing('expenses', ['id' => $expenseId]);
    }
}
