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

echo "======================================================================\n";
echo "  PULL & PERBAIKAN TOTAL ORDERAN MARKETPLACE (SHOPEE & TIKTOK SHOP)\n";
echo "======================================================================\n\n";

$channels = DB::table('channels')->get();
echo "DAFTAR CHANNEL DI DATABASE:\n";
foreach ($channels as $c) {
    echo "  Channel ID #{$c->id}: Code = '{$c->code}', Name = '{$c->name}'\n";
}
echo "\n";

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
        echo "   🚀 Memulai Penarikan & Pembaruan Order TikTok Shop (15 Hari Terakhir)...\n";
        try {
            // Jalankan penarikan 15 hari ke belakang
            $timeFrom = now()->subDays(15)->timestamp;
            $timeTo = now()->timestamp;
            
            $job = new PullOrdersFromTiktok($store, $timeFrom, $timeTo, false);
            $job->handle(app(\App\Services\TiktokService::class));
            
            $count = DB::table('orders')->where('store_id', $store->id)->count();
            echo "   ✅ SUKSES! Total orderan di toko ini sekarang: {$count} pesanan.\n";
            $tiktokCount++;
        } catch (\Throwable $e) {
            echo "   ❌ Error TikTok Shop Toko #{$store->id}: " . $e->getMessage() . "\n";
        }
    } elseif ($channelCode === 'shopee' || $store->channel_id == 1) {
        echo "   🚀 Memulai Penarikan & Pembaruan Order Shopee (15 Hari Terakhir)...\n";
        try {
            $timeFrom = now()->subDays(15)->timestamp;
            $timeTo = now()->timestamp;

            $job = new PullOrdersFromShopee($store, $timeFrom, $timeTo, false);
            $job->handle(app(\App\Services\ShopeeService::class));

            $count = DB::table('orders')->where('store_id', $store->id)->count();
            echo "   ✅ SUKSES! Total orderan di toko ini sekarang: {$count} pesanan.\n";
            $shopeeCount++;
        } catch (\Throwable $e) {
            echo "   ❌ Error Shopee Toko #{$store->id}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\n======================================================================\n";
echo "  SELESAI! SINKRONISASI PENUH SEMUA TOKO MARKEPLACE TELAH DIPROSES!\n";
echo "======================================================================\n";
