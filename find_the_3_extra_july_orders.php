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
echo "🔍 INVESTIGASI PRESISI: MENCARI EXACT 3 ORDER EXTRA DI ERP (537 VS 534)\n";
echo "======================================================================\n\n";

$tiktokService = app(TiktokService::class);
$store = Store::find(30); // Nusantara Seragam

if (!$store) {
    echo "❌ Toko ID #30 (Nusantara seragam) tidak ditemukan.\n";
    exit(1);
}

$accessToken = $store->getValidAccessToken();
$shopCipher  = $store->shop_cipher;

$dateFrom = '2026-07-01';
$dateTo   = '2026-07-31';

$orders = Order::where('store_id', $store->id)
    ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
    ->whereNotNull('completed_at')
    ->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
    ->get();

echo "🏬 Toko: {$store->store_name} (ID: {$store->id})\n";
echo "📊 Total Order di ERP Periode Juli : " . $orders->count() . " order (Target: 534)\n\n";

$extraOrders = [];

foreach ($orders as $idx => $order) {
    $mId = $order->order_marketplace_id;
    $fb  = $order->financial_breakdown ?? [];

    $stmtList = $fb['statement_transactions'] ?? $fb['statement_transaction_list'] ?? $fb['transactions'] ?? [];

    // Jika DB lokal belum punya statement data, panggil API dengan jeda usleep (Anti Rate Limit TikTok)
    if (empty($stmtList) && $accessToken && $shopCipher) {
        $retries = 0;
        while ($retries < 3) {
            try {
                usleep(150000); // 150ms delay (Anti Rate Limit)
                $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $mId);
                $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? $stmtData['transactions'] ?? [];
                break;
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'Too many requests') || str_contains($e->getMessage(), '429')) {
                    $retries++;
                    sleep(1); // Tunggu 1 detik jika kena rate limit
                } else {
                    break;
                }
            }
        }
    }

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
        $actualStmtDate = Carbon::createFromTimestamp($maxStmtTs, 'Asia/Jakarta')->format('Y-m-d');
        if ($actualStmtDate < $dateFrom || $actualStmtDate > $dateTo) {
            $extraOrders[] = [
                'order' => $order,
                'api_date' => Carbon::createFromTimestamp($maxStmtTs, 'Asia/Jakarta')->format('d/m/Y H:i'),
                'reason' => "Tanggal Cair Asli TikTok ({$actualStmtDate}) di luar bulan Juli"
            ];
        }
    } else {
        // Cek jika net_amount <= 0
        if ((float)$order->net_amount <= 0 && (float)($fb['escrow_amount'] ?? 0) <= 0) {
            $extraOrders[] = [
                'order' => $order,
                'api_date' => 'Belum Cair / Net Rp 0',
                'reason' => 'Data Net Escrow Rp 0 / Belum Settled'
            ];
        }
    }
}

echo "\n======================================================================\n";
echo "🔴 DITEMUKAN PERSIS " . count($extraOrders) . " ORDER BIANG KEROK MENGAPA ERP 537 (HARUSNYA 534):\n";
echo "======================================================================\n";

foreach ($extraOrders as $idx => $item) {
    $o = $item['order'];
    echo "  " . ($idx + 1) . ". Order ID: '{$o->order_marketplace_id}\n";
    echo "     - Tanggal ERP Saat Ini : " . ($o->completed_at ? $o->completed_at->format('d/m/Y H:i') : '-') . "\n";
    echo "     - Tanggal Asli TikTok  : {$item['api_date']}\n";
    echo "     - Alasan             : {$item['reason']}\n";

    if (str_contains($item['reason'], 'di luar bulan Juli') && $item['api_date'] !== 'Belum Cair / Net Rp 0') {
        $stSec = Carbon::createFromFormat('d/m/Y H:i', $item['api_date'], 'Asia/Jakarta')->format('Y-m-d H:i:s');
        $o->completed_at = $stSec;
        $o->save();
        echo "     ⚡ [AUTO-FIX] Berhasil menggeser Tanggal ERP ke {$stSec}\n";
    } elseif ($item['api_date'] === 'Belum Cair / Net Rp 0') {
        $o->completed_at = null;
        $o->save();
        echo "     ⚡ [AUTO-FIX] Berhasil mengosongkan completed_at (Reset) karena belum cair\n";
    }
    echo "----------------------------------------------------------------------\n";
}

echo "\n======================================================================\n";
echo "✨ FIX SELESAI! Silakan cek kembali total order di Laporan ERP!\n";
echo "======================================================================\n";
