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
    protected $signature = 'shopee:sync-returns {--days=3 : Jumlah hari ke belakang untuk dicari (default: 3)}';

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
        $days = (int) ($this->option('days') ?? 3);
        $this->info("Memulai sinkronisasi retur otomatis dari Shopee ({$days} hari ke belakang)...");
        Log::info("[Cron] Memulai shopee:sync-returns dengan days={$days}");

        $stores = Store::whereHas('channel', function($q) {
            $q->where('code', 'shopee');
        })->get();

        if ($stores->isEmpty()) {
            $this->info('Tidak ada toko Shopee yang terhubung.');
            return;
        }

        $timeTo = time();
        $timeFrom = strtotime("-{$days} days", $timeTo);

        foreach ($stores as $store) {
            $this->info("Mengirim job retur untuk toko: {$store->store_name}");
            PullReturnsFromShopee::dispatch($store, $timeFrom, $timeTo);
        }

        $this->info('Job sinkronisasi retur berhasil dikirim ke antrean.');
        Log::info('[Cron] Selesai shopee:sync-returns. Job telah di-dispatch.');
    }
}
