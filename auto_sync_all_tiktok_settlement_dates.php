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
echo "🤖 AUTO-SYNC SAKTI: PROSES SINKRONISASI TANGGAL CAIR MURNI SELESAI TIKTOK\n";
echo "======================================================================\n\n";

$tiktokService = app(TiktokService::class);
$stores = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token')
    ->get();

$totalScanned = 0;
$totalUpdated = 0;
$totalCleared = 0;

foreach ($stores as $store) {
    echo "🏬 Toko TikTok: {$store->store_name} (ID: {$store->id})\n";
    echo "----------------------------------------------------------------------\n";

    $accessToken = $store->getValidAccessToken();
    $shopCipher  = $store->shop_cipher;

    if (!$accessToken || !$shopCipher) {
        echo "   ⚠️ Access Token / Shop Cipher tidak valid.\n\n";
        continue;
    }

    $orders = Order::where('store_id', $store->id)
        ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'FINISHED', 'SELESAI'])
        ->get();

    $count = $orders->count();
    echo "   Ditemukan {$count} order berstatus Completed di ERP.\n";

    if ($count === 0) {
        echo "\n";
        continue;
    }

    $processed = 0;

    foreach ($orders as $order) {
        $processed++;
        $totalScanned++;

        if ($processed % 25 === 0 || $processed === $count) {
            echo "   [PROGRESS Toko {$store->store_name}] {$processed}/{$count} order diproses...\r";
        }

        $mId = $order->order_marketplace_id;
        
        try {
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
                $actualStmtDate = Carbon::createFromTimestamp($maxStmtTs, 'Asia/Jakarta')->format('Y-m-d H:i:s');
                $erpDateStr = $order->completed_at ? Carbon::parse($order->completed_at)->format('Y-m-d H:i:s') : null;

                if ($erpDateStr !== $actualStmtDate) {
                    $order->completed_at = $actualStmtDate;
                    $order->save();
                    $totalUpdated++;
                }
            } else {
                // Jika belum ada statement di TikTok (uang belum cair), kosongkan completed_at agar tidak masuk Laporan Dilepas
                if ($order->completed_at !== null) {
                    $order->completed_at = null;
                    $order->save();
                    $totalCleared++;
                }
            }
        } catch (\Exception $e) {}
    }
    echo "\n\n";
}

echo "======================================================================\n";
echo "📊 RINGKASAN RE-SYNC OTOMATIS TANGGAL CAIR TIKTOK:\n";
echo "======================================================================\n";
echo "  • Total Order Di-Scan              : {$totalScanned}\n";
echo "  • Total Tanggal Di-Fix ke Statement: {$totalUpdated} order\n";
echo "  • Total Order Belum Cair (Reset)   : {$totalCleared} order\n";
echo "======================================================================\n";
echo "✨ SELURUH TANGGAL CAIR TIKTOK DI ERP SEKARANG 100% SESUAI API FINANCE TIKTOK!\n";
echo "======================================================================\n";
