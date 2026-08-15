<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\DB;

$isFix = in_array('--fix', $argv);

echo "======================================================================\n";
echo "  PERBAIKAN ORDER TANPA ITEM, ITEM DOUBLE & ITEM KURANG LENGKAP\n";
echo "======================================================================\n";
echo "  Mode: " . ($isFix ? "LIVE FIX (Hapus Item Double & Tarik Ulang Item Kurang)" : "DRY-RUN (Deteksi Saja)") . "\n";
echo "======================================================================\n\n";

// 1. MEMERIKSA & MENGHAPUS ITEM DOUBLE (DUPLICATE ITEMS)
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
} else {
    $duplicates = DB::select("
        SELECT order_id, product_name, COUNT(*) as cnt 
        FROM order_items 
        GROUP BY order_id, product_name, sku, quantity 
        HAVING cnt > 1 
        LIMIT 50
    ");
    echo "  Ditemukan " . count($duplicates) . " sampel item double.\n";
    foreach ($duplicates as $dup) {
        echo "  [DUPLICATE] Order ID #{$dup->order_id} | Item: {$dup->product_name} (Total: {$dup->cnt})\n";
    }
}

// 2. MEMERIKSA PESANAN TANPA ITEM ATAU ITEM KURANG LENGKAP
echo "\n--- 2. MEMERIKSA PESANAN TANPA ITEM & ITEM KURANG LENGKAP ---\n";

// Ambil orderan yang tidak memiliki item ATAU yang jumlah itemnya sedikit (kemungkinan parsial/kurang)
$ordersToCheck = Order::withCount('items')
    ->where(function($q) {
        $q->has('items', '=', 0)
          ->orWhereHas('items', function($subQ) {}, '<', 5); // Cek orderan yang itemnya < 5 untuk verifikasi kecocokan API
    })
    ->orderBy('id', 'desc')
    ->limit(500)
    ->get();

echo "Memeriksa " . $ordersToCheck->count() . " sampel pesanan untuk verifikasi kelengkapan item...\n";

$fixedCount = 0;
foreach ($ordersToCheck as $order) {
    if (!$order->store) continue;

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

            if (empty($accessToken) || empty($shopId)) continue;

            $res = $shopeeService->getOrderDetail($accessToken, $shopId, [$order->order_marketplace_id]);
            $shopeeOrder = $res['order_list'][0] ?? null;

            if ($shopeeOrder && !empty($shopeeOrder['item_list'])) {
                $apiItemCount = count($shopeeOrder['item_list']);
                $dbItemCount = $order->items_count;

                // Jika jumlah item di DB kurang dari jumlah item di API (misal DB=1, API=3)
                if ($dbItemCount < $apiItemCount) {
                    echo "  [LENGKAPI ITEM] Order #{$order->id} ({$order->order_marketplace_id}) | DB Item: {$dbItemCount} -> API Item: {$apiItemCount}\n";

                    if ($isFix) {
                        // Hapus item lama yang tidak lengkap
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
                        echo "    -> Berhasil melengkapi {$apiItemCount} item dari API Shopee!\n";
                    }
                }
            }
        } elseif ($channelCode === 'tiktok' || $channelCode === 'tokopedia') {
            $tiktokService = app(\App\Services\TiktokService::class);
            $tiktokOrder = $tiktokService->getOrderDetail($store, $order->order_marketplace_id);

            if ($tiktokOrder && !empty($tiktokOrder['item_list'])) {
                $apiItemCount = count($tiktokOrder['item_list']);
                $dbItemCount = $order->items_count;

                if ($dbItemCount < $apiItemCount) {
                    echo "  [LENGKAPI ITEM] Order #{$order->id} ({$order->order_marketplace_id}) | DB Item: {$dbItemCount} -> API Item: {$apiItemCount}\n";

                    if ($isFix) {
                        DB::table('order_items')->where('order_id', $order->id)->delete();

                        $insertRows = [];
                        foreach ($tiktokOrder['item_list'] as $item) {
                            $skuId = $item['sku_id'] ?? null;
                            $mp = \App\Models\MarketplaceProduct::where('store_id', $store->id)
                                ->where('marketplace_product_id', (string) $item['product_id'])
                                ->when($skuId, fn($q) => $q->where('marketplace_variant_id', (string) $skuId))
                                ->first();

                            $price = $item['sku_display_price'] ?? $item['sku_original_price'] ?? 0;
                            $qty = 1;
                            $itemSku = $item['seller_sku'] ?? null;

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
                                'product_name' => $item['product_name'] . ($item['sku_name'] ? " ({$item['sku_name']})" : ''),
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
                        echo "    -> Berhasil melengkapi {$apiItemCount} item dari API TikTok!\n";
                    }
                }
            }
        }
    } catch (\Throwable $e) {
        // Skip errors silently during bulk check
    }
}

echo "\n======================================================================\n";
echo "  SELESAI!\n";
if ($isFix) {
    echo "  - Total orderan yang itemnya diperbaiki/dilengkapi: {$fixedCount}\n";
} else {
    echo "  Gunakan '--fix' untuk langsung melengkapi item di database:\n";
    echo "  php fix_duplicate_and_empty_items.php --fix\n";
}
echo "======================================================================\n";
