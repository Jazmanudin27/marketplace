<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\DB;

$isFix = in_array('--fix', $argv);

// Cek argumen --order=...
$targetOrderSn = null;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--order=')) {
        $targetOrderSn = trim(explode('=', $arg)[1]);
    }
}

echo "======================================================================\n";
echo "  PERBAIKAN ORDER TANPA ITEM, ITEM DOUBLE & ITEM KURANG LENGKAP\n";
echo "======================================================================\n";
echo "  Mode: " . ($isFix ? "LIVE FIX (Hapus Item Double & Tarik Ulang Item Kurang)" : "DRY-RUN (Deteksi Saja)") . "\n";
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
    // Jika tidak ada target spesifik, periksa pesanan yang memiliki item <= 3
    $ordersToCheck = Order::withCount('items')
        ->where(function($q) {
            $q->has('items', '<=', 3);
        })
        ->orderBy('id', 'desc')
        ->limit(2000)
        ->get();
}

echo "Memeriksa " . $ordersToCheck->count() . " pesanan...\n";

$fixedCount = 0;
foreach ($ordersToCheck as $order) {
    if (!$order->store) {
        echo "  [SKIP] Order #{$order->id} ({$order->order_marketplace_id}): Toko tidak ditemukan.\n";
        continue;
    }

    $store = $order->store;
    $channelCode = strtolower($store->channel->code ?? '');

    try {
        if ($channelCode === 'shopee') {
            $shopeeService = app(\App\Services\ShopeeService::class);
            
            try {
                $accessToken = $store->getValidAccessToken();
            } catch (\Throwable $te) {
                $accessToken = null;
            }
            
            $shopId = (int) ($store->marketplace_store_id ?: $store->shopee_shop_id);

            if (empty($accessToken) || empty($shopId)) {
                echo "  [SKIP] Token Shopee / Shop ID kosong untuk Toko '{$store->name}'.\n";
                continue;
            }

            $res = $shopeeService->getOrderDetail($accessToken, $shopId, [$order->order_marketplace_id]);
            $shopeeOrder = $res['order_list'][0] ?? null;

            if ($shopeeOrder && !empty($shopeeOrder['item_list'])) {
                $apiItemCount = count($shopeeOrder['item_list']);
                $dbItemCount = DB::table('order_items')->where('order_id', $order->id)->count();

                echo "  [ORDER CHECK] ID #{$order->id} ({$order->order_marketplace_id}) | Toko: {$store->name} | Item DB: {$dbItemCount} vs Item API: {$apiItemCount}\n";

                // Jika terdeteksi jumlah item di DB < API ATAU jika dipanggil spesifik dengan --order
                if ($dbItemCount < $apiItemCount || $targetOrderSn) {
                    if ($isFix) {
                        DB::table('order_items')->where('order_id', $order->id)->delete();

                        $insertRows = [];
                        foreach ($shopeeOrder['item_list'] as $item) {
                            $modelId = $item['model_id'] ?? null;
                            $mp = \App\Models\MarketplaceProduct::where('store_id', $store->id)
                                ->where('marketplace_product_id', (string) $item['item_id'])
                                ->when($modelId, fn($q) => $q->where('marketplace_variant_id', (string) $modelId))
                                ->first();

                            $price = $item['model_discounted_price'] ?? $item['model_original_price'] ?? 0;
                            $qty = $item['model_quantity_purchased'] ?? 1;
                            $itemSku = $item['model_sku'] ?: ($item['item_sku'] ?? null);

                            $masterProduct = $mp ? $mp->masterProduct : null;
                            if (!$masterProduct && $itemSku) {
                                $masterProduct = \App\Models\MasterProduct::where('tenant_id', $store->tenant_id)
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
                        echo "    -> ✅ SANGAT SUKSES! Berhasil memperbarui & melengkapi {$apiItemCount} item lengkap dari API Shopee!\n";
                    }
                }
            } else {
                echo "    -> [WARN] API Shopee tidak mengembalikan item_list untuk order {$order->order_marketplace_id}.\n";
            }
        } elseif ($channelCode === 'tiktok' || $channelCode === 'tokopedia') {
            $tiktokService = app(\App\Services\TiktokService::class);
            
            try {
                $accessToken = $store->getValidAccessToken();
            } catch (\Throwable $te) {
                $accessToken = null;
            }
            
            $shopCipher = $store->shop_cipher;

            if (empty($accessToken) || empty($shopCipher)) {
                echo "  [SKIP] Token / shop_cipher TikTok kosong untuk Toko '{$store->name}'.\n";
                continue;
            }

            $detailResponse = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$order->order_marketplace_id]);
            $orderList = $detailResponse['order_list'] ?? [];
            $tiktokOrder = $orderList[0] ?? null;

            if ($tiktokOrder) {
                $itemList = $tiktokOrder['item_list']
                    ?? $tiktokOrder['line_items']
                    ?? $tiktokOrder['sku_list']
                    ?? $tiktokOrder['items']
                    ?? [];

                if (empty($itemList) && !empty($tiktokOrder['packages'])) {
                    foreach ($tiktokOrder['packages'] as $pkg) {
                        if (!empty($pkg['items'])) {
                            $itemList = array_merge($itemList, $pkg['items']);
                        } elseif (!empty($pkg['item_list'])) {
                            $itemList = array_merge($itemList, $pkg['item_list']);
                        }
                    }
                }

                if (!empty($itemList)) {
                    $apiItemCount = count($itemList);
                    $dbItemCount = DB::table('order_items')->where('order_id', $order->id)->count();

                    echo "  [ORDER CHECK] ID #{$order->id} ({$order->order_marketplace_id}) | Toko: {$store->name} | Item DB: {$dbItemCount} vs Item API: {$apiItemCount}\n";

                    if ($dbItemCount < $apiItemCount || $targetOrderSn) {
                        if ($isFix) {
                            DB::table('order_items')->where('order_id', $order->id)->delete();

                            $insertRows = [];
                            foreach ($itemList as $item) {
                                $productId = (string)($item['product_id'] ?? '');
                                $skuId     = (string)($item['sku_id'] ?? '');
                                $itemSku   = $item['seller_sku'] ?? $item['sku'] ?? null;

                                $mp = \App\Models\MarketplaceProduct::where('store_id', $store->id)
                                    ->where('marketplace_product_id', $productId)
                                    ->when($skuId, fn($q) => $q->where('marketplace_variant_id', $skuId))
                                    ->first();

                                $price = $item['sku_display_price'] ?? $item['sku_original_price'] ?? $item['price'] ?? 0;
                                $qty = $item['quantity'] ?? 1;

                                $masterProduct = $mp ? $mp->masterProduct : null;
                                if (!$masterProduct && $itemSku) {
                                    $masterProduct = \App\Models\MasterProduct::where('tenant_id', $store->tenant_id)
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
                            echo "    -> ✅ SANGAT SUKSES! Berhasil memperbarui & melengkapi {$apiItemCount} item lengkap dari API TikTok!\n";
                        }
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        echo "    -> [ERROR] " . $e->getMessage() . "\n";
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
