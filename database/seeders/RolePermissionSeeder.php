<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * RolePermissionSeeder — Hak Akses Granular per Role & per User
 *
 * Roles yang dibuat:
 *  - super-admin   → Global (tenant_id = NULL), bisa lihat & kelola semua perusahaan
 *  - admin         → Per tenant, full akses dalam 1 perusahaan
 *  - admin-gudang  → Per tenant, fokus produk / inventory / pesanan
 *  - admin-keuangan → Per tenant, fokus keuangan & laporan
 *  - admin-produksi → Per tenant, fokus produksi & HRD
 *
 * Tiap role/user di-scope ke tenant via Spatie Teams (team_foreign_key = tenant_id).
 * Hak akses bisa di-override per user dengan direct permission assignment.
 */
class RolePermissionSeeder extends Seeder
{
    // =========================================================================
    // DEFINISI PERMISSION PER KELOMPOK
    // =========================================================================

    /** Semua permission yang ada di sistem */
    private function allPermissions(): array
    {
        return Permission::where('guard_name', 'web')->pluck('name')->toArray();
    }

    /** Permission modul Dashboard */
    private function dashboardPermissions(): array
    {
        return ['dashboard.index'];
    }

    /** Permission modul Master Data */
    private function masterDataPermissions(): array
    {
        return [
            'categories.index',
            'categories.create',
            'categories.edit',
            'categories.destroy',
            'brands.index',
            'brands.create',
            'brands.edit',
            'brands.destroy',
            'suppliers.index',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.destroy',
            'customers.index',
            'customers.show',
            'customers.create',
            'customers.edit',
            'customers.destroy',
            'departments.index',
            'departments.create',
            'departments.edit',
            'departments.destroy',
            'inventory-items.index',
            'inventory-items.create',
            'inventory-items.edit',
            'inventory-items.destroy',
            'tailors.index',
            'tailors.create',
            'tailors.edit',
            'tailors.destroy',
            'labor-services.index',
            'labor-services.create',
            'labor-services.edit',
            'labor-services.destroy',
            'production-statuses.index',
            'production-statuses.create',
            'production-statuses.edit',
            'production-statuses.destroy',
        ];
    }

    /** Permission modul Produk & Marketplace */
    private function productPermissions(): array
    {
        return [
            'products.index',
            'products.show',
            'products.create',
            'products.edit',
            'products.destroy',
            'products.publish',
            'products.export',
            'marketplace-products.index',
            'marketplace-products.link',
            'marketplace-products.settings',
            'marketplace-products.promote',
        ];
    }

    /** Permission modul Toko / Channel */
    private function storePermissions(): array
    {
        return [
            'stores.index',
            'stores.create',
            'stores.edit',
            'stores.destroy',
            'stores.sync',
        ];
    }

    /** Permission modul Pesanan & Transaksi */
    private function orderPermissions(): array
    {
        return [
            'orders.index',
            'orders.show',
            'orders.create',
            'orders.process',
            'orders.ship',
            'orders.print',
            'orders.export',
            'orders.sync',
            'fulfillment.index',
            'fulfillment.scan',
            'fulfillment.complete',
            'returns.index',
            'returns.sync',
            'returns.restock',
            'offline-sales.index',
            'offline-sales.show',
            'offline-sales.create',
            'offline-sales.complete',
            'offline-sales.cancel',
            'offline-sales.print',
            'chats.index',
            'chats.show',
            'chats.reply',
            'chats.sync',
        ];
    }

    /** Permission modul Inventory & Stok */
    private function inventoryPermissions(): array
    {
        return [
            'inventory.index',
            'inventory.ledger',
            'inventory.adjust',
            'inventory.stock_sync',
            'incoming-goods.index',
            'incoming-goods.create',
            'stock-opnames.index',
            'stock-opnames.create',
            
            // Purchase/Pembelian & Bahan
            'purchase-orders.index',
            'purchase-orders.create',
            'purchase-orders.edit',
            'purchase-orders.destroy',
            'purchase-orders.report',
            'goods-receipts.index',
            'goods-receipts.create',
            'goods-receipts.edit',
            'goods-receipts.destroy',
            'goods-issues.index',
            'goods-issues.create',
            'goods-issues.edit',
            'goods-issues.destroy',
            'purchase-returns.index',
            'purchase-returns.create',
            'purchase-returns.edit',
            'purchase-returns.destroy',
            'pembelian.stock_report',
            'pembelian.report_mutation',
            'pembelian.report_summary',
            'pembelian.stock_card',
            
            // Pengaduan Barang Gudang
            'complaints.index',
            'complaints.show',
            'complaints.create',
            'complaints.edit',
            'complaints.destroy',
        ];
    }

