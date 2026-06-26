<?php

namespace Tests\Feature;

use App\Models\AllowanceType;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\MasterProduct;
use App\Models\Supplier;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Store;
use App\Models\Voucher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationValidationTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $adminA;
    protected User $adminB;
    protected MasterProduct $productA;
    protected MasterProduct $productB;
    protected Supplier $supplierA;
    protected Supplier $supplierB;
    protected Customer $customerA;
    protected Customer $customerB;
    protected Employee $employeeA;
    protected Employee $employeeB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class]);

        $this->tenantA = Tenant::create([
            'name' => 'Company A',
            'status' => 'active',
        ]);

        $this->tenantB = Tenant::create([
            'name' => 'Company B',
            'status' => 'active',
        ]);

        $this->adminA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin A',
            'email' => 'admina@companya.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->adminB = User::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Admin B',
            'email' => 'adminb@companyb.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->productA = MasterProduct::create([
            'tenant_id' => $this->tenantA->id,
            'sku' => 'SKU-A',
            'name' => 'Product A',
            'price' => 10000,
            'cost_price' => 5000,
            'stock' => 100,
            'is_active' => true,
        ]);

        $this->productB = MasterProduct::create([
            'tenant_id' => $this->tenantB->id,
            'sku' => 'SKU-B',
            'name' => 'Product B',
            'price' => 12000,
            'cost_price' => 6000,
            'stock' => 100,
            'is_active' => true,
        ]);

        $this->supplierA = Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Supplier A',
            'is_active' => true,
        ]);

        $this->supplierB = Supplier::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Supplier B',
            'is_active' => true,
        ]);

        $this->customerA = Customer::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Customer A',
            'phone' => '0811111111',
            'address' => 'Street A',
        ]);

        $this->customerB = Customer::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Customer B',
            'phone' => '0822222222',
            'address' => 'Street B',
        ]);

        $this->employeeA = Employee::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Employee A',
            'position' => 'Staff A',
            'basic_salary' => 1000000,
            'is_active' => true,
        ]);

        $this->employeeB = Employee::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Employee B',
            'position' => 'Staff B',
            'basic_salary' => 1200000,
            'is_active' => true,
        ]);
    }

    public function test_incoming_good_supplier_must_belong_to_active_tenant(): void
    {
        $payload = [
            'source_type' => 'supplier',
            'incoming_date' => now()->format('Y-m-d'),
            'reference' => 'REF-A-01',
            'products' => [$this->productA->id],
            'quantities' => [10],
            'supplier_id' => $this->supplierB->id, // belongs to Tenant B
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('incoming_goods.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['supplier_id']);
    }

    public function test_offline_sale_customer_must_belong_to_active_tenant(): void
    {
        $payload = [
            'customer_id' => $this->customerB->id, // belongs to Tenant B
            'items' => [
                [
                    'master_product_id' => $this->productA->id,
                    'quantity' => 1,
                    'unit_price' => 10000,
                ]
            ],
            'payment_method' => 'tunai',
            'paid_amount' => 10000,
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('offline_sales.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['customer_id']);
    }

    public function test_expense_employee_must_belong_to_active_tenant(): void
    {
        $payload = [
            'title' => 'Office utilities',
            'category' => 'utilities',
            'payment_source' => 'kas_kecil',
            'amount' => 50000,
            'expense_date' => now()->format('Y-m-d'),
            'employee_id' => $this->employeeB->id, // belongs to Tenant B
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('finance.expenses.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['employee_id']);
    }

    public function test_leave_request_employee_must_belong_to_active_tenant(): void
    {
        $payload = [
            'employee_id' => $this->employeeB->id, // belongs to Tenant B
            'type' => 'sick',
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'notes' => 'Sick leave',
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('hr.leaves.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['employee_id']);
    }

    public function test_attendance_correction_employee_must_belong_to_active_tenant(): void
    {
        $payload = [
            'employee_id' => $this->employeeB->id, // belongs to Tenant B
            'date' => now()->format('Y-m-d'),
            'clock_in' => '08:00',
            'clock_out' => '17:00',
            'reason' => 'Forgot to scan',
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('hr.attendance.corrections.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['employee_id']);
    }

    public function test_voucher_store_must_belong_to_active_tenant(): void
    {
        $channel = \App\Models\Channel::create([
            'name' => 'Shopee',
            'code' => 'shopee',
            'is_active' => true,
        ]);

        $storeB = Store::create([
            'tenant_id' => $this->tenantB->id,
            'channel_id' => $channel->id,
            'store_name' => 'Store B',
            'marketplace_store_id' => '123456',
            'status' => 'connected',
        ]);

        $payload = [
            'name' => 'Voucher 10K',
            'code' => 'VOUCH10K',
            'type' => 'fixed',
            'value' => 10000,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->addDays(5)->format('Y-m-d'),
            'store_id' => $storeB->id, // belongs to Tenant B
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('vouchers.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['store_id']);
    }

    public function test_holiday_employees_must_belong_to_active_tenant(): void
    {
        $holiday = Holiday::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'New Year',
            'date' => now()->format('Y-m-d'),
        ]);

        $payload = [
            'employee_ids' => [$this->employeeB->id], // belongs to Tenant B
        ];

        $response = $this->actingAs($this->adminA)
            ->post(route('hr.holidays.employees.update', $holiday), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['employee_ids']);
    }

    public function test_employee_salary_allowance_types_must_belong_to_active_tenant(): void
    {
        $allowanceTypeB = AllowanceType::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Transport B',
        ]);

        $payload = [
            'salary_type' => 'monthly',
            'basic_salary' => 2000000,
            'allowances' => [
                $allowanceTypeB->id => 100000, // belongs to Tenant B
            ]
        ];

        $response = $this->actingAs($this->adminA)
            ->put(route('employees.salary.update', $this->employeeA), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors(['allowances']);
    }
}
