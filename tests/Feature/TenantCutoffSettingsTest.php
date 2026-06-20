<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantCutoffSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;
    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a tenant
        $this->tenant = Tenant::create([
            'name' => 'Test Company',
            'status' => 'active',
        ]);

        // Create an admin user
        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create a regular user (non-admin)
        $this->regularUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Regular User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
            'role' => 'employee',
        ]);
    }

    public function test_guest_cannot_access_tenant_settings(): void
    {
        $response = $this->get(route('settings.tenant.edit'));
        $response->assertRedirect(route('login'));

        $response = $this->put(route('settings.tenant.update'), [
            'name' => 'New Name',
            'cutoff_start_day' => 25,
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_tenant_settings(): void
    {
        $this->actingAs($this->regularUser);

        $response = $this->get(route('settings.tenant.edit'));
        $response->assertStatus(403);

        $response = $this->put(route('settings.tenant.update'), [
            'name' => 'New Name',
            'cutoff_start_day' => 25,
        ]);
        $response->assertStatus(403);
    }

    public function test_admin_can_access_tenant_settings_page(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('settings.tenant.edit'));
        $response->assertStatus(200);
        $response->assertSee('Profil & Pengaturan Perusahaan', false);
        $response->assertSee('Hari Mulai Cut-off Presensi & Gaji', false);
    }

    public function test_admin_can_update_cutoff_settings(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->put(route('settings.tenant.update'), [
            'name' => 'Updated Company Name',
            'cutoff_start_day' => 25,
        ]);

        $response->assertRedirect(route('settings.tenant.edit'));
        $response->assertSessionHas('success');

        $this->tenant->refresh();
        $this->assertEquals('Updated Company Name', $this->tenant->name);
        $this->assertEquals(25, $this->tenant->cutoff_start_day);
    }

    public function test_invalid_cutoff_start_day_fails_validation(): void
    {
        $this->actingAs($this->adminUser);

        // Test below range (0)
        $response = $this->put(route('settings.tenant.update'), [
            'name' => 'Test Company',
            'cutoff_start_day' => 0,
        ]);
        $response->assertSessionHasErrors('cutoff_start_day');

        // Test above range (29)
        $response = $this->put(route('settings.tenant.update'), [
            'name' => 'Test Company',
            'cutoff_start_day' => 29,
        ]);
        $response->assertSessionHasErrors('cutoff_start_day');

        // Test non-integer
        $response = $this->put(route('settings.tenant.update'), [
            'name' => 'Test Company',
            'cutoff_start_day' => 'invalid',
        ]);
        $response->assertSessionHasErrors('cutoff_start_day');
    }

    public function test_tenant_get_cutoff_range_calculation(): void
    {
        // 1. Full month (1st of month)
        $this->tenant->cutoff_start_day = 1;
        $this->tenant->save();

        [$start, $end] = $this->tenant->getCutoffRange('2026-06');
        $this->assertEquals('2026-06-01', $start);
        $this->assertEquals('2026-06-30', $end);

        // 2. Custom cutoff (e.g. 21st)
        $this->tenant->cutoff_start_day = 21;
        $this->tenant->save();

        [$start, $end] = $this->tenant->getCutoffRange('2026-06');
        $this->assertEquals('2026-05-21', $start);
        $this->assertEquals('2026-06-20', $end);

        // 3. Custom cutoff (e.g. 25th)
        $this->tenant->cutoff_start_day = 25;
        $this->tenant->save();

        [$start, $end] = $this->tenant->getCutoffRange('2026-06');
        $this->assertEquals('2026-05-25', $start);
        $this->assertEquals('2026-06-24', $end);
    }

    public function test_attendance_report_page_displays_correct_cutoff_period(): void
    {
        // Set cutoff day to 25
        $this->tenant->cutoff_start_day = 25;
        $this->tenant->save();

        $this->actingAs($this->adminUser);

        // Request report page for June 2026
        $response = $this->get(route('hr.attendance.report', ['period' => '2026-06']));
        $response->assertStatus(200);

        // Should display the cutoff range: 25 May 2026 to 24 Jun 2026
        $response->assertSee('25 May 2026');
        $response->assertSee('24 Jun 2026');
    }
}
