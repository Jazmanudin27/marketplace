<?php

/**
 * ============================================================
 * DEBUG PENYEBAB BEDA JUMLAH ORDER LAPORAN ERP vs SELLER CENTER
 * ============================================================
 * Script ini memeriksa 86 orderan di ERP untuk tanggal tertentu
 * (default: 2026-08-01 s/d 2026-08-02) dan mengelompokkannya
 * berdasarkan toko, status, dan alasan bedanya.
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;

$dateFrom = $argv[1] ?? '2026-08-01';
$dateTo   = $argv[2] ?? '2026-08-02';
$storeId  = isset($argv[3]) ? (int)$argv[3] : null;

echo "\n";
echo "======================================================================\n";
echo "  ANALISIS 86 ORDER DI LAPORAN ERP PERIODE {$dateFrom} s/d {$dateTo}\n";
echo "======================================================================\n";
if ($storeId) echo "  Filter Store ID: #{$storeId}\n";
echo "======================================================================\n\n";

$query = Order::whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
    ->where(function($q) use ($dateFrom, $dateTo) {
        $q->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
          ->orWhere(function($subQ) use ($dateFrom, $dateTo) {
              $subQ->whereNull('completed_at')
                   ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
          });
    });

if ($storeId) {
    $query->where('store_id', $storeId);
} else {
    // Hanya toko TikTok
    $query->whereHas('store.channel', fn($q) => $q->where('code', 'tiktok'));
}

$orders = $query->with('store')->get();

echo "TOTAL ORDER DITEMUKAN DI ERP : " . $orders->count() . " order\n\n";

// 1. Break Down per Toko
echo "--- 1. RINCIAN PER TOKO TIKTOK ---\n";
$byStore = $orders->groupBy('store_id');
foreach ($byStore as $sId => $storeOrders) {
    $sName = $storeOrders->first()->store->store_name ?? "Store #{$sId}";
    echo "  • Store ID #{$sId} ({$sName}): " . $storeOrders->count() . " order\n";
}
echo "\n";

// 2. Break Down per Status
echo "--- 2. RINCIAN PER STATUS ORDER ---\n";
$byStatus = $orders->groupBy('order_status');
foreach ($byStatus as $st => $stOrders) {
    echo "  • Status '{$st}': " . $stOrders->count() . " order\n";
}
echo "\n";

// 3. Break Down per Sumber Tanggal (completed_at vs order_date)
echo "--- 3. RINCIAN TANGGAL KECERMATAN (Kenapa Beda Tanggal) ---\n";
$cairDiPeriodeIniTapiOrderBulanLalu = 0;
$cairNullTapiOrderHariIni          = 0;
$normalCairHariIni                 = 0;

foreach ($orders as $o) {
    $orderDateStr     = $o->order_date ? $o->order_date->format('Y-m-d') : '';
    $completedDateStr = $o->completed_at ? (is_string($o->completed_at) ? substr($o->completed_at, 0, 10) : $o->completed_at->format('Y-m-d')) : null;

    if ($completedDateStr && $completedDateStr >= $dateFrom && $completedDateStr <= $dateTo) {
        if ($orderDateStr < $dateFrom) {
            $cairDiPeriodeIniTapiOrderBulanLalu++;
        } else {
            $normalCairHariIni++;
        }
    } elseif (!$completedDateStr && $orderDateStr >= $dateFrom && $orderDateStr <= $dateTo) {
        $cairNullTapiOrderHariIni++;
    }
}

echo "  • Order dibuat di bulan lalu, tapi CAIR/COMPLETED di periode ini : {$cairDiPeriodeIniTapiOrderBulanLalu} order\n";
echo "  • Order dibuat di periode ini & cair di periode ini               : {$normalCairHariIni} order\n";
echo "  • Order completed_at NULL (fallback ke tanggal buat)            : {$cairNullTapiOrderHariIni} order\n";

echo "\n======================================================================\n";
echo "KESIMPULAN PENYEBAB ERP MELEBIHI SELLER CENTER:\n";
if ($cairDiPeriodeIniTapiOrderBulanLalu > 0) {
    echo "  1. Ada {$cairDiPeriodeIniTapiOrderBulanLalu} order yang dibuat di bulan sebelumnya (misal Juli)\n";
    echo "     tetapi baru cair di tanggal {$dateFrom} s/d {$dateTo}.\n";
    echo "     (Di Seller Center TikTok, order ini dikelompokkan ke tanggal checkout Juli).\n";
}
if ($byStore->count() > 1 && !$storeId) {
    echo "  2. ERP menggabungkan order dari " . $byStore->count() . " toko TikTok sekaligus.\n";
}
echo "======================================================================\n\n";
