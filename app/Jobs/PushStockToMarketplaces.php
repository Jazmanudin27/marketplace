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

class PushStockToMarketplaces implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $masterProductId;
    protected $newStock;

    /**
     * Create a new job instance.
     */
    public function __construct(int $masterProductId, int $newStock)
    {
        $this->masterProductId = $masterProductId;
        $this->newStock = $newStock;
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
        $marketplaceProducts = MarketplaceProduct::where(function($q) use ($masterProduct) {
                $q->where('master_product_id', $this->masterProductId);
                if ($masterProduct->sku) {
                    $q->orWhere('marketplace_sku', $masterProduct->sku);
                }
            })
            ->where('sync_stock', true)
            ->with('store.channel')
            ->get();

        foreach ($marketplaceProducts as $mpProduct) {
            try {
                $store = $mpProduct->store;

                if (!$store || $store->status === 'disconnected') {
                    continue;
                }

                $accessToken = $store->getValidAccessToken();

                $safetyStock = (int) ($mpProduct->safety_stock ?? 0);
                $pushedStock = max(0, $masterProduct->stock - $safetyStock);

                if ($store->channel->code === 'shopee') {
                    $shopeeService->updateStock(
                        $accessToken,
                        (int) $store->marketplace_store_id,
                        (int) $mpProduct->marketplace_product_id,
                        $pushedStock,
                        $mpProduct->marketplace_variant_id
                    );

                    // Update local marketplace_products table stock
                    $mpProduct->update([
                        'stock' => $pushedStock,
                        'last_synced_at' => now(),
                    ]);
                    
                    Log::info("Berhasil push stok ke Shopee untuk item {$mpProduct->marketplace_product_id} (Stok master: {$masterProduct->stock}, Safety: {$safetyStock}, Pushed: {$pushedStock})");
                } elseif ($store->channel->code === 'tiktok') {
                    $tiktokService = app(\App\Services\TiktokService::class);
                    $tiktokService->updateStock(
                        $accessToken,
                        $store->shop_cipher,
                        $mpProduct->marketplace_product_id,
                        $mpProduct->marketplace_variant_id,
                        $pushedStock
                    );

                    // Update local marketplace_products table stock
                    $mpProduct->update([
                        'stock' => $pushedStock,
                        'last_synced_at' => now(),
                    ]);
                    
                    Log::info("Berhasil push stok ke TikTok untuk item {$mpProduct->marketplace_product_id} (Stok master: {$masterProduct->stock}, Safety: {$safetyStock}, Pushed: {$pushedStock})");
                } elseif ($store->channel->code === 'tokopedia') {
                    $tokopediaService = app(\App\Services\TokopediaService::class);
                    $tokopediaService->updateStock(
                        $accessToken,
                        $store->marketplace_store_id,
                        $mpProduct->marketplace_product_id,
                        $mpProduct->marketplace_variant_id,
                        $pushedStock
                    );

                    // Update local marketplace_products table stock
                    $mpProduct->update([
                        'stock' => $pushedStock,
                        'last_synced_at' => now(),
                    ]);
                    
                    Log::info("Berhasil push stok ke Tokopedia untuk item {$mpProduct->marketplace_product_id} (Stok master: {$masterProduct->stock}, Safety: {$safetyStock}, Pushed: {$pushedStock})");
                } elseif ($store->channel->code === 'lazada') {
                    $lazadaService = app(\App\Services\LazadaService::class);
                    $lazadaService->updateStock(
                        $accessToken,
                        $store->marketplace_store_id,
                        $mpProduct->marketplace_product_id,
                        $mpProduct->marketplace_variant_id,
                        $pushedStock
                    );

                    // Update local marketplace_products table stock
                    $mpProduct->update([
                        'stock' => $pushedStock,
                        'last_synced_at' => now(),
                    ]);
                    
                    Log::info("Berhasil push stok ke Lazada untuk item {$mpProduct->marketplace_product_id} (Stok master: {$masterProduct->stock}, Safety: {$safetyStock}, Pushed: {$pushedStock})");
                }

                \App\Models\MarketplaceSyncLog::create([
                    'tenant_id' => $store->tenant_id,
                    'marketplace_product_id' => $mpProduct->id,
                    'channel_code' => $store->channel->code,
                    'sku' => $mpProduct->marketplace_sku ?? $masterProduct->sku,
                    'pushed_stock' => $pushedStock,
                    'status' => 'success',
                ]);
                
            } catch (\Exception $e) {
                Log::error("Gagal push stok untuk marketplace product ID {$mpProduct->id}", [
                    'error' => $e->getMessage()
                ]);

                \App\Models\MarketplaceSyncLog::create([
                    'tenant_id' => $masterProduct->tenant_id,
                    'marketplace_product_id' => $mpProduct->id,
                    'channel_code' => isset($mpProduct->store->channel->code) ? $mpProduct->store->channel->code : 'unknown',
                    'sku' => $mpProduct->marketplace_sku ?? $masterProduct->sku,
                    'pushed_stock' => isset($pushedStock) ? $pushedStock : 0,
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                ]);
            }
        }
    }
}
