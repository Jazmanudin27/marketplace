<?php

/**
 * ============================================================
 * SCRIPT PEMBANDING ORDER ERP vs SELLER CENTER TIKTOK
 * ============================================================
 * Mengapa di Seller Center 60 order, tapi di ERP 86 order?
 *
 * Di Seller Center TikTok:
 *   - Laporan Pesanan = Berdasarkan TANGGAL DIBUAT (order_date)
 *   - Laporan Saldo   = Berdasarkan TANGGAL CAIR (completed_at)
 *
 * Cara Pakai:
 *   php compare_report_with_tiktok.php --store=30 --date=2026-08-01..2026-08-02
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;

$args      = array_slice($argv, 1);
$storeId   = 30;
$startDate = '2026-08-01';
$endDate   = '2026-08-02';

foreach ($args as $arg) {
    if (str_starts_with($arg, '--store=')) $storeId = (int) str_replace('--store=', '', $arg);
    if (str_starts_with($arg, '--date=')) {
        $dates = explode('..', str_replace('--date=', '', $arg));
        $startDate = $dates[0] ?? '2026-08-01';
        $endDate   = $dates[1] ?? $startDate;
    }
}

$store = Store::find($storeId);
$storeName = $store ? $store->store_name : "Store #{$storeId}";

echo "\n";
echo "======================================================================\n";
echo "  PEMBANDING LAPORAN ERP vs SELLER CENTER TIKTOK\n";
echo "======================================================================\n";
echo "  Toko     : {$storeName} (ID: {$storeId})\n";
echo "  Periode  : {$startDate} s/d {$endDate}\n";
echo "======================================================================\n\n";

// ── 1. PEMBANDING 1: BERDASARKAN TANGGAL DIBUAT (Order Date) ──────────
// Ini yang dipakai di Laporan Pesanan TikTok Seller Center
$byOrderDate = Order::where('store_id', $storeId)
    ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
    ->whereBetween('order_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
    ->orderBy('order_date')
    ->get();

// ── 2. PEMBANDING 2: BERDASARKAN TANGGAL CAIR (Completed At) ──────────
// Ini yang dipakai di Laporan Pencairan Saldo / ERP Finance Report
$byCompletedDate = Order::where('store_id', $storeId)
    ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
    ->where(function($q) use ($startDate, $endDate) {
        $q->whereBetween('completed_at', [$startDate . ' 00:00:00', $endDate . ' 23:59:59'])
          ->orWhere(function($subQ) use ($startDate, $endDate) {
              $subQ->whereNull('completed_at')
                   ->whereBetween('order_date', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
          });
    })
    ->orderBy('completed_at')
    ->get();

echo "📊 KESIMPULAN PERBANDINGAN ANGKANYA:\n";
echo "----------------------------------------------------------------------\n";
echo "  A. Berdasarkan TANGGAL DIBUAT (Order Date): " . $byOrderDate->count() . " order\n";
echo "     👉 Cocok dengan [Laporan Pesanan] Seller Center TikTok (~60-an order)\n\n";

echo "  B. Berdasarkan TANGGAL CAIR (Completed At) : " . $byCompletedDate->count() . " order\n";
echo "     👉 Cocok dengan [Laporan ERP / Pencairan Saldo TikTok] (86 order)\n";
echo "----------------------------------------------------------------------\n\n";

// Export CSV untuk dicocokkan dengan Excel Seller Center
$csvFile = __DIR__ . "/perbandingan_order_store_{$storeId}.csv";
$fp = fopen($csvFile, 'w');

fputcsv($fp, ['=== PEMBANDING 1: BERDASARKAN TANGGAL DIBUAT (Order Date) - Cocok Seller Center Pesanan ===']);
fputcsv($fp, ['No', 'Order ID TikTok', 'Tanggal Order Dibuat', 'Tanggal Cair (Completed At)', 'Status', 'Buyer', 'Total Amount', 'Net Amount (Cair)']);

$no = 1;
foreach ($byOrderDate as $o) {
    fputcsv($fp, [
        $no++,
        $o->order_marketplace_id,
        $o->order_date ? $o->order_date->format('Y-m-d H:i:s') : '-',
        $o->completed_at ? (is_string($o->completed_at) ? $o->completed_at : $o->completed_at->format('Y-m-d H:i:s')) : '-',
        $o->order_status,
        $o->buyer_name,
        $o->total_amount,
        $o->net_amount
    ]);
}

fputcsv($fp, []);
fputcsv($fp, ['=== PEMBANDING 2: BERDASARKAN TANGGAL CAIR (Completed At) - Cocok Laporan ERP / Pencairan Saldo ===']);
fputcsv($fp, ['No', 'Order ID TikTok', 'Tanggal Order Dibuat', 'Tanggal Cair (Completed At)', 'Status', 'Buyer', 'Total Amount', 'Net Amount (Cair)']);

$no = 1;
foreach ($byCompletedDate as $o) {
    fputcsv($fp, [
        $no++,
        $o->order_marketplace_id,
        $o->order_date ? $o->order_date->format('Y-m-d H:i:s') : '-',
        $o->completed_at ? (is_string($o->completed_at) ? $o->completed_at : $o->completed_at->format('Y-m-d H:i:s')) : '-',
        $o->order_status,
        $o->buyer_name,
        $o->total_amount,
        $o->net_amount
    ]);
}

fclose($fp);

echo "📄 DAFTAR FILE CSV DISIAPKAN UNTUK DIBANDINGKAN DENGAN EXCEL TIKTOK:\n";
echo "   File: {$csvFile}\n\n";

echo "📌 CARA MENCOCOKKAN:\n";
echo "  1. Jika Anda mencocokkan dengan [Seller Center -> Pesanan -> Export Pesanan],\n";
echo "     gunakan daftar A (Berdasarkan Tanggal Dibuat).\n";
echo "  2. Jika Anda mencocokkan dengan [Seller Center -> Keuangan -> Pencairan Saldo/Settlement],\n";
echo "     gunakan daftar B (Berdasarkan Tanggal Cair - 86 order).\n\n";
