<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;

$orderSn = $argv[1] ?? '260729VW6JUHK2';

echo "========================================================\n";
echo "DEBUG REAL ESCROW SHOPEE API UNTUK ORDER: {$orderSn}\n";
echo "========================================================\n\n";

$shopeeService = app(ShopeeService::class);
$store = Store::where('store_name', 'like', '%Nusantara%Seragam%')->first();

if (!$store) {
    echo "Toko tidak ditemukan.\n";
    exit;
}

try {
    $accessToken = $store->getValidAccessToken();
    $escrowRes = $shopeeService->getEscrowDetail($accessToken, (int)$store->marketplace_store_id, $orderSn);
    
    echo "RAW SHOPEE API RESPONSE:\n";
    print_r($escrowRes);
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
