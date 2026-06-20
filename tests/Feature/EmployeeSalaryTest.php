<?php

namespace Tests\Feature;

use App\Models\AllowanceType;
use App\Models\Employee;
use App\Models\EmployeeAllowance;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeSalaryTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;
    protected User $otherTenantAdmin;
    protected AllowanceType $allowanceType1;
    protected AllowanceType $allowanceType2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Main Company',
            'status' => 'active',
        ]);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@maincompany.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $otherTenant = Tenant::create([
            'name' => 'Other Company',
            'status' => 'active',
        ]);

        $this->otherTenantAdmin = User::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Admin',
            'email' => 'admin@othercompany.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->allowanceType1 = AllowanceType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Tunjangan Makan',
        ]);

        $this->allowanceType2 = AllowanceType::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Tunjangan Transport',
        ]);
    }

    public function test_admin_can_access_employees_index(): void
    {
        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Employee One',
            'position' => 'Staff',
            'basic_salary' => 1000000,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('employees.index'));

        $response->assertStatus(200);
        $response->assertSee('Employee One');
    }

    public function test_admin_can_store_employee_profile_with_defaults(): void
    {
        $payload = [
            'name' => 'New Staff member',
            'email' => 'staff@maincompany.com',
            'phone' => '0812345678',
            'position' => 'Developer',
            'join_date' => '2026-06-18',
            'address' => 'Bandung, Indonesia',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('employees.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('employees', [
            'tenant_id' => $this->tenant->id,
            'name' => 'New Staff member',
            'position' => 'Developer',
            'salary_type' => 'monthly',
            'basic_salary' => 0,
            'allowance' => 0,
            'overtime_rate' => 0,
        ]);
    }

    public function test_admin_can_update_employee_profile_only(): void
    {
        $employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Old Name',
            'position' => 'Old Position',
            'basic_salary' => 3000000,
            'allowance' => 500000,
        ]);

        $payload = [
            'name' => 'Updated Name',
            'position' => 'Updated Position',
            'email' => 'updated@maincompany.com',
            'phone' => '0899999',
            'address' => 'Jakarta',
            'join_date' => '2026-06-01',
            'is_active' => '1',
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('employees.update', $employee), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $employee->refresh();
        $this->assertEquals('Updated Name', $employee->name);
        $this->assertEquals('Updated Position', $employee->position);
        
        // Ensure salary and allowances did not change via the standard profile edit
        $this->assertEquals(3000000, $employee->basic_salary);
        $this->assertEquals(500000, $employee->allowance);
    }

    public function test_admin_can_update_employee_salary_and_allowances(): void
    {
        $employee = Employee::create([
            'tenant_id'    => $this->tenant->id,
            'name'         => 'Budi',
            'salary_type'  => 'monthly',
            'basic_salary' => 1000000,
            'allowance'    => 0,
        ]);

        $payload = [
            'salary_type'  => 'monthly',
            'basic_salary' => '2500000',
            'overtime_rate' => '25000',
            'schedules' => [
                ['day_of_week' => 1, 'clock_in' => '08:30', 'clock_out' => '16:30', 'is_off' => '0'],
                ['day_of_week' => 2, 'clock_in' => '08:30', 'clock_out' => '16:30', 'is_off' => '0'],
                ['day_of_week' => 3, 'clock_in' => '08:30', 'clock_out' => '16:30', 'is_off' => '0'],
                ['day_of_week' => 4, 'clock_in' => '08:30', 'clock_out' => '16:30', 'is_off' => '0'],
                ['day_of_week' => 5, 'clock_in' => '08:30', 'clock_out' => '16:30', 'is_off' => '0'],
                ['day_of_week' => 6, 'clock_in' => '07:30', 'clock_out' => '12:30', 'is_off' => '0'],
                ['day_of_week' => 7, 'clock_in' => null,    'clock_out' => null,    'is_off' => '1'],
            ],
            'allowances' => [
                $this->allowanceType1->id => '150000',
                $this->allowanceType2->id => '100000',
            ]
        ];

        $response = $this->actingAs($this->adminUser)
            ->put(route('employees.salary.update', $employee), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $employee->refresh();
        $this->assertEquals('monthly', $employee->salary_type);
        $this->assertEquals(2500000, $employee->basic_salary);
        $this->assertEquals(25000, $employee->overtime_rate);

        // Sum of allowances should be 250000
        $this->assertEquals(250000, $employee->allowance);

        // Verify schedule saved to employee_schedules table
        $this->assertDatabaseHas('employee_schedules', [
            'employee_id' => $employee->id,
            'day_of_week' => 1,
            'clock_in'    => '08:30',
            'clock_out'   => '16:30',
        ]);
        $this->assertDatabaseHas('employee_schedules', [
            'employee_id' => $employee->id,
            'day_of_week' => 6,
            'clock_in'    => '07:30',
            'clock_out'   => '12:30',
        ]);

        // Verify database relation records
        $this->assertDatabaseHas('employee_allowances', [
            'employee_id'      => $employee->id,
            'allowance_type_id' => $this->allowanceType1->id,
            'amount'           => 150000,
        ]);

        $this->assertDatabaseHas('employee_allowances', [
            'employee_id'      => $employee->id,
            'allowance_type_id' => $this->allowanceType2->id,
            'amount'           => 100000,
        ]);
    }

    public function test_tenant_isolation_on_updating_salary(): void
    {
        $employee = Employee::create([
            'tenant_id'    => $this->tenant->id,
            'name'         => 'Budi',
            'basic_salary' => 1000000,
        ]);

        $payload = [
            'salary_type'  => 'monthly',
            'basic_salary' => '9999999',
        ];

        // Try updating using other tenant admin
        $response = $this->actingAs($this->otherTenantAdmin)
            ->put(route('employees.salary.update', $employee), $payload);

        $response->assertStatus(403);

        $employee->refresh();
        $this->assertEquals(1000000, $employee->basic_salary);
    }
}
