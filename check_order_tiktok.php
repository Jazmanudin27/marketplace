<?php

/**
 * ============================================================
 * CEK ORDER SPESIFIK: ERP vs TikTok API
 * ============================================================
 * Cara pakai:
 *   php check_order_tiktok.php 585317667425191149
 *   php check_order_tiktok.php 585317667425191149 --fix   (langsung update ERP)
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;

$args              = array_slice($argv, 1);
$orderMarketplaceId = null;
$doFix             = in_array('--fix', $args);

foreach ($args as $arg) {
    if (!str_starts_with($arg, '--')) {
        $orderMarketplaceId = trim($arg);
    }
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

// Tentukan toko: pakai toko dari order ERP, atau cari semua toko TikTok
$storesToCheck = [];
if ($order && $order->store) {
    $storesToCheck = [$order->store];
} else {
    $storesToCheck = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
        ->where('status', '!=', 'disconnected')
        ->whereNotNull('access_token')
        ->get()
        ->all();
    echo "  (Order tidak ada di ERP, mencari di semua " . count($storesToCheck) . " toko TikTok...)\n";
}

$tiktokService = app(TiktokService::class);
$tiktokFound   = false;

foreach ($storesToCheck as $store) {
    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher  = $store->shop_cipher;

        if (empty($shopCipher)) continue;

        // Fetch detail order langsung by ID
        $detailResponse = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$orderMarketplaceId]);
        $orderList      = $detailResponse['order_list'] ?? [];

        // Jika fetch-by-ID tidak mengembalikan hasil (order lama),
        // coba dengan getOrderList search di 90 hari terakhir
        if (empty($orderList)) {
            echo "  Fetch by ID tidak ada hasil, mencoba via search (90 hari)...\n";

            $timeFrom = strtotime('-90 days');
            $timeTo   = time();
            $cursor   = '';

            do {
                $listResponse = $tiktokService->getOrderList($accessToken, $shopCipher, $timeFrom, $timeTo, $cursor);
                $orders       = $listResponse['orders'] ?? [];

                foreach ($orders as $o) {
                    $oid = (string)($o['id'] ?? $o['order_id'] ?? null);
                    if ($oid === $orderMarketplaceId) {
                        $orderList[] = $o;
                        break 2; // Ketemu, stop loop
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