    /** Permission modul HRD lengkap */
    private function hrdFullPermissions(): array
    {
        return [
            'employees.index',
            'employees.create',
            'employees.edit',
            'employees.destroy',
            'employees.salary',
            'attendance.index',
            'attendance.create',
            'attendance.edit',
            'attendance.destroy',
            'attendance.report',
            'attendance.print',
            'attendance-corrections.propose',
            'attendance-corrections.approve',
            'overtime.index',
            'overtime.create',
            'overtime.edit',
            'overtime.destroy',
            'overtime.approve',
            'leave-requests.index',
            'leave-requests.create',
            'leave-requests.edit',
            'leave-requests.destroy',
            'leave-requests.approve',
            'cash-advances.index',
            'cash-advances.create',
            'cash-advances.edit',
            'cash-advances.destroy',
            'cash-advances.approve',
            'payroll.index',
            'payroll.show',
            'payroll.generate',
            'payroll.edit',
            'payroll.pay',
            'payroll.print',
            'payroll.destroy',
            'holidays.index',
            'holidays.create',
            'holidays.edit',
            'holidays.destroy',
            'allowance-types.index',
            'allowance-types.create',
            'allowance-types.edit',
            'allowance-types.destroy',
            'late-penalties.index',
            'late-penalties.create',
            'late-penalties.edit',
            'late-penalties.destroy',
        ];
    }

    /** Permission modul HRD terbatas (hanya lihat presensi) */
    private function hrdViewOnlyPermissions(): array
    {
        return [
            'attendance.index',
            'attendance.report',
        ];
    }

    /** Permission modul Keuangan lengkap */
    private function financeFullPermissions(): array
    {
        return [
            'finance.incomes.index',
            'finance.incomes.create',
            'finance.incomes.edit',
            'finance.incomes.destroy',
            'finance.expenses.index',
            'finance.expenses.create',
            'finance.expenses.edit',
            'finance.expenses.destroy',
            'finance.transfers.index',
            'finance.transfers.create',
            'finance.transfers.edit',
            'finance.transfers.destroy',
            'finance.reconciliation.index',
            'finance.profit-loss.index',
            'profit.index',
            'profit.margin',
            'reports.product_margins',
            'reports.store_sales',
            'reports.reseller_receivables',
            'reports.inventory_turnover',
        ];
    }

    /** Permission modul Laporan Gudang lengkap */
    private function warehouseReportPermissions(): array
    {
        return [
            'reports.summary',
            'reports.summary.print',
            'reports.stock',
            'reports.stock.print',
            'reports.ledger',
            'reports.ledger.print',
            'reports.opname',
            'reports.opname.print',
            'reports.analytics',
            'reports.master_product',
        ];
    }

    /** Permission modul Laporan Gudang (read-only, untuk keuangan) */
    private function warehouseReportViewPermissions(): array
    {
        return [
            'reports.summary',
            'reports.stock',
        ];
    }

    /** Permission modul Produksi */
    private function productionPermissions(): array
    {
        return [
            'spks.index',
            'spks.show',
            'spks.create',
            'spks.edit',
            'spks.destroy',
            'product-recipes.index',
            'product-recipes.create',
            'product-recipes.edit',
            'product-recipes.destroy',
            'reports.production_hpp',
        ];
    }

    /** Permission manajemen Users & Roles */
    private function userManagementPermissions(): array
    {
        return [
            'users.index',
            'users.create',
            'users.edit',
            'users.destroy',
            'roles.index',
            'roles.create',
            'roles.edit',
            'roles.destroy',
        ];
    }

    /** Permission User & Role terbatas (tanpa destroy, untuk admin) */
    private function userManagementSafePermissions(): array
    {
        return [
            'users.index',
            'users.create',
            'users.edit',
            'roles.index',
            'roles.create',
            'roles.edit',
        ];
    }

    /** Permission Pengaturan Tenant */
    private function tenantSettingsPermissions(): array
    {
        return ['settings.tenant.edit'];
    }

    /** Permission Super Admin: Kelola semua tenant */
    private function superAdminOnlyPermissions(): array
    {
        return [
            'tenants.index',
            'tenants.create',
            'tenants.edit',
            'tenants.destroy',
        ];
    }

