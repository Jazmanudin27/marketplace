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
    protected $signature = 'shopee:sync-orders 
                            {--store= : ID Toko Shopee (Opsional, jika kosong semua toko)}
                            {--days=90 : Jumlah hari ke belakang (default 90 hari)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Secara otomatis menarik pesanan Shopee yang belum ada atau tertinggal di database ERP';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storeId = $this->option('store') ? (int) $this->option('store') : null;
        $days    = max(1, min(90, (int) ($this->option('days') ?? 90)));

        $this->info("Memulai sinkronisasi pesanan Shopee ({$days} hari ke belakang)...");
        Log::info('[Cron] Memulai shopee:sync-orders');

        $query = Store::whereHas('channel', function($q) {
            $q->where('code', 'shopee');
        });

        if ($storeId) {
            $query->where('id', $storeId);
        }

        $stores = $query->get();

        if ($stores->isEmpty()) {
            $this->info('Tidak ada toko Shopee yang sesuai.');
            return 0;
        }

        // Delegasikan ke SyncMissingShopeeOrders agar logika batching kilat dijalankan
        $params = ['--days' => $days];
        if ($storeId) {
            $params['--store'] = $storeId;
        }

        return $this->call('shopee:sync-missing', $params);
    }
}
