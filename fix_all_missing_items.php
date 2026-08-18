<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\TiktokService;
use App\Services\ShopeeService;

echo "======================================================================\n";
echo "🛠️ PERBAIKAN OTOMATIS: REKONSILIASI ITEM PESANAN YANG KOSONG IN ERP\n";
echo "======================================================================\n\n";

$ordersWithoutItems = Order::whereDoesntHave('items')
    ->whereNotNull('order_marketplace_id')
    ->where('order_marketplace_id', 'NOT LIKE', 'MANUAL-%')
    ->where('order_marketplace_id', 'NOT LIKE', 'SHOPEE-DEMO-%')
    ->where('order_marketplace_id', 'NOT LIKE', 'DS-%')
    ->get();

echo "Ditemukan " . $ordersWithoutItems->count() . " pesanan tanpa item produk.\n\n";

if ($ordersWithoutItems->isEmpty()) {
    echo "✅ Semua pesanan marketplace di database ERP sudah memiliki item produk lengkap.\n";
    exit;
}

$tiktokService = app(TiktokService::class);
$shopeeService = app(ShopeeService::class);

$fixedCount = 0;

foreach ($ordersWithoutItems as $ord) {
    $store = $ord->store;
    if (!$store) continue;

    $channelCode = strtolower($store->channel->code ?? '');
    echo "🔍 Memproses Order: {$ord->order_marketplace_id} | Toko: {$store->store_name} ({$channelCode})...\n";

    if (str_contains($channelCode, 'tiktok') || str_contains($channelCode, 'tokopedia')) {
        try {
            $accessToken = $store->getValidAccessToken();
            $shopCipher = $store->shop_cipher;

            $res = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$ord->order_marketplace_id]);
            $tOrders = $res['order_list'] ?? $res['orders'] ?? [];

            if (!empty($tOrders[0])) {
                $tOrder = $tOrders[0];
                $itemList = $tOrder['line_items']
                    ?? $tOrder['item_list']
                    ?? $tOrder['order_line_list']
                    ?? $tOrder['sku_list']
                    ?? $tOrder['items']
                    ?? [];

                if (empty($itemList) && !empty($tOrder['packages'])) {
                    foreach ($tOrder['packages'] as $pkg) {
                        if (!empty($pkg['items'])) $itemList = array_merge($itemList, $pkg['items']);
                        elseif (!empty($pkg['line_items'])) $itemList = array_merge($itemList, $pkg['line_items']);
                        elseif (!empty($pkg['item_list'])) $itemList = array_merge($itemList, $pkg['item_list']);
                    }
                }

                if (!empty($itemList)) {
                    foreach ($itemList as $item) {
                        $productId = (string)($item['product_id'] ?? '');
                        $skuId     = (string)($item['sku_id'] ?? '');
                        $sellerSku = $item['seller_sku'] ?? $item['sku'] ?? null;
                        $skuName   = $item['sku_name'] ?? $item['variation_name'] ?? null;
                        $origPrice = (float)($item['original_price'] ?? $item['price'] ?? 0);
                        $sDisc     = (float)($item['seller_discount'] ?? 0);
                        $pDisc     = (float)($item['platform_discount'] ?? 0);
                        $qty       = (int)($item['quantity'] ?? 1);
                        $unitPrice = (float)($item['sale_price'] ?? $item['sku_display_price'] ?? $item['price'] ?? $origPrice);
                        $pName     = $item['product_name'] ?? $item['item_name'] ?? 'Produk TikTok';
                        $vName     = $item['sku_name'] ?? $item['variant_name'] ?? '';

                        OrderItem::create([
                            'order_id'               => $ord->id,
                            'sku'                    => $sellerSku ?: $skuId,
                            'seller_sku'             => $sellerSku,
                            'sku_id'                 => $skuId,
                            'sku_name'               => $skuName ?: $vName,
                            'product_name'           => mb_substr($pName . ($vName ? ' - ' . $vName : ''), 0, 250),
                            'price'                  => $unitPrice,
                            'original_price'         => $origPrice,
                            'seller_discount'        => $sDisc,
                            'platform_discount'      => $pDisc,
                            'quantity'               => $qty,
                            'total_price'            => $unitPrice * $qty,
                        ]);
                    }
                    $fixedCount++;
                    echo "   └─ ✅ Berhasil menambahkan " . count($itemList) . " item produk!\n";
                } else {
                    echo "   └─ ⚠️ Response API TikTok tidak mengembalikan item_list.\n";
                }
            }
        } catch (\Exception $e) {
            echo "   └─ ❌ Error API TikTok: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n======================================================================\n";
echo "🎉 SELESAI! Berhasil memperbaiki {$fixedCount} pesanan yang tadinya tidak memiliki item produk.\n";
echo "======================================================================\n";
