<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\MarketplaceProduct;
use App\Services\LazadaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullProductsFromLazada implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $storeId;

    public function __construct(Store $store)
    {
        $this->storeId = $store->id;
    }

    public function handle(LazadaService $lazadaService): void
    {
        $store = Store::find($this->storeId);

        if (!$store) {
            Log::warning('[Lazada] PullProductsFromLazada: Store #' . $this->storeId . ' no longer exists.');
            return;
        }

        if ($store->status !== 'connected' || empty($store->access_token)) {
            Log::warning("[Lazada] Toko {$store->store_name} tidak terhubung.");
            return;
        }

        try {
            $accessToken = $store->getValidAccessToken();
            $shopId = $store->marketplace_store_id;

            $response = $lazadaService->getProductSearch(
                $accessToken,
                $shopId,
                $store->tenant_id
            );

            $products = $response['products'] ?? [];
            $totalSynced = 0;

            foreach ($products as $product) {
                $productId = $product['id'];
                $productName = $product['name'];
                $mainImage = $product['image_url'];
                $skus = $product['skus'] ?? [];

                if (empty($skus)) {
                    MarketplaceProduct::updateOrCreate(
                        [
                            'store_id' => $store->id,
                            'marketplace_product_id' => $productId,
                            'marketplace_variant_id' => null,
                        ],
                        [
                            'name' => $productName,
                            'marketplace_sku' => $product['sku'],
                            'price' => $product['price'],
                            'stock' => $product['stock'],
                            'image_url' => $mainImage,
                            'last_synced_at' => now(),
                        ]
                    );
                    $totalSynced++;
                    continue;
                }

                // Jika ada beberapa varian / SKU
                foreach ($skus as $sku) {
                    $variantId = (string) ($sku['SkuId'] ?? '');
                    $sellerSku = $sku['SellerSku'] ?? null;
                    $price = (float) ($sku['price'] ?? 0);
                    // Ambil stock (quantity) secara dinamis
                    $stock = (int) ($sku['quantity'] ?? $sku['Available'] ?? 0);
                    
                    $skuName = $productName;
                    
                    // Coba baca opsi varian jika tersedia
                    $options = [];
                    if (!empty($sku['saleProp'])) {
                        foreach ($sku['saleProp'] as $propKey => $propVal) {
                            $options[] = $propVal;
                        }
                    }
                    if (!empty($options)) {
                        $skuName .= ' - ' . implode(', ', $options);
                    }

                    MarketplaceProduct::updateOrCreate(
                        [
                            'store_id' => $store->id,
                            'marketplace_product_id' => $productId,
                            'marketplace_variant_id' => $variantId ?: null,
                        ],
                        [
                            'name' => $skuName,
                            'marketplace_sku' => $sellerSku ?: null,
                            'price' => $price,
                            'stock' => $stock,
                            'image_url' => $sku['Images'][0] ?? $mainImage,
                            'last_synced_at' => now(),
                        ]
                    );
                    $totalSynced++;
                }
            }

            Log::info("[Lazada] Berhasil sinkronisasi {$totalSynced} produk/varian untuk toko {$store->store_name}");

        } catch (\Exception $e) {
            Log::error("[Lazada] Gagal menarik produk untuk toko {$store->store_name}: " . $e->getMessage());
        }
    }
}
