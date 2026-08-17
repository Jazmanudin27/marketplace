<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;
use Carbon\Carbon;

$dateFrom = $argv[1] ?? null;
$dateTo   = $argv[2] ?? null;

echo "======================================================================\n";
echo "🔍 AUDIT PERBEDAAN JUMLAH DANA DILEPAS TIKTOK (ERP VS MARKETPLACE)\n";
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

    if (!$accessToken || !$shopCipher) continue;

    $query = Order::where('store_id', $store->id)
        ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'FINISHED', 'SELESAI']);

    if ($dateFrom && $dateTo) {
        $query->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
        echo "   Periode Filter ERP: {$dateFrom} s/d {$dateTo}\n";
    }

    $orders = $query->get();
    echo "   Total Order Completed di ERP : " . $orders->count() . " order\n\n";

    $settledOrders   = [];
    $unsettledOrders = [];
    $dateShiftOrders = [];

    foreach ($orders as $order) {
        $mId = $order->order_marketplace_id;
        try {
            $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $mId);
            $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? $stmtData['transactions'] ?? [];

            if (empty($stmtList)) {
                $unsettledOrders[] = [
                    'id' => $mId,
                    'erp_date' => $order->completed_at ? $order->completed_at->format('d/m/Y H:i') : '-',
                    'reason' => 'Belum ada transaksi Statement di TikTok (Belum Cair / Dalam Proses)'
                ];
                continue;
            }

            $hasSettled = false;
            $maxStmtTs  = null;

            foreach ($stmtList as $st) {
                if (isset($st['settlement_amount']) || isset($st['statement_id'])) {
                    $hasSettled = true;
                }
                $stTime = $st['statement_time'] ?? $st['paid_time'] ?? null;
                if ($stTime) {
                    $stSec = (is_numeric($stTime) && strlen((string)$stTime) >= 13) ? (int)($stTime / 1000) : (int)$stTime;
                    if ($maxStmtTs === null || $stSec > $maxStmtTs) {
                        $maxStmtTs = $stSec;
                    }
                }
            }

            if (!$hasSettled) {
                $unsettledOrders[] = [
                    'id' => $mId,
                    'erp_date' => $order->completed_at ? $order->completed_at->format('d/m/Y H:i') : '-',
                    'reason' => 'Status Statement belum SETTLED di TikTok'
                ];
            } else {
                $actualStmtDate = $maxStmtTs ? Carbon::createFromTimestamp($maxStmtTs, 'Asia/Jakarta')->format('Y-m-d') : null;
                $erpDateStr     = $order->completed_at ? $order->completed_at->format('Y-m-d') : null;

                if ($dateFrom && $dateTo && $actualStmtDate && ($actualStmtDate < $dateFrom || $actualStmtDate > $dateTo)) {
                    $dateShiftOrders[] = [
                        'id' => $mId,
                        'erp_date' => $order->completed_at->format('d/m/Y H:i'),
                        'api_date' => Carbon::createFromTimestamp($maxStmtTs, 'Asia/Jakarta')->format('d/m/Y H:i'),
                        'reason' => "Tanggal Cair API TikTok ({$actualStmtDate}) berada di LUAR PERIODE filter ({$dateFrom} s/d {$dateTo})"
                    ];
                } else {
                    $settledOrders[] = $mId;
                }
            }
        } catch (\Exception $e) {
            $unsettledOrders[] = [
                'id' => $mId,
                'erp_date' => $order->completed_at ? $order->completed_at->format('d/m/Y H:i') : '-',
                'reason' => 'Gagal API Statement: ' . $e->getMessage()
            ];
        }
    }

    echo "📊 HASIL CEK RINCIAN TIKTOK API:\n";
    echo "  • Total Order BENAR-BENAR CAIR (Settled di Periode) : " . count($settledOrders) . " order\n";
    echo "  • Total Order BELUM CAIR di TikTok API            : " . count($unsettledOrders) . " order\n";
    echo "  • Total Order TANGGAL CAIR BEDA PERIODE            : " . count($dateShiftOrders) . " order\n\n";

    if (!empty($unsettledOrders)) {
        echo "⚠️ ANOMALI 1: Order di ERP 'Completed', tapi di TikTok API Finance BELUM CAIR (" . count($unsettledOrders) . " order):\n";
        foreach ($unsettledOrders as $idx => $u) {
            echo "   " . ($idx + 1) . ". Order ID: {$u['id']} | Tanggal ERP: {$u['erp_date']} | Alasan: {$u['reason']}\n";
        }
        echo "\n";
    }

    if (!empty($dateShiftOrders)) {
        echo "⚠️ ANOMALI 2: Order yang Tanggal Cair Aslinya Berada di Luar Periode Filter (" . count($dateShiftOrders) . " order):\n";
        foreach ($dateShiftOrders as $idx => $d) {
            echo "   " . ($idx + 1) . ". Order ID: {$d['id']} | Tanggal ERP: {$d['erp_date']} | Tanggal Asli TikTok: {$d['api_date']} | Alasan: {$d['reason']}\n";
        }
        echo "\n";
    }
}

echo "======================================================================\n";
