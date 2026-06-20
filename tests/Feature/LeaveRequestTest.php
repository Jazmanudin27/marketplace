<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\LeaveRequest;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveRequestTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'status' => 'active',
        ]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'HR Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'username' => 'johndoe',
            'password' => bcrypt('password'),
            'basic_salary' => 5000000,
        ]);
    }

    public function test_employee_can_submit_leave_request(): void
    {
        $payload = [
            'type' => 'sick',
            'start_date' => today()->addDays(2)->toDateString(),
            'end_date' => today()->addDays(3)->toDateString(),
            'notes' => 'Demam tinggi',
        ];

        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave = LeaveRequest::first();
        $this->assertNotNull($leave);
        $this->assertEquals($this->tenant->id, $leave->tenant_id);
        $this->assertEquals($this->employee->id, $leave->employee_id);
        $this->assertEquals('sick', $leave->type);
        $this->assertEquals(today()->addDays(2)->toDateString(), $leave->start_date->toDateString());
        $this->assertEquals(today()->addDays(3)->toDateString(), $leave->end_date->toDateString());
        $this->assertEquals('Demam tinggi', $leave->notes);
        $this->assertEquals('pending', $leave->status);
    }

    public function test_employee_can_submit_retroactive_leave_request(): void
    {
        $payload = [
            'type' => 'sick',
            'start_date' => today()->subDays(2)->toDateString(),
            'end_date' => today()->subDays(1)->toDateString(),
            'notes' => 'Sakit 2 hari lalu',
        ];

        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave = LeaveRequest::first();
        $this->assertNotNull($leave);
        $this->assertEquals(today()->subDays(2)->toDateString(), $leave->start_date->toDateString());
        $this->assertEquals(today()->subDays(1)->toDateString(), $leave->end_date->toDateString());
        $this->assertEquals('Sakit 2 hari lalu', $leave->notes);
    }

    public function test_employee_cannot_submit_overlapping_leave_request(): void
    {
        LeaveRequest::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'type' => 'leave',
            'start_date' => today()->addDays(2)->toDateString(),
            'end_date' => today()->addDays(5)->toDateString(),
            'status' => 'pending',
        ]);

        $payload = [
            'type' => 'sick',
            'start_date' => today()->addDays(4)->toDateString(),
            'end_date' => today()->addDays(6)->toDateString(),
            'notes' => 'Tumpang tindih',
        ];

        $response = $this->actingAs($this->employee, 'employee')
            ->post(route('employee.leaves.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertNull(LeaveRequest::where('notes', 'Tumpang tindih')->first());
    }

    public function test_admin_can_approve_leave_request(): void
    {
        $leave = LeaveRequest::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'type' => 'permission',
            'start_date' => today()->addDays(1)->toDateString(),
            'end_date' => today()->addDays(1)->toDateString(),
            'status' => 'pending',
            'is_deducted' => true,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('hr.leaves.approve', $leave));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave->refresh();
        $this->assertEquals('approved', $leave->status);
        $this->assertEquals($this->admin->id, $leave->approved_by);

        // Check attendance record generated
        $attendance = Attendance::first();
        $this->assertNotNull($attendance);
        $this->assertEquals($this->tenant->id, $attendance->tenant_id);
        $this->assertEquals($this->employee->id, $attendance->employee_id);
        $this->assertEquals(today()->addDays(1)->toDateString(), $attendance->date->toDateString());
        $this->assertEquals('permission', $attendance->status);
        $this->assertTrue($attendance->is_deducted);
    }

    public function test_admin_can_reject_leave_request(): void
    {
        $leave = LeaveRequest::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'type' => 'leave',
            'start_date' => today()->addDays(1)->toDateString(),
            'end_date' => today()->addDays(1)->toDateString(),
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('hr.leaves.reject', $leave));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $leave->refresh();
        $this->assertEquals('rejected', $leave->status);
    }
}
