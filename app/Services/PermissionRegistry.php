<?php

namespace App\Services;

use Spatie\Permission\Models\Permission;

class PermissionRegistry
{
    protected static ?array $cachedGroups = null;

    /**
     * Get all structured permission groups aligned 1:1 with the sidebar.
     *
     * @return array<string, array<string, string>>
     */
    public static function getPermissionGroups(): array
    {
        if (static::$cachedGroups !== null) {
            return static::$cachedGroups;
        }

        $groups = [
            'Menu Utama' => [
                'dashboard.index'               => 'Dashboard Utama (Selamat Datang)',
                'dashboard.marketing'           => 'Widget Dashboard: Marketing',
                'dashboard.finance'             => 'Widget Dashboard: Keuangan',
                'dashboard.production_purchase' => 'Widget Dashboard: Pembelian & Produksi',
                'dashboard.warehouse'           => 'Widget Dashboard: Gudang Jadi',
                'orders.index'                  => 'Lihat Menu Pesanan Masuk',
                'orders.show'                   => 'Detail Pesanan Masuk',
                'orders.create'                 => 'Input Pesanan Manual',
                'orders.process'                => 'Proses / Packing Pesanan',
                'orders.ship'                   => 'Kirim Pesanan',
                'orders.print'                  => 'Cetak Label / Resi Pengiriman',
                'orders.export'                 => 'Export Data Pesanan',
                'orders.sync'                   => 'Sinkronisasi Pesanan Marketplace',
                'returns.index'                 => 'Lihat Menu Pesanan Retur',
                'returns.sync'                  => 'Sinkronisasi Retur Marketplace',
                'returns.restock'               => 'Restock Barang Retur ke Gudang',
                'products.index'                => 'Lihat Master Produk',
                'products.show'                 => 'Detail Master Produk',
                'products.create'               => 'Tambah Master Produk',
                'products.edit'                 => 'Edit Master Produk',
                'products.destroy'              => 'Hapus Master Produk',
                'products.publish'              => 'Publish Produk ke Marketplace',
                'products.export'               => 'Export Excel Master Produk',
                'marketplace-products.index'    => 'Lihat Marketplace Produk',
                'marketplace-products.link'     => 'Petakan (Link) Produk ke Master',
                'marketplace-products.settings' => 'Pengaturan Harga & Stok Saluran',
                'marketplace-products.promote'  => 'Promosi Produk Marketplace',
                'marketing.teams.index'         => 'Target Marketing (Target & Komisi Tim)',
            ],
            'Data Master' => [
                'departments.index'             => 'Lihat Departemen',
                'inventory-items.index'         => 'Lihat Master Barang (Bahan & Operasional)',
                'inventory-items.create'        => 'Tambah Master Barang',
                'inventory-items.edit'          => 'Edit Master Barang',
                'inventory-items.destroy'       => 'Hapus Master Barang',
                'categories.index'              => 'Lihat Kategori Produk',
                'categories.create'             => 'Tambah Kategori Produk',
                'categories.edit'               => 'Edit Kategori Produk',
                'categories.destroy'            => 'Hapus Kategori Produk',
                'brands.index'                  => 'Lihat Merk / Brand',
                'brands.create'                 => 'Tambah Merk / Brand',
                'brands.edit'                   => 'Edit Merk / Brand',
                'brands.destroy'                => 'Hapus Merk / Brand',
                'suppliers.index'               => 'Lihat Supplier',
                'suppliers.create'              => 'Tambah Supplier',
                'suppliers.edit'                => 'Edit Supplier',
                'suppliers.destroy'             => 'Hapus Supplier',
                'customers.index'               => 'Lihat Pelanggan',
                'customers.show'                => 'Detail Pelanggan',
                'customers.create'              => 'Tambah Pelanggan',
                'customers.edit'                => 'Edit Pelanggan',
                'customers.destroy'             => 'Hapus Pelanggan',
                'bank-accounts.index'           => 'Lihat Master Bank / Kas',
                'bank-accounts.create'          => 'Tambah Master Bank / Kas',
                'bank-accounts.edit'            => 'Edit Master Bank / Kas',
                'bank-accounts.destroy'         => 'Hapus Master Bank / Kas',
                'finance-categories.index'      => 'Lihat Kategori Biaya & Kas',
                'finance-categories.create'     => 'Tambah Kategori Biaya & Kas',
                'finance-categories.edit'       => 'Edit Kategori Biaya & Kas',
                'finance-categories.destroy'    => 'Hapus Kategori Biaya & Kas',
                'employees.index'               => 'Lihat Karyawan',
                'employees.create'              => 'Tambah Karyawan',
                'employees.edit'                => 'Edit Karyawan',
                'employees.destroy'             => 'Hapus Karyawan',
                'employees.salary'              => 'Kelola Pengaturan Gaji Pokok & Lembur',
                'tailors.index'                 => 'Lihat Master Vendor / Mitra Jahit',
                'labor_services.index'          => 'Lihat Jasa Produksi',
                'production-statuses.index'     => 'Lihat Status Produksi',
                'production-stages.index'       => 'Lihat Tahapan Produksi',
                'users.index'                   => 'Lihat Pengguna Sistem',
                'users.create'                  => 'Tambah Pengguna Sistem',
                'users.edit'                    => 'Edit Pengguna Sistem',
                'users.destroy'                 => 'Hapus Pengguna Sistem',
                'roles.index'                   => 'Kelola Hak Akses & Level (Role)',
                'settings.tenant.edit'          => 'Pengaturan Profil Perusahaan / Toko',
            ],
            'Keuangan (Kas & Operasional)' => [
                'finance.incomes.index'         => 'Lihat Pemasukan Lain',
                'finance.incomes.create'        => 'Input Pemasukan Baru',
                'finance.incomes.edit'          => 'Edit Catatan Pemasukan',
                'finance.incomes.destroy'       => 'Hapus Catatan Pemasukan',
                'finance.expenses.index'        => 'Lihat Pengeluaran & Biaya Operasional',
                'finance.expenses.create'       => 'Input Pengeluaran Baru',
                'finance.expenses.edit'         => 'Edit Catatan Pengeluaran',
                'finance.expenses.destroy'      => 'Hapus Catatan Pengeluaran',
                'finance.transfers.index'       => 'Lihat Transfer Dana Antar Kas',
                'finance.transfers.create'      => 'Buat Transfer Dana Baru',
                'finance.transfers.edit'        => 'Edit Transfer Dana',
                'finance.transfers.destroy'     => 'Hapus Transfer Dana',
                'finance.reconciliation.index'  => 'Kelola Rekonsiliasi Bank / Rekening',
            ],
            'Pembelian' => [
                'purchase-orders.index'         => 'Lihat Purchase Order (PO)',
                'purchase-orders.create'        => 'Tambah Purchase Order (PO)',
                'purchase-orders.edit'          => 'Edit Purchase Order (PO)',
                'purchase-orders.destroy'       => 'Hapus Purchase Order (PO)',
                'goods-receipts.index'          => 'Lihat Penerimaan Barang (PO & Non-PO)',
                'goods-receipts.create'         => 'Tambah Penerimaan Barang',
                'goods-receipts.edit'           => 'Edit Penerimaan Barang',
                'goods-receipts.destroy'        => 'Hapus Penerimaan Barang',
                'goods-issues.index'            => 'Lihat Pengeluaran Barang',
                'goods-issues.create'           => 'Tambah Pengeluaran Barang',
                'goods-issues.edit'             => 'Edit Pengeluaran Barang',
                'goods-issues.destroy'          => 'Hapus Pengeluaran Barang',
                'purchase-returns.index'        => 'Lihat Retur Pembelian',
                'purchase-returns.create'       => 'Tambah Retur Pembelian',
                'purchase-returns.edit'         => 'Edit Retur Pembelian',
                'purchase-returns.destroy'      => 'Hapus Retur Pembelian',
                'supplier-payables.index'       => 'Lihat Hutang Supplier',
            ],
            'Gudang Jadi' => [
                'inventory.index'               => 'Lihat Mutasi Masuk & Keluar Gudang Jadi',
                'inventory.ledger'              => 'Lihat Kartu Stok Jadi',
                'inventory.adjust'              => 'Penyesuaian Stok Jadi (Adjust)',
                'stock-opnames.index'           => 'Lihat Opname Stok Jadi',
                'stock-opnames.create'          => 'Mulai / Tambah Opname Stok Jadi',
                'fulfillment.index'             => 'Lihat Scan & Kemas Barcode (Fulfillment)',
                'fulfillment.scan'              => 'Scan Barcode Kemas Pesanan',
                'fulfillment.complete'          => 'Selesaikan Kemasan Pesanan',
            ],
            'Titipan Barang (Konsinyasi)' => [
                'supplier-consignments.index'      => 'Lihat Penerimaan Barang Titipan',
                'supplier-consignments.create'     => 'Tambah Penerimaan Barang Titipan',
                'supplier-consignments.edit'       => 'Edit Penerimaan Barang Titipan',
                'supplier-consignments.destroy'    => 'Hapus Penerimaan Barang Titipan',
                'supplier-consignments.stock_card' => 'Lihat Kartu Stok Konsinyasi',
                'supplier-consignments.settlement' => 'Lihat & Kelola Riwayat Setoran Supplier',
            ],
            'Marketing & Penjualan' => [
                'spks.index'                    => 'Lihat SPK (Surat Perintah Kerja)',
                'spks.show'                     => 'Detail SPK (Surat Perintah Kerja)',
                'spks.create'                   => 'Tambah SPK (Surat Perintah Kerja)',
                'spks.edit'                     => 'Edit SPK (Surat Perintah Kerja)',
                'spks.destroy'                  => 'Hapus SPK (Surat Perintah Kerja)',
                'spks.view_hpp'                 => 'Lihat Rincian HPP Produksi pada Detail SPK',
                'spks.edit_costs'               => 'Edit / Kelola Biaya SPK di Akhir',
                'product-recipes.index'         => 'Lihat Formula Produk (BOM)',
                'product-recipes.create'        => 'Tambah Formula Produk (BOM)',
                'product-recipes.edit'          => 'Edit Formula Produk (BOM)',
                'product-recipes.destroy'       => 'Hapus Formula Produk (BOM)',
                'offline-sales.index'           => 'Lihat Penjualan Offline (POS)',
                'offline-sales.show'            => 'Detail Penjualan Offline (POS)',
                'offline-sales.create'          => 'Buat Penjualan POS Baru',
                'offline-sales.edit'            => 'Edit Transaksi POS',
                'offline-sales.destroy'         => 'Hapus Transaksi POS',
                'offline-sales.approve'         => 'Approve / Setujui Transaksi POS',
                'offline-sales.complete'        => 'Selesaikan Transaksi POS',
                'offline-sales.cancel'          => 'Batalkan Transaksi POS',
                'offline-sales.print'           => 'Cetak Struk / Nota POS',
                'vouchers.index'                => 'Lihat Voucher Promo POS',
                'vouchers.create'               => 'Buat Voucher POS',
                'vouchers.edit'                 => 'Edit Voucher POS',
                'vouchers.destroy'              => 'Hapus Voucher POS',
                'marketing.ads.index'           => 'Melihat Dashboard Iklan & Keputusan (Ads)',
            ],
            'Kepegawaian (HRD)' => [
                'attendance.index'               => 'Lihat Presensi / Absensi',
                'attendance.create'              => 'Input Kehadiran Manual',
                'attendance.edit'                => 'Edit Kehadiran',
                'attendance.destroy'             => 'Hapus Kehadiran',
                'attendance.report'              => 'Lihat Laporan Absensi',
                'attendance.print'               => 'Cetak / Export Rekap Absensi',
                'attendance-corrections.propose' => 'Ajukan Koreksi Presensi',
                'attendance-corrections.approve' => 'Setujui Koreksi Presensi',
                'leave-requests.index'           => 'Lihat Pengajuan Izin & Cuti',
                'leave-requests.create'          => 'Input Izin / Cuti Manual',
                'leave-requests.edit'            => 'Edit Izin / Cuti',
                'leave-requests.destroy'         => 'Hapus Izin / Cuti',
                'leave-requests.approve'         => 'Setujui Pengajuan Izin / Cuti',
                'overtime.index'                 => 'Lihat Lembur / Overtime',
                'overtime.create'                => 'Input Lembur Karyawan',
                'overtime.edit'                  => 'Edit Data Lembur',
                'overtime.destroy'               => 'Hapus Data Lembur',
                'overtime.approve'               => 'Setujui Pengajuan Lembur',
                'cash-advances.index'            => 'Lihat Kasbon (Cash Advance)',
                'cash-advances.create'           => 'Input Kasbon Baru',
                'cash-advances.edit'             => 'Edit Kasbon',
                'cash-advances.destroy'          => 'Hapus Kasbon',
                'cash-advances.approve'          => 'Setujui Kasbon',
                'payroll.index'                  => 'Lihat Gaji / Payroll',
                'payroll.show'                   => 'Detail Slip Gaji',
                'payroll.generate'               => 'Generate Slip Gaji Bulanan',
                'payroll.edit'                   => 'Edit & Penyesuaian Slip Gaji',
                'payroll.pay'                    => 'Bayar & Konfirmasi Gaji',
                'payroll.print'                  => 'Cetak Slip Gaji',
                'payroll.destroy'                => 'Hapus Slip Gaji (Draft)',
                'allowance-types.index'          => 'Lihat & Kelola Master Tunjangan',
                'late-penalties.index'           => 'Lihat & Kelola Aturan Denda Terlambat',
                'holidays.index'                 => 'Lihat & Kelola Hari Libur',
            ],
            'Laporan Marketing' => [
                'reports.sales'                  => 'Laporan Penjualan',
                'reports.store_sales'            => 'Rekonsiliasi Omset',
                'reports.released_sales'         => 'Laporan Penjualan Dilepas (Dana Cair)',
                'reports.reseller_receivables'   => 'Saldo & Piutang',
            ],
            'Laporan Gudang' => [
                'reports.summary'                => 'Rekap Persediaan',
                'reports.stock'                  => 'Stok Barang Jadi',
                'reports.ledger'                 => 'Kartu Stok Jadi',
                'reports.inventory_turnover'     => 'Perputaran Stok',
                'reports.production_hpp'         => 'HPP Produksi',
                'reports.master_product'         => 'Laporan Master Produk',
                'marketplace_products.print_report' => 'Laporan Produk Marketplace',
            ],
            'Laporan Pembelian' => [
                'purchase-orders.report'         => 'Laporan Pembelian',
                'pembelian.stock_report'         => 'Laporan Stok Bahan',
                'pembelian.report_mutation'      => 'Laporan Mutasi Bahan',
                'pembelian.report_summary'       => 'Rekap Persediaan Bahan',
                'pembelian.stock_card'           => 'Kartu Stok Bahan',
            ],
            'Laporan Keuangan' => [
                'finance.profit_loss'               => 'Laba Rugi',
                'profit.index'                      => 'Profit Pesanan',
                'finance.marketplace_wallets.index' => 'Saldo Marketplace',
                'profit.margin'                     => 'Margin Produk Aktual',
                'reports.product_margins'           => 'Margin Master Produk',
            ],
            'Toko Marketplace & Chat' => [
                'stores.index'                   => 'Lihat Toko Marketplace (Kelola Toko)',
                'stores.create'                  => 'Tambah Toko Marketplace',
                'stores.edit'                    => 'Edit Toko Marketplace',
                'stores.destroy'                 => 'Hapus Toko Marketplace',
                'stores.sync'                    => 'Sinkronisasi Data Toko',
                'chats.index'                    => 'Lihat Inbox Chat',
                'chats.show'                     => 'Membaca Percakapan Chat',
                'chats.reply'                    => 'Membalas Chat',
                'chats.sync'                     => 'Sinkronisasi Chat',
            ],
        ];

        // Ensure all registered permissions exist in the database for guard 'web'
        self::ensurePermissionsExist($groups);

        static::$cachedGroups = $groups;
        return $groups;
    }

    /**
     * Ensure all permissions in the groups exist in the database.
     *
     * @param array<string, array<string, string>> $groups
     */
    protected static function ensurePermissionsExist(array $groups): void
    {
        try {
            $allPermKeys = [];
            foreach ($groups as $perms) {
                foreach (array_keys($perms) as $key) {
                    $allPermKeys[] = $key;
                }
            }

            $existing = Permission::where('guard_name', 'web')
                ->whereIn('name', $allPermKeys)
                ->pluck('name')
                ->toArray();

            $missing = array_diff($allPermKeys, $existing);

            if (!empty($missing)) {
                $now = now();
                $insertData = [];
                foreach ($missing as $name) {
                    $insertData[] = [
                        'name'       => $name,
                        'guard_name' => 'web',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
                Permission::insert($insertData);
                app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
            }
        } catch (\Exception $e) {
            // Silently catch in case table is not yet migrated in tests
        }
    }
}
