<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::first();
$shopee = app(App\Services\ShopeeService::class);

$path = '/api/v2/product/update_price';
$timestamp = time();
$accessToken = $store->access_token;
$shopId = (int)$store->marketplace_store_id;

$sign = $shopee->signShopRequest($path, $timestamp, $accessToken, $shopId);

$queryParams = [
    'partner_id' => (int)env('SHOPEE_PARTNER_ID'),
    'timestamp' => $timestamp,
    'sign' => $sign,
    'access_token' => $accessToken,
    'shop_id' => $shopId,
];

$response = Illuminate\Support\Facades\Http::post('https://openplatform.sandbox.test-stable.shopee.sg' . $path . '?' . http_build_query($queryParams), [
    'item_id' => 844142209,
    'price_list' => [
        [
            'model_id' => 10006273397,
            'original_price' => 85000.00
        ]
    ]
]);

echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