    /** Legacy / Backward-Compatibility Permissions */
    private function legacyPermissions(): array
    {
        return [
            'manage-categories',
            'manage-brands',
            'manage-suppliers',
            'manage-employees',
            'manage-customers',
            'manage-users',
            'manage-products',
            'manage-stores',
            'manage-incoming-goods',
            'manage-orders',
            'manage-fulfillment',
            'manage-returns',
            'manage-offline-sales',
            'manage-chats',
            'manage-inventory',
            'view-warehouse-reports',
            'view-financial-reports',
            'manage-finance',
            'view-attendance',
            'propose-attendance-correction',
            'approve-attendance-correction',
            'approve-attendance-corrections',
            'print-attendance-report',
        ];
    }

    // =========================================================================
    // PERMISSION PER ROLE
    // =========================================================================

    /** Permission Admin: hampir semua, kecuali aksi berbahaya (tenant, super-admin) */
    private function adminPermissions(): array
    {
        return array_unique(array_merge(
            $this->dashboardPermissions(),
            $this->masterDataPermissions(),
            $this->productPermissions(),
            $this->storePermissions(),
            $this->orderPermissions(),
            $this->inventoryPermissions(),
            $this->hrdFullPermissions(),
            $this->financeFullPermissions(),
            $this->warehouseReportPermissions(),
            $this->productionPermissions(),
            $this->userManagementSafePermissions(), // tanpa destroy users/roles
            $this->tenantSettingsPermissions(),
            $this->legacyPermissions(), // All legacy permissions
        ));
    }

    /** Permission Admin Gudang */
    private function adminGudangPermissions(): array
    {
        return array_unique(array_merge(
            $this->dashboardPermissions(),
            $this->productPermissions(),
            $this->storePermissions(),
            $this->orderPermissions(),
            $this->inventoryPermissions(),
            $this->warehouseReportPermissions(),
            $this->hrdViewOnlyPermissions(), // hanya lihat presensi
            // Warehouse legacy permissions
            [
                'manage-products',
                'manage-incoming-goods',
                'manage-orders',
                'manage-fulfillment',
                'manage-returns',
                'manage-offline-sales',
                'manage-chats',
                'manage-inventory',
                'view-warehouse-reports',
            ]
        ));
    }

    /** Permission Admin Keuangan */
    private function adminKeuanganPermissions(): array
    {
        return array_unique(array_merge(
            $this->dashboardPermissions(),
            $this->financeFullPermissions(),
            $this->warehouseReportViewPermissions(),
            // Lihat pesanan saja (untuk keperluan rekonsiliasi)
            [
                'orders.index',
                'orders.show',
                'orders.export',
                'offline-sales.index',
                'offline-sales.show',
            ],
            // Finance legacy permissions
            [
                'view-financial-reports',
                'manage-finance',
            ]
        ));
    }

    /** Permission Admin Produksi */
    private function adminProduksiPermissions(): array
    {
        return array_unique(array_merge(
            $this->dashboardPermissions(),
            $this->productionPermissions(),
            // HRD: presensi, lembur, cuti, kasbon, payroll (lihat saja)
            [
                'employees.index',
                'attendance.index',
                'attendance.create',
                'attendance.edit',
                'attendance.destroy',
                'attendance.report',
                'attendance.print',
                'attendance-corrections.propose',
                'attendance-corrections.approve',
                'overtime.index',
                'overtime.create',
                'overtime.approve',
                'leave-requests.index',
                'leave-requests.create',
                'leave-requests.approve',
                'cash-advances.index',
                'cash-advances.create',
                'cash-advances.approve',
                'payroll.index',
                'payroll.show',
                'payroll.print',
                'holidays.index',
            ],
            // Inventory: lihat saja
            [
                'inventory.index',
                'inventory.ledger',
            ],
            // Produk: lihat saja
            [
                'products.index',
                'products.show',
            ],
            // HRD/Attendance legacy permissions
            [
                'view-attendance',
                'propose-attendance-correction',
                'approve-attendance-correction',
                'approve-attendance-corrections',
                'print-attendance-report',
            ]
        ));
    }

