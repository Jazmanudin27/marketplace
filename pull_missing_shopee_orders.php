<?php

/**
 * ============================================================
 * PULL SEMUA ORDER SHOPEE YANG BELUM ADA DI ERP (90 HARI)
 * ============================================================
 * Melengkapi order Shopee bulan Juni, Juli & 90 hari terakhir.
 *
 * Cara pakai:
 *   php pull_missing_shopee_orders.php --days=90
 *   php pull_missing_shopee_orders.php --store=15 --days=90
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Jobs\PullOrdersFromShopee;
use App\Services\ShopeeService;
use Illuminate\Support\Facades\DB;

$args     = array_slice($argv, 1);
$isDryRun = in_array('--dry-run', $args);
$storeId  = null;
$days     = 90;
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

if (!$startTs || !$endTs || $startTs > $endTs) {
    echo "ERROR: Rentang tanggal tidak valid.\n"; exit(1);
}

$totalDays = (int)(($endTs - $startTs) / 86400) + 1;
echo "\n";
echo "======================================================================\n";
echo "  PULL ORDER SHOPEE (Fast Direct Commit)\n";
echo "======================================================================\n";
echo "  Mode  : " . ($isDryRun ? "DRY-RUN (preview saja)" : "LIVE (insert ke DB)") . "\n";
echo "  Dari  : " . date('d-m-Y', $startTs) . " s/d " . date('d-m-Y', $endTs) . " ({$totalDays} hari)\n";
echo "  Toko  : " . ($storeId ? "Store ID #{$storeId}" : "Semua toko Shopee aktif") . "\n";
echo "======================================================================\n\n";

$storeQuery = Store::whereHas('channel', fn($q) => $q->where('code', 'shopee'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token');

if ($storeId) $storeQuery->where('id', $storeId);
$stores = $storeQuery->get();

if ($stores->isEmpty()) { echo "ERROR: Tidak ada toko Shopee aktif.\n"; exit(1); }

$shopeeService = app(ShopeeService::class);
try {
    DB::statement("SET SESSION innodb_lock_wait_timeout = 3;");
} catch (\Exception $e) {}

$grandNew    = 0;
$grandExists = 0;
$grandError  = 0;

$stepSeconds = 15 * 86400; // Limit Shopee API max 15 days

foreach ($stores as $store) {
    echo "======================================================================\n";
    echo "TOKO: {$store->store_name} (ID: {$store->id})\n";
    echo "======================================================================\n";

    try {
        $accessToken = $store->getValidAccessToken();
        $shopId      = (int) $store->marketplace_store_id;

        if (!$shopId) {
            echo "  SKIP: marketplace_store_id kosong.\n\n";
            continue;
        }

        $jobInstance   = new PullOrdersFromShopee($store, $startTs, $endTs);
        $jobInstance->skipStockDeduction = true;
        $reflection    = new \ReflectionClass($jobInstance);

        if ($reflection->hasProperty('store')) {
            $storeProp = $reflection->getProperty('store');
            $storeProp->setAccessible(true);
            $storeProp->setValue($jobInstance, $store);
        }

        $saveMethod    = $reflection->getMethod('saveOrder');
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

            $allOrderSn = [];
            $cursor  = '';
            $hasMore = true;
            $pageCount = 0;

            while ($hasMore) {
                try {
                    $resp = $shopeeService->getOrderList($accessToken, $shopId, $chunkStart, $chunkEnd, 'create_time', $cursor);
                } catch (\Exception $e) {
                    echo "API Error: " . $e->getMessage() . "\n";
                    $storeError++;
                    break;
                }

                $orderList = $resp['order_list'] ?? [];
                if (empty($orderList)) break;

                foreach ($orderList as $o) {
                    if (!empty($o['order_sn'])) {
                        $allOrderSn[] = $o['order_sn'];
                    }
                }

                $hasMore = $resp['more'] ?? false;
                $cursor  = $resp['next_cursor'] ?? '';
                if (++$pageCount > 50) break;
            }

            $allOrderSn = array_unique($allOrderSn);

            if (empty($allOrderSn)) {
                echo "0 order.\n";
                $chunkStart = $chunkEnd + 1;
                continue;
            }

            $existingIds = Order::where('store_id', $store->id)
                ->whereIn('order_marketplace_id', $allOrderSn)
                ->pluck('order_marketplace_id')
                ->toArray();

            $missingIds = array_diff($allOrderSn, $existingIds);
            $totalChunk = count($allOrderSn);
            $missingCnt = count($missingIds);

            echo "Shopee={$totalChunk}, ERP=" . count($existingIds) . ", PERLU PULL={$missingCnt}\n";
            $storeExists += count($existingIds);

            if ($missingCnt === 0) {
                $chunkStart = $chunkEnd + 1;
                continue;
            }

            if ($isDryRun) {
                echo "    [DRY-RUN] Skip simpan.\n";
                $storeNew += $missingCnt;
                $chunkStart = $chunkEnd + 1;
                continue;
            }

            $missingArr = array_values($missingIds);
            $chunks     = array_chunk($missingArr, 50);

            foreach ($chunks as $chunk) {
                try {
                    $detailResp = $shopeeService->getOrderDetail($accessToken, $shopId, $chunk);
                    $detailList = $detailResp['order_list'] ?? [];

                    foreach ($detailList as $shopeeOrder) {
                        $orderSn = $shopeeOrder['order_sn'] ?? null;
                        if (!$orderSn) continue;

                        try {
                            retry(8, function() use ($saveMethod, $jobInstance, $shopeeOrder) {
                                $saveMethod->invoke($jobInstance, $shopeeOrder);
                            }, 1000);

                            $storeNew++;
                            echo "    [+] Saved & Committed: {$orderSn}\n";
                            if (ob_get_level() > 0) ob_flush();
                            flush();
                        } catch (\Exception $e) {
                            echo "    [ERROR] {$orderSn}: " . $e->getMessage() . "\n";
                            if (ob_get_level() > 0) ob_flush();
                            flush();
                            $storeError++;
                        }
                    }
                } catch (\Exception $e) {
                    echo "    [DETAIL API ERROR]: " . $e->getMessage() . "\n";
                    $storeError += count($chunk);
                }
            }

            $chunkStart = $chunkEnd + 1;
        }

        echo "\n  Ringkasan [{$store->store_name}]: Ada={$storeExists} | Baru/Update={$storeNew} | Error={$storeError}\n\n";

        $grandNew    += $storeNew;
        $grandExists += $storeExists;
        $grandError  += $storeError;

    } catch (\Exception $e) {
        echo "  ERROR toko: " . $e->getMessage() . "\n\n";
        $grandError++;
    }
}

echo "======================================================================\n";
echo "  RINGKASAN AKHIR " . ($isDryRun ? "(DRY-RUN)" : "") . "\n";
echo "======================================================================\n";
echo "  Sudah ada di ERP          : {$grandExists} order\n";
echo "  Berhasil ditambahkan      : {$grandNew} order\n";
echo "  Gagal / Error             : {$grandError} order\n";
echo "======================================================================\n";

echo "\nSelesai!\n\n";
