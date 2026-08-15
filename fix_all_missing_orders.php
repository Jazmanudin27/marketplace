<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use App\Models\Channel;
use App\Jobs\PullOrdersFromTiktok;
use App\Jobs\PullOrdersFromShopee;
use Illuminate\Support\Facades\DB;

$fromDate = '2026-08-01 00:00:00';
$toDate   = '2026-08-16 23:59:59';

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--from=')) {
        $fromDate = trim(explode('=', $arg)[1]) . ' 00:00:00';
    }
    if (str_starts_with($arg, '--to=')) {
        $toDate = trim(explode('=', $arg)[1]) . ' 23:59:59';
    }
}

$timeFrom = strtotime($fromDate);
$timeTo   = strtotime($toDate);

echo "======================================================================\n";
echo "  PENARIKAN KILAT ORDER MARKETPLACE PERIODE BULAN AGUSTUS 2026\n";
echo "======================================================================\n";
echo "  Rentang Waktu : " . date('Y-m-d H:i:s', $timeFrom) . " s/d " . date('Y-m-d H:i:s', $timeTo) . "\n";
echo "======================================================================\n\n";

$activeStores = Store::where('status', 'connected')->get();
echo "Ditemukan " . $activeStores->count() . " Toko Berstatus CONNECTED untuk ditarik orderannya:\n\n";

$shopeeCount = 0;
$tiktokCount = 0;

foreach ($activeStores as $store) {
    $channelCode = strtolower($store->channel->code ?? '');
    echo "----------------------------------------------------------------------\n";
    echo "🏬 TOKO: #{$store->id} - {$store->name} (Tenant #{$store->tenant_id})\n";
    echo "   Channel: {$channelCode} (Channel ID #{$store->channel_id})\n";

    if (in_array($channelCode, ['tiktok', 'tiktok_shop', 'tokopedia']) || $store->channel_id == 3) {
        echo "   🚀 Memulai Penarikan & Pembaruan Order TikTok Shop (Agustus 2026)...\n";
        try {
            $job = new PullOrdersFromTiktok($store, $timeFrom, $timeTo, false);
            $job->handle(app(\App\Services\TiktokService::class));
            
            $count = DB::table('orders')
                ->where('store_id', $store->id)
                ->whereBetween('order_date', [$fromDate, $toDate])
                ->count();
            echo "   ✅ SUKSES! Total orderan di toko ini pada 1-16 Agustus: {$count} pesanan.\n";
            $tiktokCount++;
        } catch (\Throwable $e) {
            echo "   ❌ Error TikTok Shop Toko #{$store->id}: " . $e->getMessage() . "\n";
        }
    } elseif ($channelCode === 'shopee' || $store->channel_id == 1) {
        echo "   🚀 Memulai Penarikan & Pembaruan Order Shopee (Agustus 2026)...\n";
        try {
            $job = new PullOrdersFromShopee($store, $timeFrom, $timeTo, false);
            $job->handle(app(\App\Services\ShopeeService::class));

            $count = DB::table('orders')
                ->where('store_id', $store->id)
                ->whereBetween('order_date', [$fromDate, $toDate])
                ->count();
            echo "   ✅ SUKSES! Total orderan di toko ini pada 1-16 Agustus: {$count} pesanan.\n";
            $shopeeCount++;
        } catch (\Throwable $e) {
            echo "   ❌ Error Shopee Toko #{$store->id}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n======================================================================\n";
echo "  SELESAI! SINKRONISASI PENUH PERIODE AGUSTUS 2026 TELAH DIPROSES!\n";
echo "======================================================================\n";
