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
    protected $price;

    /**
     * Create a new job instance.
     */
    public function __construct(int $masterProductId, float $price)
    {
        $this->masterProductId = $masterProductId;
        $this->price = $price;
    }

    /**
     * Execute the job.
     */
    public function handle(ShopeeService $shopeeService): void
    {
        $masterProduct = MasterProduct::find($this->masterProductId);
        if (!$masterProduct) {
            return;
        }

        // Get all marketplace products mapped to this master product
        $marketplaceProducts = MarketplaceProduct::where('master_product_id', $this->masterProductId)
            ->where('sync_stock', true)
            ->with('store.channel')
            ->get();

        foreach ($marketplaceProducts as $mpProduct) {
            try {
                $store = $mpProduct->store;

                if (!$store || $store->status !== 'connected') {
                    continue;
                }

                if ($store->channel->code === 'shopee') {
                    $shopeeService->updatePrice(
                        $store->access_token,
                        (int) $store->marketplace_store_id,
                        (int) $mpProduct->marketplace_product_id,
                        $this->price,
                        $mpProduct->marketplace_variant_id
                    );

                    // Update local marketplace_products table price
                    $mpProduct->update([
                        'price' => $this->price,
                        'last_synced_at' => now(),
                    ]);
                    
                    Log::info("Berhasil push harga ke Shopee untuk item {$mpProduct->marketplace_product_id}");
                }
                
                // Tambahkan kondisi untuk Tokopedia/Lazada di sini nanti...
                
            } catch (\Exception $e) {
                Log::error("Gagal push harga untuk marketplace product ID {$mpProduct->id}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
