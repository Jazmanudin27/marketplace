<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\MarketplaceProduct;
use App\Services\ShopeeService;
use Illuminate\Support\Facades\Log;

class SyncShopeeProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:sync-products 
                            {store? : ID Toko Shopee (Opsional, jika kosong semua toko)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menarik dan menyinkronkan seluruh produk dari Shopee ke tabel marketplace_products';

    /**
     * Execute the console command.
     */
    public function handle(ShopeeService $shopee)
    {
        $storeArg = $this->argument('store');

        $query = Store::whereHas('channel', function ($q) {
            $q->where('code', 'shopee');
        })->where('status', '!=', 'disconnected');

        if ($storeArg) {
            $query->where('id', (int) $storeArg);
        }

        $stores = $query->get();

        if ($stores->isEmpty()) {
            $this->error('Tidak ditemukan toko Shopee aktif' . ($storeArg ? " dengan ID #{$storeArg}." : '.'));
            return 1;
        }

        $this->info("=== MEMULAI SINKRONISASI PRODUK SHOPEE ===");
        $this->info("Ditemukan {$stores->count()} toko untuk diproses.\n");

        foreach ($stores as $store) {
            $this->info("▶ Sinkronisasi Toko #{$store->id}: {$store->store_name} (Shopee)...");

            try {
                $shopId = (int) $store->marketplace_store_id;
                $accessToken = $store->getValidAccessToken();

                $offset = 0;
                $pageSize = 50;
                $hasMore = true;
                $totalSynced = 0;
                $syncStartTime = now();

                while ($hasMore) {
                    $listData = $shopee->getItemList($accessToken, $shopId, $offset, $pageSize);
                    $items = $listData['item'] ?? $listData['item_list'] ?? [];

                    if (empty($items)) {
                        break;
                    }

                    $itemIds = collect($items)->pluck('item_id')->filter()->values()->toArray();
                    if (empty($itemIds)) {
                        break;
                    }

                    $chunks = array_chunk($itemIds, 50);

                    foreach ($chunks as $chunkItemIds) {
                        $infoData = $shopee->getItemBaseInfo($accessToken, $shopId, $chunkItemIds);
                        $itemList = $infoData['item_list'] ?? $infoData['item'] ?? [];

                        foreach ($itemList as $item) {
                            $imageUrl = $item['image']['image_url_list'][0] ?? null;
                            $isShopeePo = !empty($item['pre_order']['is_pre_order']);
                            $description = $item['description'] ?? $item['description_info']['extended_description'] ?? $item['description_info']['description'] ?? null;

                            if (!empty($item['has_model'])) {
                                $models = [];
                                try {
                                    $modelData = $shopee->getModelList($accessToken, $shopId, (int) $item['item_id']);
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
                                                $modelNameTrim = trim($model['model_name']);
                                                if (isset($variantImages[$modelNameTrim])) {
                                                    $finalImageUrl = $variantImages[$modelNameTrim];
                                                } else {
                                                    foreach ($variantImages as $optKey => $optImg) {
                                                        if (stripos($modelNameTrim, $optKey) !== false) {
                                                            $finalImageUrl = $optImg;
                                                            break;
                                                        }
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
                                    Log::warning("Gagal ambil model untuk item {$item['item_id']}", ['error' => $e->getMessage()]);
                                }

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

                    $hasMore = !empty($listData['has_next_page']);
                    $offset = $listData['next_offset'] ?? ($offset + $pageSize);
                }

                $this->info("  ✔ Selesai! {$totalSynced} item/varian berhasil disinkronkan untuk toko {$store->store_name}.\n");
            } catch (\Throwable $e) {
                $this->error("  ✖ Gagal pada toko {$store->store_name}: " . $e->getMessage() . "\n");
                Log::error("[CLI Sync Shopee Products] Error: " . $e->getMessage(), [
                    'store_id' => $store->id,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("=== SEMUA PROSES SINKRONISASI SHOPEE SELESAI ===");
        return 0;
    }
}
