<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;
use Carbon\Carbon;

$dateFrom = $argv[1] ?? '2026-07-01';
$dateTo   = $argv[2] ?? '2026-07-31';

echo "======================================================================\n";
echo "⚡ AUDIT SUPER KILAT PERBEDAAN JUMLAH DANA DILEPAS TIKTOK\n";
echo "======================================================================\n\n";

$tiktokService = app(TiktokService::class);
$stores = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token')
    ->get();

foreach ($stores as $store) {
    echo "🏬 Toko TikTok: {$store->store_name} (ID: {$store->id})\n";
    echo "----------------------------------------------------------------------\n";

    $accessToken = $store->getValidAccessToken();
    $shopCipher  = $store->shop_cipher;

    $orders = Order::where('store_id', $store->id)
        ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'FINISHED', 'SELESAI'])
        ->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
        ->get();

    $totalCount = $orders->count();
    echo "   Periode Filter ERP : {$dateFrom} s/d {$dateTo}\n";
    echo "   Total Order Completed di ERP : {$totalCount} order\n\n";

    if ($totalCount === 0) continue;

    $settledOrders   = [];
    $unsettledOrders = [];
    $dateShiftOrders = [];

    $processed = 0;

    foreach ($orders as $order) {
        $processed++;
        if ($processed % 20 === 0 || $processed === $totalCount) {
            echo "   [PROGRESS] Memeriksa {$processed} dari {$totalCount} order...\r";
        }

        $mId = $order->order_marketplace_id;
        $fb  = $order->financial_breakdown ?? [];

        $stmtList = $fb['statement_transactions'] ?? $fb['statement_transaction_list'] ?? $fb['transactions'] ?? [];

        // Jika local DB tidak punya statement data, panggil API (jika ada token)
        if (empty($stmtList) && $accessToken && $shopCipher) {
            try {
                $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $mId);
                $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? $stmtData['transactions'] ?? [];
            } catch (\Exception $ex) {}
        }

        if (empty($stmtList)) {
            // Cek apakah escrow_amount / net_amount > 0
            if ((float)$order->net_amount <= 0 && (float)($fb['escrow_amount'] ?? 0) <= 0) {
                $unsettledOrders[] = [
                    'id' => $mId,
                    'erp_date' => $order->completed_at ? $order->completed_at->format('d/m/Y H:i') : '-',
                    'reason' => 'Data Net Escrow Rp 0 / Belum Cair di TikTok API'
                ];
                continue;
            }
        }

        $maxStmtTs = null;
        foreach ($stmtList as $st) {
            $stTime = $st['statement_time'] ?? $st['paid_time'] ?? null;
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
                $dateShiftOrders[] = [
                    'id' => $mId,
                    'erp_date' => $order->completed_at ? $order->completed_at->format('d/m/Y H:i') : '-',
                    'api_date' => Carbon::createFromTimestamp($maxStmtTs, 'Asia/Jakarta')->format('d/m/Y H:i'),
                    'reason' => "Tanggal Cair Asli TikTok ({$actualStmtDate}) berada di luar periode ({$dateFrom} s/d {$dateTo})"
                ];
            } else {
                $settledOrders[] = $mId;
            }
        } else {
            $settledOrders[] = $mId;
        }
    }

    echo "\n\n📊 HASIL AUDIT DANA DILEPAS:\n";
    echo "  • Total Order BENAR-BENAR CAIR (Settled di Periode) : " . count($settledOrders) . " order\n";
    echo "  • Total Order ANOMALI / UNSETTLED                  : " . count($unsettledOrders) . " order\n";
    echo "  • Total Order TANGGAL CAIR BEDA PERIODE            : " . count($dateShiftOrders) . " order\n\n";

    if (!empty($unsettledOrders)) {
        echo "⚠️ ORDER YANG BELUM CAIR DI TIKTOK (" . count($unsettledOrders) . " order):\n";
        foreach ($unsettledOrders as $idx => $u) {
            echo "   " . ($idx + 1) . ". Order ID: {$u['id']} | Tanggal ERP: {$u['erp_date']} | Alasan: {$u['reason']}\n";
        }
        echo "\n";
    }

    if (!empty($dateShiftOrders)) {
        echo "⚠️ ORDER YANG TANGGAL CAIR ASLINYA BEDA PERIODE (" . count($dateShiftOrders) . " order):\n";
        foreach ($dateShiftOrders as $idx => $d) {
            echo "   " . ($idx + 1) . ". Order ID: {$d['id']} | Tanggal ERP: {$d['erp_date']} | Tanggal Asli TikTok: {$d['api_date']} | Alasan: {$d['reason']}\n";
        }
        echo "\n";
    }
}

echo "======================================================================\n";
