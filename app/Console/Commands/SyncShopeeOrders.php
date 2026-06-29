<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Jobs\PullOrdersFromShopee;
use Illuminate\Support\Facades\Log;

class SyncShopeeOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:sync-orders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Secara otomatis menarik pesanan terbaru dari semua toko Shopee';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi pesanan otomatis dari Shopee...');
        Log::info('[Cron] Memulai shopee:sync-orders');

        $stores = Store::whereHas('channel', function($q) {
            $q->where('code', 'shopee');
        })->get();

        if ($stores->isEmpty()) {
            $this->info('Tidak ada toko Shopee yang terhubung.');
            return;
        }

        // Tarik pesanan 1 hari terakhir (lebih pendek karena ditarik otomatis sering)
        $timeTo = time();
        $timeFrom = strtotime('-1 day', $timeTo);

        foreach ($stores as $store) {
            $this->info("Mengirim job untuk toko: {$store->store_name}");
            PullOrdersFromShopee::dispatch($store, $timeFrom, $timeTo);
        }

        $this->info('Job sinkronisasi pesanan berhasil dikirim ke antrean.');
        Log::info('[Cron] Selesai shopee:sync-orders. Job telah di-dispatch.');
    }
}
