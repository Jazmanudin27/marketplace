<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;

$excelFile = $argv[1] ?? null;
$dateFrom  = $argv[2] ?? '2026-07-01';
$dateTo    = $argv[3] ?? '2026-07-31';

echo "======================================================================\n";
echo "🔍 COMPARISON DIFF TOOL: LAPORAN EXCEL TIKTOK VS DATABASE ERP LOKAL\n";
echo "======================================================================\n\n";

if (!$excelFile || !file_exists($excelFile)) {
    echo "📌 CARA PENGGUNAAN:\n";
    echo "1. Simpan/upload file list ID Order dari Excel TikTok ke server (misal: tiktok_juli.txt atau tiktok_juli.csv)\n";
    echo "2. Jalankan perintah:\n";
    echo "   php diff_erp_vs_tiktok_excel.php tiktok_juli.txt 2026-07-01 2026-07-31\n\n";

    echo "💡 ATAU EXPORT DAFTAR ID ORDER ERP JULI UNTUK DICOCOKKAN DI EXCEL:\n";
    
    $stores = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))->get();
    foreach ($stores as $store) {
        $orders = Order::where('store_id', $store->id)
            ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
            ->whereNotNull('completed_at')
            ->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->pluck('order_marketplace_id')
            ->toArray();

        $filename = "erp_orders_store_{$store->id}_juli.txt";
        file_put_contents($filename, implode("\n", $orders));
        echo "   • Toko {$store->store_name} (ID {$store->id}): {$orders->count()} order -> Exported to file: {$filename}\n";
    }
    echo "\n======================================================================\n";
    exit(0);
}

// Jika file input diberikan
$content = file_get_contents($excelFile);
preg_match_all('/\b58\d{16}\b/', $content, $matches);
$tiktokExcelIds = array_unique($matches[0] ?? []);

echo "📊 Total Order ID terdeteksi dari File Excel TikTok: " . count($tiktokExcelIds) . " order\n";

$erpOrders = Order::whereHas('store.channel', fn($q) => $q->where('code', 'tiktok'))
    ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
    ->whereNotNull('completed_at')
    ->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
    ->get();

$erpIds = $erpOrders->pluck('order_marketplace_id')->toArray();
echo "📊 Total Order ID terdeteksi di Laporan Dilepas ERP: " . count($erpIds) . " order\n\n";

$extraInErp = array_diff($erpIds, $tiktokExcelIds);
$missingInErp = array_diff($tiktokExcelIds, $erpIds);

echo "----------------------------------------------------------------------\n";
echo "🔴 1. ORDERAN YANG ADA DI ERP TAPI TIDAK ADA DI EXCEL TIKTOK (" . count($extraInErp) . " Order):\n";
if (empty($extraInErp)) {
    echo "   (Tidak ada / 0)\n";
} else {
    foreach (array_values($extraInErp) as $idx => $id) {
        $o = $erpOrders->firstWhere('order_marketplace_id', $id);
        echo "   " . ($idx + 1) . ". ID: {$id} | Toko: " . ($o->store->store_name ?? '-') . " | Released Date ERP: " . ($o->completed_at ? $o->completed_at->format('d/m/Y H:i') : '-') . "\n";
    }
}

echo "\n----------------------------------------------------------------------\n";
echo "🔵 2. ORDERAN YANG ADA DI EXCEL TIKTOK TAPI BELUM/TIDAK ADA DI ERP (" . count($missingInErp) . " Order):\n";
if (empty($missingInErp)) {
    echo "   (Tidak ada / 0)\n";
} else {
    foreach (array_values($missingInErp) as $idx => $id) {
        $oDb = Order::where('order_marketplace_id', $id)->first();
        if ($oDb) {
            echo "   " . ($idx + 1) . ". ID: {$id} | Status DB ERP: {$oDb->order_status} | Released Date ERP: " . ($oDb->completed_at ? $oDb->completed_at->format('d/m/Y H:i') : 'NULL') . "\n";
        } else {
            echo "   " . ($idx + 1) . ". ID: {$id} | BELUM MASUK ERP SAMA SEKALI!\n";
        }
    }
}

echo "======================================================================\n";
