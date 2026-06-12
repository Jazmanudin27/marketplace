<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\MarketplaceProduct;
use App\Models\MasterProduct;
use App\Services\TokopediaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PullProductsFromTokopedia implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $store;

    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    public function handle(TokopediaService $tokopediaService): void
    {
        if ($this->store->status !== 'connected') {
            Log::warning("[Tokopedia] Toko {$this->store->store_name} tidak terhubung.");
            return;
        }

        try {
            Log::info("[Tokopedia] Memulai penarikan produk untuk toko {$this->store->store_name}");

            // 1. Get Access Token
            $accessToken = $tokopediaService->getAccessToken($this->store->marketplace_store_id);

            // 2. Fetch Products
            $response = $tokopediaService->getProductSearch(
                $accessToken,
                $this->store->marketplace_store_id,
                $this->store->tenant_id
            );

            $products = $response['products'] ?? [];
            $totalSynced = 0;

            foreach ($products as $product) {
                // Find matching Master Product in ERP by SKU
                $masterProduct = null;
                if (!empty($product['sku'])) {
                    $masterProduct = MasterProduct::where('tenant_id', $this->store->tenant_id)
                        ->where('sku', $product['sku'])
                        ->first();
                }

                // Update or Create local Marketplace Product
                MarketplaceProduct::updateOrCreate(
                    [
                        'store_id'               => $this->store->id,
                        'marketplace_product_id' => $product['id'],
                        'marketplace_variant_id' => null, // Tokopedia single product default
                    ],
                    [
                        'master_product_id' => $masterProduct ? $masterProduct->id : null,
                        'name'              => $product['name'],
                        'marketplace_sku'   => $product['sku'] ?: null,
                        'price'             => $product['price'],
                        'stock'             => $product['stock'],
                        'image_url'         => $product['image_url'] ?? null,
                        'last_synced_at'    => now(),
                    ]
                );
                
                $totalSynced++;
            }

            Log::info("[Tokopedia] Berhasil sinkronisasi {$totalSynced} produk untuk toko {$this->store->store_name}");

        } catch (\Exception $e) {
            Log::error("[Tokopedia] Gagal menarik produk untuk toko {$this->store->store_name}: " . $e->getMessage() . " di " . $e->getFile() . ":" . $e->getLine());
        }
    }
}
