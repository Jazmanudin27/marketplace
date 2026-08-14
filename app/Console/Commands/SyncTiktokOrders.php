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
    protected $signature = 'tiktok:sync-orders 
                            {--store= : ID Toko TikTok (Opsional, jika kosong semua toko)}
                            {--days=90 : Jumlah hari ke belakang (default 90 hari)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Secara otomatis menarik pesanan TikTok yang belum ada di database ERP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storeId = $this->option('store') ? (int) $this->option('store') : null;
        $days    = max(1, min(90, (int) ($this->option('days') ?? 90)));

        $this->info("Memulai sinkronisasi pesanan TikTok ({$days} hari ke belakang)...");
        Log::info('[Cron] Memulai tiktok:sync-orders');

        $query = Store::whereHas('channel', function($q) {
            $q->whereIn('code', ['tiktok', 'tokopedia']);
        });

        if ($storeId) {
            $query->where('id', $storeId);
        }

        $stores = $query->get();

        if ($stores->isEmpty()) {
            $this->info('Tidak ada toko TikTok/Tokopedia yang sesuai.');
            return 0;
        }

        // Delegasikan ke SyncMissingTiktokOrders agar logika batching kilat dijalankan
        $params = ['--days' => $days];
        if ($storeId) {
            $params['--store'] = $storeId;
        }

        return $this->call('tiktok:sync-missing', $params);
    }
}
