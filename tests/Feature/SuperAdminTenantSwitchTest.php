<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminTenantSwitchTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $systemTenant;
    protected Tenant $tenantA;
    protected Tenant $tenantB;
    protected User $superAdmin;
    protected User $adminA;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Tenant 1 (System / Super Admin)
        $this->systemTenant = Tenant::create([
            'name' => 'System Tenant',
            'status' => 'active',
        ]);

        // Create Tenant 2 (Company A)
        $this->tenantA = Tenant::create([
            'name' => 'Company A',
            'status' => 'active',
        ]);

        // Create Tenant 3 (Company B)
        $this->tenantB = Tenant::create([
            'name' => 'Company B',
            'status' => 'active',
        ]);

        // Super Admin
        $this->superAdmin = User::create([
            'tenant_id' => $this->systemTenant->id,
            'name' => 'Super Admin',
            'email' => 'superadmin@test.com',
            'password' => bcrypt('password'),
            'role' => 'super-admin',
        ]);

        // Admin A
        $this->adminA = User::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Admin A',
            'email' => 'admin_a@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        // Create some suppliers
        Supplier::create([
            'tenant_id' => $this->tenantA->id,
            'name' => 'Supplier A',
            'contact_person' => 'John',
            'is_active' => true,
        ]);

        Supplier::create([
            'tenant_id' => $this->tenantB->id,
            'name' => 'Supplier B',
            'contact_person' => 'Jane',
            'is_active' => true,
        ]);
    }

    public function test_super_admin_can_switch_tenant_in_session(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->post(route('switch-tenant'), [
            'tenant_id' => $this->tenantA->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals($this->tenantA->id, session('selected_tenant_id'));
        $this->assertEquals($this->tenantA->id, $this->superAdmin->tenant_id);
    }

    public function test_super_admin_context_isolation(): void
    {
        $this->actingAs($this->superAdmin);

        // 1. Without switching, Super Admin should see all suppliers
        $response = $this->get(route('suppliers.index'));
        $response->assertStatus(200);
        $response->assertSee('Supplier A');
        $response->assertSee('Supplier B');

        // 2. Switch to Tenant A (Company A)
        $this->post(route('switch-tenant'), [
            'tenant_id' => $this->tenantA->id,
        ]);

        // 3. Now it should only see Tenant A's suppliers
        $response = $this->get(route('suppliers.index'));
        $response->assertStatus(200);
        $response->assertSee('Supplier A');
        $response->assertDontSee('Supplier B');

        // 4. Switch back to System Tenant (ID 1)
        $this->post(route('switch-tenant'), [
            'tenant_id' => $this->systemTenant->id,
        ]);

        // 5. It should see all suppliers again
        $response = $this->get(route('suppliers.index'));
        $response->assertStatus(200);
        $response->assertSee('Supplier A');
        $response->assertSee('Supplier B');
    }

    public function test_non_super_admin_cannot_switch_tenant(): void
    {
        $this->actingAs($this->adminA);

        $response = $this->post(route('switch-tenant'), [
            'tenant_id' => $this->tenantB->id,
        ]);

        // Verify it doesn't allow switching (redirects but session selected_tenant_id is not set)
        $this->assertNull(session('selected_tenant_id'));
        $this->assertEquals($this->tenantA->id, $this->adminA->tenant_id);

        // Verify index only shows Admin A's suppliers
        $response = $this->get(route('suppliers.index'));
        $response->assertStatus(200);
        $response->assertSee('Supplier A');
        $response->assertDontSee('Supplier B');
    }

    public function test_admin_role_can_access_all_menus_except_company_settings(): void
    {
        $this->actingAs($this->adminA);

        // Can access suppliers (Master Supplier)
        $response = $this->get(route('suppliers.index'));
        $response->assertStatus(200);

        // Cannot access company settings
        $response = $this->get(route('settings.tenant.edit'));
        $response->assertStatus(403);
    }

    public function test_super_admin_can_access_company_settings(): void
    {
        $this->actingAs($this->superAdmin);

        $response = $this->get(route('settings.tenant.edit'));
        $response->assertStatus(200);
    }
}
