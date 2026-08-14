<?php

/**
 * ============================================================
 * RESYNC STATUS ORDER TIKTOK → ERP
 * ============================================================
 * Script ini mencari order yang statusnya tidak sinkron antara
 * ERP dan TikTok Marketplace, lalu memperbarui status ERP
 * sesuai status terbaru dari TikTok API.
 *
 * Cara pakai:
 *   php resync_tiktok_status.php              → semua toko TikTok
 *   php resync_tiktok_status.php --store=43   → hanya store_id tertentu
 *   php resync_tiktok_status.php --dry-run    → preview saja, tidak update
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
$storeId   = null;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--store=')) {
        $storeId = (int) str_replace('--store=', '', $arg);
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

// ── Banner ─────────────────────────────────────────────────────
echo "\n";
echo "======================================================================\n";
echo "       RESYNC STATUS ORDER TIKTOK SHOP -> ERP\n";
echo "======================================================================\n";
echo ($isDryRun ? "PERINGATAN: MODE DRY-RUN: Tidak ada perubahan yang disimpan.\n" : "INFO: MODE LIVE: Perubahan akan disimpan ke database.\n");
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

$totalFound   = 0;
$totalUpdated = 0;
$totalSkipped = 0;
$totalError   = 0;

foreach ($stores as $store) {
    echo "--------------------------------------------------------------------\n";
    echo "TOKO: {$store->store_name} (ID: {$store->id})\n";
    echo "--------------------------------------------------------------------\n";

    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher  = $store->shop_cipher;

        if (empty($shopCipher)) {
            echo "  PERINGATAN: shop_cipher kosong, skip toko ini.\n\n";
            continue;
        }

        // Ambil order dari ERP yang kemungkinan tidak sinkron:
        // - CANCELLED di ERP -> mungkin COMPLETED di TikTok
        // - READY_TO_SHIP, SHIPPED, DELIVERED -> mungkin sudah berubah
        // - UNPAID -> mungkin sudah dibayar/dibatalkan
        $erpOrders = Order::where('store_id', $store->id)
            ->whereIn('order_status', [
                'CANCELLED',
                'READY_TO_SHIP',
                'SHIPPED',
                'DELIVERED',
                'UNPAID',
            ])
            ->whereNotNull('order_marketplace_id')
            ->orderByDesc('created_at')
            ->get(['id', 'order_marketplace_id', 'order_status', 'created_at']);

        if ($erpOrders->isEmpty()) {
            echo "  OK: Tidak ada order yang perlu dicek untuk toko ini.\n\n";
            continue;
        }

        echo "  INFO: Ditemukan " . $erpOrders->count() . " order ERP yang akan dicek ke TikTok API...\n";
        $totalFound += $erpOrders->count();

        // Chunk per 50 (limit TikTok API)
        $chunks = $erpOrders->chunk(50);

        foreach ($chunks as $chunk) {
            $orderIds = $chunk->pluck('order_marketplace_id')->toArray();

            try {
                // Fetch status terbaru dari TikTok API
                $detailResponse = $tiktokService->getOrderDetail($accessToken, $shopCipher, $orderIds);
                $orderList      = $detailResponse['order_list'] ?? [];

                if (empty($orderList)) {
                    echo "  PERINGATAN: TikTok API tidak mengembalikan data untuk batch ini.\n";
                    $totalSkipped += count($orderIds);
                    continue;
                }

                // Buat map: order_marketplace_id => data TikTok
                $tiktokMap = [];
                foreach ($orderList as $tiktokOrder) {
                    $tid = $tiktokOrder['order_id'] ?? $tiktokOrder['id'] ?? null;
                    if ($tid) {
                        $tiktokMap[(string)$tid] = $tiktokOrder;
                    }
                }

                // Bandingkan status dan update jika berbeda
                foreach ($chunk as $erpOrder) {
                    $mid          = (string) $erpOrder->order_marketplace_id;
                    $tiktokOrder  = $tiktokMap[$mid] ?? null;

                    if (!$tiktokOrder) {
                        echo "  SKIP [{$mid}]: Tidak ditemukan di TikTok API (mungkin order lama/terhapus).\n";
                        $totalSkipped++;
                        continue;
                    }

                    $rawStatus    = strtoupper((string) ($tiktokOrder['order_status'] ?? $tiktokOrder['status'] ?? 'UNKNOWN'));
                    $tiktokStatus = $statusMapping[$rawStatus] ?? $rawStatus;
                    $erpStatus    = $erpOrder->order_status;

                    if ($erpStatus === $tiktokStatus) {
                        $totalSkipped++;
                        continue; // Sudah sinkron
                    }

                    // ── Status berbeda → perlu update ─────────────────────
                    echo "  TIDAK SINKRON [{$mid}]: ERP={$erpStatus} -> TikTok={$tiktokStatus}";

                    if ($isDryRun) {
                        echo " [DRY-RUN]\n";
                        $totalUpdated++;
                        continue;
                    }

                    // Update status di ERP
                    $updateData = ['order_status' => $tiktokStatus];

                    // Jika sekarang COMPLETED, set completed_at
                    if (in_array($tiktokStatus, ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])) {
                        $deliveryTs = $tiktokOrder['delivery_time']
                            ?? $tiktokOrder['update_time']
                            ?? $tiktokOrder['paid_time']
                            ?? time();

                        if (is_numeric($deliveryTs) && strlen((string)$deliveryTs) >= 13) {
                            $deliveryTs = (int)($deliveryTs / 1000);
                        }

                        $updateData['completed_at'] = date('Y-m-d H:i:s', (int)$deliveryTs);
                    }

                    // Jika sekarang CANCELLED, simpan alasan pembatalan
                    if ($tiktokStatus === 'CANCELLED') {
                        $cancelReason = $tiktokOrder['cancel_reason'] ?? $tiktokOrder['cancellation_reason'] ?? null;
                        $cancelledBy  = $tiktokOrder['cancel_user'] ?? $tiktokOrder['cancel_by'] ?? null;
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

            } catch (\Exception $e) {
                echo "  ERROR saat fetch batch dari TikTok API: " . $e->getMessage() . "\n";
                $totalError += count($orderIds);
            }

            usleep(300000); // Jeda 300ms agar tidak rate-limit
        }

        echo "\n";

    } catch (\Exception $e) {
        echo "  ERROR memproses toko {$store->store_name}: " . $e->getMessage() . "\n\n";
        $totalError++;
    }
}

// ── Ringkasan Akhir ─────────────────────────────────────────────
echo "======================================================================\n";
echo "  RINGKASAN HASIL\n";
echo "======================================================================\n";
echo "  Total order dicek        : {$totalFound}\n";
echo "  Diupdate (tidak sinkron) : {$totalUpdated}\n";
echo "  Dilewati (sudah sinkron) : {$totalSkipped}\n";
echo "  Error                    : {$totalError}\n";
echo "======================================================================\n";

if ($isDryRun) {
    echo "\nINFO: Ini adalah DRY-RUN. Jalankan tanpa --dry-run untuk menyimpan perubahan.\n";
}

echo "\nSelesai!\n\n";
