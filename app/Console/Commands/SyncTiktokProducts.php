<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Jobs\PullProductsFromTiktok;
use App\Services\TiktokService;
use Illuminate\Support\Facades\Log;

class SyncTiktokProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:sync-products 
                            {store? : ID Toko TikTok (Opsional, jika kosong semua toko)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menarik dan menyinkronkan seluruh produk dari TikTok Shop / Tokopedia ke tabel marketplace_products';

    /**
     * Execute the console command.
     */
    public function handle(TiktokService $tiktokService)
    {
        $storeArg = $this->argument('store');

        $query = Store::whereHas('channel', function ($q) {
            $q->whereIn('code', ['tiktok', 'tokopedia']);
        })->where('status', '!=', 'disconnected');

        if ($storeArg) {
            $query->where('id', (int) $storeArg);
        }

        $stores = $query->get();

        if ($stores->isEmpty()) {
            $this->error('Tidak ditemukan toko TikTok/Tokopedia aktif' . ($storeArg ? " dengan ID #{$storeArg}." : '.'));
            return 1;
        }

        $this->info("=== MEMULAI SINKRONISASI PRODUK TIKTOK/TOKOPEDIA ===");
        $this->info("Ditemukan {$stores->count()} toko untuk diproses.\n");

        foreach ($stores as $store) {
            $platform = $store->channel->code === 'tokopedia' ? 'Tokopedia' : 'TikTok Shop';
            $this->info("▶ Sinkronisasi Toko #{$store->id}: {$store->store_name} ({$platform})...");

            try {
                // Eksekusi langsung secara synchronous di CLI tanpa limit waktu
                $job = new PullProductsFromTiktok($store);
                $job->handle($tiktokService);

                $productCount = \App\Models\MarketplaceProduct::where('store_id', $store->id)->count();
                $this->info("  ✔ Berhasil! Total {$productCount} varian/produk tersimpan untuk toko ini.\n");
            } catch (\Throwable $e) {
                $this->error("  ✖ Gagal pada toko {$store->store_name}: " . $e->getMessage() . "\n");
                Log::error("[CLI Sync TikTok Products] Gagal: " . $e->getMessage(), [
                    'store_id' => $store->id,
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->info("=== SEMUA PROSES SINKRONISASI SELESAI ===");
        return 0;
    }
}
