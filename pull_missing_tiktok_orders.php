<?php

/**
 * ============================================================
 * PULL SEMUA ORDER TIKTOK YANG BELUM ADA DI ERP (Direct Fast)
 * ============================================================
 *
 * Cara pakai:
 *   php pull_missing_tiktok_orders.php --days=30
 *   php pull_missing_tiktok_orders.php --store=24 --days=30
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Jobs\PullOrdersFromTiktok;
use App\Services\TiktokService;
use Illuminate\Support\Facades\DB;

$args     = array_slice($argv, 1);
$isDryRun = in_array('--dry-run', $args);
$storeId  = null;
$days     = 30;
$fromDate = null;
$toDate   = null;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--store=')) $storeId  = (int) str_replace('--store=', '', $arg);
    if (str_starts_with($arg, '--days='))  $days     = max(1, min(90, (int) str_replace('--days=', '', $arg)));
    if (str_starts_with($arg, '--from='))  $fromDate = str_replace('--from=', '', $arg);
    if (str_starts_with($arg, '--to='))    $toDate   = str_replace('--to=', '', $arg);
}

if ($fromDate && $toDate) {
    $startTs = strtotime($fromDate . ' 00:00:00');
    $endTs   = strtotime($toDate   . ' 23:59:59');
} else {
    $startTs = strtotime("-{$days} days 00:00:00");
    $endTs   = strtotime('today 23:59:59');
}

$statusMapping = [
    '100' => 'UNPAID', '111' => 'READY_TO_SHIP', '112' => 'SHIPPED',
    '121' => 'SHIPPED', '122' => 'DELIVERED', '130' => 'COMPLETED', '140' => 'CANCELLED',
    'UNPAID' => 'UNPAID', 'AWAITING_SHIPMENT' => 'READY_TO_SHIP',
    'AWAITING_COLLECTION' => 'SHIPPED', 'PARTIALLY_SHIPPING' => 'SHIPPED',
    'IN_TRANSIT' => 'SHIPPED', 'DELIVERED' => 'DELIVERED',
    'COMPLETED' => 'COMPLETED', 'CANCELLED' => 'CANCELLED', 'IN_CANCEL' => 'CANCELLED',
];

echo "\n";
echo "======================================================================\n";
echo "  PULL ORDER TIKTOK YANG BELUM ADA (Fast Direct Commit)\n";
echo "======================================================================\n";
echo "  Mode  : " . ($isDryRun ? "DRY-RUN (preview saja)" : "LIVE (insert ke DB)") . "\n";
echo "  Dari  : " . date('d-m-Y', $startTs) . " s/d " . date('d-m-Y', $endTs) . "\n";
echo "  Toko  : " . ($storeId ? "Store ID #{$storeId}" : "Semua toko TikTok aktif") . "\n";
echo "======================================================================\n\n";

$query = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token');

if ($storeId) $query->where('id', $storeId);
$stores = $query->get();

if ($stores->isEmpty()) { echo "Tidak ada toko TikTok aktif.\n"; exit(0); }

$tiktokService = app(TiktokService::class);
try { DB::statement("SET SESSION innodb_lock_wait_timeout = 3;"); } catch (\Exception $e) {}

$grandNew    = 0;
$grandExists = 0;
$grandError  = 0;

$stepSeconds = 30 * 86400;

foreach ($stores as $store) {
    echo "======================================================================\n";
    echo "TOKO: {$store->store_name} (ID: {$store->id})\n";
    echo "======================================================================\n";

    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher  = $store->shop_cipher;

        $jobInstance = new PullOrdersFromTiktok($store, $startTs, $endTs);
        $jobInstance->skipStockDeduction = true;
        $reflection  = new \ReflectionClass($jobInstance);

        if ($reflection->hasProperty('store')) {
            $sp = $reflection->getProperty('store');
            $sp->setAccessible(true);
            $sp->setValue($jobInstance, $store);
        }

        $saveMethod = $reflection->getMethod('saveOrder');
        $saveMethod->setAccessible(true);

        $storeNew    = 0;
        $storeExists = 0;
        $storeError  = 0;

        $chunkStart = $startTs;

        while ($chunkStart <= $endTs) {
            $chunkEnd  = min($chunkStart + $stepSeconds - 1, $endTs);
            $labelFrom = date('Y-m-d', $chunkStart);
            $labelTo   = date('Y-m-d', $chunkEnd);

            echo "  [{$labelFrom} s/d {$labelTo}] Fetch API... ";
            if (ob_get_level() > 0) ob_flush(); flush();

            $tiktokOrderMap = [];
            $cursor     = '';
            $pageCount  = 0;

            do {
                try {
                    $resp = $tiktokService->getOrderList($accessToken, $shopCipher, $chunkStart, $chunkEnd, $cursor);
                } catch (\Exception $e) {
                    echo "API Error: " . $e->getMessage() . "\n";
                    break;
                }

                $orders = $resp['orders'] ?? [];
                foreach ($orders as $o) {
                    $oid = (string)($o['id'] ?? $o['order_id'] ?? null);
                    if ($oid) $tiktokOrderMap[$oid] = $o;
                }

                $cursor = $resp['next_cursor'] ?? '';
                if (++$pageCount > 50) break;
            } while (!empty($cursor));

            $allOrderIds = array_keys($tiktokOrderMap);
            $totalCount  = count($allOrderIds);

            if ($totalCount === 0) {
                echo "0 order\n";
                if (ob_get_level() > 0) ob_flush(); flush();
                $chunkStart = $chunkEnd + 1;
                continue;
            }

            // Cek mana yang sudah ada di DB ERP
            $existingIds = Order::where('store_id', $store->id)
                ->whereIn('order_marketplace_id', $allOrderIds)
                ->whereIn('order_status', ['COMPLETED', 'CANCELLED', 'SELESAI', 'FINISHED', 'BATAL'])
                ->pluck('order_marketplace_id')
                ->toArray();

            $missingIds = array_diff($allOrderIds, $existingIds);
            $missingCnt = count($missingIds);
            $storeExists += count($existingIds);

            if ($missingCnt === 0) {
                echo "TikTok={$totalCount}, ERP=" . count($existingIds) . " (Semua sudah ada)\n";
                if (ob_get_level() > 0) ob_flush(); flush();
                $chunkStart = $chunkEnd + 1;
                continue;
            }

            echo "TikTok={$totalCount}, ERP=" . count($existingIds) . ", BELUM ADA={$missingCnt}\n";
            if (ob_get_level() > 0) ob_flush(); flush();

            if ($isDryRun) {
                foreach ($missingIds as $mid) {
                    echo "    [DRY-RUN BELUM ADA] ID: {$mid}\n";
                }
                $storeNew += $missingCnt;
                $chunkStart = $chunkEnd + 1;
                continue;
            }

            // Fetch detail & save
            $chunks = array_chunk(array_values($missingIds), 50);

            foreach ($chunks as $chunk) {
                try {
                    $detailResp = $tiktokService->getOrderDetail($accessToken, $shopCipher, $chunk);
                    $detailList = $detailResp['orders'] ?? $detailResp['order_list'] ?? [];

                    $detailMap = [];
                    foreach ($detailList as $do) {
                        $dId = (string)($do['id'] ?? $do['order_id'] ?? null);
                        if ($dId) $detailMap[$dId] = $do;
                    }

                    foreach ($chunk as $mId) {
                        $orderData = $detailMap[$mId] ?? $tiktokOrderMap[$mId] ?? null;
                        if (!$orderData) continue;

                        try {
                            retry(4, function() use ($saveMethod, $jobInstance, $orderData) {
                                $saveMethod->invoke($jobInstance, $orderData);
                            }, 200);

                            $storeNew++;
                            echo "    [+] Saved & Committed ID: {$mId}\n";
                            if (ob_get_level() > 0) ob_flush(); flush();
                        } catch (\Exception $e) {
                            echo "    [ERROR] ID {$mId}: " . $e->getMessage() . "\n";
                            if (ob_get_level() > 0) ob_flush(); flush();
                            $storeError++;
                        }
                    }
                } catch (\Exception $e) {
                    echo "    [DETAIL API ERROR]: " . $e->getMessage() . "\n";
                    if (ob_get_level() > 0) ob_flush(); flush();
                    $storeError += count($chunk);
                }
            }

            $chunkStart = $chunkEnd + 1;
        }

        echo "\n  Hasil [{$store->store_name}]: Sudah Ada=" . ($storeExists) . " | Baru/Update={$storeNew} | Error={$storeError}\n\n";

        $grandNew    += $storeNew;
        $grandExists += $storeExists;
        $grandError  += $storeError;

    } catch (\Exception $e) {
        echo "  ERROR Toko: " . $e->getMessage() . "\n\n";
        $grandError++;
    }
}

echo "======================================================================\n";
echo "  RINGKASAN AKHIR PULL TIKTOK\n";
echo "======================================================================\n";
echo "  Sudah ada di ERP          : {$grandExists} order\n";
echo "  Berhasil ditambahkan      : {$grandNew} order\n";
echo "  Gagal / Error             : {$grandError} order\n";
echo "======================================================================\n\n";