    /** Permission Admin Penjualan */
    private function adminPenjualanPermissions(): array
    {
        return array_unique(array_merge(
            $this->dashboardPermissions(),
            // Pesanan online & offline — akses penuh
            $this->orderPermissions(),
            [
                'offline-sales.index',
                'offline-sales.create',
                'offline-sales.edit',
                'offline-sales.destroy',
                'offline-sales.print',
            ],
            // Pelanggan — akses penuh
            [
                'customers.index',
                'customers.create',
                'customers.edit',
                'customers.destroy',
            ],
            // Produk & Toko — lihat saja (tidak bisa ubah harga/stok)
            [
                'products.index',
                'products.show',
            ],
            $this->storePermissions(),   // kelola toko/channel
            // Voucher
            [
                'vouchers.index',
                'vouchers.create',
                'vouchers.edit',
                'vouchers.destroy',
            ],
            // Inventory — lihat saja
            [
                'inventory.index',
                'inventory.ledger',
            ],
            // Laporan penjualan
            [
                'reports.sales',
                'reports.export',
            ],
            // Sales/Order legacy permissions
            [
                'manage-orders',
                'manage-offline-sales',
                'manage-stores',
                'manage-customers',
            ]
        ));
    }

    // =========================================================================
    // RUN
    // =========================================================================

    public function run(): void
    {
        // 1. Reset cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 1b. Dynamically ensure all granular permissions are registered in database
        $allPermissions = array_unique(array_merge(
            $this->dashboardPermissions(),
            $this->masterDataPermissions(),
            $this->productPermissions(),
            $this->storePermissions(),
            $this->orderPermissions(),
            $this->inventoryPermissions(),
            $this->hrdFullPermissions(),
            $this->hrdViewOnlyPermissions(),
            $this->financeFullPermissions(),
            $this->warehouseReportPermissions(),
            $this->warehouseReportViewPermissions(),
            $this->productionPermissions(),
            $this->userManagementPermissions(),
            $this->userManagementSafePermissions(),
            $this->tenantSettingsPermissions(),
            $this->superAdminOnlyPermissions(),
            $this->legacyPermissions()
        ));

        foreach ($allPermissions as $permName) {
            Permission::firstOrCreate([
                'name'       => $permName,
                'guard_name' => 'web',
            ]);
        }

        // =====================================================================
        // 2. SUPER ADMIN — Tenant Sistem Khusus
        // =====================================================================
        // Super Admin memerlukan tenant_id valid (FK constraint) dan Spatie team.
        // Kita buat tenant "System" khusus yang merepresentasikan "global admin".
        // Deteksi Super Admin via kolom users.role = 'super-admin' (bukan via tenant_id).
        $systemTenant = Tenant::firstOrCreate(
            ['name' => 'Super Admin (Global)'],
            ['name' => 'Super Admin (Global)', 'status' => 'active']
        );

        setPermissionsTeamId($systemTenant->id);

        $superAdminRole = Role::firstOrCreate([
            'name'       => 'super-admin',
            'guard_name' => 'web',
        ]);

        // Super Admin mendapat SEMUA permission termasuk kelola tenant
        $superAdminRole->syncPermissions($this->allPermissions());

        // Buat / update user Super Admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@erp.com'],
            [
                'tenant_id' => $systemTenant->id,
                'name'      => 'Super Administrator',
                'password'  => Hash::make('SuperAdmin@2024!'),
                'role'      => 'super-admin',
            ]
        );

        // Pastikan tenant_id benar jika user sudah ada
        if ($superAdmin->tenant_id !== $systemTenant->id) {
            $superAdmin->update(['tenant_id' => $systemTenant->id]);
        }

        $superAdmin->syncRoles([$superAdminRole]);

        // =====================================================================
        // 3. ROLE PER TENANT (hanya tenant yang bukan Super Admin (Global))
        // =====================================================================
        $tenants = Tenant::where('name', '!=', 'Super Admin (Global)')->get();

