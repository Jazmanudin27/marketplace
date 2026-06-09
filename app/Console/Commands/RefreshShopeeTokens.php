<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\ShopeeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshShopeeTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:refresh-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh Shopee access tokens that are about to expire';

    /**
     * Execute the console command.
     */
    public function handle(ShopeeService $shopee)
    {
        $this->info('Starting Shopee token refresh process...');

        // Cari toko Shopee yang statusnya connected dan tokennya akan expired dalam 30 menit ke depan
        $stores = Store::whereHas('channel', function ($query) {
            $query->where('code', 'shopee');
        })
        ->where('status', 'connected')
        ->whereNotNull('refresh_token')
        ->where('token_expires_at', '<=', now()->addMinutes(30))
        ->get();

        if ($stores->isEmpty()) {
            $this->info('No tokens need to be refreshed right now.');
            return;
        }

        foreach ($stores as $store) {
            $this->info("Refreshing token for store: {$store->store_name} (ID: {$store->id})");

            try {
                $shopId = (int) $store->marketplace_store_id;
                $tokenData = $shopee->refreshAccessToken($store->refresh_token, $shopId);

                $store->update([
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? $store->refresh_token,
                    'token_expires_at' => now()->addSeconds($tokenData['expire_in'] ?? 3600),
                    'status' => 'connected',
                ]);

                $this->info("✅ Successfully refreshed token for {$store->store_name}");
                Log::info('Auto-refresh Shopee token successful', ['store_id' => $store->id]);

            } catch (\Throwable $e) {
                $this->error("❌ Failed to refresh token for {$store->store_name}: {$e->getMessage()}");
                Log::error('Auto-refresh Shopee token failed', [
                    'store_id' => $store->id,
                    'message' => $e->getMessage()
                ]);

                // Jika refresh token gagal (misal expired), ubah status toko menjadi expired
                $store->update(['status' => 'expired']);
            }
        }

        $this->info('Finished Shopee token refresh process.');
    }
}
