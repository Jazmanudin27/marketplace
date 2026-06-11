<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$shopee = app(\App\Services\ShopeeService::class);

// Ambil Store ID:13 yang berhasil refresh
$store = \App\Models\Store::find(13);
if (!$store) { echo "Store 13 not found\n"; exit; }

echo "Store: " . $store->store_name . PHP_EOL;
echo "ShopID: " . $store->marketplace_store_id . PHP_EOL;
echo "Token expires: " . $store->token_expires_at . PHP_EOL;

$accessToken = $store->getAttributes()['access_token'];
$shopId = (int) $store->marketplace_store_id;

echo "Token (first 30): " . substr($accessToken, 0, 30) . "..." . PHP_EOL;
echo PHP_EOL;

// Test getCategoryTree
try {
    echo "Calling getCategoryTree..." . PHP_EOL;
    $cats = $shopee->getCategoryTree($accessToken, $shopId, 'id');
    echo "SUCCESS! Total categories: " . count($cats) . PHP_EOL;
} catch (\Throwable $e) {
    echo "FAILED: " . $e->getMessage() . PHP_EOL;
}

// Juga test store 9
echo PHP_EOL . "=== Testing Store ID:9 ===" . PHP_EOL;
$store9 = \App\Models\Store::find(9);
if ($store9) {
    $token9 = $store9->getAttributes()['access_token'];
    $shopId9 = (int) $store9->marketplace_store_id;
    echo "ShopID: $shopId9" . PHP_EOL;
    echo "Token (first 30): " . substr($token9, 0, 30) . "..." . PHP_EOL;
    try {
        $cats9 = $shopee->getCategoryTree($token9, $shopId9, 'id');
        echo "SUCCESS! Total categories: " . count($cats9) . PHP_EOL;
    } catch (\Throwable $e) {
        echo "FAILED: " . $e->getMessage() . PHP_EOL;
    }
}
