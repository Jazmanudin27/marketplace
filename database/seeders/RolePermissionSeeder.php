<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\Tenant;
use App\Models\User;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Daftar menu / permissions global
        $permissions = [
            // Master Data
            'manage-categories',
            'manage-brands',
            'manage-suppliers',
            'manage-employees',
            'manage-customers',
            'manage-users',
            // Products
            'manage-products',
            // Toko
            'manage-stores',
            // Transaksi
            'manage-incoming-goods',
            'manage-orders',
            'manage-fulfillment',
            'manage-returns',
            'manage-offline-sales',
            'manage-chats',
            // Persediaan
            'manage-inventory',
            // Laporan
            'view-warehouse-reports',
            // Keuangan
            'view-financial-reports',
            'manage-finance',
        ];

        // Buat permission secara global
        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'web',
            ]);
        }

        // 3. Setup role dan assign ke masing-masing tenant
        $tenants = Tenant::all();
        foreach ($tenants as $tenant) {
            // Set team_id agar role yang dibuat terafiliasi dengan tenant
            setPermissionsTeamId($tenant->id);

            // Buat default roles untuk tenant ini
            $adminRole = Role::firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'admin',
                'guard_name' => 'web',
            ]);

            $warehouseRole = Role::firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'warehouse',
                'guard_name' => 'web',
            ]);

            $financeRole = Role::firstOrCreate([
                'tenant_id' => $tenant->id,
                'name' => 'finance',
                'guard_name' => 'web',
            ]);

            // Sync permission ke roles
            // Admin: Semua akses
            $adminRole->syncPermissions($permissions);

            // Warehouse: Kelola produk, stok, transaksi, & laporan gudang
            $warehouseRole->syncPermissions([
                'manage-products',
                'manage-incoming-goods',
                'manage-orders',
                'manage-fulfillment',
                'manage-returns',
                'manage-offline-sales',
                'manage-chats',
                'manage-inventory',
                'view-warehouse-reports',
            ]);

            // Finance: Melihat laporan keuangan & transaksi keuangan
            $financeRole->syncPermissions([
                'view-financial-reports',
                'manage-finance',
            ]);

            // 4. Petakan user yang sudah ada pada tenant ini ke Spatie Role berdasarkan kolom user.role
            $users = User::where('tenant_id', $tenant->id)->get();
            foreach ($users as $user) {
                if ($user->role === 'admin') {
                    $user->assignRole($adminRole);
                } elseif ($user->role === 'warehouse') {
                    $user->assignRole($warehouseRole);
                } elseif ($user->role === 'finance') {
                    $user->assignRole($financeRole);
                }
            }
        }
    }
}
