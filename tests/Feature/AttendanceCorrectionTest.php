<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\LatePenaltyRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceCorrectionTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $admin;
    protected User $owner;
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Test Tenant',
            'status' => 'active',
        ]);

        // Create Spatie roles
        $adminRole = \Spatie\Permission\Models\Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'admin',
            'guard_name' => 'web',
        ]);
        $ownerRole = \Spatie\Permission\Models\Role::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'owner',
            'guard_name' => 'web',
        ]);

        // Seed permissions
        $managePerm = \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'manage-employees',
            'guard_name' => 'web',
        ]);
        $approvePerm = \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => 'approve-attendance-corrections',
            'guard_name' => 'web',
        ]);
        $adminRole->syncPermissions([$managePerm]);
        $ownerRole->syncPermissions([$managePerm, $approvePerm]);

        $this->admin = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'HR Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
        setPermissionsTeamId($this->tenant->id);
        $this->admin->assignRole($adminRole);

        $this->owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Shop Owner',
            'email' => 'owner@test.com',
            'password' => bcrypt('password'),
            'role' => 'owner',
        ]);
        $this->owner->assignRole($ownerRole);

        $this->employee = Employee::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'John Doe',
            'username' => 'johndoe',
            'password' => bcrypt('password'),
            'basic_salary' => 5000000,
        ]);
    }

    public function test_employee_cannot_submit_correction_request(): void
    {
        // Route should be removed/inactive for employee corrections
        $response = $this->actingAs($this->employee, 'employee')
            ->post('/employee/corrections', [
                'date' => '2026-06-15',
                'clock_in' => '08:00',
                'reason' => 'Lupa scan',
            ]);

        $response->assertStatus(404);
        $this->assertEquals(0, AttendanceCorrection::count());
    }

    public function test_admin_can_submit_correction_request(): void
    {
        $payload = [
            'employee_id' => $this->employee->id,
            'date' => '2026-06-15',
            'clock_in' => '08:00',
            'clock_out' => '17:00',
            'reason' => 'Karyawan lupa scan masuk dan pulang karena mati lampu',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('hr.attendance.corrections.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $correction = AttendanceCorrection::first();
        $this->assertNotNull($correction);
        $this->assertEquals($this->tenant->id, $correction->tenant_id);
        $this->assertEquals($this->employee->id, $correction->employee_id);
        $this->assertEquals('2026-06-15', $correction->date->toDateString());
        $this->assertEquals('08:00:00', $correction->clock_in);
        $this->assertEquals('17:00:00', $correction->clock_out);
        $this->assertEquals('pending', $correction->status);
    }

    public function test_admin_cannot_submit_duplicate_pending_correction(): void
    {
        AttendanceCorrection::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-06-15',
            'clock_in' => '08:00:00',
            'reason' => 'First correction',
            'status' => 'pending',
        ]);

        $payload = [
            'employee_id' => $this->employee->id,
            'date' => '2026-06-15',
            'clock_in' => '08:30',
            'reason' => 'Second correction',
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('hr.attendance.corrections.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertEquals(1, AttendanceCorrection::count());
    }

    public function test_owner_can_approve_correction_creating_attendance(): void
    {
        LatePenaltyRule::create([
            'tenant_id' => $this->tenant->id,
            'min_minutes' => 15,
            'penalty_amount' => 50000,
        ]);

        $correction = AttendanceCorrection::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-06-15', // Monday
            'clock_in' => '08:30:00', // 30 mins late
            'clock_out' => '16:00:00',
            'reason' => 'Lupa scan',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('hr.attendance.corrections.approve', $correction->id), [
                'admin_notes' => 'Disetujui owner',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $correction->refresh();
        $this->assertEquals('approved', $correction->status);
        $this->assertEquals('Disetujui owner', $correction->admin_notes);
        $this->assertEquals($this->owner->id, $correction->approved_by);

        // Check attendance created
        $att = Attendance::where('employee_id', $this->employee->id)->first();
        $this->assertNotNull($att);
        $this->assertEquals('08:30:00', $att->clock_in);
        $this->assertEquals(30, $att->late_minutes);
        $this->assertEquals(50000, $att->late_penalty);
    }

    public function test_owner_can_reject_correction(): void
    {
        $correction = AttendanceCorrection::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-06-15',
            'clock_in' => '08:00:00',
            'reason' => 'Lupa scan',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('hr.attendance.corrections.reject', $correction->id), [
                'admin_notes' => 'Ditolak owner',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $correction->refresh();
        $this->assertEquals('rejected', $correction->status);
        $this->assertEquals('Ditolak owner', $correction->admin_notes);
        $this->assertEquals($this->owner->id, $correction->approved_by);
    }

    public function test_admin_cannot_approve_or_reject_correction(): void
    {
        $correction = AttendanceCorrection::create([
            'tenant_id' => $this->tenant->id,
            'employee_id' => $this->employee->id,
            'date' => '2026-06-15',
            'clock_in' => '08:00:00',
            'reason' => 'Lupa scan',
            'status' => 'pending',
        ]);

        // Admin approve attempt should 403
        $responseApprove = $this->actingAs($this->admin)
            ->post(route('hr.attendance.corrections.approve', $correction->id), [
                'admin_notes' => 'Admin mencoba menyetujui',
            ]);
        $responseApprove->assertStatus(403);

        // Admin reject attempt should 403
        $responseReject = $this->actingAs($this->admin)
            ->post(route('hr.attendance.corrections.reject', $correction->id), [
                'admin_notes' => 'Admin mencoba menolak',
            ]);
        $responseReject->assertStatus(403);

        $correction->refresh();
        $this->assertEquals('pending', $correction->status);
    }
}
