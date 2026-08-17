<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "======================================================================\n";
echo "⚡ SCANNER & AUTO-UPDATE TANGGAL DITERIMA TIKTOK (MODE KILAT CHUNK 50)\n";
echo "======================================================================\n\n";

$tiktokService = app(TiktokService::class);
$stores = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token')
    ->get();

$totalScanned = 0;
$totalUpdated = 0;
$totalMatch   = 0;

foreach ($stores as $store) {
    echo "🏬 Toko: [{$store->store_name}] (ID #{$store->id})\n";
    echo "----------------------------------------------------------------------\n";

    $accessToken = $store->getValidAccessToken();
    $shopCipher  = $store->shop_cipher;

    if (!$accessToken || !$shopCipher) {
        echo "   ⚠️ Access Token / Shop Cipher tidak valid.\n\n";
        continue;
    }

    $orders = Order::where('store_id', $store->id)
        ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'FINISHED', 'SELESAI', 'READY_TO_SHIP', 'SHIPPED'])
        ->get();

    $orderCount = $orders->count();
    echo "   Ditemukan {$orderCount} transaksi pesanan di database ERP lokal.\n";

    if ($orderCount === 0) {
        echo "\n";
        continue;
    }

    // Chunk per 50 Order ID agar API TikTok terpanggil kilat
    $chunks = $orders->chunk(50);

    foreach ($chunks as $chunkIndex => $orderChunk) {
        $marketplaceIds = $orderChunk->pluck('order_marketplace_id')->filter()->values()->toArray();
        if (empty($marketplaceIds)) continue;

        try {
            $detailResp = $tiktokService->getOrderDetail($accessToken, $shopCipher, $marketplaceIds);
            $tOrderList = $detailResp['orders'] ?? $detailResp['order_list'] ?? [];

            $tiktokMap = [];
            foreach ($tOrderList as $to) {
                $toId = (string)($to['id'] ?? $to['order_id'] ?? null);
                if ($toId) $tiktokMap[$toId] = $to;
            }

            foreach ($orderChunk as $order) {
                $totalScanned++;
                $mId = $order->order_marketplace_id;
                $tOrder = $tiktokMap[$mId] ?? null;

                if (!$tOrder) continue;

                $changed = false;

                // 1. TANGGAL SELESAI / DITERIMA (statement_time dari Finance API | finish_time | delivered_time)
                $stmtTs = null;
                try {
                    $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $mId);
                    $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? $stmtData['transactions'] ?? [];
                    foreach ($stmtList as $st) {
                        $stTime = $st['statement_time'] ?? $st['settlement_time'] ?? null;
                        if ($stTime) {
                            $stSec = (is_numeric($stTime) && strlen((string)$stTime) >= 13) ? (int)($stTime / 1000) : (int)$stTime;
                            if ($stmtTs === null || $stSec > $stmtTs) {
                                $stmtTs = $stSec;
                            }
                        }
                    }
                } catch (\Exception $exStmt) {}

                $compTs = $stmtTs ?? $tOrder['finish_time'] ?? $tOrder['delivered_time'] ?? $tOrder['complete_time'] ?? $tOrder['delivery_time'] ?? $tOrder['update_time'] ?? null;
                
                if ($compTs) {
                    $cSec = (is_numeric($compTs) && strlen((string)$compTs) >= 13) ? (int)($compTs / 1000) : (int)$compTs;
                    $apiCompDate = Carbon::createFromTimestamp($cSec, 'Asia/Jakarta')->format('Y-m-d H:i:s');
                    $erpCompDate = $order->completed_at ? Carbon::parse($order->completed_at)->format('Y-m-d H:i:s') : null;

                    if ($erpCompDate !== $apiCompDate) {
                        $order->completed_at = $apiCompDate;
                        $changed = true;
                    }
                }

                // 2. TANGGAL ORDER (create_time) dalam WIB (Asia/Jakarta)
                $createTs = $tOrder['create_time'] ?? null;
                if ($createTs) {
                    $crSec = (is_numeric($createTs) && strlen((string)$createTs) >= 13) ? (int)($createTs / 1000) : (int)$createTs;
                    $apiOrderDate = Carbon::createFromTimestamp($crSec, 'Asia/Jakarta')->format('Y-m-d H:i:s');
                    $erpOrderDate = $order->order_date ? Carbon::parse($order->order_date)->format('Y-m-d H:i:s') : null;

                    if ($erpOrderDate !== $apiOrderDate) {
                        $order->order_date = $apiOrderDate;
                        $changed = true;
                    }
                }

                if ($changed) {
                    $order->save();
                    $totalUpdated++;
                    echo "   [UPDATED] Order: {$mId} -> ERP Date: " . ($order->completed_at ? Carbon::parse($order->completed_at)->format('d/m/Y H:i') : '-') . "\n";
                } else {
                    $totalMatch++;
                }
            }
        } catch (\Exception $e) {
            echo "   ⚠️ Error fetching chunk: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
}

echo "======================================================================\n";
echo "📊 RINGKASAN SCANNER KILAT PERBAIKAN TANGGAL TIKTOK\n";
echo "======================================================================\n";
echo "  • Total Order Di-Scan           : {$totalScanned}\n";
echo "  • Total Tanggal Sudah Sama/Match : {$totalMatch}\n";
echo "  • Total Tanggal Di-Fix / Update  : " . ($totalUpdated > 0 ? "⚡ {$totalUpdated} ORDER SUCCESS UPDATED!" : "✅ 0 (Semua tanggal sudah presisi!)") . "\n";
echo "======================================================================\n";