        foreach ($tenants as $tenant) {
            // Set team ID agar semua operasi Role/Permission terikat ke tenant ini
            setPermissionsTeamId($tenant->id);

            // ── Buat / pastikan semua role ada untuk tenant ini ──────────────
            $adminRole = Role::firstOrCreate([
                'name'       => 'admin',
                'guard_name' => 'web',
            ]);

            $ownerRole = Role::firstOrCreate([
                'name'       => 'owner',
                'guard_name' => 'web',
            ]);

            $adminGudangRole = Role::firstOrCreate([
                'name'       => 'admin-gudang',
                'guard_name' => 'web',
            ]);

            $adminKeuanganRole = Role::firstOrCreate([
                'name'       => 'admin-keuangan',
                'guard_name' => 'web',
            ]);

            $adminProduksiRole = Role::firstOrCreate([
                'name'       => 'admin-produksi',
                'guard_name' => 'web',
            ]);

            $adminPenjualanRole = Role::firstOrCreate([
                'name'       => 'admin-penjualan',
                'guard_name' => 'web',
            ]);

            // Role lama — pertahankan (backward-compatible)
            $warehouseRole = Role::firstOrCreate([
                'name'       => 'warehouse',
                'guard_name' => 'web',
            ]);

            $financeRole = Role::firstOrCreate([
                'name'       => 'finance',
                'guard_name' => 'web',
            ]);

            // ── Sync Permission ke setiap Role ───────────────────────────────

            // Owner: Semua permission dalam tenant (sama seperti admin, tapi bisa hapus user/role)
            $ownerRole->syncPermissions(
                array_unique(array_merge(
                    $this->adminPermissions(),
                    $this->userManagementPermissions(), // termasuk destroy
                ))
            );

            // Admin: Hampir semua, kecuali destroy users/roles
            $adminRole->syncPermissions($this->adminPermissions());

            // Admin Gudang: Fokus produk, inventory, pesanan
            $adminGudangRole->syncPermissions($this->adminGudangPermissions());

            // Admin Keuangan: Fokus keuangan & laporan
            $adminKeuanganRole->syncPermissions($this->adminKeuanganPermissions());

            // Admin Produksi: Fokus produksi & HRD
            $adminProduksiRole->syncPermissions($this->adminProduksiPermissions());

            // Admin Penjualan: Fokus pesanan, pelanggan, toko
            $adminPenjualanRole->syncPermissions($this->adminPenjualanPermissions());

            // Role lama: warehouse → sama seperti admin-gudang
            $warehouseRole->syncPermissions($this->adminGudangPermissions());

            // Role lama: finance → sama seperti admin-keuangan
            $financeRole->syncPermissions($this->adminKeuanganPermissions());

            // ── Petakan user yang sudah ada ke role Spatie ───────────────────
            $users = User::where('tenant_id', $tenant->id)->get();

            foreach ($users as $user) {
                $roleMap = [
                    'admin'            => $adminRole,
                    'owner'            => $ownerRole,
                    'admin-gudang'     => $adminGudangRole,
                    'admin-keuangan'   => $adminKeuanganRole,
                    'admin-produksi'   => $adminProduksiRole,
                    'admin-penjualan'  => $adminPenjualanRole,
                    'warehouse'        => $warehouseRole,
                    'finance'          => $financeRole,
                ];

                if (isset($roleMap[$user->role])) {
                    $user->syncRoles([$roleMap[$user->role]]);
                }
            }

            // ── Buat user demo per role (hanya jika belum ada) ───────────────
            $this->createDemoUsers($tenant, [
                'adminRole'        => $adminRole,
                'adminGudangRole'  => $adminGudangRole,
                'adminKeuanganRole' => $adminKeuanganRole,
                'adminProduksiRole' => $adminProduksiRole,
                'adminPenjualanRole' => $adminPenjualanRole,
                'ownerRole'        => $ownerRole,
            ]);
        }

        // Reset cache setelah semua selesai
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
    }

    // =========================================================================
    // HELPER: Buat user demo per tenant
    // =========================================================================
    private function createDemoUsers(Tenant $tenant, array $roles): void
    {
        $slug = \Illuminate\Support\Str::slug($tenant->name);

        $demoUsers = [
            [
                'name'     => "Admin Gudang — {$tenant->name}",
                'email'    => "gudang@{$slug}.demo",
                'role'     => 'admin-gudang',
                'roleObj'  => $roles['adminGudangRole'],
            ],
            [
                'name'     => "Admin Keuangan — {$tenant->name}",
                'email'    => "keuangan@{$slug}.demo",
                'role'     => 'admin-keuangan',
                'roleObj'  => $roles['adminKeuanganRole'],
            ],
            [
                'name'     => "Admin Produksi — {$tenant->name}",
                'email'    => "produksi@{$slug}.demo",
                'role'     => 'admin-produksi',
                'roleObj'  => $roles['adminProduksiRole'],
            ],
            [
                'name'     => "Admin Penjualan — {$tenant->name}",
                'email'    => "penjualan@{$slug}.demo",
                'role'     => 'admin-penjualan',
                'roleObj'  => $roles['adminPenjualanRole'],
            ],
        ];

        foreach ($demoUsers as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'tenant_id' => $tenant->id,
                    'name'      => $userData['name'],
                    'password'  => Hash::make('password'),
                    'role'      => $userData['role'],
                ]
            );

            $user->syncRoles([$userData['roleObj']]);
        }
    }
}
