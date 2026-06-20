<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;

/**
 * Migration: Buat semua permission granular per menu per aksi.
 * Format: {modul}.{aksi}
 * Contoh: products.index, products.create, orders.print, dst.
 */
return new class extends Migration
{
    /**
     * Daftar semua permission granular yang akan dibuat.
     */
    private function getPermissions(): array
    {
        return [
            // ─── Dashboard ───────────────────────────────────────────
            'dashboard.index',

            // ─── Master Data: Kategori ────────────────────────────────
            'categories.index',
            'categories.create',
            'categories.edit',
            'categories.destroy',

            // ─── Master Data: Brand ───────────────────────────────────
            'brands.index',
            'brands.create',
            'brands.edit',
            'brands.destroy',

            // ─── Master Data: Supplier ────────────────────────────────
            'suppliers.index',
            'suppliers.create',
            'suppliers.edit',
            'suppliers.destroy',

            // ─── Master Data: Pelanggan ───────────────────────────────
            'customers.index',
            'customers.show',
            'customers.create',
            'customers.edit',
            'customers.destroy',

            // ─── Produk Master ────────────────────────────────────────
            'products.index',
            'products.show',
            'products.create',
            'products.edit',
            'products.destroy',
            'products.publish',
            'products.export',

            // ─── Produk Marketplace ───────────────────────────────────
            'marketplace-products.index',
            'marketplace-products.link',
            'marketplace-products.settings',
            'marketplace-products.promote',

            // ─── Toko / Channel ───────────────────────────────────────
            'stores.index',
            'stores.create',
            'stores.edit',
            'stores.destroy',
            'stores.sync',

            // ─── Pesanan (Orders) ─────────────────────────────────────
            'orders.index',
            'orders.show',
            'orders.process',
            'orders.ship',
            'orders.print',
            'orders.export',
            'orders.sync',

            // ─── Fulfillment / Kemasan ────────────────────────────────
            'fulfillment.index',
            'fulfillment.scan',
            'fulfillment.complete',

            // ─── Retur Pesanan ────────────────────────────────────────
            'returns.index',
            'returns.sync',
            'returns.restock',

            // ─── Penjualan Offline ────────────────────────────────────
            'offline-sales.index',
            'offline-sales.show',
            'offline-sales.create',
            'offline-sales.edit',
            'offline-sales.destroy',
            'offline-sales.complete',
            'offline-sales.cancel',
            'offline-sales.print',

            // ─── Voucher / Diskon ─────────────────────────────────────
            'vouchers.index',
            'vouchers.create',
            'vouchers.edit',
            'vouchers.destroy',

            // ─── Chat Inbox ───────────────────────────────────────────
            'chats.index',
            'chats.show',
            'chats.reply',
            'chats.sync',

            // ─── Inventory & Stok ─────────────────────────────────────
            'inventory.index',
            'inventory.ledger',
            'inventory.adjust',

            // ─── Barang Masuk (Incoming Goods) ───────────────────────
            'incoming-goods.index',
            'incoming-goods.create',

            // ─── Stock Opname ─────────────────────────────────────────
            'stock-opnames.index',
            'stock-opnames.create',

            // ─── HRD: Karyawan ────────────────────────────────────────
            'employees.index',
            'employees.create',
            'employees.edit',
            'employees.destroy',
            'employees.salary',

            // ─── HRD: Presensi (Attendance) ───────────────────────────
            'attendance.index',
            'attendance.create',
            'attendance.edit',
            'attendance.destroy',
            'attendance.report',
            'attendance.print',

            // ─── HRD: Koreksi Presensi ────────────────────────────────
            'attendance-corrections.propose',
            'attendance-corrections.approve',

            // ─── HRD: Lembur (Overtime) ───────────────────────────────
            'overtime.index',
            'overtime.create',
            'overtime.edit',
            'overtime.destroy',
            'overtime.approve',

            // ─── HRD: Pengajuan Cuti/Izin (Leave) ───────────────────
            'leave-requests.index',
            'leave-requests.create',
            'leave-requests.edit',
            'leave-requests.destroy',
            'leave-requests.approve',

            // ─── HRD: Kasbon (Cash Advance) ───────────────────────────
            'cash-advances.index',
            'cash-advances.create',
            'cash-advances.edit',
            'cash-advances.destroy',
            'cash-advances.approve',

            // ─── HRD: Penggajian (Payroll) ───────────────────────────
            'payroll.index',
            'payroll.show',
            'payroll.generate',
            'payroll.edit',
            'payroll.pay',
            'payroll.print',
            'payroll.destroy',

            // ─── HRD: Hari Libur (Holidays) ─────────────────────────
            'holidays.index',
            'holidays.create',
            'holidays.edit',
            'holidays.destroy',

            // ─── HRD: Tipe Tunjangan ─────────────────────────────────
            'allowance-types.index',
            'allowance-types.create',
            'allowance-types.edit',
            'allowance-types.destroy',

            // ─── HRD: Denda Keterlambatan ─────────────────────────────
            'late-penalties.index',
            'late-penalties.create',
            'late-penalties.edit',
            'late-penalties.destroy',

            // ─── Keuangan: Pemasukan ─────────────────────────────────
            'finance.incomes.index',
            'finance.incomes.create',
            'finance.incomes.edit',
            'finance.incomes.destroy',

            // ─── Keuangan: Pengeluaran ────────────────────────────────
            'finance.expenses.index',
            'finance.expenses.create',
            'finance.expenses.edit',
            'finance.expenses.destroy',

            // ─── Keuangan: Transfer Dana ─────────────────────────────
            'finance.transfers.index',
            'finance.transfers.create',
            'finance.transfers.edit',
            'finance.transfers.destroy',

            // ─── Keuangan: Rekonsiliasi ───────────────────────────────
            'finance.reconciliation.index',

            // ─── Keuangan: Laporan Laba Rugi ─────────────────────────
            'finance.profit-loss.index',
            'profit.index',

            // ─── Laporan Gudang & Penjualan ───────────────────────────
            'reports.summary',
            'reports.summary.print',
            'reports.stock',
            'reports.stock.print',
            'reports.ledger',
            'reports.ledger.print',
            'reports.opname',
            'reports.opname.print',
            'reports.analytics',
            'reports.sales',
            'reports.export',

            // ─── Produksi ─────────────────────────────────────────────
            'production-orders.index',
            'production-orders.show',
            'production-orders.create',
            'production-orders.edit',
            'production-orders.destroy',

            // ─── Users & Role Management ──────────────────────────────
            'users.index',
            'users.create',
            'users.edit',
            'users.destroy',

            'roles.index',
            'roles.create',
            'roles.edit',
            'roles.destroy',

            // ─── Pengaturan Perusahaan ────────────────────────────────
            'settings.tenant.edit',

            // ─── Super Admin: Kelola Semua Tenant ────────────────────
            'tenants.index',
            'tenants.create',
            'tenants.edit',
            'tenants.destroy',

            // ─── Legacy / Backward-Compatibility Permissions ─────────
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

    public function up(): void
    {
        // Reset cache sebelum operasi
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->getPermissions() as $name) {
            Permission::firstOrCreate([
                'name'       => $name,
                'guard_name' => 'web',
            ]);
        }
    }

    public function down(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        foreach ($this->getPermissions() as $name) {
            Permission::where('name', $name)
                ->where('guard_name', 'web')
                ->delete();
        }
    }
};
