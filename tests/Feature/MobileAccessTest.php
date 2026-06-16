<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileAccessTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected User $adminUser;
    protected User $gudangUser;
    protected User $produksiUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Mobile Test Tenant',
            'status' => 'active',
        ]);

        $this->adminUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin User',
            'email' => 'admin@mobiletest.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->gudangUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Gudang User',
            'email' => 'gudang@mobiletest.com',
            'password' => bcrypt('password'),
            'role' => 'warehouse',
        ]);

        $this->produksiUser = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Produksi User',
            'email' => 'produksi@mobiletest.com',
            'password' => bcrypt('password'),
            'role' => 'produksi',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('mobile.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_access_all_mobile_dashboards(): void
    {
        $this->actingAs($this->adminUser);

        // Redirect index
        $response = $this->get(route('mobile.index'));
        $response->assertRedirect(route('mobile.owner'));

        // Access Owner
        $this->get(route('mobile.owner'))->assertStatus(200);

        // Access Gudang
        $this->get(route('mobile.gudang'))->assertStatus(200);

        // Access Produksi
        $this->get(route('mobile.produksi'))->assertStatus(200);
    }

    public function test_gudang_user_access_control(): void
    {
        $this->actingAs($this->gudangUser);

        // Redirect index
        $response = $this->get(route('mobile.index'));
        $response->assertRedirect(route('mobile.gudang'));

        // Access Owner is Denied
        $this->get(route('mobile.owner'))->assertStatus(403);

        // Access Gudang is Allowed
        $this->get(route('mobile.gudang'))->assertStatus(200);

        // Access Produksi is Denied
        $this->get(route('mobile.produksi'))->assertStatus(403);
    }

    public function test_produksi_user_access_control(): void
    {
        $this->actingAs($this->produksiUser);

        // Redirect index
        $response = $this->get(route('mobile.index'));
        $response->assertRedirect(route('mobile.produksi'));

        // Access Owner is Denied
        $this->get(route('mobile.owner'))->assertStatus(403);

        // Access Gudang is Denied
        $this->get(route('mobile.gudang'))->assertStatus(403);

        // Access Produksi is Allowed
        $this->get(route('mobile.produksi'))->assertStatus(200);
    }
}
