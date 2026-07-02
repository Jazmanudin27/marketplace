<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use App\Services\ShopeeService;
use App\Services\TiktokService;

echo "=== 1. UJI COBA API RETUR SHOPEE ===\n";
$shopeeStores = Store::whereHas('channel', function($q) { $q->where('code', 'shopee'); })
    ->where('status', 'connected')
    ->get();

foreach ($shopeeStores as $store) {
    echo "Mencoba Toko Shopee: {$store->store_name} (ID: {$store->id})...\n";
    try {
        $shopee = app(ShopeeService::class);
        $accessToken = $store->getValidAccessToken();
        
        $timeFrom = now()->subDays(15)->timestamp;
        $timeTo = now()->timestamp;
        
        echo "Mengirim request ke Shopee getReturnList (15 hari terakhir)...\n";
        $response = $shopee->getReturnList(
            $accessToken,
            (int) $store->marketplace_store_id,
            0,
            50,
            $timeFrom,
            $timeTo
        );
        
        echo "Response raw/keys: " . implode(', ', array_keys($response)) . "\n";
        if (isset($response['return'])) {
            echo "Ditemukan " . count($response['return']) . " data retur di Shopee.\n";
            foreach ($response['return'] as $index => $ret) {
                echo " - Retur #" . ($index + 1) . ": SN " . $ret['return_sn'] . " | Order SN: " . ($ret['order_sn'] ?? 'N/A') . " | Status: " . ($ret['status'] ?? 'N/A') . "\n";
            }
        } else {
            echo "Format response Shopee tidak memiliki key 'return'. Response: " . json_encode($response) . "\n";
        }
    } catch (\Throwable $e) {
        echo "❌ GAGAL: " . $e->getMessage() . "\n";
    }
    echo "--------------------------------------------------\n";
}

echo "\n=== 2. UJI COBA API RETUR TIKTOK ===\n";
$tiktokStores = Store::whereHas('channel', function($q) { $q->where('code', 'tiktok'); })
    ->where('status', 'connected')
    ->get();

foreach ($tiktokStores as $store) {
    echo "Mencoba Toko TikTok: {$store->store_name} (ID: {$store->id})...\n";
    try {
        $tiktok = app(TiktokService::class);
        $accessToken = $store->getValidAccessToken();
        $shopCipher = $store->shop_cipher;
        
        if (empty($shopCipher)) {
            echo "❌ GAGAL: shop_cipher kosong!\n";
            continue;
        }
        
        echo "Mengirim request ke TikTok getReturnList...\n";
        $response = $tiktok->getReturnList($accessToken, $shopCipher);
        
        echo "Response raw/keys: " . implode(', ', array_keys($response)) . "\n";
        if (isset($response['returns'])) {
            echo "Ditemukan " . count($response['returns']) . " data retur di TikTok.\n";
            foreach ($response['returns'] as $index => $ret) {
                echo " - Retur #" . ($index + 1) . ": ID " . $ret['return_id'] . " | Order ID: " . ($ret['order_id'] ?? 'N/A') . " | Status: " . ($ret['status'] ?? 'N/A') . "\n";
            }
        } else {
            echo "Format response TikTok tidak memiliki key 'returns'. Response: " . json_encode($response) . "\n";
        }
    } catch (\Throwable $e) {
        echo "❌ GAGAL: " . $e->getMessage() . "\n";
    }
    echo "--------------------------------------------------\n";
}
