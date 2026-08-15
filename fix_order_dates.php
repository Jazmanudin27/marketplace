<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

$isFix = in_array('--fix', $argv);
$days = 90;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--days=')) {
        $days = (int) explode('=', $arg)[1];
    }
}

echo "======================================================================\n";
echo "  PERBAIKAN TANGGAL ORDER & TANGGAL DILEPAS/DITERIMA (ERP MARKETPLACE)\n";
echo "======================================================================\n";
echo "  Jangkauan : {$days} hari terakhir\n";
echo "  Mode      : " . ($isFix ? "LIVE FIX (Update Tanggal Order & Tanggal Dilepas)" : "DRY-RUN (Deteksi Saja)") . "\n";
echo "======================================================================\n\n";

$stores = Store::where('status', '!=', 'disconnected')->get();
$totalFixed = 0;

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
                echo "  [SKIP] Token Shopee / Shop ID kosong.\n";
                continue;
            }

            $orders = Order::where('store_id', $store->id)
                ->where('order_date', '>=', now()->subDays($days))
                ->get();

            echo "  Memeriksa " . $orders->count() . " pesanan Shopee...\n";

            $orderChunks = $orders->chunk(50);
            foreach ($orderChunks as $chunk) {
                $snList = $chunk->pluck('order_marketplace_id')->filter()->toArray();
                if (empty($snList)) continue;

                $res = $shopeeService->getOrderDetail($accessToken, $shopId, $snList);
                $shopeeOrders = collect($res['order_list'] ?? [])->keyBy('order_sn');

                foreach ($chunk as $order) {
                    $shopeeOrder = $shopeeOrders->get($order->order_marketplace_id);
                    if (!$shopeeOrder) continue;

                    // 1. Exact Order Date (create_time)
                    $createTs = $shopeeOrder['create_time'] ?? null;
                    $exactOrderDate = $createTs ? date('Y-m-d H:i:s', $createTs) : null;

                    // 2. Exact Completed / Released Date
                    $status = strtoupper($shopeeOrder['order_status'] ?? $order->order_status);
                    $isCompleted = in_array($status, ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED']);
                    
                    $completedTs = null;
                    if ($isCompleted) {
                        $completedTs = $shopeeOrder['pickup_done_time'] 
                            ?? $shopeeOrder['pay_time']
                            ?? $shopeeOrder['update_time']
                            ?? $createTs;
                    }

                    $exactCompletedAt = $completedTs ? date('Y-m-d H:i:s', $completedTs) : null;

                    $needUpdate = false;
                    $updateData = [];

                    if ($exactOrderDate && date('Y-m-d H:i', strtotime($order->order_date)) !== date('Y-m-d H:i', strtotime($exactOrderDate))) {
                        $updateData['order_date'] = $exactOrderDate;
                        $needUpdate = true;
                    }

                    if ($isCompleted && $exactCompletedAt && ($order->completed_at === null || date('Y-m-d H:i', strtotime($order->completed_at)) !== date('Y-m-d H:i', strtotime($exactCompletedAt)))) {
                        $updateData['completed_at'] = $exactCompletedAt;
                        $needUpdate = true;
                    }

                    if ($needUpdate) {
                        if ($isFix) {
                            DB::table('orders')->where('id', $order->id)->update($updateData);
                            $totalFixed++;
                            echo "  [FIXED] Order #{$order->id} ({$order->order_marketplace_id}) | Tgl Order: {$exactOrderDate} | Tgl Dilepas: " . ($exactCompletedAt ?: 'NULL') . "\n";
                        } else {
                            echo "  [MISMATCH] Order #{$order->id} ({$order->order_marketplace_id}) | ERP Tgl Order: {$order->order_date} vs Shopee API: {$exactOrderDate}\n";
                        }
                    }
                }
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

            $orders = Order::where('store_id', $store->id)
                ->where('order_date', '>=', now()->subDays($days))
                ->get();

            echo "  Memeriksa " . $orders->count() . " pesanan TikTok...\n";

            $orderChunks = $orders->chunk(50);
            foreach ($orderChunks as $chunk) {
                $snList = $chunk->pluck('order_marketplace_id')->filter()->toArray();
                if (empty($snList)) continue;

                $detailResponse = $tiktokService->getOrderDetail($accessToken, $shopCipher, $snList);
                $tiktokOrders = collect($detailResponse['order_list'] ?? [])->keyBy('id');

                foreach ($chunk as $order) {
                    $tiktokOrder = $tiktokOrders->get($order->order_marketplace_id);
                    if (!$tiktokOrder) continue;

                    // 1. Exact Order Date (create_time)
                    $createTs = $tiktokOrder['create_time'] ?? $tiktokOrder['create_time_ge'] ?? null;
                    if (is_numeric($createTs) && strlen((string)$createTs) >= 13) {
                        $createTs = (int)($createTs / 1000);
                    }
                    $exactOrderDate = $createTs ? date('Y-m-d H:i:s', $createTs) : null;

                    // 2. Exact Completed / Released Date
                    $status = strtoupper($order->order_status);
                    $isCompleted = in_array($status, ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED']);

                    $completedTs = null;
                    if ($isCompleted) {
                        $completedTs = $tiktokOrder['delivery_time'] 
                            ?? $tiktokOrder['update_time'] 
                            ?? $tiktokOrder['paid_time'] 
                            ?? $createTs;
                        if (is_numeric($completedTs) && strlen((string)$completedTs) >= 13) {
                            $completedTs = (int)($completedTs / 1000);
                        }
                    }

                    $exactCompletedAt = $completedTs ? date('Y-m-d H:i:s', $completedTs) : null;

                    $needUpdate = false;
                    $updateData = [];

                    if ($exactOrderDate && date('Y-m-d H:i', strtotime($order->order_date)) !== date('Y-m-d H:i', strtotime($exactOrderDate))) {
                        $updateData['order_date'] = $exactOrderDate;
                        $needUpdate = true;
                    }

                    if ($isCompleted && $exactCompletedAt && ($order->completed_at === null || date('Y-m-d H:i', strtotime($order->completed_at)) !== date('Y-m-d H:i', strtotime($exactCompletedAt)))) {
                        $updateData['completed_at'] = $exactCompletedAt;
                        $needUpdate = true;
                    }

                    if ($needUpdate) {
                        if ($isFix) {
                            DB::table('orders')->where('id', $order->id)->update($updateData);
                            $totalFixed++;
                            echo "  [FIXED] Order #{$order->id} ({$order->order_marketplace_id}) | Tgl Order: {$exactOrderDate} | Tgl Dilepas: " . ($exactCompletedAt ?: 'NULL') . "\n";
                        } else {
                            echo "  [MISMATCH] Order #{$order->id} ({$order->order_marketplace_id}) | ERP Tgl Order: {$order->order_date} vs TikTok API: {$exactOrderDate}\n";
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            echo "  [ERROR TikTok API] " . $e->getMessage() . "\n";
        }
    }
}

echo "\n======================================================================\n";
echo "  SELESAI!\n";
if ($isFix) {
    echo "  - Total pesanan yang tanggalnya diperbarui presisi: {$totalFixed}\n";
} else {
    echo "  Gunakan '--fix' untuk langsung memperbarui tanggal di database:\n";
    echo "  php fix_order_dates.php --days={$days} --fix\n";
}
echo "======================================================================\n";
