<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

$isFix = in_array('--fix', $argv);

$targetOrderSn = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--order=')) {
        $targetOrderSn = trim(explode('=', $arg)[1]);
    }
}

echo "======================================================================\n";
echo "  PERBAIKAN ORDER KILAT: ITEM DOUBLE & ITEM KURANG (CROSS-STORE MATCH)\n";
echo "======================================================================\n";
echo "  Mode: " . ($isFix ? "LIVE FIX (Hapus Item Double & Melengkapi Item Kurang)" : "DRY-RUN (Deteksi Saja)") . "\n";
if ($targetOrderSn) {
    echo "  Target Spesifik Order SN: {$targetOrderSn}\n";
}
echo "======================================================================\n\n";

// 1. MEMERIKSA & MENGHAPUS ITEM DOUBLE (DUPLICATE ITEMS)
if (!$targetOrderSn) {
    echo "--- 1. MEMERIKSA ITEM DOUBLE (DUPLICATE ORDER ITEMS) ---\n";
    if ($isFix) {
        $deleted = DB::delete("
            DELETE t1 FROM order_items t1
            INNER JOIN order_items t2 
            ON t1.order_id = t2.order_id 
            AND t1.product_name = t2.product_name 
            AND COALESCE(t1.sku, '') = COALESCE(t2.sku, '') 
            AND t1.quantity = t2.quantity
            AND t1.id > t2.id
        ");
        echo "  ✅ Berhasil menghapus {$deleted} baris item double!\n";
    }
}

// 2. MEMERIKSA PESANAN TANPA ITEM ATAU ITEM KURANG LENGKAP
echo "\n--- 2. MEMERIKSA KELENGKAPAN ITEM PESANAN ---\n";

if ($targetOrderSn) {
    $ordersToCheck = Order::where('order_marketplace_id', $targetOrderSn)
        ->orWhere('invoice_number', $targetOrderSn)
        ->get();
} else {
    $ordersToCheck = Order::doesntHave('items')
        ->orWhereHas('items', function($subQ) {}, '<', 2)
        ->orderBy('id', 'desc')
        ->limit(1000)
        ->get();
}

echo "Ditemukan " . $ordersToCheck->count() . " pesanan target untuk diperiksa.\n";

$allStores = Store::where('status', '!=', 'disconnected')->get()->keyBy('id');
$shopeeStores = $allStores->filter(fn($s) => strtolower($s->channel->code ?? '') === 'shopee');
$tiktokStores = $allStores->filter(fn($s) => in_array(strtolower($s->channel->code ?? ''), ['tiktok', 'tokopedia']));

$fixedCount = 0;

foreach ($ordersToCheck as $order) {
    $currentStore = $allStores->get($order->store_id);
    $channelCode = strtolower($currentStore->channel->code ?? '');

    $foundStore = null;
    $foundOrderData = null;

    if ($channelCode === 'shopee') {
        $shopeeService = app(\App\Services\ShopeeService::class);
        $candidateStores = $currentStore ? collect([$currentStore])->concat($shopeeStores->except($currentStore->id)) : $shopeeStores;

        foreach ($candidateStores as $sStore) {
            try {
                $accessToken = $sStore->getValidAccessToken();
                $shopId = (int) ($sStore->marketplace_store_id ?: $sStore->shopee_shop_id);

                if (empty($accessToken) || empty($shopId)) continue;

                $res = $shopeeService->getOrderDetail($accessToken, $shopId, [trim($order->order_marketplace_id)]);
                $shopeeOrder = $res['order_list'][0] ?? null;

                if ($shopeeOrder && !empty($shopeeOrder['item_list'])) {
                    $foundStore = $sStore;
                    $foundOrderData = $shopeeOrder;
                    break;
                }
            } catch (\Throwable $e) {
                // Continue checking next store
            }
        }

        if ($foundStore && $foundOrderData && !empty($foundOrderData['item_list'])) {
            $apiItemCount = count($foundOrderData['item_list']);
            $dbItemCount = DB::table('order_items')->where('order_id', $order->id)->count();

            if ($dbItemCount < $apiItemCount || $targetOrderSn) {
                echo "  [LENGKAPI ITEM] Order #{$order->id} ({$order->order_marketplace_id}) | Toko: {$foundStore->name} | DB: {$dbItemCount} item -> API: {$apiItemCount} item\n";

                if ($isFix) {
                    // Update store_id jika ternyata milik toko terhubung lain
                    if ($order->store_id !== $foundStore->id) {
                        DB::table('orders')->where('id', $order->id)->update(['store_id' => $foundStore->id]);
                    }

                    DB::table('order_items')->where('order_id', $order->id)->delete();

                    $insertRows = [];
                    foreach ($foundOrderData['item_list'] as $item) {
                        $modelId = $item['model_id'] ?? null;
                        $mp = \App\Models\MarketplaceProduct::where('store_id', $foundStore->id)
                            ->where('marketplace_product_id', (string) $item['item_id'])
                            ->when($modelId, fn($q) => $q->where('marketplace_variant_id', (string) $modelId))
                            ->first();

                        $price = $item['model_discounted_price'] ?? $item['model_original_price'] ?? 0;
                        $qty = $item['model_quantity_purchased'] ?? 1;
                        $itemSku = $item['model_sku'] ?: ($item['item_sku'] ?? null);

                        $masterProduct = $mp ? $mp->masterProduct : null;
                        if (!$masterProduct && $itemSku) {
                            $masterProduct = \App\Models\MasterProduct::where('tenant_id', $foundStore->tenant_id)
                                ->where('sku', trim($itemSku))
                                ->first();
                        }

                        $insertRows[] = [
                            'order_id' => $order->id,
                            'marketplace_product_id' => $mp ? $mp->id : null,
                            'master_product_id' => $masterProduct ? $masterProduct->id : null,
                            'product_name' => $item['item_name'] . ($item['model_name'] ? " ({$item['model_name']})" : ''),
                            'sku' => $itemSku,
                            'price' => $price,
                            'total_price' => $price * $qty,
                            'cost_price' => $masterProduct ? (float) $masterProduct->cost_price : 0,
                            'quantity' => $qty,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    DB::table('order_items')->insert($insertRows);
                    $fixedCount++;
                    echo "    -> ✅ SANGAT SUKSES! Berhasil melengkapi {$apiItemCount} item dari Shopee API!\n";
                }
            }
        }
    } elseif ($channelCode === 'tiktok' || $channelCode === 'tokopedia') {
        $tiktokService = app(\App\Services\TiktokService::class);
        $candidateStores = $currentStore ? collect([$currentStore])->concat($tiktokStores->except($currentStore->id)) : $tiktokStores;

        foreach ($candidateStores as $tStore) {
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
                        $foundStore = $tStore;
                        $foundOrderData = $itemList;
                        break;
                    }
                }
            } catch (\Throwable $e) {
                // Continue checking next store
            }
        }

        if ($foundStore && !empty($foundOrderData)) {
            $apiItemCount = count($foundOrderData);
            $dbItemCount = DB::table('order_items')->where('order_id', $order->id)->count();

            if ($dbItemCount < $apiItemCount || $targetOrderSn) {
                echo "  [LENGKAPI ITEM] Order #{$order->id} ({$order->order_marketplace_id}) | Toko: {$foundStore->name} | DB: {$dbItemCount} item -> API: {$apiItemCount} item\n";

                if ($isFix) {
                    if ($order->store_id !== $foundStore->id) {
                        DB::table('orders')->where('id', $order->id)->update(['store_id' => $foundStore->id]);
                    }

                    DB::table('order_items')->where('order_id', $order->id)->delete();

                    $insertRows = [];
                    foreach ($foundOrderData as $item) {
                        $productId = (string)($item['product_id'] ?? '');
                        $skuId     = (string)($item['sku_id'] ?? '');
                        $itemSku   = $item['seller_sku'] ?? $item['sku'] ?? null;

                        $mp = \App\Models\MarketplaceProduct::where('store_id', $foundStore->id)
                            ->where('marketplace_product_id', $productId)
                            ->when($skuId, fn($q) => $q->where('marketplace_variant_id', $skuId))
                            ->first();

                        $price = $item['sku_display_price'] ?? $item['sku_original_price'] ?? $item['price'] ?? 0;
                        $qty = $item['quantity'] ?? 1;

                        $masterProduct = $mp ? $mp->masterProduct : null;
                        if (!$masterProduct && $itemSku) {
                            $masterProduct = \App\Models\MasterProduct::where('tenant_id', $foundStore->tenant_id)
                                ->where('sku', trim($itemSku))
                                ->first();
                        }

                        $pName = $item['product_name'] ?? $item['item_name'] ?? 'Produk TikTok';
                        $vName = $item['sku_name'] ?? $item['variant_name'] ?? '';

                        $insertRows[] = [
                            'order_id' => $order->id,
                            'marketplace_product_id' => $mp ? $mp->id : null,
                            'master_product_id' => $masterProduct ? $masterProduct->id : null,
                            'product_name' => $pName . ($vName ? " ({$vName})" : ''),
                            'sku' => $itemSku,
                            'price' => $price,
                            'total_price' => $price * $qty,
                            'cost_price' => $masterProduct ? (float) $masterProduct->cost_price : 0,
                            'quantity' => $qty,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    DB::table('order_items')->insert($insertRows);
                    $fixedCount++;
                    echo "    -> ✅ SANGAT SUKSES! Berhasil melengkapi {$apiItemCount} item dari TikTok API!\n";
                }
            }
        }
    }
}

echo "\n======================================================================\n";
echo "  SELESAI!\n";
if ($isFix) {
    echo "  - Total orderan yang itemnya diperbaiki/dilengkapi: {$fixedCount}\n";
} else {
    echo "  Gunakan '--fix' untuk langsung melengkapi item di database:\n";
    echo "  php fix_duplicate_and_empty_items.php " . ($targetOrderSn ? "--order={$targetOrderSn} " : "") . "--fix\n";
}
echo "======================================================================\n";
