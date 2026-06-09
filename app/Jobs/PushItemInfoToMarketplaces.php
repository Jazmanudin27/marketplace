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

class PushItemInfoToMarketplaces implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $masterProductId;
    protected $data;

    /**
     * Create a new job instance.
     */
    public function __construct(int $masterProductId, array $data)
    {
        $this->masterProductId = $masterProductId;
        $this->data = $data; // Data berisi name, description, weight
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
        // Note: For basic info like description/weight, we only need to push ONCE per marketplace_product_id.
        // If a product has multiple variants in ERP, they all map to the same marketplace_product_id.
        // We will group by marketplace_product_id to avoid duplicate API calls for the same item.
        $marketplaceProducts = MarketplaceProduct::where('master_product_id', $this->masterProductId)
            ->with('store.channel')
            ->get()
            ->unique('marketplace_product_id');

        foreach ($marketplaceProducts as $mpProduct) {
            try {
                $store = $mpProduct->store;

                if (!$store || $store->status !== 'connected') {
                    continue;
                }

                if ($store->channel->code === 'shopee') {
                    $shopeeService->updateItemBaseInfo(
                        $store->access_token,
                        (int) $store->marketplace_store_id,
                        (int) $mpProduct->marketplace_product_id,
                        $this->data
                    );

                    // Update nama di tabel marketplace_products juga (hanya jika item utama)
                    if (isset($this->data['name']) && empty($mpProduct->marketplace_variant_id)) {
                        MarketplaceProduct::where('marketplace_product_id', $mpProduct->marketplace_product_id)
                            ->whereNull('marketplace_variant_id')
                            ->update([
                                'name' => $this->data['name'],
                                'last_synced_at' => now(),
                            ]);
                    }
                    
                    Log::info("Berhasil push info produk (PIM) ke Shopee untuk item {$mpProduct->marketplace_product_id}");
                }
                
            } catch (\Exception $e) {
                Log::error("Gagal push info produk (PIM) untuk marketplace product ID {$mpProduct->id}", [
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
}
