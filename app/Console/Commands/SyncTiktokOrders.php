<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Jobs\PullOrdersFromTiktok;
use Illuminate\Support\Facades\Log;

class SyncTiktokOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tiktok:sync-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Secara otomatis menarik pesanan terbaru dari semua toko TikTok & Tokopedia';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi pesanan otomatis dari TikTok & Tokopedia...');
        Log::info('[Cron] Memulai tiktok:sync-orders');

        $stores = Store::whereHas('channel', function($q) {
            $q->whereIn('code', ['tiktok', 'tokopedia']);
        })->get();

        if ($stores->isEmpty()) {
            $this->info('Tidak ada toko TikTok/Tokopedia yang terhubung.');
            return;
        }

        // Tarik pesanan 3 hari terakhir (lebih pendek karena ditarik otomatis sering)
        $timeTo = time();
        $timeFrom = strtotime('-3 days', $timeTo);

        foreach ($stores as $store) {
            $this->info("Mengirim job untuk toko: {$store->store_name}");
            PullOrdersFromTiktok::dispatch($store, $timeFrom, $timeTo);
        }

        $this->info('Job sinkronisasi pesanan berhasil dikirim ke antrean.');
        Log::info('[Cron] Selesai tiktok:sync-orders. Job telah di-dispatch.');
    }
}
