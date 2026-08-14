<?php

/**
 * ============================================================
 * PULL SEMUA ORDER TIKTOK YANG BELUM ADA DI ERP
 * ============================================================
 * FIX: Simpan data lengkap dari getOrderList sebagai fallback
 * ketika getOrderDetail gagal untuk order lama (> 30 hari).
 *
 * Cara pakai:
 *   php pull_missing_tiktok_orders.php                             -> 30 hari
 *   php pull_missing_tiktok_orders.php --days=90                  -> 90 hari
 *   php pull_missing_tiktok_orders.php --from=2026-07-01 --to=2026-08-14
 *   php pull_missing_tiktok_orders.php --store=17                 -> 1 toko
 *   php pull_missing_tiktok_orders.php --dry-run                  -> preview
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

// ── Banner ─────────────────────────────────────────────────────
$totalDays = (int)(($endTs - $startTs) / 86400) + 1;
echo "\n";
echo "======================================================================\n";
echo "  PULL ORDER TIKTOK YANG BELUM ADA DI ERP (v2 - dengan fallback)\n";
echo "======================================================================\n";
echo "  Mode  : " . ($isDryRun ? "DRY-RUN (preview saja)" : "LIVE (insert ke DB)") . "\n";
echo "  Dari  : " . date('d-m-Y', $startTs) . " s/d " . date('d-m-Y', $endTs) . " ({$totalDays} hari)\n";
echo "  Toko  : " . ($storeId ? "Store ID #{$storeId}" : "Semua toko TikTok") . "\n";
echo "======================================================================\n\n";

// ── Ambil toko ────────────────────────────────────────────────
$storeQuery = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token');
if ($storeId) $storeQuery->where('id', $storeId);
$stores = $storeQuery->get();

if ($stores->isEmpty()) { echo "ERROR: Tidak ada toko TikTok aktif.\n"; exit(1); }
echo "Ditemukan " . $stores->count() . " toko TikTok aktif.\n\n";

$tiktokService  = app(TiktokService::class);
$statusMapping  = [
    '100' => 'UNPAID', '111' => 'READY_TO_SHIP', '112' => 'SHIPPED',
    '121' => 'SHIPPED', '122' => 'DELIVERED', '130' => 'COMPLETED', '140' => 'CANCELLED',
    'UNPAID' => 'UNPAID', 'AWAITING_SHIPMENT' => 'READY_TO_SHIP',
    'AWAITING_COLLECTION' => 'SHIPPED', 'PARTIALLY_SHIPPING' => 'SHIPPED',
    'IN_TRANSIT' => 'SHIPPED', 'DELIVERED' => 'DELIVERED',
    'COMPLETED' => 'COMPLETED', 'CANCELLED' => 'CANCELLED', 'IN_CANCEL' => 'CANCELLED',
];

$grandNew    = 0;
$grandExists = 0;
$grandError  = 0;

foreach ($stores as $store) {
    echo "======================================================================\n";
    echo "TOKO: {$store->store_name} (ID: {$store->id})\n";
    echo "======================================================================\n";

    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher  = $store->shop_cipher;
        if (empty($shopCipher)) { echo "  SKIP: shop_cipher kosong.\n\n"; continue; }

        // Siapkan akses ke processOrder via Reflection
        $jobInstance = new PullOrdersFromTiktok($store, $startTs, $endTs);
        $reflection  = new \ReflectionClass($jobInstance);
        $processMethod = $reflection->getMethod('processOrder');
        $processMethod->setAccessible(true);

        $storeNew    = 0;
        $storeExists = 0;
        $storeError  = 0;

        // ── Proses per hari ───────────────────────────────────────────
        $currentDay = $startTs;

        while ($currentDay <= $endTs) {
            $dayStart = mktime(0,  0,  0,  date('n', $currentDay), date('j', $currentDay), date('Y', $currentDay));
            $dayEnd   = mktime(23, 59, 59, date('n', $currentDay), date('j', $currentDay), date('Y', $currentDay));
            $dayLabel = date('Y-m-d', $currentDay);

            echo "  [{$dayLabel}] Menarik order... ";

            // ── STEP 1: getOrderList → simpan ID DAN data lengkapnya ──
            // Data dari list ini jadi FALLBACK jika getOrderDetail gagal
            $tiktokOrderMap = []; // order_id => data order dari search API
            $cursor     = '';
            $pageCount  = 0;
            $prevCursor = null;

            do {
                try {
                    $resp = $tiktokService->getOrderList($accessToken, $shopCipher, $dayStart, $dayEnd, $cursor);
                } catch (\Exception $e) {
                    echo "API Error: " . $e->getMessage() . "\n";
                    $storeError++;
                    goto next_day;
                }

                $orders = $resp['orders'] ?? [];
                foreach ($orders as $o) {
                    $oid = (string)($o['id'] ?? $o['order_id'] ?? null);
                    if ($oid) $tiktokOrderMap[$oid] = $o; // simpan full object
                }

                $prevCursor = $cursor;
                $cursor     = $resp['next_cursor'] ?? '';
                $hasMore    = $resp['more'] ?? false;
                if ($cursor === $prevCursor || ++$pageCount > 20) break;
                usleep(100000);

            } while ($hasMore && $cursor);

            if (empty($tiktokOrderMap)) {
                echo "0 order.\n";
                goto next_day;
            }

            $tiktokIds = array_keys($tiktokOrderMap);

            // ── STEP 2: Cek mana yang belum ada di ERP ────────────────
            $existingIds = Order::where('store_id', $store->id)
                ->whereIn('order_marketplace_id', $tiktokIds)
                ->pluck('order_marketplace_id')
                ->toArray();

            $missingIds = array_diff($tiktokIds, $existingIds);
            $totalDay   = count($tiktokIds);
            $missingCnt = count($missingIds);

            echo "TikTok={$totalDay}, ERP=" . count($existingIds) . ", ";

            $storeExists += count($existingIds);

            if ($missingCnt === 0) {
                echo "Semua sudah ada.\n";
                goto next_day;
            }

            echo "BELUM ADA={$missingCnt}";

            if ($isDryRun) {
                echo " [DRY-RUN]\n";
                $storeNew += $missingCnt;
                goto next_day;
            }

            echo "\n";

            // ── STEP 3: Coba getOrderDetail untuk data yang lebih lengkap ─
            $missingArr     = array_values($missingIds);
            $detailMap      = []; // order_id => detail data (lebih lengkap dari list)

            $chunks = array_chunk($missingArr, 50);
            foreach ($chunks as $chunk) {
                try {
                    $detailResp = $tiktokService->getOrderDetail($accessToken, $shopCipher, $chunk);
                    $detailList = $detailResp['order_list'] ?? [];

                    foreach ($detailList as $d) {
                        $did = (string)($d['order_id'] ?? $d['id'] ?? null);
                        if ($did) $detailMap[$did] = $d;
                    }
                } catch (\Exception $e) {
                    // getOrderDetail gagal → akan pakai data dari list
                }
                usleep(200000);
            }

            // ── STEP 4: Proses setiap order yang belum ada ────────────
            foreach ($missingArr as $mid) {
                // Prioritas: pakai data dari getOrderDetail (lengkap)
                // Fallback: pakai data dari getOrderList (minimal tapi cukup)
                $orderData = $detailMap[$mid] ?? $tiktokOrderMap[$mid] ?? null;

                if (!$orderData) {
                    echo "    [SKIP] {$mid}: tidak ada data\n";
                    $storeError++;
                    continue;
                }

                $dataSource = isset($detailMap[$mid]) ? 'detail' : 'list(fallback)';

                try {
                    $processMethod->invoke($jobInstance, $orderData);
                    echo "    [+] {$mid} ({$dataSource})\n";
                    $storeNew++;
                } catch (\Exception $e) {
                    echo "    [ERROR] {$mid}: " . $e->getMessage() . "\n";
                    $storeError++;
                }

                usleep(100000);
            }

            next_day:
            $currentDay = strtotime('+1 day', $currentDay);
        }

        echo "\n";
        echo "  Ringkasan [{$store->store_name}]: Ada={$storeExists} | Baru={$storeNew} | Error={$storeError}\n\n";

        $grandNew    += $storeNew;
        $grandExists += $storeExists;
        $grandError  += $storeError;

    } catch (\Exception $e) {
        echo "  ERROR toko: " . $e->getMessage() . "\n\n";
        $grandError++;
    }
}

// ── Ringkasan ──────────────────────────────────────────────────
echo "======================================================================\n";
echo "  RINGKASAN AKHIR " . ($isDryRun ? "(DRY-RUN)" : "") . "\n";
echo "======================================================================\n";
echo "  Sudah ada di ERP          : {$grandExists} order\n";
echo "  Berhasil ditambahkan      : {$grandNew} order\n";
echo "  Gagal / Error             : {$grandError} order\n";
echo "======================================================================\n";

if ($isDryRun && $grandNew > 0) {
    echo "\nAda {$grandNew} order belum masuk ERP.\n";
    echo "Jalankan tanpa --dry-run untuk menarik:\n";
    if ($fromDate && $toDate) {
        echo "  php pull_missing_tiktok_orders.php --from={$fromDate} --to={$toDate}";
    } else {
        echo "  php pull_missing_tiktok_orders.php --days={$days}";
    }
    if ($storeId) echo " --store={$storeId}";
    echo "\n";
}

echo "\nSelesai!\n\n";
