<?php

/**
 * ============================================================
 * RESYNC STATUS + TGL LEPAS (completed_at) TIKTOK -> ERP
 * ============================================================
 * Masalah yang diselesaikan:
 * 1. Status order tidak sinkron (ERP Cancel, TikTok Complete)
 * 2. completed_at null/salah → order tidak muncul di laporan
 *    tanggal yang benar sesuai pencairan TikTok Seller Center
 *
 * Cara pakai:
 *   php resync_tiktok_status.php                     -> 30 hari, semua toko
 *   php resync_tiktok_status.php --days=90           -> 90 hari (max TikTok)
 *   php resync_tiktok_status.php --store=17          -> hanya 1 toko
 *   php resync_tiktok_status.php --dry-run           -> preview, tidak disimpan
 *   php resync_tiktok_status.php --debug             -> tampilkan detail API
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
$args     = array_slice($argv, 1);
$isDryRun = in_array('--dry-run', $args);
$isDebug  = in_array('--debug', $args);
$storeId  = null;
$days     = 30;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--store=')) $storeId = (int) str_replace('--store=', '', $arg);
    if (str_starts_with($arg, '--days='))  $days = max(1, min(90, (int) str_replace('--days=', '', $arg)));
}

// ── Status Mapping ─────────────────────────────────────────────
$statusMapping = [
    '100' => 'UNPAID',       '111' => 'READY_TO_SHIP', '112' => 'SHIPPED',
    '121' => 'SHIPPED',      '122' => 'DELIVERED',      '130' => 'COMPLETED',
    '140' => 'CANCELLED',
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
echo "  RESYNC STATUS + TGL LEPAS (completed_at) TIKTOK -> ERP\n";
echo "======================================================================\n";
echo "  Mode     : " . ($isDryRun ? "DRY-RUN (preview saja, tidak ada yang disimpan)" : "LIVE (perubahan disimpan ke DB)") . "\n";
echo "  Rentang  : " . date('d-m-Y', $timeFrom) . " s/d " . date('d-m-Y', $timeTo) . " ({$days} hari)\n";
echo "  Toko     : " . ($storeId ? "Store ID #{$storeId}" : "Semua toko TikTok") . "\n";
echo "======================================================================\n\n";

// ── Ambil toko TikTok aktif ───────────────────────────────────
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

// ── Counter ────────────────────────────────────────────────────
$totalCompared      = 0;
$fixedStatus        = 0;
$fixedCompletedAt   = 0;
$fixedBoth          = 0;
$alreadySynced      = 0;
$totalError         = 0;

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

        // ── STEP 1: Tarik semua order dari TikTok (by date range) ─────
        echo "  Mengambil data dari TikTok API...\n";

        $tiktokMap  = []; // order_marketplace_id => data lengkap
        $cursor     = '';
        $pageCount  = 0;
        $prevCursor = null;

        do {
            $response = $tiktokService->getOrderList($accessToken, $shopCipher, $timeFrom, $timeTo, $cursor);
            $orders   = $response['orders'] ?? [];

            if ($isDebug) {
                echo "  [DEBUG] Halaman " . ($pageCount + 1) . ": " . count($orders) . " order\n";
            }

            foreach ($orders as $o) {
                $oid = (string)($o['id'] ?? $o['order_id'] ?? null);
                if ($oid) {
                    $rawStatus          = strtoupper((string)($o['order_status'] ?? $o['status'] ?? 'UNKNOWN'));
                    $tiktokMap[$oid] = [
                        'status'    => $statusMapping[$rawStatus] ?? $rawStatus,
                        'raw'       => $o,
                    ];
                }
            }

            $prevCursor = $cursor;
            $cursor     = $response['next_cursor'] ?? '';
            $hasMore    = $response['more'] ?? false;

            if ($cursor === $prevCursor || ++$pageCount > 20) break;
            usleep(200000);

        } while ($hasMore && $cursor);

        $tiktokCount = count($tiktokMap);
        echo "  TikTok API: {$tiktokCount} order ditemukan.\n";

        if ($tiktokCount === 0) {
            echo "  INFO: Tidak ada data TikTok untuk periode ini.\n\n";
            continue;
        }

        // ── STEP 2: Fetch detail untuk order yang ada di TikTok ───────
        // Diperlukan untuk mendapat update_time / delivery_time yang akurat
        echo "  Mengambil detail order untuk mendapat tanggal lepas yang akurat...\n";

        $tiktokIds     = array_keys($tiktokMap);
        $detailChunks  = array_chunk($tiktokIds, 50);

        foreach ($detailChunks as $chunk) {
            try {
                $detailResp = $tiktokService->getOrderDetail($accessToken, $shopCipher, $chunk);
                $detailList = $detailResp['order_list'] ?? [];

                foreach ($detailList as $detail) {
                    $did = (string)($detail['order_id'] ?? $detail['id'] ?? null);
                    if ($did && isset($tiktokMap[$did])) {
                        // Merge data detail ke map (lebih lengkap dari list)
                        $tiktokMap[$did]['raw'] = array_merge($tiktokMap[$did]['raw'], $detail);

                        // Timpa status dari detail (lebih akurat)
                        $rawDetail = strtoupper((string)($detail['order_status'] ?? $detail['status'] ?? $tiktokMap[$did]['status']));
                        $tiktokMap[$did]['status'] = $statusMapping[$rawDetail] ?? $rawDetail;
                    }
                }
            } catch (\Exception $e) {
                if ($isDebug) echo "  [DEBUG] getOrderDetail error: " . $e->getMessage() . "\n";
                // Tetap lanjut, gunakan data dari getOrderList
            }
            usleep(200000);
        }

        // ── STEP 3: Bandingkan dengan ERP dan fix ─────────────────────
        $erpOrders = Order::where('store_id', $store->id)
            ->whereIn('order_marketplace_id', $tiktokIds)
            ->get(['id', 'order_marketplace_id', 'order_status', 'completed_at', 'created_at']);

        echo "  ERP: " . $erpOrders->count() . " order cocok dengan data TikTok.\n";

        $storeFixStatus       = 0;
        $storeFixCompletedAt  = 0;

        foreach ($erpOrders as $erpOrder) {
            $mid         = (string)$erpOrder->order_marketplace_id;
            $tiktokEntry = $tiktokMap[$mid] ?? null;
            if (!$tiktokEntry) continue;

            $totalCompared++;
            $tiktokStatus = $tiktokEntry['status'];
            $tiktokRaw    = $tiktokEntry['raw'];
            $erpStatus    = $erpOrder->order_status;

            // ── Hitung completed_at yang benar dari TikTok ────────────
            $correctCompletedAt = null;
            if (in_array($tiktokStatus, ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])) {
                // Prioritas: delivery_time > update_time > paid_time
                $ts = $tiktokRaw['delivery_time']
                   ?? $tiktokRaw['update_time']
                   ?? $tiktokRaw['paid_time']
                   ?? null;

                if ($ts && is_numeric($ts)) {
                    if (strlen((string)$ts) >= 13) $ts = (int)($ts / 1000);
                    $correctCompletedAt = date('Y-m-d H:i:s', (int)$ts);
                }
            }

            // ── Deteksi apa yang perlu difix ──────────────────────────
            $needFixStatus      = ($erpStatus !== $tiktokStatus);
            $needFixCompletedAt = false;

            if ($correctCompletedAt) {
                $erpCompletedAt = $erpOrder->completed_at
                    ? (is_string($erpOrder->completed_at)
                        ? $erpOrder->completed_at
                        : $erpOrder->completed_at->format('Y-m-d H:i:s'))
                    : null;

                // Fix jika: completed_at null, atau beda tanggal dengan TikTok
                $needFixCompletedAt = (
                    is_null($erpCompletedAt) ||
                    substr($erpCompletedAt, 0, 10) !== substr($correctCompletedAt, 0, 10)
                );
            }

            if (!$needFixStatus && !$needFixCompletedAt) {
                $alreadySynced++;
                continue; // Sudah sinkron sempurna
            }

            // ── Tampilkan masalah ──────────────────────────────────────
            if ($needFixStatus && $needFixCompletedAt) {
                echo "  [{$mid}] STATUS + TGL LEPAS tidak sinkron:\n";
                echo "    Status       : ERP={$erpStatus} -> TikTok={$tiktokStatus}\n";
                echo "    Tgl Lepas    : ERP=" . ($erpOrder->completed_at ?? 'NULL') . " -> TikTok={$correctCompletedAt}\n";
            } elseif ($needFixStatus) {
                echo "  [{$mid}] STATUS tidak sinkron: ERP={$erpStatus} -> TikTok={$tiktokStatus}\n";
            } elseif ($needFixCompletedAt) {
                echo "  [{$mid}] TGL LEPAS tidak sinkron: ERP=" . ($erpOrder->completed_at ?? 'NULL') . " -> TikTok={$correctCompletedAt}\n";
            }

            if ($isDryRun) {
                echo "    [DRY-RUN] Tidak disimpan.\n";
                if ($needFixStatus) $fixedStatus++;
                if ($needFixCompletedAt) $fixedCompletedAt++;
                if ($needFixStatus && $needFixCompletedAt) $fixedBoth++;
                $storeFixStatus      += ($needFixStatus ? 1 : 0);
                $storeFixCompletedAt += ($needFixCompletedAt ? 1 : 0);
                continue;
            }

            // ── Buat update data ───────────────────────────────────────
            $updateData = [];

            if ($needFixStatus) {
                $updateData['order_status'] = $tiktokStatus;

                // Jika jadi CANCELLED, simpan alasan
                if ($tiktokStatus === 'CANCELLED') {
                    $cr = $tiktokRaw['cancel_reason'] ?? $tiktokRaw['cancellation_reason'] ?? null;
                    $cb = $tiktokRaw['cancel_user']   ?? $tiktokRaw['cancel_by'] ?? null;
                    if ($cr) $updateData['cancel_reason'] = $cr;
                    if ($cb) $updateData['cancelled_by']  = $cb;
                }
            }

            if ($needFixCompletedAt && $correctCompletedAt) {
                $updateData['completed_at'] = $correctCompletedAt;
            }

            // Jika status jadi COMPLETED tapi completed_at belum di-set
            if ($needFixStatus && in_array($tiktokStatus, ['COMPLETED', 'DELIVERED']) && !isset($updateData['completed_at'])) {
                $updateData['completed_at'] = $correctCompletedAt ?? now()->toDateTimeString();
            }

            Order::where('id', $erpOrder->id)->update($updateData);
            echo "    -> BERHASIL DIUPDATE\n";

            Log::info('[ResyncTiktok] Order diperbarui', [
                'order_id'             => $erpOrder->id,
                'order_marketplace_id' => $mid,
                'fix_status'           => $needFixStatus,
                'fix_completed_at'     => $needFixCompletedAt,
                'old_status'           => $erpStatus,
                'new_status'           => $tiktokStatus,
                'new_completed_at'     => $correctCompletedAt,
            ]);

            if ($needFixStatus) { $fixedStatus++; $storeFixStatus++; }
            if ($needFixCompletedAt) { $fixedCompletedAt++; $storeFixCompletedAt++; }
            if ($needFixStatus && $needFixCompletedAt) $fixedBoth++;
        }

        echo "  Hasil toko ini: {$storeFixStatus} status difix, {$storeFixCompletedAt} tgl lepas difix.\n\n";

    } catch (\Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n\n";
        $totalError++;
    }
}

// ── Ringkasan ──────────────────────────────────────────────────
echo "======================================================================\n";
echo "  RINGKASAN HASIL " . ($isDryRun ? "(DRY-RUN)" : "") . "\n";
echo "======================================================================\n";
echo "  Total order dibandingkan     : {$totalCompared}\n";
echo "  Sudah sinkron (tidak diubah) : {$alreadySynced}\n";
echo "  Status difix                 : {$fixedStatus}\n";
echo "  Tgl Lepas (completed_at) difix: {$fixedCompletedAt}\n";
echo "  Keduanya difix               : {$fixedBoth}\n";
echo "  Error                        : {$totalError}\n";
echo "======================================================================\n";

if ($isDryRun && ($fixedStatus > 0 || $fixedCompletedAt > 0)) {
    echo "\nJalankan tanpa --dry-run untuk menyimpan perubahan:\n";
    $cmd = "php resync_tiktok_status.php --days={$days}";
    if ($storeId) $cmd .= " --store={$storeId}";
    echo "  {$cmd}\n";
}

echo "\nSelesai!\n\n";
