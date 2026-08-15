<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

echo "======================================================================\n";
echo "  PENCARIAN CROSS-STORE ORDER KOSONG (SHOPEE & TIKTOK API)\n";
echo "======================================================================\n\n";

$emptyOrders = Order::doesntHave('items')
    ->with('store.channel')
    ->orderBy('id', 'desc')
    ->limit(10)
    ->get();

$allTikTokStores = Store::whereHas('channel', function($q) {
        $q->whereIn('code', ['tiktok', 'tokopedia']);
    })
    ->where('status', '!=', 'disconnected')
    ->get();

$allShopeeStores = Store::whereHas('channel', function($q) {
        $q->where('code', 'shopee');
    })
    ->where('status', '!=', 'disconnected')
    ->get();

foreach ($emptyOrders as $order) {
    echo "----------------------------------------------------------------------\n";
    echo "ID ERP          : #{$order->id}\n";
    echo "Order SN / MP ID: '{$order->order_marketplace_id}'\n";
    echo "Current Store ID: #{$order->store_id} (" . ($order->store->name ?? 'N/A') . ")\n";
    echo "Channel         : " . ($order->store->channel->code ?? 'N/A') . "\n";

    $channelCode = strtolower($order->store->channel->code ?? '');
    $foundInStore = null;
    $foundItems = [];

    if ($channelCode === 'tiktok' || $channelCode === 'tokopedia') {
        $tiktokService = app(\App\Services\TiktokService::class);
        
        foreach ($allTikTokStores as $tStore) {
            try {
                $accessToken = $tStore->getValidAccessToken();
                $shopCipher = $tStore->shop_cipher;

                if (empty($accessToken) || empty($shopCipher)) continue;

                $res = $tiktokService->getOrderDetail($accessToken, $shopCipher, [trim($order->order_marketplace_id)]);
                $orderList = $res['order_list'] ?? [];
                $tiktokOrder = $orderList[0] ?? null;

                if ($tiktokOrder) {
                    $itemList = $tiktokOrder['item_list']
                        ?? $tiktokOrder['line_items']
                        ?? $tiktokOrder['sku_list']
                        ?? $tiktokOrder['items']
                        ?? [];

                    if (!empty($itemList)) {
                        $foundInStore = $tStore;
                        $foundItems = $itemList;
                        break;
                    }
                }
            } catch (\Throwable $e) {
                // Continue checking next store
            }
        }

        if ($foundInStore) {
            echo "🎯 WOOOW! DITEMUKAN DI TOKO TERHUBUNG LAIN:\n";
            echo "   Toko Asli Resmikan : ID #{$foundInStore->id} - {$foundInStore->name}\n";
            echo "   Jumlah Item API    : " . count($foundItems) . " item!\n";
            foreach ($foundItems as $idx => $it) {
                echo "      [" . ($idx + 1) . "] " . ($it['product_name'] ?? $it['item_name'] ?? 'Produk') . " | Qty: " . ($it['quantity'] ?? 1) . "\n";
            }
        } else {
            echo "⚠️ Tetap tidak ditemukan di seluruh " . $allTikTokStores->count() . " Toko TikTok terhubung.\n";
        }
    } elseif ($channelCode === 'shopee') {
        $shopeeService = app(\App\Services\ShopeeService::class);

        foreach ($allShopeeStores as $sStore) {
            try {
                $accessToken = $sStore->getValidAccessToken();
                $shopId = (int) ($sStore->marketplace_store_id ?: $sStore->shopee_shop_id);

                if (empty($accessToken) || empty($shopId)) continue;

                $res = $shopeeService->getOrderDetail($accessToken, $shopId, [trim($order->order_marketplace_id)]);
                $shopeeOrder = $res['order_list'][0] ?? null;

                if ($shopeeOrder && !empty($shopeeOrder['item_list'])) {
                    $foundInStore = $sStore;
                    $foundItems = $shopeeOrder['item_list'];
                    break;
                }
            } catch (\Throwable $e) {
                // Continue checking next store
            }
        }

        if ($foundInStore) {
            echo "🎯 WOOOW! DITEMUKAN DI TOKO SHOPEE TERHUBUNG LAIN:\n";
            echo "   Toko Asli Resmikan : ID #{$foundInStore->id} - {$foundInStore->name}\n";
            echo "   Jumlah Item API    : " . count($foundItems) . " item!\n";
            foreach ($foundItems as $idx => $it) {
                echo "      [" . ($idx + 1) . "] " . $it['item_name'] . " | Qty: " . ($it['model_quantity_purchased'] ?? 1) . "\n";
            }
        } else {
            echo "⚠️ Tetap tidak ditemukan di seluruh " . $allShopeeStores->count() . " Toko Shopee terhubung.\n";
        }
    }
}

echo "\n======================================================================\n";
