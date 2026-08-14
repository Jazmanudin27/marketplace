<?php

/**
 * ============================================================
 * PULL SEMUA ORDER TIKTOK (Realtime Direct Commit)
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

if (!$startTs || !$endTs || $startTs > $endTs) {
    echo "ERROR: Rentang tanggal tidak valid.\n"; exit(1);
}

$totalDays = (int)(($endTs - $startTs) / 86400) + 1;
echo "\n";
echo "======================================================================\n";
echo "  PULL ORDER TIKTOK (Realtime Direct Commit)\n";
echo "======================================================================\n";
echo "  Mode  : " . ($isDryRun ? "DRY-RUN (preview saja)" : "LIVE (insert ke DB)") . "\n";
echo "  Dari  : " . date('d-m-Y', $startTs) . " s/d " . date('d-m-Y', $endTs) . " ({$totalDays} hari)\n";
echo "  Toko  : " . ($storeId ? "Store ID #{$storeId}" : "Semua toko TikTok") . "\n";
echo "======================================================================\n\n";

$storeQuery = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token');
if ($storeId) $storeQuery->where('id', $storeId);
$stores = $storeQuery->get();

if ($stores->isEmpty()) { echo "ERROR: Tidak ada toko TikTok aktif.\n"; exit(1); }

$tiktokService  = app(TiktokService::class);

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
        if (empty($shopCipher)) { echo "  SKIP: shop_cipher kosong.\n\n"; continue; }

        $jobInstance   = new PullOrdersFromTiktok($store, $startTs, $endTs);
        $reflection    = new \ReflectionClass($jobInstance);
        $processMethod = $reflection->getMethod('processOrder');
        $processMethod->setAccessible(true);

        $storeNew    = 0;
        $storeExists = 0;
        $storeError  = 0;

        $chunkStart = $startTs;

        while ($chunkStart <= $endTs) {
            $chunkEnd  = min($chunkStart + $stepSeconds - 1, $endTs);
            $labelFrom = date('Y-m-d', $chunkStart);
            $labelTo   = date('Y-m-d', $chunkEnd);

            echo "  [{$labelFrom} s/d {$labelTo}] Fetch API... ";

            $tiktokOrderMap = [];
            $cursor     = '';
            $pageCount  = 0;

            do {
                try {
                    $resp = $tiktokService->getOrderList($accessToken, $shopCipher, $chunkStart, $chunkEnd, $cursor);
                } catch (\Exception $e) {
                    echo "API Error: " . $e->getMessage() . "\n";
                    $storeError++;
                    break;
                }

                $orders = $resp['orders'] ?? [];
                foreach ($orders as $o) {
                    $oid = (string)($o['id'] ?? $o['order_id'] ?? null);
                    if ($oid) $tiktokOrderMap[$oid] = $o;
                }

                $cursor  = $resp['next_cursor'] ?? '';
                $hasMore = $resp['more'] ?? false;
                if (++$pageCount > 50) break;

            } while ($hasMore && $cursor);

            if (empty($tiktokOrderMap)) {
                echo "0 order.\n";
                $chunkStart = $chunkEnd + 1;
                continue;
            }

            $tiktokIds = array_keys($tiktokOrderMap);

            $existingIds = Order::where('store_id', $store->id)
                ->whereIn('order_marketplace_id', $tiktokIds)
                ->pluck('order_marketplace_id')
                ->toArray();

            $missingIds = array_diff($tiktokIds, $existingIds);
            $totalChunk = count($tiktokIds);
            $missingCnt = count($missingIds);

            echo "TikTok={$totalChunk}, ERP=" . count($existingIds) . ", BELUM ADA={$missingCnt}\n";
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
            $detailMap  = [];

            if ($chunkEnd >= strtotime('-30 days')) {
                $chunks = array_chunk($missingArr, 50);
                foreach ($chunks as $chunk) {
                    try {
                        $detailResp = $tiktokService->getOrderDetail($accessToken, $shopCipher, $chunk);
                        $detailList = $detailResp['order_list'] ?? [];
                        foreach ($detailList as $d) {
                            $did = (string)($d['order_id'] ?? $d['id'] ?? null);
                            if ($did) $detailMap[$did] = $d;
                        }
                    } catch (\Exception $e) {}
                }
            }

            // Direct Commit per Order
            foreach ($missingArr as $mid) {
                $orderData = $detailMap[$mid] ?? $tiktokOrderMap[$mid] ?? null;
                if (!$orderData) continue;

                try {
                    DB::transaction(function() use ($processMethod, $jobInstance, $orderData) {
                        $processMethod->invoke($jobInstance, $orderData);
                    });
                    $storeNew++;
                    echo "    [+] Saved & Committed: {$mid}\n";
                } catch (\Exception $e) {
                    echo "    [ERROR] {$mid}: " . $e->getMessage() . "\n";
                    $storeError++;
                }
            }

            $chunkStart = $chunkEnd + 1;
        }

        echo "\n  Ringkasan [{$store->store_name}]: Ada={$storeExists} | Baru={$storeNew} | Error={$storeError}\n\n";

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
