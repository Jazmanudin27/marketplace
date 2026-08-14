<?php

/**
 * ============================================================
 * CEK ORDER SPESIFIK: ERP vs TikTok API
 * ============================================================
 * Cara pakai:
 *   php check_order_tiktok.php 585052996251845745
 *   php check_order_tiktok.php 585052996251845745 --fix
 *   php check_order_tiktok.php 585052996251845745 --store=30
 *   php check_order_tiktok.php 585052996251845745 --store=30 --date=2026-07-16
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;

$args               = array_slice($argv, 1);
$orderMarketplaceId = null;
$doFix              = in_array('--fix', $args);
$filterStoreId      = null;
$hintDate           = null; // tanggal order dibuat (untuk mempercepat search)

foreach ($args as $arg) {
    if (!str_starts_with($arg, '--')) {
        $orderMarketplaceId = trim($arg);
    }
    if (str_starts_with($arg, '--store=')) $filterStoreId = (int) str_replace('--store=', '', $arg);
    if (str_starts_with($arg, '--date='))  $hintDate      = str_replace('--date=', '', $arg);
}

if (!$orderMarketplaceId) {
    echo "Penggunaan: php check_order_tiktok.php <ORDER_ID> [--fix]\n";
    echo "Contoh   : php check_order_tiktok.php 585317667425191149\n";
    echo "           php check_order_tiktok.php 585317667425191149 --fix\n";
    exit(1);
}

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

echo "\n";
echo "======================================================================\n";
echo "  CEK ORDER: {$orderMarketplaceId}\n";
echo "======================================================================\n\n";

// ── STEP 1: Cek di Database ERP ───────────────────────────────
echo "[ DATABASE ERP ]\n";
$order = Order::where('order_marketplace_id', $orderMarketplaceId)->first();

if (!$order) {
    echo "  STATUS : TIDAK DITEMUKAN di database ERP!\n\n";
} else {
    $store = $order->store;
    echo "  ERP ID         : {$order->id}\n";
    echo "  Toko           : " . ($store->store_name ?? 'N/A') . " (Store ID: {$order->store_id})\n";
    echo "  Status ERP     : {$order->order_status}\n";
    echo "  Buyer          : {$order->buyer_name}\n";
    echo "  Total Amount   : Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
    echo "  Net Amount     : Rp " . number_format($order->net_amount, 0, ',', '.') . "\n";
    echo "  Marketplace Fee: Rp " . number_format($order->marketplace_fee, 0, ',', '.') . "\n";
    echo "  Dibuat         : {$order->created_at}\n";
    echo "  Completed At   : " . ($order->completed_at ?? '-') . "\n";
    echo "  Cancel Reason  : " . ($order->cancel_reason ?? '-') . "\n";
}

echo "\n";

// ── STEP 2: Cek ke TikTok API ─────────────────────────────────
echo "[ TIKTOK API ]\n";

// Tentukan toko yang akan dicek
$storesToCheck = [];
if ($filterStoreId) {
    // --store= diberikan: langsung cari di toko itu
    $s = Store::find($filterStoreId);
    if ($s) $storesToCheck = [$s];
    echo "  (Mencari di toko: {$s->store_name} sesuai --store={$filterStoreId})\n";
} elseif ($order && $order->store) {
    // Order ada di ERP: pakai toko dari ERP
    $storesToCheck = [$order->store];
} else {
    // Cari di semua toko TikTok
    $storesToCheck = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
        ->where('status', '!=', 'disconnected')
        ->whereNotNull('access_token')
        ->get()->all();
    echo "  (Order tidak ada di ERP. Mencari di " . count($storesToCheck) . " toko...)\n";
    echo "  TIP: Gunakan --store=ID untuk langsung ke toko tertentu (lebih cepat)\n";
    if ($hintDate) echo "  Hint tanggal: {$hintDate}\n";
}

$tiktokService = app(TiktokService::class);
$tiktokFound   = false;

foreach ($storesToCheck as $store) {
    echo "  Cek toko: {$store->store_name} (ID: {$store->id})... ";
    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher  = $store->shop_cipher;

        if (empty($shopCipher)) { echo "skip (no cipher)\n"; continue; }

        // ── Coba fetch by ID ──────────────────────────────────────
        $orderList = [];
        try {
            $detailResponse = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$orderMarketplaceId]);
            $orderList      = $detailResponse['order_list'] ?? [];
        } catch (\Exception $eDetail) {
            $msg = $eDetail->getMessage();
            // "Internal error" = order bukan milik toko ini → skip ke toko berikutnya
            if (str_contains($msg, 'Internal error') || str_contains($msg, '105001')) {
                echo "bukan toko ini.\n";
                continue;
            }
            // Error lain → tetap lanjut ke fallback search
        }

        // ── Fallback: search by date range ────────────────────────
        if (empty($orderList)) {
            echo "coba via search... ";

            // Gunakan hint date jika ada (jauh lebih cepat)
            if ($hintDate) {
                $timeFrom = strtotime($hintDate . ' 00:00:00');
                $timeTo   = strtotime($hintDate . ' 23:59:59');
            } else {
                $timeFrom = strtotime('-90 days');
                $timeTo   = time();
            }

            $cursor = '';
            do {
                $listResponse = $tiktokService->getOrderList($accessToken, $shopCipher, $timeFrom, $timeTo, $cursor);
                $orders       = $listResponse['orders'] ?? [];

                foreach ($orders as $o) {
                    $oid = (string)($o['id'] ?? $o['order_id'] ?? null);
                    if ($oid === $orderMarketplaceId) {
                        $orderList[] = $o;
                        break 2;
                    }
                }

                $prevCursor = $cursor;
                $cursor     = $listResponse['next_cursor'] ?? '';
                $hasMore    = $listResponse['more'] ?? false;
                if ($cursor === $prevCursor) break;
                usleep(200000);
            } while ($hasMore && $cursor);
        }

        if (empty($orderList)) {
            echo "  Order tidak ditemukan di toko: {$store->store_name}\n";
            continue;
        }

        $tiktokFound   = true;
        $tiktokOrder   = $orderList[0];
        $rawStatus     = strtoupper((string)($tiktokOrder['order_status'] ?? $tiktokOrder['status'] ?? 'UNKNOWN'));
        $tiktokStatus  = $statusMapping[$rawStatus] ?? $rawStatus;

        echo "  Ditemukan di toko : {$store->store_name} (ID: {$store->id})\n";
        echo "  Status TikTok     : {$tiktokStatus} (raw: {$rawStatus})\n";
        echo "  Buyer             : " . ($tiktokOrder['recipient_address']['name'] ?? $tiktokOrder['buyer_username'] ?? '-') . "\n";
        echo "  Create Time       : " . (isset($tiktokOrder['create_time']) ? date('Y-m-d H:i:s', (int)$tiktokOrder['create_time']) : '-') . "\n";
        echo "  Update Time       : " . (isset($tiktokOrder['update_time']) ? date('Y-m-d H:i:s', (int)$tiktokOrder['update_time']) : '-') . "\n";
        echo "  Cancel Reason     : " . ($tiktokOrder['cancel_reason'] ?? $tiktokOrder['cancellation_reason'] ?? '-') . "\n";

        echo "\n";

        // ── STEP 3: Bandingkan & fix ──────────────────────────────────
        if ($order) {
            $erpStatus = $order->order_status;
            echo "[ PERBANDINGAN ]\n";

            if ($erpStatus === $tiktokStatus) {
                echo "  Status SINKRON: ERP={$erpStatus} == TikTok={$tiktokStatus}\n";
            } else {
                echo "  Status TIDAK SINKRON!\n";
                echo "  ERP     : {$erpStatus}\n";
                echo "  TikTok  : {$tiktokStatus}\n";

                if ($doFix) {
                    $updateData = ['order_status' => $tiktokStatus];

                    if (in_array($tiktokStatus, ['COMPLETED', 'DELIVERED'])) {
                        $deliveryTs = $tiktokOrder['delivery_time']
                            ?? $tiktokOrder['update_time']
                            ?? time();
                        if (is_numeric($deliveryTs) && strlen((string)$deliveryTs) >= 13) {
                            $deliveryTs = (int)($deliveryTs / 1000);
                        }
                        $updateData['completed_at'] = date('Y-m-d H:i:s', (int)$deliveryTs);
                    }

                    if ($tiktokStatus === 'CANCELLED') {
                        $cr = $tiktokOrder['cancel_reason'] ?? $tiktokOrder['cancellation_reason'] ?? null;
                        $cb = $tiktokOrder['cancel_user']   ?? $tiktokOrder['cancel_by'] ?? null;
                        if ($cr) $updateData['cancel_reason'] = $cr;
                        if ($cb) $updateData['cancelled_by']  = $cb;
                    }

                    Order::where('id', $order->id)->update($updateData);
                    echo "\n  DIPERBAIKI: Status ERP diupdate dari {$erpStatus} -> {$tiktokStatus}\n";
                } else {
                    echo "\n  Jalankan dengan --fix untuk memperbaiki:\n";
                    echo "  php check_order_tiktok.php {$orderMarketplaceId} --fix\n";
                }
            }
        }

        break; // Sudah ketemu, stop cek toko lain
    } catch (\Exception $e) {
        echo "  ERROR di toko {$store->store_name}: " . $e->getMessage() . "\n";
    }
}

if (!$tiktokFound) {
    echo "  Order TIDAK DITEMUKAN di TikTok API.\n";
    echo "  (Kemungkinan: order > 90 hari atau sudah dihapus dari sistem TikTok)\n";
}

echo "\n======================================================================\n\n";
