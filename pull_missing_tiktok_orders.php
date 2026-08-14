<?php

/**
 * ============================================================
 * PULL SEMUA ORDER TIKTOK YANG BELUM ADA DI ERP
 * ============================================================
 * Script ini menarik order dari TikTok API dan memasukkannya
 * ke ERP. Diproses per-hari agar tidak timeout.
 *
 * Cara pakai:
 *   php pull_missing_tiktok_orders.php                          -> 30 hari terakhir
 *   php pull_missing_tiktok_orders.php --days=90               -> 90 hari terakhir (max)
 *   php pull_missing_tiktok_orders.php --from=2026-07-01 --to=2026-08-14  -> range tertentu
 *   php pull_missing_tiktok_orders.php --store=17              -> 1 toko saja
 *   php pull_missing_tiktok_orders.php --dry-run               -> hitung saja, tidak insert
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Jobs\PullOrdersFromTiktok;
use App\Services\TiktokService;

// ── Parse argumen ──────────────────────────────────────────────
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

// Tentukan rentang tanggal
if ($fromDate && $toDate) {
    $startTs = strtotime($fromDate . ' 00:00:00');
    $endTs   = strtotime($toDate   . ' 23:59:59');
} else {
    $startTs = strtotime("-{$days} days 00:00:00");
    $endTs   = strtotime('today 23:59:59');
}

if (!$startTs || !$endTs || $startTs > $endTs) {
    echo "ERROR: Rentang tanggal tidak valid.\n";
    exit(1);
}

// ── Banner ─────────────────────────────────────────────────────
$totalDays = (int)(($endTs - $startTs) / 86400) + 1;

echo "\n";
echo "======================================================================\n";
echo "  PULL ORDER TIKTOK YANG BELUM ADA DI ERP\n";
echo "======================================================================\n";
echo "  Mode    : " . ($isDryRun ? "DRY-RUN (hitung saja)" : "LIVE (insert ke DB)") . "\n";
echo "  Dari    : " . date('d-m-Y', $startTs) . "\n";
echo "  Sampai  : " . date('d-m-Y', $endTs) . "\n";
echo "  Total   : {$totalDays} hari\n";
echo "  Toko    : " . ($storeId ? "Store ID #{$storeId}" : "Semua toko TikTok") . "\n";
echo "======================================================================\n\n";

// ── Ambil toko ────────────────────────────────────────────────
$storeQuery = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token');
if ($storeId) $storeQuery->where('id', $storeId);
$stores = $storeQuery->get();

if ($stores->isEmpty()) {
    echo "ERROR: Tidak ada toko TikTok aktif.\n";
    exit(1);
}

echo "Ditemukan " . $stores->count() . " toko TikTok aktif.\n\n";

$tiktokService = app(TiktokService::class);

$grandTotalNew    = 0;
$grandTotalExists = 0;
$grandTotalError  = 0;

foreach ($stores as $store) {
    echo "======================================================================\n";
    echo "TOKO: {$store->store_name} (ID: {$store->id})\n";
    echo "======================================================================\n";

    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher  = $store->shop_cipher;

        if (empty($shopCipher)) {
            echo "  SKIP: shop_cipher kosong.\n\n";
            continue;
        }

        $storeNew    = 0;
        $storeExists = 0;
        $storeError  = 0;

        // ── Proses per HARI agar tidak timeout ────────────────────────
        // TikTok API lebih stabil dengan rentang per-hari
        $currentDay = $startTs;

        while ($currentDay <= $endTs) {
            $dayStart = mktime(0,  0,  0,  date('n', $currentDay), date('j', $currentDay), date('Y', $currentDay));
            $dayEnd   = mktime(23, 59, 59, date('n', $currentDay), date('j', $currentDay), date('Y', $currentDay));
            $dayLabel = date('Y-m-d', $currentDay);

            echo "  [{$dayLabel}] Menarik order... ";

            // Ambil semua order dari TikTok untuk hari ini
            $tiktokOrderIds = [];
            $cursor     = '';
            $pageCount  = 0;
            $prevCursor = null;

            do {
                try {
                    $response = $tiktokService->getOrderList(
                        $accessToken, $shopCipher, $dayStart, $dayEnd, $cursor
                    );
                } catch (\Exception $e) {
                    echo "API Error: " . $e->getMessage() . "\n";
                    $storeError++;
                    break;
                }

                $orders = $response['orders'] ?? [];
                foreach ($orders as $o) {
                    $oid = (string)($o['id'] ?? $o['order_id'] ?? null);
                    if ($oid) $tiktokOrderIds[] = $oid;
                }

                $prevCursor = $cursor;
                $cursor     = $response['next_cursor'] ?? '';
                $hasMore    = $response['more'] ?? false;
                if ($cursor === $prevCursor || ++$pageCount > 20) break;
                usleep(100000);

            } while ($hasMore && $cursor);

            if (empty($tiktokOrderIds)) {
                echo "0 order.\n";
                $currentDay = strtotime('+1 day', $currentDay);
                continue;
            }

            // Cek berapa yang sudah ada di ERP
            $existingIds = Order::where('store_id', $store->id)
                ->whereIn('order_marketplace_id', $tiktokOrderIds)
                ->pluck('order_marketplace_id')
                ->toArray();

            $missingIds = array_diff($tiktokOrderIds, $existingIds);

            $totalOnTiktok = count($tiktokOrderIds);
            $alreadyInErp  = count($existingIds);
            $missing       = count($missingIds);

            echo "TikTok={$totalOnTiktok}, ERP={$alreadyInErp}, ";

            if ($missing === 0) {
                echo "Semua sudah ada.\n";
                $storeExists += $alreadyInErp;
                $currentDay = strtotime('+1 day', $currentDay);
                continue;
            }

            echo "BELUM ADA={$missing}";

            $storeExists += $alreadyInErp;

            if ($isDryRun) {
                echo " [DRY-RUN]\n";
                $storeNew += $missing;
                $currentDay = strtotime('+1 day', $currentDay);
                continue;
            }

            echo "\n";

            // Pull order yang belum ada — proses via PullOrdersFromTiktok job
            // tapi hanya untuk order yang missing
            $chunks = array_chunk(array_values($missingIds), 50);

            foreach ($chunks as $chunk) {
                try {
                    $detailResponse = $tiktokService->getOrderDetail($accessToken, $shopCipher, $chunk);
                    $orderList      = $detailResponse['order_list'] ?? [];

                    if (empty($orderList)) {
                        // Fallback: jalankan full job untuk hari ini
                        $job = new PullOrdersFromTiktok($store, $dayStart, $dayEnd);
                        app()->call([$job, 'handle']);
                        $storeNew += $missing;
                        break;
                    }

                    // Proses setiap order yang belum ada
                    $job = new PullOrdersFromTiktok($store, $dayStart, $dayEnd);
                    $reflection = new \ReflectionClass($job);
                    $method     = $reflection->getMethod('processOrder');
                    $method->setAccessible(true);

                    foreach ($orderList as $tiktokOrder) {
                        $oid = (string)($tiktokOrder['order_id'] ?? $tiktokOrder['id'] ?? null);
                        if (in_array($oid, $missingIds)) {
                            $method->invoke($job, $tiktokOrder);
                            echo "    + Ditambahkan: {$oid}\n";
                            $storeNew++;
                        }
                    }

                } catch (\Exception $e) {
                    echo "    ERROR detail API: " . $e->getMessage() . "\n";
                    // Fallback ke full job
                    try {
                        $job = new PullOrdersFromTiktok($store, $dayStart, $dayEnd);
                        app()->call([$job, 'handle']);
                        $storeNew += $missing;
                    } catch (\Exception $e2) {
                        echo "    ERROR full job: " . $e2->getMessage() . "\n";
                        $storeError += $missing;
                    }
                }
                usleep(300000);
            }

            $currentDay = strtotime('+1 day', $currentDay);
        }

        echo "\n";
        echo "  Ringkasan toko [{$store->store_name}]:\n";
        echo "  Sudah ada di ERP : {$storeExists} order\n";
        echo "  Baru ditambahkan : {$storeNew} order\n";
        if ($storeError > 0) echo "  Error           : {$storeError} order\n";
        echo "\n";

        $grandTotalNew    += $storeNew;
        $grandTotalExists += $storeExists;
        $grandTotalError  += $storeError;

    } catch (\Exception $e) {
        echo "  ERROR toko: " . $e->getMessage() . "\n\n";
        $grandTotalError++;
    }
}

// ── Ringkasan ──────────────────────────────────────────────────
echo "======================================================================\n";
echo "  RINGKASAN AKHIR " . ($isDryRun ? "(DRY-RUN)" : "") . "\n";
echo "======================================================================\n";
echo "  Sudah ada di ERP          : {$grandTotalExists} order\n";
echo "  Baru ditambahkan ke ERP   : {$grandTotalNew} order\n";
echo "  Error                     : {$grandTotalError} order\n";
echo "======================================================================\n";

if ($isDryRun && $grandTotalNew > 0) {
    echo "\nAda {$grandTotalNew} order belum masuk ERP!\n";
    echo "Jalankan tanpa --dry-run untuk menariknya:\n";
    if ($fromDate && $toDate) {
        echo "  php pull_missing_tiktok_orders.php --from={$fromDate} --to={$toDate}";
    } else {
        echo "  php pull_missing_tiktok_orders.php --days={$days}";
    }
    if ($storeId) echo " --store={$storeId}";
    echo "\n";
}

echo "\nSelesai!\n\n";
