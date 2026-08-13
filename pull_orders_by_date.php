<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;
use App\Jobs\PullOrdersFromShopee;
use App\Jobs\PullOrdersFromTiktok;

$startDateInput = $argv[1] ?? '2026-08-01';
$endDateInput   = $argv[2] ?? '2026-08-02';

$timeFrom = strtotime($startDateInput . ' 00:00:00');
$timeTo   = strtotime($endDateInput . ' 23:59:59');

if (!$timeFrom || !$timeTo) {
    echo "❌ Format tanggal salah. Gunakan format YYYY-MM-DD (Contoh: php pull_orders_by_date.php 2026-08-01 2026-08-02)\n";
    exit;
}

echo "========================================================================\n";
echo "PENARIKAN PESANAN MARKETPLACE BERDASARKAN RENTANG TANGGAL\n";
echo "Periode Tanggal: " . date('Y-m-d H:i:s', $timeFrom) . " s/d " . date('Y-m-d H:i:s', $timeTo) . "\n";
echo "========================================================================\n\n";

$stores = Store::where('is_active', true)
    ->whereHas('channel', function($q) {
        $q->whereIn('code', ['shopee', 'tiktok', 'tokopedia']);
    })->get();

echo "Menemukan " . $stores->count() . " toko online terhubung.\n\n";

foreach ($stores as $store) {
    $channelCode = strtolower($store->channel->code ?? '');
    echo "📌 Memproses Penarikan untuk Toko: {$store->store_name} ({$store->channel->name})... ";

    try {
        if (str_contains($channelCode, 'shopee')) {
            $job = new PullOrdersFromShopee($store, $timeFrom, $timeTo);
            $job->handle();
            echo "✅ SELESAI (Shopee)\n";
        } elseif (str_contains($channelCode, 'tiktok') || str_contains($channelCode, 'tokopedia')) {
            $job = new PullOrdersFromTiktok($store, $timeFrom, $timeTo);
            $job->handle();
            echo "✅ SELESAI (TikTok)\n";
        }
    } catch (\Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
}

echo "\n========================================================================\n";
echo "✨ SELURUH PESANAN PERIODE TANGGAL {$startDateInput} s/d {$endDateInput} BERHASIL DITARIK KE ERP!\n";
echo "========================================================================\n";
