<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

$days = 90;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--days=')) {
        $days = (int) explode('=', $arg)[1];
    }
}

echo "======================================================================\n";
echo "  PULL RETUR & PENGEMBALIAN DANA KILAT (SHOPEE & TIKTOK 90 HARI)\n";
echo "======================================================================\n";
echo "  Jangkauan : {$days} hari terakhir\n";
echo "======================================================================\n\n";

$stores = Store::where('status', '!=', 'disconnected')->get();
$timeFrom = now()->subDays($days)->timestamp;
$timeTo = now()->timestamp;

$totalApiReturns = 0;
$totalErpStatusReturns = 0;

// 1. PULL DARI API SHOPEE & TIKTOK (90 HARI)
foreach ($stores as $store) {
    $channelCode = strtolower($store->channel->code ?? '');
    echo "🏬 Toko: {$store->name} ({$channelCode})\n";

    if ($channelCode === 'shopee') {
        try {
            $shopeeService = app(\App\Services\ShopeeService::class);
            try {
                $accessToken = $store->getValidAccessToken();
            } catch (\Throwable $te) {
                $accessToken = null;
            }
            $shopId = (int) ($store->marketplace_store_id ?: $store->shopee_shop_id);

            if (empty($accessToken) || empty($shopId)) {
                echo "  [SKIP] Token / Shop ID Shopee kosong.\n";
                continue;
            }

            $res = $shopeeService->getReturnList($accessToken, $shopId, 0, 100, $timeFrom, $timeTo);
            $returnList = $res['return'] ?? [];
            echo "  Ditemukan " . count($returnList) . " kasus retur di Shopee API (90 hari).\n";

            foreach ($returnList as $rItem) {
                $returnSn = $rItem['return_sn'];
                $detail = $shopeeService->getReturnDetail($accessToken, $shopId, $returnSn);

                if (empty($detail)) continue;

                $orderSn = $detail['order_sn'] ?? null;
                $order = Order::where('tenant_id', $store->tenant_id)
                    ->where('order_marketplace_id', $orderSn)
                    ->first();

                $refundAmt = (float) ($detail['refund_amount'] ?? 0);
                if ($refundAmt == 0 && $order) {
                    $refundAmt = (float) $order->total_amount;
                }

                ReturnOrder::updateOrCreate(
                    [
                        'tenant_id' => $store->tenant_id,
                        'return_sn' => $returnSn,
                    ],
                    [
                        'store_id' => $store->id,
                        'order_id' => $order ? $order->id : null,
                        'return_tracking_number' => $detail['tracking_number'] ?? null,
                        'shipping_provider' => $detail['shipping_carrier'] ?? null,
                        'reason' => $detail['reason'] ?? 'Shopee Return',
                        'status' => strtoupper($detail['status'] ?? 'COMPLETED'),
                        'refund_amount' => $refundAmt,
                        'created_at' => date('Y-m-d H:i:s', $detail['create_time'] ?? time()),
                        'updated_at' => now(),
                    ]
                );
                $totalApiReturns++;
            }
        } catch (\Throwable $e) {
            echo "  [ERROR Shopee API] " . $e->getMessage() . "\n";
        }
    } elseif ($channelCode === 'tiktok' || $channelCode === 'tokopedia') {
        try {
            $tiktokService = app(\App\Services\TiktokService::class);
            try {
                $accessToken = $store->getValidAccessToken();
            } catch (\Throwable $te) {
                $accessToken = null;
            }
            $shopCipher = $store->shop_cipher;

            if (empty($accessToken) || empty($shopCipher)) {
                echo "  [SKIP] Token / shop_cipher TikTok kosong.\n";
                continue;
            }

            // TikTok Reverse API Search
            $res = $tiktokService->getReturnList($accessToken, $shopCipher, 1, 100);
            $returnList = $res['returns'] ?? $res['return_list'] ?? [];
            echo "  Ditemukan " . count($returnList) . " kasus retur di TikTok API.\n";

            foreach ($returnList as $rItem) {
                $returnSn = $rItem['return_id'] ?? $rItem['id'] ?? null;
                $orderSn = $rItem['order_id'] ?? null;
                if (!$returnSn) continue;

                $order = Order::where('tenant_id', $store->tenant_id)
                    ->where('order_marketplace_id', $orderSn)
                    ->first();

                $refundAmt = (float) ($rItem['refund_amount']['refund_total'] ?? $rItem['refund_amount'] ?? 0);
                if ($refundAmt == 0 && $order) {
                    $refundAmt = (float) $order->total_amount;
                }

                ReturnOrder::updateOrCreate(
                    [
                        'tenant_id' => $store->tenant_id,
                        'return_sn' => (string) $returnSn,
                    ],
                    [
                        'store_id' => $store->id,
                        'order_id' => $order ? $order->id : null,
                        'reason' => $rItem['reason'] ?? 'TikTok Return',
                        'status' => strtoupper($rItem['status'] ?? 'COMPLETED'),
                        'refund_amount' => $refundAmt,
                        'created_at' => date('Y-m-d H:i:s', (int)(($rItem['create_time'] ?? time()) / (strlen((string)($rItem['create_time'] ?? time())) >= 13 ? 1000 : 1))),
                        'updated_at' => now(),
                    ]
                );
                $totalApiReturns++;
            }
        } catch (\Throwable $e) {
            echo "  [ERROR TikTok API] " . $e->getMessage() . "\n";
        }
    }
}

// 2. REKONSILIASI OTOMATIS: DARI PESANAN BERSTATUS RETURNED / REFUNDED DI ERP
echo "\n--- 2. REKONSILIASI PESANAN BERSTATUS RETURNED/REFUNDED DI ERP ---\n";
$returnedOrders = Order::whereIn('order_status', ['RETURNED', 'REFUNDED', 'RETURN', 'CANCELLED'])
    ->where('order_date', '>=', now()->subDays($days))
    ->get();

echo "Memeriksa " . $returnedOrders->count() . " pesanan berstatus retur/refund di ERP...\n";

foreach ($returnedOrders as $order) {
    if (in_array(strtoupper($order->order_status), ['RETURNED', 'REFUNDED', 'RETURN'])) {
        $exists = ReturnOrder::where('order_id', $order->id)->exists();
        if (!$exists) {
            ReturnOrder::create([
                'tenant_id'     => $order->tenant_id,
                'store_id'      => $order->store_id,
                'order_id'      => $order->id,
                'return_sn'     => 'RET-' . $order->order_marketplace_id,
                'reason'        => 'Pengembalian / Refund Pesanan (' . $order->order_status . ')',
                'status'        => 'COMPLETED',
                'refund_amount' => (float) $order->total_amount,
                'created_at'    => $order->completed_at ?? $order->order_date ?? now(),
                'updated_at'    => now(),
            ]);
            $totalErpStatusReturns++;
        }
    }
}

echo "\n======================================================================\n";
echo "  SELESAI SINKRONISASI RETUR & REFUND!\n";
echo "  - Total Kasus Retur dari API Marketplace : {$totalApiReturns}\n";
echo "  - Total Retur Terbuat dari Status Order : {$totalErpStatusReturns}\n";
echo "======================================================================\n";
