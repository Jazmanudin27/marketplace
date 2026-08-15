<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\DB;

echo "======================================================================\n";
echo "  DEBUGGING ORDERS WITH 0 ITEMS (ERP MARKETPLACE)\n";
echo "======================================================================\n\n";

$emptyOrders = Order::doesntHave('items')
    ->with('store.channel')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

echo "Ditemukan " . $emptyOrders->count() . " sampel orderan yang benar-benar 0 ITEM:\n\n";

foreach ($emptyOrders as $order) {
    echo "----------------------------------------------------------------------\n";
    echo "ID ERP          : #{$order->id}\n";
    echo "Order SN / MP ID: '{$order->order_marketplace_id}'\n";
    echo "Store ID / Nama : #{$order->store_id} - " . ($order->store->name ?? 'N/A') . "\n";
    echo "Channel         : " . ($order->store->channel->code ?? 'N/A') . "\n";
    echo "Status Order    : {$order->order_status}\n";
    echo "Order Date      : {$order->order_date}\n";

    $store = $order->store;
    if (!$store) {
        echo "❌ SKIP: Store ID {$order->store_id} tidak ada di database!\n";
        continue;
    }

    $channelCode = strtolower($store->channel->code ?? '');

    if ($channelCode === 'shopee') {
        try {
            $shopeeService = app(\App\Services\ShopeeService::class);
            $accessToken = $store->getValidAccessToken();
            $shopId = (int) ($store->marketplace_store_id ?: $store->shopee_shop_id);

            echo "Shopee Shop ID  : {$shopId}\n";
            echo "Access Token    : " . (empty($accessToken) ? 'KOSONG!' : 'TERSEDIA (' . substr($accessToken, 0, 10) . '...)') . "\n";

            if (!empty($accessToken) && !empty($shopId)) {
                $res = $shopeeService->getOrderDetail($accessToken, $shopId, [trim($order->order_marketplace_id)]);
                $shopeeOrder = $res['order_list'][0] ?? null;

                if ($shopeeOrder) {
                    $itemList = $shopeeOrder['item_list'] ?? [];
                    echo "API Shopee Response: OK (Ditemukan " . count($itemList) . " item di API Shopee!)\n";
                    foreach ($itemList as $idx => $it) {
                        echo "   [" . ($idx + 1) . "] " . $it['item_name'] . " | Model: " . ($it['model_name'] ?? '-') . " | Qty: " . ($it['model_quantity_purchased'] ?? 1) . "\n";
                    }
                } else {
                    echo "⚠️ API Shopee Response: Kosong/Order SN '{$order->order_marketplace_id}' tidak ditemukan di Toko ini.\n";
                }
            }
        } catch (\Throwable $e) {
            echo "❌ Error API Shopee: " . $e->getMessage() . "\n";
        }
    } elseif ($channelCode === 'tiktok' || $channelCode === 'tokopedia') {
        try {
            $tiktokService = app(\App\Services\TiktokService::class);
            $accessToken = $store->getValidAccessToken();
            $shopCipher = $store->shop_cipher;

            echo "TikTok shop_cipher: " . ($shopCipher ?: 'KOSONG!') . "\n";
            echo "Access Token       : " . (empty($accessToken) ? 'KOSONG!' : 'TERSEDIA') . "\n";

            if (!empty($accessToken) && !empty($shopCipher)) {
                $res = $tiktokService->getOrderDetail($accessToken, $shopCipher, [trim($order->order_marketplace_id)]);
                $orderList = $res['order_list'] ?? [];
                $tiktokOrder = $orderList[0] ?? null;

                if ($tiktokOrder) {
                    $itemList = $tiktokOrder['item_list']
                        ?? $tiktokOrder['line_items']
                        ?? $tiktokOrder['sku_list']
                        ?? $tiktokOrder['items']
                        ?? [];

                    echo "API TikTok Response: OK (Ditemukan " . count($itemList) . " item di API TikTok!)\n";
                    foreach ($itemList as $idx => $it) {
                        echo "   [" . ($idx + 1) . "] " . ($it['product_name'] ?? $it['item_name'] ?? 'Produk') . " | Qty: " . ($it['quantity'] ?? 1) . "\n";
                    }
                } else {
                    echo "⚠️ API TikTok Response: Kosong/Order ID '{$order->order_marketplace_id}' tidak ditemukan di Toko ini.\n";
                }
            }
        } catch (\Throwable $e) {
            echo "❌ Error API TikTok: " . $e->getMessage() . "\n";
        }
    } else {
        echo "ℹ️ Channel ini ({$channelCode}) bukan Shopee/TikTok.\n";
    }
}

echo "\n======================================================================\n";
