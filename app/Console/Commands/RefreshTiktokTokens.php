<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\TiktokService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RefreshTiktokTokens extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:refresh-tokens';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Refresh TikTok and Tokopedia access tokens that are about to expire';

    /**
     * Execute the console command.
     */
    public function handle(TiktokService $tiktok)
    {
        $this->info('Starting TikTok/Tokopedia token refresh process...');

        // Cari toko TikTok dan Tokopedia yang statusnya connected dan tokennya akan expired dalam 30 menit ke depan
        $stores = Store::whereHas('channel', function ($query) {
            $query->whereIn('code', ['tiktok', 'tokopedia']);
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
                $tokenData = $tiktok->refreshAccessToken($store->refresh_token);

                $store->update([
                    'access_token' => $tokenData['access_token'],
                    'refresh_token' => $tokenData['refresh_token'] ?? $store->refresh_token,
                    'token_expires_at' => date('Y-m-d H:i:s', $tokenData['access_token_expire_in'] ?? (time() + 86400)),
                    'status' => 'connected',
                ]);

                $this->info("✅ Successfully refreshed token for {$store->store_name}");
                Log::info('Auto-refresh TikTok/Tokopedia token successful', ['store_id' => $store->id]);

            } catch (\Throwable $e) {
                $this->error("❌ Failed to refresh token for {$store->store_name}: {$e->getMessage()}");
                Log::error('Auto-refresh TikTok/Tokopedia token failed', [
                    'store_id' => $store->id,
                    'message' => $e->getMessage()
                ]);

                // Jika refresh token gagal (misal expired), ubah status toko menjadi expired
                $store->update(['status' => 'expired']);
            }
        }

        $this->info('Finished TikTok/Tokopedia token refresh process.');
    }
}
