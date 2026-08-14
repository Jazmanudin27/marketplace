<?php

/**
 * ============================================================
 * RESYNC STATUS ORDER TIKTOK -> ERP
 * ============================================================
 * Script ini mencari order yang statusnya tidak sinkron antara
 * ERP dan TikTok Marketplace, menggunakan getOrderList (by date)
 * agar tidak terbatas oleh limitasi TikTok API on fetch-by-ID.
 *
 * Cara pakai:
 *   php resync_tiktok_status.php                    -> 30 hari terakhir, semua toko
 *   php resync_tiktok_status.php --days=7           -> 7 hari terakhir
 *   php resync_tiktok_status.php --store=17         -> hanya store_id tertentu
 *   php resync_tiktok_status.php --dry-run          -> preview saja, tidak update
 *   php resync_tiktok_status.php --debug            -> tampilkan raw API response
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;
use Illuminate\Support\Facades\Log;

// ── Parse argumen CLI ─────────────────────────────────────────
$args      = array_slice($argv, 1);
$isDryRun  = in_array('--dry-run', $args);
$isDebug   = in_array('--debug', $args);
$storeId   = null;
$days      = 30; // Default 30 hari

foreach ($args as $arg) {
    if (str_starts_with($arg, '--store=')) {
        $storeId = (int) str_replace('--store=', '', $arg);
    }
    if (str_starts_with($arg, '--days=')) {
        $days = (int) str_replace('--days=', '', $arg);
        $days = max(1, min(90, $days)); // Clamp 1-90 hari
    }
}

// ── Status Mapping (sama dengan PullOrdersFromTiktok) ─────────
$statusMapping = [
    '100'                 => 'UNPAID',
    '111'                 => 'READY_TO_SHIP',
    '112'                 => 'SHIPPED',
    '121'                 => 'SHIPPED',
    '122'                 => 'DELIVERED',
    '130'                 => 'COMPLETED',
    '140'                 => 'CANCELLED',
    'UNPAID'              => 'UNPAID',
    'AWAITING_SHIPMENT'   => 'READY_TO_SHIP',
    'AWAITING_COLLECTION' => 'SHIPPED',
    'PARTIALLY_SHIPPING'  => 'SHIPPED',
    'IN_TRANSIT'          => 'SHIPPED',
    'DELIVERED'           => 'DELIVERED',
    'COMPLETED'           => 'COMPLETED',
    'CANCELLED'           => 'CANCELLED',
    'IN_CANCEL'           => 'CANCELLED',
];

$timeFrom = strtotime("-{$days} days");
$timeTo   = time();

// ── Banner ─────────────────────────────────────────────────────
echo "\n";
echo "======================================================================\n";
echo "       RESYNC STATUS ORDER TIKTOK SHOP -> ERP\n";
echo "======================================================================\n";
echo ($isDryRun ? "MODE: DRY-RUN (preview saja, tidak ada yang disimpan)\n" : "MODE: LIVE (perubahan akan disimpan ke database)\n");
echo "Rentang: " . date('d-m-Y', $timeFrom) . " s/d " . date('d-m-Y', $timeTo) . " ({$days} hari)\n";
echo ($isDebug ? "DEBUG: ON\n" : "");
echo "\n";

// ── Ambil toko TikTok yang aktif ──────────────────────────────
$storeQuery = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token');

if ($storeId) {
    $storeQuery->where('id', $storeId);
}

$stores = $storeQuery->get();

if ($stores->isEmpty()) {
    echo "ERROR: Tidak ada toko TikTok aktif yang ditemukan.\n";
    exit(1);
}

echo "OK: Ditemukan " . $stores->count() . " toko TikTok aktif.\n\n";

$tiktokService = app(TiktokService::class);

$totalCompared = 0;
$totalUpdated  = 0;
$totalSkipped  = 0;
$totalError    = 0;

foreach ($stores as $store) {
    echo "--------------------------------------------------------------------\n";
    echo "TOKO: {$store->store_name} (ID: {$store->id})\n";
    echo "--------------------------------------------------------------------\n";

    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher  = $store->shop_cipher;

        if (empty($shopCipher)) {
            echo "  SKIP: shop_cipher kosong.\n\n";
            continue;
        }

        // ── STEP 1: Ambil semua order dari TikTok API (by date range) ─
        echo "  Mengambil order dari TikTok API ({$days} hari terakhir)...\n";

        $tiktokOrderMap = []; // order_marketplace_id => ['status' => ..., 'raw' => ...]
        $cursor     = '';
        $pageCount  = 0;
        $prevCursor = null;

        do {
            $response = $tiktokService->getOrderList(
                $accessToken,
                $shopCipher,
                $timeFrom,
                $timeTo,
                $cursor
            );

            if ($isDebug) {
                echo "  [DEBUG] getOrderList response keys: " . json_encode(array_keys($response)) . "\n";
                echo "  [DEBUG] orders count: " . count($response['orders'] ?? []) . "\n";
            }

            $orders = $response['orders'] ?? [];

            foreach ($orders as $o) {
                $oid = (string) ($o['id'] ?? $o['order_id'] ?? null);
                if ($oid) {
                    $rawStatus    = strtoupper((string) ($o['order_status'] ?? $o['status'] ?? 'UNKNOWN'));
                    $mappedStatus = $statusMapping[$rawStatus] ?? $rawStatus;
                    $tiktokOrderMap[$oid] = [
                        'status' => $mappedStatus,
                        'raw'    => $o,
                    ];
                }
            }

            $prevCursor = $cursor;
            $cursor     = $response['next_cursor'] ?? '';
            $hasMore    = $response['more'] ?? false;

            if ($cursor === $prevCursor || ++$pageCount > 20) break;

            usleep(200000); // Jeda 200ms antar halaman

        } while ($hasMore && $cursor);

        $tiktokCount = count($tiktokOrderMap);
        echo "  TikTok API mengembalikan {$tiktokCount} order (dari {$days} hari terakhir).\n";

        if ($tiktokCount === 0) {
            echo "  INFO: Tidak ada order dari TikTok untuk periode ini. Skip.\n\n";
            continue;
        }

        // ── STEP 2: Bandingkan dengan data ERP ────────────────────────
        $tiktokIds = array_keys($tiktokOrderMap);

        // Ambil order ERP yang order_marketplace_id-nya ada di hasil TikTok
        $erpOrders = Order::where('store_id', $store->id)
            ->whereIn('order_marketplace_id', $tiktokIds)
            ->get(['id', 'order_marketplace_id', 'order_status']);

        echo "  ERP memiliki " . $erpOrders->count() . " order yang cocok dengan data TikTok.\n";

        $notSyncCount = 0;

        foreach ($erpOrders as $erpOrder) {
            $mid          = (string) $erpOrder->order_marketplace_id;
            $tiktokEntry  = $tiktokOrderMap[$mid] ?? null;

            if (!$tiktokEntry) {
                $totalSkipped++;
                continue;
            }

            $tiktokStatus = $tiktokEntry['status'];
            $erpStatus    = $erpOrder->order_status;
            $totalCompared++;

            if ($erpStatus === $tiktokStatus) {
                $totalSkipped++;
                continue; // Sudah sinkron
            }

            // ── Status berbeda → tidak sinkron ────────────────────────
            $notSyncCount++;
            echo "  TIDAK SINKRON [{$mid}]: ERP={$erpStatus} -> TikTok={$tiktokStatus}";

            if ($isDryRun) {
                echo " [DRY-RUN]\n";
                $totalUpdated++;
                continue;
            }

            // Update status di ERP
            $tiktokRaw  = $tiktokEntry['raw'];
            $updateData = ['order_status' => $tiktokStatus];

            if (in_array($tiktokStatus, ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])) {
                $deliveryTs = $tiktokRaw['delivery_time']
                    ?? $tiktokRaw['update_time']
                    ?? $tiktokRaw['paid_time']
                    ?? time();

                if (is_numeric($deliveryTs) && strlen((string)$deliveryTs) >= 13) {
                    $deliveryTs = (int)($deliveryTs / 1000);
                }
                $updateData['completed_at'] = date('Y-m-d H:i:s', (int)$deliveryTs);
            }

            if ($tiktokStatus === 'CANCELLED') {
                $cancelReason = $tiktokRaw['cancel_reason'] ?? $tiktokRaw['cancellation_reason'] ?? null;
                $cancelledBy  = $tiktokRaw['cancel_user']   ?? $tiktokRaw['cancel_by']           ?? null;
                if ($cancelReason) $updateData['cancel_reason'] = $cancelReason;
                if ($cancelledBy)  $updateData['cancelled_by']  = $cancelledBy;
            }

            Order::where('id', $erpOrder->id)->update($updateData);

            echo " -> BERHASIL DIUPDATE\n";

            Log::info('[ResyncTiktok] Status diperbarui', [
                'order_id'             => $erpOrder->id,
                'order_marketplace_id' => $mid,
                'old_status'           => $erpStatus,
                'new_status'           => $tiktokStatus,
            ]);

            $totalUpdated++;
        }

        if ($notSyncCount === 0) {
            echo "  Semua order untuk toko ini sudah sinkron.\n";
        }

        echo "\n";

    } catch (\Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n\n";
        $totalError++;
    }
}

// ── Ringkasan Akhir ─────────────────────────────────────────────
echo "======================================================================\n";
echo "  RINGKASAN HASIL\n";
echo "======================================================================\n";
echo "  Total order dibandingkan  : {$totalCompared}\n";
echo "  Tidak sinkron (diupdate)  : {$totalUpdated}\n";
echo "  Sudah sinkron (dilewati)  : {$totalSkipped}\n";
echo "  Error                     : {$totalError}\n";
echo "======================================================================\n";

if ($isDryRun && $totalUpdated > 0) {
    echo "\nAda {$totalUpdated} order tidak sinkron.\n";
    echo "Jalankan tanpa --dry-run untuk memperbaikinya:\n";
    echo "  php resync_tiktok_status.php" . ($storeId ? " --store={$storeId}" : "") . " --days={$days}\n";
} elseif ($isDryRun) {
    echo "\nSemua order sudah sinkron!\n";
}

echo "\nSelesai!\n\n";
