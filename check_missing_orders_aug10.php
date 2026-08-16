<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use App\Models\Order;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use Illuminate\Support\Facades\DB;

$fromDate = '2026-08-10 00:00:00';
$toDate   = '2026-08-16 23:59:59';
$startTs  = strtotime($fromDate);
$endTs    = strtotime($toDate);

echo "======================================================================\n";
echo "🔍 PENGECEKAN PERSAMAAN PESANAN MARKETPLACE VS ERP (10 - 16 AGUSTUS 2026)\n";
echo "======================================================================\n";
echo "  Periode : 10 Agustus 2026 s/d 16 Agustus 2026\n";
echo "======================================================================\n\n";

$shopeeService = app(ShopeeService::class);
$tiktokService = app(TiktokService::class);

$shopeeStores = Store::whereHas('channel', fn($q) => $q->where('code', 'shopee'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token')
    ->get();

$tiktokStores = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token')
    ->get();

$grandTotalLive = 0;
$grandTotalInDb = 0;
$grandTotalMissing = 0;
$missingOrderList = [];

// 1. CEK SHOPEE STORES
echo "🛒 1. MENGECEK TOKO SHOPEE...\n";
echo "----------------------------------------------------------------------\n";

foreach ($shopeeStores as $store) {
    echo "  Toko: [{$store->name}] (ID #{$store->id})\n";
    $accessToken = $store->getValidAccessToken();
    $shopId = (int) $store->shop_id;
    
    if (!$accessToken || !$shopId) {
        echo "   ⚠️ Token / Shop ID tidak valid.\n";
        continue;
    }

    $storeLiveCount = 0;
    $storeMissing = [];
    
    // Chunk per 1 hari agar API tidak timeout
    $chunkStep = 86400;
    for ($curStart = $startTs; $curStart < $endTs; $curStart += $chunkStep) {
        $curEnd = min($curStart + $chunkStep - 1, $endTs);
        $cursor = '';
        
        do {
            try {
                $resp = $shopeeService->getOrderList($accessToken, $shopId, $curStart, $curEnd, 'create_time', $cursor);
                $ordersList = $resp['order_list'] ?? [];
                
                foreach ($ordersList as $o) {
                    $orderSn = $o['order_sn'] ?? null;
                    if (!$orderSn) continue;
                    
                    $storeLiveCount++;
                    $exists = Order::where('order_marketplace_id', $orderSn)->exists();
                    if (!$exists) {
                        $storeMissing[] = $orderSn;
                    }
                }
                
                $cursor = $resp['next_cursor'] ?? '';
                $hasMore = !empty($resp['more']) || !empty($cursor);
            } catch (\Exception $e) {
                echo "   ⚠️ Error fetching chunk: " . $e->getMessage() . "\n";
                $hasMore = false;
            }
        } while ($hasMore);
    }

    $missingCount = count($storeMissing);
    $inDbCount = $storeLiveCount - $missingCount;
    
    $grandTotalLive += $storeLiveCount;
    $grandTotalInDb += $inDbCount;
    $grandTotalMissing += $missingCount;

    echo "   • Live di API Shopee : {$storeLiveCount} order\n";
    echo "   • Sudah Ada di ERP   : {$inDbCount} order\n";
    echo "   • Belum Masuk ERP    : " . ($missingCount > 0 ? "⚠️ {$missingCount} order" : "✅ 0 (Lengkap!)") . "\n";

    if ($missingCount > 0) {
        foreach ($storeMissing as $mId) {
            $missingOrderList[] = ['channel' => 'Shopee', 'store' => $store->name, 'id' => $mId];
        }
    }
    echo "\n";
}

// 2. CEK TIKTOK STORES
echo "🎵 2. MENGECEK TOKO TIKTOK SHOP...\n";
echo "----------------------------------------------------------------------\n";

foreach ($tiktokStores as $store) {
    echo "  Toko: [{$store->name}] (ID #{$store->id})\n";
    $accessToken = $store->getValidAccessToken();
    $shopCipher = $store->shop_cipher;

    if (!$accessToken || !$shopCipher) {
        echo "   ⚠️ Token / Shop Cipher tidak valid.\n";
        continue;
    }

    $storeLiveCount = 0;
    $storeMissing = [];

    $chunkStep = 86400;
    for ($curStart = $startTs; $curStart < $endTs; $curStart += $chunkStep) {
        $curEnd = min($curStart + $chunkStep - 1, $endTs);
        $cursor = '';

        do {
            try {
                $resp = $tiktokService->getOrderList($accessToken, $shopCipher, $curStart, $curEnd, $cursor);
                $ordersList = $resp['orders'] ?? $resp['order_list'] ?? [];

                foreach ($ordersList as $o) {
                    $orderId = $o['id'] ?? $o['order_id'] ?? null;
                    if (!$orderId) continue;

                    $storeLiveCount++;
                    $exists = Order::where('order_marketplace_id', $orderId)->exists();
                    if (!$exists) {
                        $storeMissing[] = $orderId;
                    }
                }

                $cursor = $resp['next_cursor'] ?? $resp['next_page_token'] ?? '';
                $hasMore = !empty($cursor);
            } catch (\Exception $e) {
                echo "   ⚠️ Error fetching chunk: " . $e->getMessage() . "\n";
                $hasMore = false;
            }
        } while ($hasMore);
    }

    $missingCount = count($storeMissing);
    $inDbCount = $storeLiveCount - $missingCount;

    $grandTotalLive += $storeLiveCount;
    $grandTotalInDb += $inDbCount;
    $grandTotalMissing += $missingCount;

    echo "   • Live di API TikTok : {$storeLiveCount} order\n";
    echo "   • Sudah Ada di ERP   : {$inDbCount} order\n";
    echo "   • Belum Masuk ERP    : " . ($missingCount > 0 ? "⚠️ {$missingCount} order" : "✅ 0 (Lengkap!)") . "\n";

    if ($missingCount > 0) {
        foreach ($storeMissing as $mId) {
            $missingOrderList[] = ['channel' => 'TikTok', 'store' => $store->name, 'id' => $mId];
        }
    }
    echo "\n";
}

echo "======================================================================\n";
echo "📊 RINGKASAN AUDIT PENGECEKAN PESANAN (10 - 16 AGUSTUS 2026)\n";
echo "======================================================================\n";
echo "  • Total Order di Marketplace Live : {$grandTotalLive}\n";
echo "  • Total Order Sudah Masuk ERP    : {$grandTotalInDb}\n";
echo "  • Total Order BELUM Masuk ERP     : " . ($grandTotalMissing > 0 ? "⚠️ {$grandTotalMissing} ORDER MISSING" : "✅ 0 (SEMUA ORDER LENGKAP MASUK ERP!)") . "\n";
echo "======================================================================\n";

if ($grandTotalMissing > 0) {
    echo "\n📌 DAFTAR ORDER MARKETPLACE YANG BELUM MASUK ERP:\n";
    foreach ($missingOrderList as $idx => $m) {
        echo "  " . ($idx + 1) . ". [{$m['channel']}] {$m['store']} -> Order ID: {$m['id']}\n";
    }
    echo "\n💡 PETUNJUK: Untuk menarik seluruh order yang belum masuk tersebut secara otomatis, jalankan:\n";
    echo "   php artisan shopee:sync-missing --from=2026-08-10 --to=2026-08-16\n";
    echo "   php artisan tiktok:sync-missing --from=2026-08-10 --to=2026-08-16\n";
}
