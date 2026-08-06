<?php

namespace App\Jobs;

use App\Models\MasterProduct;
use App\Models\MarketplaceProduct;
use App\Services\ShopeeService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PushPriceToMarketplaces implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $masterProductId;
    protected $newPrice;

    /**
     * Create a new job instance.
     */
    public function __construct(int $masterProductId, float $newPrice)
    {
        $this->masterProductId = $masterProductId;
        $this->newPrice = $newPrice;
    }

    /**
     * Execute the job.
     */
    public function handle(ShopeeService $shopeeService): void
    {
        $masterProduct = MasterProduct::find($this->masterProductId);
        if (!$masterProduct) return;

        // Cari semua marketplace product yang sinkronisasi harga aktif
        $marketplaceProducts = MarketplaceProduct::with('store.channel')
            ->where('sync_price', true)
            ->where(function($q) use ($masterProduct) {
                $q->where('master_product_id', $this->masterProductId);
                if ($masterProduct->sku) {
                    $q->orWhere('marketplace_sku', $masterProduct->sku);
                }
            })
            ->get();

        foreach ($marketplaceProducts as $mp) {
            try {
                if ($mp->store->status === 'disconnected' || (empty($mp->store->access_token) && empty($mp->store->refresh_token))) {
                    continue;
                }

                $channelCode = strtolower($mp->store->channel->code ?? '');
                $targetPrice = 0;

                if ($channelCode === 'shopee') {
                    $targetPrice = (float) ($masterProduct->shopee_price ?? 0);
                } elseif ($channelCode === 'tiktok') {
                    $targetPrice = (float) ($masterProduct->tiktok_price ?? 0);
                } elseif ($channelCode === 'lazada') {
                    $targetPrice = (float) ($masterProduct->lazada_price ?? 0);
                } elseif ($channelCode === 'tokopedia') {
                    $targetPrice = (float) ($masterProduct->shopee_price ?? 0);
                }

                // HARGA OFFLINE (price / reseller_price) TIDAK BOLEH PUSH KE MARKETPLACE
                // Hanya push jika harga khusus channel tersebut diisi dan > 0
                if ($targetPrice <= 0) {
                    Log::info("[Marketplace Push] Skip update harga untuk channel {$channelCode} pada MP Product ID: {$mp->id} karena harga khusus channel ({$channelCode}_price) belum diisi.");
                    continue;
                }

                $accessToken = $mp->store->getValidAccessToken();

                if ($channelCode === 'shopee') {
                    $shopeeService->updatePrice(
                        $accessToken,
                        (int) $mp->store->marketplace_store_id,
                        (int) $mp->marketplace_product_id,
                        $targetPrice,
                        $mp->marketplace_variant_id
                    );
                    Log::info("[Shopee] Berhasil update harga untuk MP Product ID: {$mp->id} menjadi {$targetPrice}");
                } elseif ($channelCode === 'tiktok') {
                    $tiktokService = app(\App\Services\TiktokService::class);
                    $tiktokService->updatePrice(
                        $accessToken,
                        $mp->store->shop_cipher,
                        $mp->marketplace_product_id,
                        $mp->marketplace_variant_id,
                        $targetPrice
                    );
                    Log::info("[TikTok] Berhasil update harga untuk MP Product ID: {$mp->id} menjadi {$targetPrice}");
                } elseif ($channelCode === 'tokopedia') {
                    $tokopediaService = app(\App\Services\TokopediaService::class);
                    $tokopediaService->updatePrice(
                        $accessToken,
                        $mp->store->marketplace_store_id,
                        $mp->marketplace_product_id,
                        $mp->marketplace_variant_id,
                        $targetPrice
                    );
                    Log::info("[Tokopedia] Berhasil update harga untuk MP Product ID: {$mp->id} menjadi {$targetPrice}");
                } elseif ($channelCode === 'lazada') {
                    $lazadaService = app(\App\Services\LazadaService::class);
                    $lazadaService->updatePrice(
                        $accessToken,
                        $mp->store->marketplace_store_id,
                        $mp->marketplace_product_id,
                        $mp->marketplace_variant_id,
                        $targetPrice
                    );
                    Log::info("[Lazada] Berhasil update harga untuk MP Product ID: {$mp->id} menjadi {$targetPrice}");
                }

                // Update local price in marketplace_products table
                $mp->update([
                    'price' => $targetPrice,
                    'last_synced_at' => now()
                ]);

            } catch (\Exception $e) {
                Log::error("[Marketplace] Gagal update harga untuk MP Product ID: {$mp->id}. Error: " . $e->getMessage());
            }
        }
    }
}
