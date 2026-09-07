<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Income;
use App\Models\Expense;
use App\Models\FundTransfer;
use App\Models\BankAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialMutationEditDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Finance Tenant',
        ]);

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'role' => 'admin',
        ]);
    }

    public function test_mutations_page_displays_incomes_expenses_and_transfers_with_action_buttons()
    {
        $bank = BankAccount::create([
            'tenant_id' => $this->tenant->id,
            'bank_name' => 'BCA Utama',
            'account_number' => '1234567890',
            'account_name' => 'PT Test',
            'current_balance' => 10000000,
            'is_active' => true,
        ]);

        $income = Income::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Suntikan Modal Tambahan',
            'category' => 'investment',
            'payment_destination' => 'BCA Utama',
            'amount' => 5000000,
            'income_date' => now()->toDateString(),
            'description' => 'Modal awal periode',
        ]);

        $expense = Expense::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Beli Alat Tulis Kantor',
            'category' => 'utilities',
            'payment_source' => 'BCA Utama',
            'amount' => 250000,
            'expense_date' => now()->toDateString(),
            'description' => 'Kertas & lakban',
        ]);

        $transfer = FundTransfer::create([
            'tenant_id' => $this->tenant->id,
            'source' => 'kas_besar',
            'destination' => 'kas_kecil',
            'amount' => 500000,
            'transfer_date' => now()->toDateString(),
            'description' => 'Isi kas kecil operasional',
        ]);

        $response = $this->actingAs($this->user)->get(route('finance.mutations.index'));

        $response->assertStatus(200);
        $response->assertSee('Suntikan Modal Tambahan');
        $response->assertSee('Beli Alat Tulis Kantor');
        $response->assertSee('Isi kas kecil operasional');
        $response->assertSee('edit-income-btn');
        $response->assertSee('edit-expense-btn');
        $response->assertSee('edit-transfer-btn');
        $response->assertSee(route('finance.incomes.destroy', $income->id));
        $response->assertSee(route('finance.expenses.destroy', $expense->id));
        $response->assertSee(route('finance.transfers.destroy', $transfer->id));
    }

    public function test_can_update_and_delete_income_with_redirect()
    {
        $income = Income::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Old Income Title',
            'category' => 'other',
            'payment_destination' => 'kas_besar',
            'amount' => 1000000,
            'income_date' => now()->toDateString(),
            'description' => 'Old Desc',
        ]);

        $returnUrl = route('finance.mutations.index', ['search' => 'Updated']);

        // Update
        $response = $this->actingAs($this->user)->put(route('finance.incomes.update', $income), [
            'title' => 'Updated Income Title',
            'category' => 'investment',
            'payment_destination' => 'kas_besar',
            'amount' => 2000000,
            'income_date' => now()->toDateString(),
            'description' => 'Updated Desc',
            'redirect_to' => $returnUrl,
        ]);

        $response->assertRedirect($returnUrl);
        $this->assertDatabaseHas('incomes', [
            'id' => $income->id,
            'title' => 'Updated Income Title',
            'amount' => 2000000,
        ]);

        // Delete
        $deleteResponse = $this->actingAs($this->user)->delete(route('finance.incomes.destroy', $income), [
            'redirect_to' => $returnUrl,
        ]);

        $deleteResponse->assertRedirect($returnUrl);
        $this->assertDatabaseMissing('incomes', [
            'id' => $income->id,
        ]);
    }

    public function test_can_update_and_delete_expense_with_redirect()
    {
        $expense = Expense::create([
            'tenant_id' => $this->tenant->id,
            'title' => 'Old Expense Title',
            'category' => 'utilities',
            'payment_source' => 'kas_kecil',
            'amount' => 150000,
            'expense_date' => now()->toDateString(),
            'description' => 'Old Desc',
        ]);

        $returnUrl = route('finance.mutations.index', ['source_type' => 'expense']);

        // Update
        $response = $this->actingAs($this->user)->put(route('finance.expenses.update', $expense), [
            'title' => 'Updated Expense Title',
            'category' => 'salary',
            'payment_source' => 'kas_kecil',
            'amount' => 300000,
            'expense_date' => now()->toDateString(),
            'description' => 'Updated Desc',
            'redirect_to' => $returnUrl,
        ]);

        $response->assertRedirect($returnUrl);
        $this->assertDatabaseHas('expenses', [
            'id' => $expense->id,
            'title' => 'Updated Expense Title',
            'amount' => 300000,
        ]);

        // Delete
        $deleteResponse = $this->actingAs($this->user)->delete(route('finance.expenses.destroy', $expense), [
            'redirect_to' => $returnUrl,
        ]);

        $deleteResponse->assertRedirect($returnUrl);
        $this->assertDatabaseMissing('expenses', [
            'id' => $expense->id,
        ]);
    }

    public function test_can_update_and_delete_transfer_with_redirect()
    {
        $transfer = FundTransfer::create([
            'tenant_id' => $this->tenant->id,
            'source' => 'kas_besar',
            'destination' => 'kas_kecil',
            'amount' => 500000,
            'transfer_date' => now()->toDateString(),
            'description' => 'Old Transfer',
        ]);

        $returnUrl = route('finance.mutations.index', ['source_type' => 'transfer']);

        // Update
        $response = $this->actingAs($this->user)->put(route('finance.transfers.update', $transfer), [
            'source' => 'kas_besar',
            'destination' => 'kas_kecil',
            'amount' => 750000,
            'transfer_date' => now()->toDateString(),
            'description' => 'Updated Transfer',
            'redirect_to' => $returnUrl,
        ]);

        $response->assertRedirect($returnUrl);
        $this->assertDatabaseHas('fund_transfers', [
            'id' => $transfer->id,
            'amount' => 750000,
            'description' => 'Updated Transfer',
        ]);

        // Delete
        $deleteResponse = $this->actingAs($this->user)->delete(route('finance.transfers.destroy', $transfer), [
            'redirect_to' => $returnUrl,
        ]);

        $deleteResponse->assertRedirect($returnUrl);
        $this->assertDatabaseMissing('fund_transfers', [
            'id' => $transfer->id,
        ]);
    }
}
