<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    private function getPermissionGroups()
    {
        // Ensure new granular dashboard permissions exist in database
        $newDashPermissions = [
            'dashboard.marketing',
            'dashboard.finance',
            'dashboard.production_purchase',
            'dashboard.warehouse',
            'inventory-items.index',
            'inventory-items.create',
            'inventory-items.edit',
            'inventory-items.destroy'
        ];
        foreach ($newDashPermissions as $pName) {
            try {
                \Spatie\Permission\Models\Permission::findOrCreate($pName, 'web');
            } catch (\Exception $e) {
                // Ignore any DB check exceptions
            }
        }

        return [
            'Dashboard' => [
                'dashboard.index' => 'Melihat Dashboard Utama (Selamat Datang)',
                'dashboard.marketing' => 'Melihat Dashboard Marketing',
                'dashboard.finance' => 'Melihat Dashboard Keuangan',
                'dashboard.production_purchase' => 'Melihat Dashboard Pembelian & Produksi',
                'dashboard.warehouse' => 'Melihat Dashboard Gudang Jadi',
            ],
            'Master Barang (Bahan & Operasional)' => [
                'inventory-items.index' => 'Lihat Master Barang',
                'inventory-items.create' => 'Tambah Master Barang',
                'inventory-items.edit' => 'Edit Master Barang',
                'inventory-items.destroy' => 'Hapus Master Barang',
            ],
            'Master Kategori & Merk' => [
                'categories.index' => 'Lihat Kategori',
                'categories.create' => 'Tambah Kategori',
                'categories.edit' => 'Edit Kategori',
                'categories.destroy' => 'Hapus Kategori',
                'brands.index' => 'Lihat Merk/Brand',
                'brands.create' => 'Tambah Merk/Brand',
                'brands.edit' => 'Edit Merk/Brand',
                'brands.destroy' => 'Hapus Merk/Brand',
            ],
            'Master Supplier & Pelanggan' => [
                'suppliers.index' => 'Lihat Supplier',
                'suppliers.create' => 'Tambah Supplier',
                'suppliers.edit' => 'Edit Supplier',
                'suppliers.destroy' => 'Hapus Supplier',
                'customers.index' => 'Lihat Pelanggan',
                'customers.show' => 'Detail Pelanggan',
                'customers.create' => 'Tambah Pelanggan',
                'customers.edit' => 'Edit Pelanggan',
                'customers.destroy' => 'Hapus Pelanggan',
            ],
            'Produk' => [
                'products.index' => 'Lihat Master Produk',
                'products.show' => 'Detail Master Produk',
                'products.create' => 'Tambah Master Produk',
                'products.edit' => 'Edit Master Produk',
                'products.destroy' => 'Hapus Master Produk',
                'products.publish' => 'Publish Produk ke Marketplace',
                'products.export' => 'Export Excel Master Produk',
                'marketplace-products.index' => 'Lihat Produk Marketplace',
                'marketplace-products.link' => 'Petakan (Link) Produk',
                'marketplace-products.settings' => 'Pengaturan Harga & Stok Saluran',
                'marketplace-products.promote' => 'Promosi Produk',
            ],
            'Toko / Channel' => [
                'stores.index' => 'Lihat Toko Marketplace',
                'stores.create' => 'Tambah Toko',
                'stores.edit' => 'Edit Toko',
                'stores.destroy' => 'Hapus Toko',
                'stores.sync' => 'Sinkronisasi Data Toko',
            ],
            'Pembelian' => [
                'purchase-orders.index' => 'Lihat Purchase Order (PO)',
                'purchase-orders.create' => 'Tambah Purchase Order (PO)',
                'purchase-orders.edit' => 'Edit Purchase Order (PO)',
                'purchase-orders.destroy' => 'Hapus Purchase Order (PO)',
                'purchase-orders.report' => 'Laporan Pembelian',
                'goods-receipts.index' => 'Lihat Penerimaan Barang (PO & Non-PO)',
                'goods-receipts.create' => 'Tambah Penerimaan Barang',
                'goods-receipts.edit' => 'Edit Penerimaan Barang',
                'goods-receipts.destroy' => 'Hapus Penerimaan Barang',
                'goods-issues.index' => 'Lihat Pengeluaran Barang',
                'goods-issues.create' => 'Tambah Pengeluaran Barang',
                'goods-issues.edit' => 'Edit Pengeluaran Barang',
                'goods-issues.destroy' => 'Hapus Pengeluaran Barang',
                'purchase-returns.index' => 'Lihat Retur Pembelian',
                'purchase-returns.create' => 'Tambah Retur Pembelian',
                'purchase-returns.edit' => 'Edit Retur Pembelian',
                'purchase-returns.destroy' => 'Hapus Retur Pembelian',
                'pembelian.stock_report' => 'Lihat Laporan Stok Bahan',
                'pembelian.report_mutation' => 'Lihat Laporan Mutasi Bahan',
                'pembelian.report_summary' => 'Lihat Rekap Persediaan Bahan',
                'pembelian.stock_card' => 'Lihat Kartu Stok Bahan',
            ],
            'Pesanan & Transaksi' => [
                'orders.index' => 'Lihat Pesanan Masuk',
                'orders.show' => 'Detail Pesanan',
                'orders.process' => 'Proses Pesanan',
                'orders.ship' => 'Kirim Pesanan',
                'orders.print' => 'Cetak Label Pengiriman / Invoice',
                'orders.export' => 'Export Pesanan',
                'orders.sync' => 'Sinkronisasi Pesanan Marketplace',
                'fulfillment.index' => 'Lihat Kemas Pesanan (Fulfillment)',
                'fulfillment.scan' => 'Scan Kemas Pesanan',
                'fulfillment.complete' => 'Selesaikan Kemasan',
                'returns.index' => 'Lihat Pesanan Retur',
                'returns.sync' => 'Sinkronisasi Retur',
                'returns.restock' => 'Restock Barang Retur',
            ],
            'Penjualan Offline (POS)' => [
                'offline-sales.index' => 'Lihat Penjualan Offline',
                'offline-sales.show' => 'Detail Penjualan Offline',
                'offline-sales.create' => 'Buat Penjualan POS Baru',
                'offline-sales.edit' => 'Edit Penjualan POS',
                'offline-sales.destroy' => 'Hapus Penjualan POS',
                'offline-sales.complete' => 'Selesaikan Transaksi POS',
                'offline-sales.cancel' => 'Batalkan Transaksi POS',
                'offline-sales.print' => 'Cetak Struk/Nota POS',
                'vouchers.index' => 'Lihat Voucher POS',
                'vouchers.create' => 'Buat Voucher POS',
                'vouchers.edit' => 'Edit Voucher POS',
                'vouchers.destroy' => 'Hapus Voucher POS',
            ],
            'Chat Inbox' => [
                'chats.index' => 'Lihat Inbox Chat',
                'chats.show' => 'Membaca Percakapan',
                'chats.reply' => 'Membalas Chat',
                'chats.sync' => 'Sinkronisasi Chat',
            ],
            'Inventory & Persediaan' => [
                'inventory.index' => 'Lihat Stok Gudang',
                'inventory.ledger' => 'Lihat Kartu Stok',
                'inventory.adjust' => 'Penyesuaian Stok (Adjust)',
                'incoming-goods.index' => 'Lihat Barang Masuk',
                'incoming-goods.create' => 'Tambah Barang Masuk',
                'stock-opnames.index' => 'Lihat Stok Opname',
                'stock-opnames.create' => 'Mulai/Tambah Stok Opname',
            ],
            'HRD: Karyawan & Presensi' => [
                'employees.index' => 'Lihat Data Karyawan',
                'employees.create' => 'Tambah Karyawan',
                'employees.edit' => 'Edit Karyawan',
                'employees.destroy' => 'Hapus Karyawan',
                'employees.salary' => 'Kelola Settings Gaji Pokok & Lembur',
                'attendance.index' => 'Lihat Daftar Kehadiran',
                'attendance.create' => 'Input Kehadiran Manual',
                'attendance.edit' => 'Edit Kehadiran',
                'attendance.destroy' => 'Hapus Kehadiran',
                'attendance.report' => 'Lihat Laporan Absensi',
                'attendance.print' => 'Cetak/Export Rekap Absensi',
                'attendance-corrections.propose' => 'Ajukan Koreksi Presensi',
                'attendance-corrections.approve' => 'Setujui Koreksi Presensi',
            ],
            'HRD: Cuti, Lembur, Gaji & Lainnya' => [
                'overtime.index' => 'Lihat Lemburan',
                'overtime.create' => 'Input Lembur Karyawan',
                'overtime.edit' => 'Edit Data Lembur',
                'overtime.destroy' => 'Hapus Data Lembur',
                'overtime.approve' => 'Setujui Pengajuan Lembur',
                'leave-requests.index' => 'Lihat Pengajuan Cuti/Izin',
                'leave-requests.create' => 'Input Cuti/Izin Manual',
                'leave-requests.edit' => 'Edit Cuti/Izin',
                'leave-requests.destroy' => 'Hapus Cuti/Izin',
                'leave-requests.approve' => 'Setujui Cuti/Izin',
                'cash-advances.index' => 'Lihat Kasbon/Pinjaman',
                'cash-advances.create' => 'Input Kasbon Baru',
                'cash-advances.edit' => 'Edit Kasbon',
                'cash-advances.destroy' => 'Hapus Kasbon',
                'cash-advances.approve' => 'Setujui Kasbon',
                'payroll.index' => 'Lihat Rekap Payroll Gaji',
                'payroll.show' => 'Detail Slip Gaji',
                'payroll.generate' => 'Generate Slip Gaji Bulanan',
                'payroll.edit' => 'Edit & Tambah Penyesuaian Slip Gaji',
                'payroll.pay' => 'Bayar & Konfirmasi Gaji',
                'payroll.print' => 'Cetak Slip Gaji',
                'payroll.destroy' => 'Hapus Slip Gaji (Draft)',
                'holidays.index' => 'Lihat & Kelola Hari Libur',
                'allowance-types.index' => 'Lihat & Kelola Tunjangan Kustom',
                'late-penalties.index' => 'Lihat & Kelola Aturan Denda Telat',
            ],
            'Keuangan & POS Profit' => [
                'finance.incomes.index' => 'Lihat Pemasukan Keuangan',
                'finance.incomes.create' => 'Input Pemasukan Baru',
                'finance.incomes.edit' => 'Edit Pemasukan',
                'finance.incomes.destroy' => 'Hapus Pemasukan',
                'finance.expenses.index' => 'Lihat Pengeluaran Keuangan',
                'finance.expenses.create' => 'Input Pengeluaran Baru',
                'finance.expenses.edit' => 'Edit Pengeluaran',
                'finance.expenses.destroy' => 'Hapus Pengeluaran',
                'finance.transfers.index' => 'Lihat Transfer Kas',
                'finance.transfers.create' => 'Buat Transfer Kas Baru',
                'finance.transfers.edit' => 'Edit Transfer Kas',
                'finance.transfers.destroy' => 'Hapus Transfer Kas',
                'finance.reconciliation.index' => 'Kelola Rekonsiliasi Bank/Platform',
                'finance.profit-loss.index' => 'Melihat Laporan Laba Rugi',
                'profit.index' => 'Melihat Laba / Profit Bersih Penjualan',
            ],
            'Laporan Penjualan & Gudang' => [
                'reports.summary' => 'Ringkasan Laporan Penjualan',
                'reports.stock' => 'Laporan Stok Barang',
                'reports.ledger' => 'Laporan Mutasi Barang',
                'reports.opname' => 'Laporan Stok Opname',
                'reports.analytics' => 'Analisis Performa Penjualan & Produk',
                'reports.sales' => 'Laporan Penjualan Kasir POS',
            ],
            'Produksi & Maklon' => [
                'production-orders.index' => 'Lihat Perintah Produksi',
                'production-orders.show' => 'Detail Perintah Produksi',
                'production-orders.create' => 'Buat Perintah Produksi',
                'production-orders.edit' => 'Edit Perintah Produksi',
                'production-orders.destroy' => 'Hapus Perintah Produksi',
            ],
            'Pengaturan Akun & Level' => [
                'users.index' => 'Kelola Pengguna Sistem',
                'roles.index' => 'Kelola Hak Akses & Level (Role)',
                'settings.tenant.edit' => 'Pengaturan Informasi Perusahaan/Toko',
            ],
        ];
    }

    public function index(Request $request)
    {
        $authUser = Auth::user();

        if ($authUser->isSuperAdmin()) {
            // Super Admin: bisa lihat semua tenant
            $tenants = Tenant::where('id', '!=', 1)->orderBy('name')->get();
            $selectedTenantId = $request->get('tenant_id', $authUser->tenant_id);

            $query = User::with(['roles', 'permissions'])->where('tenant_id', '!=', 1);

            if ($selectedTenantId && $selectedTenantId > 1) {
                $query->where('tenant_id', $selectedTenantId);
                $tenantId = $selectedTenantId;
            } else {
                $tenantId = null;
                $selectedTenantId = null;
            }
        } else {
            // Admin biasa: hanya lihat tenant sendiri
            $tenants = null;
            $selectedTenantId = null;
            $tenantId = $authUser->tenant_id;
            $query = User::with(['roles', 'permissions'])->where('tenant_id', $tenantId);
        }

        if ($tenantId) {
            setPermissionsTeamId($tenantId);
        }

        // Filter: pencarian nama/email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter: role
        if ($request->filled('role')) {
            $roleName = $request->role;
            $query->whereHas('roles', fn($q) => $q->where('name', $roleName));
        }

        $users = $query->orderBy('name')->get();

        // Roles for the add/edit modal dropdown (scoped to selected tenant)
        if ($tenantId) {
            $roles = \Spatie\Permission\Models\Role::where('tenant_id', $tenantId)->get();
        } else {
            // Super Admin without specific tenant: empty for now
            $roles = collect();
        }

        // All distinct role names for filter dropdown bar
        $roleNames = \Spatie\Permission\Models\Role::whereNotNull('tenant_id')
            ->where('tenant_id', '!=', 1)
            ->select('name')->distinct()->pluck('name');

        $permissionGroups = $this->getPermissionGroups();

        return view('settings.users.index', compact(
            'users',
            'roles',
            'tenants',
            'selectedTenantId',
            'roleNames',
            'permissionGroups'
        ));
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();
        $tenantId = $authUser->isSuperAdmin()
            ? $request->input('tenant_id', null)
            : $authUser->tenant_id;

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                }),
            ],
            'password' => 'required|string|min:8',
            'role_id'  => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
        ]);

        $role = \Spatie\Permission\Models\Role::where('tenant_id', $tenantId)->findOrFail($request->role_id);

        $user = User::create([
            'tenant_id' => $tenantId,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $role->name,
        ]);

        setPermissionsTeamId($tenantId);
        $user->assignRole($role);

        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        } else {
            $user->syncPermissions([]);
        }

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isSuperAdmin()) {
            abort_unless($user->tenant_id === $authUser->tenant_id, 403);
        }

        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)->where(function ($query) use ($user) {
                    return $query->where('tenant_id', $user->tenant_id);
                }),
            ],
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = \Spatie\Permission\Models\Role::where('tenant_id', $user->tenant_id)->findOrFail($request->role_id);

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->role  = $role->name;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        setPermissionsTeamId($user->tenant_id);
        $user->syncRoles([$role]);

        return back()->with('success', 'Data Pengguna berhasil diperbarui.');
    }

    public function editPermissions(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isSuperAdmin()) {
            abort_unless($user->tenant_id === $authUser->tenant_id, 403);
        }

        setPermissionsTeamId($user->tenant_id);

        $userPermissions = $user->permissions->pluck('name')->toArray();
        $permissionGroups = $this->getPermissionGroups();

        return view('settings.users.permissions', compact('user', 'userPermissions', 'permissionGroups'));
    }

    public function updatePermissions(Request $request, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isSuperAdmin()) {
            abort_unless($user->tenant_id === $authUser->tenant_id, 403);
        }

        $request->validate([
            'permissions' => 'nullable|array',
        ]);

        setPermissionsTeamId($user->tenant_id);
        $user->syncPermissions($request->permissions ?? []);

        return redirect()->route('users.index')->with('success', 'Hak Akses Khusus untuk ' . $user->name . ' berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isSuperAdmin()) {
            abort_unless($user->tenant_id === $authUser->tenant_id, 403);
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();
        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
