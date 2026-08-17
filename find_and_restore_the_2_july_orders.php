<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;
use Carbon\Carbon;

echo "======================================================================\n";
echo "🔍 PRECIS VISION: MEMULIHKAN 2 ORDER KEKURANGAN AGAR TOTAL MENJADI 534\n";
echo "======================================================================\n\n";

$tiktokService = app(TiktokService::class);
$store = Store::find(30); // Nusantara Seragam

if (!$store) {
    echo "❌ Toko ID #30 tidak ditemukan.\n";
    exit(1);
}

$accessToken = $store->getValidAccessToken();
$shopCipher  = $store->shop_cipher;

$dateFrom = '2026-07-01';
$dateTo   = '2026-07-31';

// 1. Cek total order saat ini di ERP untuk bulan Juli
$currentJulyOrders = Order::where('store_id', $store->id)
    ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
    ->whereNotNull('completed_at')
    ->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
    ->get();

echo "🏬 Toko: {$store->store_name} (ID: {$store->id})\n";
echo "📊 Jumlah Order Saat Ini di ERP (Bulan Juli) : " . $currentJulyOrders->count() . " order (Target: 534)\n\n";

// 2. Cari order yang completed_at null ATAU baru saja tergeser, lalu periksa API TikTok Finance-nya
$candidateOrders = Order::where('store_id', $store->id)
    ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
    ->where(function($q) use ($dateFrom, $dateTo) {
        $q->whereNull('completed_at')
          ->orWhere('completed_at', '>', '2026-07-31 23:59:59')
          ->orWhereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
    })
    ->get();

echo "🔍 Memeriksa " . $candidateOrders->count() . " kandidat order untuk menemukan 2 order bulan Juli...\n\n";

$restoredCount = 0;

foreach ($candidateOrders as $order) {
    if ($currentJulyOrders->count() + $restoredCount >= 534) {
        // Stop jika sudah mencapai target 534
    }

    $mId = $order->order_marketplace_id;

    try {
        usleep(150000); // 150ms delay
        $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $mId);
        $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? $stmtData['transactions'] ?? [];

        $maxStmtTs = null;
        foreach ($stmtList as $st) {
            $stTime = $st['statement_time'] ?? $st['settlement_time'] ?? null;
            if ($stTime) {
                $stSec = (is_numeric($stTime) && strlen((string)$stTime) >= 13) ? (int)($stTime / 1000) : (int)$stTime;
                if ($maxStmtTs === null || $stSec > $maxStmtTs) {
                    $maxStmtTs = $stSec;
                }
            }
        }

        if ($maxStmtTs) {
            $stmtDate = Carbon::createFromTimestamp($maxStmtTs, 'Asia/Jakarta')->format('Y-m-d H:i:s');
            $stmtDay  = Carbon::createFromTimestamp($maxStmtTs, 'Asia/Jakarta')->format('Y-m-d');

            if ($stmtDay >= $dateFrom && $stmtDay <= $dateTo) {
                $order->completed_at = $stmtDate;
                $order->save();
                $restoredCount++;
                echo "   🟢 [RESTORED] Order ID: {$mId} -> Berhasil diset completed_at: {$stmtDate} (Bulan Juli)\n";
            }
        }
    } catch (\Exception $e) {}
}

$finalCount = Order::where('store_id', $store->id)
    ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
    ->whereNotNull('completed_at')
    ->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
    ->count();

echo "\n======================================================================\n";
echo "📊 HASIL AKHIR PEMULIHAN:\n";
echo "  • Jumlah Order Awal   : " . $currentJulyOrders->count() . " order\n";
echo "  • Berhasil Dipulihkan : +{$restoredCount} order\n";
echo "  • TOTAL AKHIR DI ERP  : " . ($finalCount === 534 ? "🎯 534 ORDER (100% EXPLICIT MATCH DENGAN EXCEL TIKTOK!)" : "{$finalCount} order") . "\n";
echo "======================================================================\n";
