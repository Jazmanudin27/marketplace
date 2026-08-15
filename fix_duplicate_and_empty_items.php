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
echo "  PERBAIKAN ORDER KILAT: TIKTOK API v202309 + SHOPEE MATCHING\n";
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
        ->orderBy('id', 'desc')
        ->get();
}

echo "Ditemukan " . $ordersToCheck->count() . " pesanan kosong untuk diperiksa.\n";

if ($ordersToCheck->isEmpty()) {
    echo "✅ Semua pesanan sudah memiliki item lengkap 100%!\n";
    exit(0);
}

$allStores = Store::where('status', '!=', 'disconnected')->get();
$shopeeStores = $allStores->filter(fn($s) => strtolower($s->channel->code ?? '') === 'shopee');
$tiktokStores = $allStores->filter(fn($s) => in_array(strtolower($s->channel->code ?? ''), ['tiktok', 'tokopedia']));

$shopeeService = app(\App\Services\ShopeeService::class);
$tiktokService = app(\App\Services\TiktokService::class);

$fixedCount = 0;
$orderChunks = $ordersToCheck->chunk(50);

foreach ($orderChunks as $chunk) {
    $snList = $chunk->pluck('order_marketplace_id')->map(fn($sn) => trim($sn))->filter()->toArray();
    if (empty($snList)) continue;

    // A. TEST TERHADAP SELURUH TOKO SHOPEE TERHUBUNG
    foreach ($shopeeStores as $sStore) {
        try {
            $accessToken = $sStore->getValidAccessToken();
            $shopId = (int) ($sStore->marketplace_store_id ?: $sStore->shopee_shop_id);
            if (empty($accessToken) || empty($shopId)) continue;

            $res = $shopeeService->getOrderDetail($accessToken, $shopId, $snList);
            $shopeeOrders = collect($res['order_list'] ?? [])->keyBy('order_sn');

            foreach ($chunk as $order) {
                $shopeeOrder = $shopeeOrders->get($order->order_marketplace_id);
                if ($shopeeOrder && !empty($shopeeOrder['item_list'])) {
                    $apiItemCount = count($shopeeOrder['item_list']);
                    echo "  [LENGKAPI ITEM - SHOPEE] Order #{$order->id} ({$order->order_marketplace_id}) | Toko: {$sStore->name} | Items: {$apiItemCount}\n";

                    if ($isFix) {
                        if ($order->store_id !== $sStore->id) {
                            DB::table('orders')->where('id', $order->id)->update(['store_id' => $sStore->id]);
                        }

                        DB::table('order_items')->where('order_id', $order->id)->delete();

                        $insertRows = [];
                        foreach ($shopeeOrder['item_list'] as $item) {
                            $modelId = $item['model_id'] ?? null;
                            $mp = \App\Models\MarketplaceProduct::where('store_id', $sStore->id)
                                ->where('marketplace_product_id', (string) $item['item_id'])
                                ->when($modelId, fn($q) => $q->where('marketplace_variant_id', (string) $modelId))
                                ->first();

                            $price = $item['model_discounted_price'] ?? $item['model_original_price'] ?? 0;
                            $qty = $item['model_quantity_purchased'] ?? 1;
                            $itemSku = $item['model_sku'] ?: ($item['item_sku'] ?? null);

                            $masterProduct = $mp ? $mp->masterProduct : null;
                            if (!$masterProduct && $itemSku) {
                                $masterProduct = \App\Models\MasterProduct::where('tenant_id', $sStore->tenant_id)
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
        } catch (\Throwable $e) {
            // Continue next store
        }
    }

    // B. TEST TERHADAP SELURUH TOKO TIKTOK TERHUBUNG (SUPPORTS API v202309)
    foreach ($tiktokStores as $tStore) {
        try {
            $accessToken = $tStore->getValidAccessToken();
            $shopCipher = $tStore->shop_cipher;
            if (empty($accessToken) || empty($shopCipher)) continue;

            $res = $tiktokService->getOrderDetail($accessToken, $shopCipher, $snList);
            $tiktokOrders = collect($res['orders'] ?? $res['order_list'] ?? [])->keyBy('id');

            foreach ($chunk as $order) {
                $tiktokOrder = $tiktokOrders->get($order->order_marketplace_id);
                if ($tiktokOrder) {
                    $itemList = $tiktokOrder['line_items']
                        ?? $tiktokOrder['item_list']
                        ?? $tiktokOrder['sku_list']
                        ?? $tiktokOrder['items']
                        ?? [];

                    if (!empty($itemList)) {
                        $apiItemCount = count($itemList);
                        echo "  [LENGKAPI ITEM - TIKTOK] Order #{$order->id} ({$order->order_marketplace_id}) | Toko: {$tStore->name} | Items: {$apiItemCount}\n";

                        if ($isFix) {
                            if ($order->store_id !== $tStore->id) {
                                DB::table('orders')->where('id', $order->id)->update(['store_id' => $tStore->id]);
                            }

                            DB::table('order_items')->where('order_id', $order->id)->delete();

                            $insertRows = [];
                            foreach ($itemList as $item) {
                                $productId = (string)($item['product_id'] ?? '');
                                $skuId     = (string)($item['sku_id'] ?? '');
                                $itemSku   = $item['seller_sku'] ?? $item['sku'] ?? null;

                                $mp = \App\Models\MarketplaceProduct::where('store_id', $tStore->id)
                                    ->where('marketplace_product_id', $productId)
                                    ->when($skuId, fn($q) => $q->where('marketplace_variant_id', $skuId))
                                    ->first();

                                $price = $item['original_price'] ?? $item['sale_price'] ?? $item['sku_display_price'] ?? $item['price'] ?? 0;
                                $qty = $item['quantity'] ?? 1;

                                $masterProduct = $mp ? $mp->masterProduct : null;
                                if (!$masterProduct && $itemSku) {
                                    $masterProduct = \App\Models\MasterProduct::where('tenant_id', $tStore->tenant_id)
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
        } catch (\Throwable $e) {
            // Continue next store
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
