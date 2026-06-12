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
            ->where('master_product_id', $this->masterProductId)
            ->get();

        foreach ($marketplaceProducts as $mp) {
            try {
                if ($mp->store->status !== 'connected' || empty($mp->store->access_token)) {
                    continue;
                }

                if ($mp->store->channel->code === 'shopee') {
                    $shopeeService->updatePrice(
                        $mp->store->access_token,
                        (int) $mp->store->marketplace_store_id,
                        (int) $mp->marketplace_product_id,
                        $this->newPrice,
                        $mp->marketplace_variant_id
                    );
                    Log::info("[Shopee] Berhasil update harga untuk MP Product ID: {$mp->id} menjadi {$this->newPrice}");
                } elseif ($mp->store->channel->code === 'tiktok') {
                    $tiktokService = app(\App\Services\TiktokService::class);
                    $tiktokService->updatePrice(
                        $mp->store->access_token,
                        $mp->store->shop_cipher,
                        $mp->marketplace_product_id,
                        $mp->marketplace_variant_id,
                        $this->newPrice
                    );
                    Log::info("[TikTok] Berhasil update harga untuk MP Product ID: {$mp->id} menjadi {$this->newPrice}");
                } elseif ($mp->store->channel->code === 'tokopedia') {
                    $tokopediaService = app(\App\Services\TokopediaService::class);
                    $tokopediaService->updatePrice(
                        $mp->store->access_token,
                        $mp->store->marketplace_store_id,
                        $mp->marketplace_product_id,
                        $mp->marketplace_variant_id,
                        $this->newPrice
                    );
                    Log::info("[Tokopedia] Berhasil update harga untuk MP Product ID: {$mp->id} menjadi {$this->newPrice}");
                }

                // Update local price in marketplace_products table
                $mp->update([
                    'price' => $this->newPrice,
                    'last_synced_at' => now()
                ]);

            } catch (\Exception $e) {
                Log::error("[Marketplace] Gagal update harga untuk MP Product ID: {$mp->id}. Error: " . $e->getMessage());
            }
        }
    }
}
