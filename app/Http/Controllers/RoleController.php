<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleController extends Controller
{
    private function getPermissionGroups()
    {
        return [
            'Master Data' => [
                'manage-categories' => 'Mengelola Kategori',
                'manage-brands' => 'Mengelola Merk',
                'manage-suppliers' => 'Mengelola Supplier',
                'manage-employees' => 'Mengelola Karyawan',
                'manage-customers' => 'Mengelola Pelanggan',
                'manage-users' => 'Mengelola Pengguna & Hak Akses',
            ],
            'Produk' => [
                'manage-products' => 'Mengelola Master & Marketplace Produk',
            ],
            'Toko' => [
                'manage-stores' => 'Kelola Toko Marketplace',
            ],
            'Transaksi' => [
                'manage-incoming-goods' => 'Mengelola Barang Masuk',
                'manage-orders' => 'Mengelola Pesanan Masuk',
                'manage-fulfillment' => 'Kemas Pesanan (Fulfillment Scan)',
                'manage-returns' => 'Mengelola Pesanan Retur',
                'manage-offline-sales' => 'Mengelola Penjualan Offline',
                'manage-chats' => 'Mengelola Inbox Chat',
            ],
            'Persediaan' => [
                'manage-inventory' => 'Mengelola Stok & Opname Stok',
            ],
            'Laporan' => [
                'view-warehouse-reports' => 'Melihat Laporan Gudang',
            ],
            'Keuangan' => [
                'view-financial-reports' => 'Melihat Laporan Keuangan (Laba Rugi & Profit)',
                'manage-finance' => 'Mengelola Transaksi Keuangan (Pemasukan, Pengeluaran, Transfer, Rekonsiliasi)',
            ]
        ];
    }

    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        
        // Scope permissions team id
        setPermissionsTeamId($tenantId);

        $roles = Role::where('tenant_id', $tenantId)->get();
        $permissionGroups = $this->getPermissionGroups();

        return view('roles.index', compact('roles', 'permissionGroups'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        setPermissionsTeamId($tenantId);

        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        $roleName = strtolower(trim($request->name));

        // Cek jika role sudah ada untuk tenant ini
        $exists = Role::where('tenant_id', $tenantId)
            ->where('name', $roleName)
            ->exists();

        if ($exists) {
            return back()->with('error', 'Role dengan nama tersebut sudah ada.');
        }

        $role = Role::create([
            'tenant_id' => $tenantId,
            'name' => $roleName,
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return back()->with('success', 'Role baru berhasil ditambahkan.');
    }

    public function update(Request $request, Role $role)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($role->tenant_id === $tenantId, 403);
        setPermissionsTeamId($tenantId);

        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'nullable|array',
        ]);

        $roleName = strtolower(trim($request->name));

        // Admin role name cannot be changed
        if ($role->name === 'admin' && $roleName !== 'admin') {
            return back()->with('error', 'Role Admin Utama tidak boleh diubah namanya.');
        }

        // Cek jika nama diubah dan ternyata bentrok dengan role lain
        if ($roleName !== $role->name) {
            $exists = Role::where('tenant_id', $tenantId)
                ->where('name', $roleName)
                ->where('id', '!=', $role->id)
                ->exists();

            if ($exists) {
                return back()->with('error', 'Role dengan nama tersebut sudah ada.');
            }

            $role->name = $roleName;
            $role->save();
        }

        // Admin role must keep all permissions
        if ($role->name === 'admin') {
            $allPermissions = Permission::all()->pluck('name')->toArray();
            $role->syncPermissions($allPermissions);
        } else {
            $role->syncPermissions($request->permissions ?? []);
        }

        return back()->with('success', 'Role berhasil diperbarui.');
    }

    public function destroy(Role $role)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($role->tenant_id === $tenantId, 403);

        if ($role->name === 'admin') {
            return back()->with('error', 'Role Admin Utama tidak dapat dihapus.');
        }

        // Cek jika role masih digunakan oleh user lain
        $userCount = \App\Models\User::role($role->name)->count();
        if ($userCount > 0) {
            return back()->with('error', "Role ini tidak dapat dihapus karena sedang digunakan oleh {$userCount} pengguna.");
        }

        $role->delete();
        return back()->with('success', 'Role berhasil dihapus.');
    }
}
