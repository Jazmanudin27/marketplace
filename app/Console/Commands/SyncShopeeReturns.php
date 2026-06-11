<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Jobs\PullReturnsFromShopee;
use Illuminate\Support\Facades\Log;

class SyncShopeeReturns extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shopee:sync-returns';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Secara otomatis menarik data retur terbaru dari semua toko Shopee';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai sinkronisasi retur otomatis dari Shopee...');
        Log::info('[Cron] Memulai shopee:sync-returns');

        $stores = Store::whereHas('channel', function($q) {
            $q->where('code', 'shopee');
        })->get();

        if ($stores->isEmpty()) {
            $this->info('Tidak ada toko Shopee yang terhubung.');
            return;
        }

        // Tarik retur 3 hari terakhir (lebih pendek karena ditarik otomatis sering)
        $timeTo = time();
        $timeFrom = strtotime('-3 days', $timeTo);

        foreach ($stores as $store) {
            $this->info("Mengirim job retur untuk toko: {$store->store_name}");
            PullReturnsFromShopee::dispatch($store, $timeFrom, $timeTo);
        }

        $this->info('Job sinkronisasi retur berhasil dikirim ke antrean.');
        Log::info('[Cron] Selesai shopee:sync-returns. Job telah di-dispatch.');
    }
}
