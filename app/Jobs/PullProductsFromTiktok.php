<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\MarketplaceProduct;
use App\Services\TiktokService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullProductsFromTiktok implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $storeId;

    public function __construct(Store $store)
    {
        $this->storeId = $store->id;
    }

    public function handle(TiktokService $tiktokService): void
    {
        // Safely fetch the store — it may have been deleted since the job was queued.
        $store = Store::find($this->storeId);

        if (! $store) {
            Log::warning('[TikTok] PullProductsFromTiktok: Store #' . $this->storeId . ' no longer exists. Discarding job.');
            return;
        }

        $this->store = $store;

        if ($this->store->status === 'disconnected' || (empty($this->store->access_token) && empty($this->store->refresh_token))) {
            Log::warning("[TikTok] Toko {$this->store->store_name} tidak terhubung.");
            return;
        }

        try {
            $accessToken = $this->store->getValidAccessToken();
            $shopCipher = $this->store->shop_cipher;

            if (empty($shopCipher)) {
                Log::warning("[TikTok] shop_cipher kosong untuk toko {$this->store->store_name}.");
                return;
            }

            $pageToken = '';
            $totalSynced = 0;

            do {
                $response = $tiktokService->getProductSearch(
                    $accessToken,
                    $shopCipher,
                    $pageToken
                );

                $products = $response['products'] ?? [];
                
                foreach ($products as $product) {
                    $productId = $product['id'];
                    
                    // Fetch product detail for image and real price
                    $detailData = [];
                    try {
                        $detailData = $tiktokService->getProductDetail($accessToken, $shopCipher, $productId);
                    } catch (\Exception $e) {
                        Log::warning("[TikTok] Gagal mengambil detail produk {$productId}: " . $e->getMessage());
                    }

                    $productName = $detailData['title'] ?? $product['title'];
                    $mainImage = null;
                    if (!empty($detailData['main_images'][0]['urls'][0])) {
                        $mainImage = $detailData['main_images'][0]['urls'][0];
                    }

                    // Ambil deskripsi produk
                    $description = $detailData['description'] ?? null;

                    // Use SKUs from detail data if available, otherwise fallback to search data
                    $skus = $detailData['skus'] ?? $product['skus'] ?? [];

                    if (empty($skus)) {
                        // Jika tidak ada varian/SKU, kita simpan parent saja
                        MarketplaceProduct::updateOrCreate(
                            [
                                'store_id' => $this->store->id,
                                'marketplace_product_id' => $productId,
                                'marketplace_variant_id' => null,
                            ],
                            [
                                'name' => $productName,
                                'description' => $description,
                                'marketplace_sku' => null,
                                'price' => 0, // Harga default jika tidak ada SKU
                                'stock' => 0,
                                'image_url' => $mainImage,
                                'last_synced_at' => now(),
                            ]
                        );
                        $totalSynced++;
                        continue;
                    }

                    // Jika ada varian/SKU
                    foreach ($skus as $sku) {
                        $variantId = $sku['id'];
                        $price = $sku['price']['sale_price'] ?? $sku['price']['tax_exclusive_price'] ?? $sku['price']['original_price'] ?? 0;
                        $stock = $sku['inventory'][0]['quantity'] ?? 0;
                        $skuName = $productName;

                        // Tambahkan nama varian jika ada
                        $salesAttributes = $sku['sales_attributes'] ?? [];
                        if (!empty($salesAttributes)) {
                            $attrNames = array_map(fn($attr) => $attr['value_name'] ?? '', $salesAttributes);
                            $skuName .= ' - ' . implode(', ', array_filter($attrNames));
                        }

                        MarketplaceProduct::updateOrCreate(
                            [
                                'store_id' => $this->store->id,
                                'marketplace_product_id' => $productId,
                                'marketplace_variant_id' => $variantId,
                            ],
                            [
                                'name' => $skuName,
                                'description' => $description,
                                'marketplace_sku' => $sku['seller_sku'] ?: null, // seller_sku might be ""
                                'price' => $price,
                                'stock' => $stock,
                                'image_url' => $mainImage,
                                'last_synced_at' => now(),
                            ]
                        );
                        $totalSynced++;
                    }
                }

                $pageToken = $response['next_page_token'] ?? '';
                $hasMore = !empty($pageToken);
                
            } while ($hasMore);

            Log::info("[TikTok] Berhasil sinkronisasi {$totalSynced} varian/produk untuk toko {$this->store->store_name}");

        } catch (\Exception $e) {
            Log::error("[TikTok] Gagal menarik produk untuk toko {$this->store->store_name}: " . $e->getMessage() . " di " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
