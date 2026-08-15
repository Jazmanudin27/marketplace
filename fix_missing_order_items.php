<?php

/**
 * ============================================================
 * RECOVER & POPULATE MISSING ORDER ITEMS (0 Items Recovery)
 * ============================================================
 * Script ini mendeteksi semua pesanan di ERP yang sudah ada di
 * tabel `orders` TETAPI rincian `order_items`-nya masih kosong (0 item).
 * Kemudian script menarik ulang detail produk dari Shopee/TikTok API
 * dan menyimpannya secara direct kilat via Query Builder.
 *
 * Cara pakai:
 *   php fix_missing_order_items.php
 *   php fix_missing_order_items.php --store=33
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Models\MarketplaceProduct;
use App\Models\MasterProduct;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use Illuminate\Support\Facades\DB;

$args    = array_slice($argv, 1);
$storeId = null;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--store=')) $storeId = (int) str_replace('--store=', '', $arg);
}

echo "\n";
echo "======================================================================\n";
echo "  RECOVER & ISI ULANG ITEM PESANAN YANG KOSONG DI ERP\n";
echo "======================================================================\n";
echo "  Toko : " . ($storeId ? "Store ID #{$storeId}" : "Semua Toko") . "\n";
echo "======================================================================\n\n";

$query = Order::whereDoesntHave('items');
if ($storeId) $query->where('store_id', $storeId);

$emptyOrders = $query->with('store.channel')->get();

echo "Ditemukan " . $emptyOrders->count() . " pesanan di ERP yang `order_items`-nya masih kosong.\n\n";

if ($emptyOrders->isEmpty()) {
    echo "✨ Semua pesanan di ERP sudah memiliki rincian item produk lengkap 100%!\n\n";
    exit(0);
}

// Group orders by store to batch API requests
$ordersByStore = $emptyOrders->groupBy('store_id');
$shopeeService = app(ShopeeService::class);
$tiktokService = app(TiktokService::class);

$fixedCount = 0;
$failedCount = 0;

foreach ($ordersByStore as $sId => $orders) {
    $store = $orders->first()->store;
    if (!$store) continue;

    $channelCode = strtolower($store->channel->code ?? '');
    echo "--------------------------------------------------------------------\n";
    echo "Memproses Toko: {$store->store_name} (ID: {$store->id}) - Channel: {$channelCode}\n";
    echo "--------------------------------------------------------------------\n";

    try {
        $accessToken = $store->getValidAccessToken();

        if ($channelCode === 'shopee') {
            $shopId = (int) $store->marketplace_store_id;
            $orderSns = $orders->pluck('order_marketplace_id')->toArray();
            $chunks = array_chunk($orderSns, 50);

            foreach ($chunks as $chunk) {
                try {
                    $detailResp = $shopeeService->getOrderDetail($accessToken, $shopId, $chunk);
                    $shopeeOrders = $detailResp['order_list'] ?? [];

                    foreach ($shopeeOrders as $sOrder) {
                        $orderSn = $sOrder['order_sn'] ?? null;
                        if (!$orderSn) continue;

                        $erpOrder = Order::where('store_id', $store->id)
                            ->where('order_marketplace_id', $orderSn)
                            ->first();

                        if (!$erpOrder) continue;

                        $itemList = $sOrder['item_list'] ?? [];
                        if (empty($itemList)) continue;

                        // Insert items directly using DB Query Builder (Fastest, zero lock)
                        $insertRows = [];
                        foreach ($itemList as $item) {
                            $modelId = $item['model_id'] ?? null;
                            $mpQuery = MarketplaceProduct::where('store_id', $store->id)
                                ->where('marketplace_product_id', (string) $item['item_id']);
                            if ($modelId) $mpQuery->where('marketplace_variant_id', (string) $modelId);
                            $mp = $mpQuery->first();

                            if (!$mp && $modelId) {
                                $mp = MarketplaceProduct::where('store_id', $store->id)
                                    ->where('marketplace_product_id', (string) $item['item_id'])
                                    ->first();
                            }

                            $masterProduct = $mp ? $mp->masterProduct : null;
                            $itemSku = $item['model_sku'] ?: ($item['item_sku'] ?? null);

                            if (!$masterProduct && $itemSku) {
                                $masterProduct = MasterProduct::where('tenant_id', $store->tenant_id)
                                    ->where('sku', trim($itemSku))
                                    ->first();
                            }

                            $price     = (float) ($item['model_discounted_price'] ?? $item['model_original_price'] ?? 0);
                            $qty       = (int) ($item['model_quantity_purchased'] ?? 1);
                            $costPrice = $masterProduct ? (float) $masterProduct->cost_price : 0;

                            $insertRows[] = [
                                'order_id'               => $erpOrder->id,
                                'sku'                    => $itemSku,
                                'marketplace_product_id' => $mp ? $mp->id : null,
                                'master_product_id'      => $masterProduct ? $masterProduct->id : null,
                                'product_name'           => mb_substr($item['item_name'] . (!empty($item['model_name']) ? ' - ' . $item['model_name'] : ''), 0, 250),
                                'price'                  => $price,
                                'quantity'               => $qty,
                                'total_price'            => $price * $qty,
                                'cost_price'             => $costPrice,
                                'hpp_subtotal'           => $costPrice * $qty,
                                'created_at'             => now(),
                                'updated_at'             => now(),
                            ];
                        }

                        if (!empty($insertRows)) {
                            DB::table('order_items')->insert($insertRows);
                            $fixedCount++;
                            echo "  [FIXED] Order {$orderSn}: Inserted " . count($insertRows) . " items.\n";
                        }
                    }
                } catch (\Exception $e) {
                    echo "  [ERROR API Chunk]: " . $e->getMessage() . "\n";
                    $failedCount += count($chunk);
                }
            }

        } elseif ($channelCode === 'tiktok') {
            $shopCipher = $store->shop_cipher;
            $orderIds = $orders->pluck('order_marketplace_id')->toArray();
            $chunks = array_chunk($orderIds, 50);

            foreach ($chunks as $chunk) {
                try {
                    $detailResp = $tiktokService->getOrderDetail($accessToken, $shopCipher, $chunk);
                    $tiktokOrders = $detailResp['orders'] ?? $detailResp['order_list'] ?? [];

                    foreach ($tiktokOrders as $tOrder) {
                        $orderId = (string)($tOrder['id'] ?? $tOrder['order_id'] ?? null);
                        if (!$orderId) continue;

                        $erpOrder = Order::where('store_id', $store->id)
                            ->where('order_marketplace_id', $orderId)
                            ->first();

                        if (!$erpOrder) continue;

                        $itemList = [];
                        if (!empty($tOrder['item_list'])) $itemList = $tOrder['item_list'];
                        elseif (!empty($tOrder['items'])) $itemList = $tOrder['items'];
                        elseif (!empty($tOrder['package_list'])) {
                            foreach ($tOrder['package_list'] as $pkg) {
                                if (!empty($pkg['items'])) $itemList = array_merge($itemList, $pkg['items']);
                                elseif (!empty($pkg['item_list'])) $itemList = array_merge($itemList, $pkg['item_list']);
                            }
                        }

                        if (empty($itemList)) continue;

                        $insertRows = [];
                        foreach ($itemList as $item) {
                            $productId = (string)($item['product_id'] ?? '');
                            $skuId     = (string)($item['sku_id'] ?? '');
                            $sellerSku = $item['seller_sku'] ?? $item['sku'] ?? null;

                            $mp = null;
                            if ($productId) {
                                $mpQuery = MarketplaceProduct::where('store_id', $store->id)
                                    ->where('marketplace_product_id', $productId);
                                if ($skuId) $mpQuery->where('marketplace_variant_id', $skuId);
                                $mp = $mpQuery->first();
                            }

                            $masterProduct = $mp ? $mp->masterProduct : null;
                            if (!$masterProduct && $sellerSku) {
                                $masterProduct = MasterProduct::where('tenant_id', $store->tenant_id)
                                    ->where('sku', trim($sellerSku))
                                    ->first();
                            }

                            $price     = (float) ($item['sku_sale_price'] ?? $item['sale_price'] ?? $item['price'] ?? 0);
                            $qty       = (int) ($item['quantity'] ?? 1);
                            $costPrice = $masterProduct ? (float) $masterProduct->cost_price : 0;
                            $itemSku   = $sellerSku ?: ($skuId ?: ($productId ?: 'TIKTOK-ITEM'));

                            $insertRows[] = [
                                'order_id'               => $erpOrder->id,
                                'sku'                    => $itemSku,
                                'marketplace_product_id' => $mp ? $mp->id : null,
                                'master_product_id'      => $masterProduct ? $masterProduct->id : null,
                                'product_name'           => mb_substr($item['product_name'] ?? $item['item_name'] ?? 'TikTok Item', 0, 250),
                                'price'                  => $price,
                                'quantity'               => $qty,
                                'total_price'            => $price * $qty,
                                'cost_price'             => $costPrice,
                                'hpp_subtotal'           => $costPrice * $qty,
                                'created_at'             => now(),
                                'updated_at'             => now(),
                            ];
                        }

                        if (!empty($insertRows)) {
                            DB::table('order_items')->insert($insertRows);
                            $fixedCount++;
                            echo "  [FIXED] Order {$orderId}: Inserted " . count($insertRows) . " items.\n";
                        }
                    }
                } catch (\Exception $e) {
                    echo "  [ERROR API Chunk]: " . $e->getMessage() . "\n";
                    $failedCount += count($chunk);
                }
            }
        }
    } catch (\Exception $e) {
        echo "  [ERROR Store]: " . $e->getMessage() . "\n";
    }
}

echo "\n======================================================================\n";
echo "  RINGKASAN HASIL PERBAIKAN ITEM KOSONG\n";
echo "======================================================================\n";
echo "  Total Order Berhasil Diisi Itemnya : {$fixedCount} order\n";
echo "  Total Order Gagal                   : {$failedCount} order\n";
echo "======================================================================\n\n";
