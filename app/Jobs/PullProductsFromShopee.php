<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\MarketplaceProduct;
use App\Services\ShopeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullProductsFromShopee implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;
    protected int $storeId;

    public function __construct(Store $store)
    {
        $this->storeId = $store->id;
    }

    public function handle(ShopeeService $shopeeService): void
    {
        $store = Store::find($this->storeId);

        if (!$store) {
            Log::warning('[Shopee] PullProductsFromShopee: Store #' . $this->storeId . ' no longer exists.');
            return;
        }

        if ($store->status === 'disconnected' || empty($store->marketplace_store_id)) {
            Log::warning("[Shopee] Toko {$store->store_name} tidak terhubung.");
            return;
        }

        @set_time_limit(600);
        @ini_set('memory_limit', '512M');

        try {
            $shopId = (int) $store->marketplace_store_id;
            $accessToken = $store->getValidAccessToken();

            $offset = 0;
            $pageSize = 50;
            $hasMore = true;
            $totalSynced = 0;
            $syncStartTime = now();

            while ($hasMore) {
                // 1. Get Item List
                $listData = $shopeeService->getItemList($accessToken, $shopId, $offset, $pageSize);
                $items = $listData['item'] ?? $listData['item_list'] ?? [];

                if (empty($items)) {
                    break;
                }

                $itemIds = collect($items)->pluck('item_id')->filter()->values()->toArray();
                if (empty($itemIds)) {
                    break;
                }

                // Chunk itemIds in max 50 per batch
                $chunks = array_chunk($itemIds, 50);

                foreach ($chunks as $chunkItemIds) {
                    // 2. Get Item Base Info
                    $infoData = $shopeeService->getItemBaseInfo($accessToken, $shopId, $chunkItemIds);
                    $itemList = $infoData['item_list'] ?? $infoData['item'] ?? [];

                    // 3. Save to database
                    foreach ($itemList as $item) {
                        $imageUrl = null;
                        if (!empty($item['image']['image_url_list'][0])) {
                            $imageUrl = $item['image']['image_url_list'][0];
                        }

                        // Pre-Order setting
                        $isShopeePo = !empty($item['pre_order']['is_pre_order']);

                        // Deskripsi produk
                        $description = null;
                        if (isset($item['description'])) {
                            $description = $item['description'];
                        } elseif (isset($item['description_info']['extended_description'])) {
                            $description = $item['description_info']['extended_description'];
                        } elseif (isset($item['description_info']['description'])) {
                            $description = $item['description_info']['description'];
                        }

                        if (!empty($item['has_model'])) {
                            // Jika punya varian (model), panggil API get_model_list
                            $models = [];
                            try {
                                $modelData = $shopeeService->getModelList($accessToken, $shopId, (int) $item['item_id']);
                                $models = $modelData['model'] ?? $modelData['model_list'] ?? [];
                                $tierVariations = $modelData['tier_variation'] ?? [];

                                $variantImages = [];
                                foreach ($tierVariations as $tier) {
                                    foreach ($tier['option_list'] ?? [] as $option) {
                                        if (!empty($option['image']['image_url_list'][0])) {
                                            $variantImages[trim($option['option'])] = $option['image']['image_url_list'][0];
                                        }
                                    }
                                }

                                if (count($models) > 0) {
                                    foreach ($models as $model) {
                                        $price = $model['price_info'][0]['current_price'] ?? $model['price_info'][0]['original_price'] ?? $model['price'] ?? 0;
                                        $stock = $model['stock_info_v2']['summary_info']['total_available_stock'] ?? $model['stock_info'][0]['current_stock'] ?? $model['stock'] ?? $model['normal_stock'] ?? 0;
                                        $variantName = $item['item_name'] . ' - ' . ($model['model_name'] ?? 'Varian');

                                        $finalImageUrl = $imageUrl;
                                        if (!empty($model['model_name'])) {
                                            $options = explode(',', $model['model_name']);
                                            foreach ($options as $opt) {
                                                $opt = trim($opt);
                                                if (isset($variantImages[$opt])) {
                                                    $finalImageUrl = $variantImages[$opt];
                                                    break;
                                                }
                                            }
                                        }

                                        MarketplaceProduct::updateOrCreate(
                                            [
                                                'store_id' => $store->id,
                                                'marketplace_product_id' => (string) $item['item_id'],
                                                'marketplace_variant_id' => (string) ($model['model_id'] ?? $model['id'] ?? ''),
                                            ],
                                            [
                                                'marketplace_sku' => $model['model_sku'] ?? null,
                                                'name' => $variantName,
                                                'description' => $description,
                                                'price' => $price,
                                                'stock' => $stock,
                                                'image_url' => $finalImageUrl,
                                                'is_pre_order' => $isShopeePo,
                                                'last_synced_at' => now(),
                                            ]
                                        );

                                        $totalSynced++;
                                    }
                                }
                            } catch (\Throwable $e) {
                                Log::warning("[Shopee] Gagal ambil model untuk item {$item['item_id']}", ['error' => $e->getMessage()]);
                            }

                            // Jika model kosong atau gagal diambil, simpan produk induk agar tidak hilang
                            if (empty($models)) {
                                $price = $item['price_info'][0]['current_price'] ?? $item['price_info'][0]['original_price'] ?? $item['price'] ?? 0;
                                $stock = $item['stock_info_v2']['summary_info']['total_available_stock'] ?? $item['stock_info'][0]['current_stock'] ?? $item['stock'] ?? 0;

                                MarketplaceProduct::updateOrCreate(
                                    [
                                        'store_id' => $store->id,
                                        'marketplace_product_id' => (string) $item['item_id'],
                                        'marketplace_variant_id' => null,
                                    ],
                                    [
                                        'marketplace_sku' => $item['item_sku'] ?? null,
                                        'name' => $item['item_name'],
                                        'description' => $description,
                                        'price' => $price,
                                        'stock' => $stock,
                                        'image_url' => $imageUrl,
                                        'is_pre_order' => $isShopeePo,
                                        'last_synced_at' => now(),
                                    ]
                                );

                                $totalSynced++;
                            }
                        } else {
                            // Produk tanpa varian
                            $price = $item['price_info'][0]['current_price'] ?? $item['price_info'][0]['original_price'] ?? $item['price'] ?? 0;
                            $stock = $item['stock_info_v2']['summary_info']['total_available_stock'] ?? $item['stock_info'][0]['current_stock'] ?? $item['stock'] ?? 0;

                            MarketplaceProduct::updateOrCreate(
                                [
                                    'store_id' => $store->id,
                                    'marketplace_product_id' => (string) $item['item_id'],
                                    'marketplace_variant_id' => null,
                                ],
                                [
                                    'marketplace_sku' => $item['item_sku'] ?? null,
                                    'name' => $item['item_name'],
                                    'description' => $description,
                                    'price' => $price,
                                    'stock' => $stock,
                                    'image_url' => $imageUrl,
                                    'is_pre_order' => $isShopeePo,
                                    'last_synced_at' => now(),
                                ]
                            );

                            $totalSynced++;
                        }
                    }
                }

                $hasMore = !empty($listData['has_next_page']) || !empty($listData['has_more']);
                $offset = isset($listData['next_offset']) ? (int) $listData['next_offset'] : ($offset + $pageSize);
            }

            // Bersihkan produk lama hanya jika sinkronisasi berhasil mendapatkan produk
            if ($totalSynced > 0) {
                MarketplaceProduct::where('store_id', $store->id)
                    ->where(function ($q) use ($syncStartTime) {
                        $q->whereNull('last_synced_at')
                          ->orWhere('last_synced_at', '<', $syncStartTime);
                    })
                    ->delete();
            }

            Log::info("[Shopee] Berhasil menarik {$totalSynced} produk dari toko {$store->store_name}.");

        } catch (\Throwable $e) {
            Log::error('[Shopee] Gagal sync produk Shopee', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
