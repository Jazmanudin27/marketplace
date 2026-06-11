<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;
use App\Services\TiktokService;

$store = Store::whereHas('channel', function($q) {
    $q->where('code', 'tiktok');
})->first();

$tiktokService = app(TiktokService::class);
$productId = '1735843179446044440';
$skuId = '1735843157050165016';
$warehouseId = '7479788832461473552';

$accessToken = $store->access_token;
$shopCipher = $store->shop_cipher;

$pathPrice = '/product/202309/products/' . $productId . '/prices/update';
$bodyPrice = [
    'skus' => [
        [
            'id' => $skuId,
            'price' => [
                'sale_price' => '5000001'
            ]
        ]
    ]
];

$queryParamsPrice = [
    'app_key' => config('services.tiktok.app_key'),
    'timestamp' => time(),
    'shop_cipher' => $shopCipher,
];
$bodyJsonPrice = json_encode($bodyPrice);
$strPrice = '';
ksort($queryParamsPrice);
foreach ($queryParamsPrice as $k => $v) { $strPrice .= $k . $v; }
$baseStringPrice = config('services.tiktok.app_secret') . $pathPrice . $strPrice . $bodyJsonPrice . config('services.tiktok.app_secret');
$queryParamsPrice['sign'] = hash_hmac('sha256', $baseStringPrice, config('services.tiktok.app_secret'));
$queryParamsPrice['access_token'] = $accessToken;

$chPrice = curl_init('https://open-api.tiktokglobalshop.com' . $pathPrice . '?' . http_build_query($queryParamsPrice));
curl_setopt($chPrice, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chPrice, CURLOPT_POST, true);
curl_setopt($chPrice, CURLOPT_POSTFIELDS, $bodyJsonPrice);
curl_setopt($chPrice, CURLOPT_HTTPHEADER, [
    'x-tts-access-token: ' . $accessToken,
    'Content-Type: application/json'
]);
echo "PRICE UPDATE: " . curl_exec($chPrice) . "\n";


$pathStock = '/product/202309/products/' . $productId . '/inventory/update';
$bodyStock = [
    'skus' => [
        [
            'id' => $skuId,
            'inventory' => [
                [
                    'quantity' => 2,
                    'warehouse_id' => $warehouseId
                ]
            ]
        ]
    ]
];
$queryParamsStock = [
    'app_key' => config('services.tiktok.app_key'),
    'timestamp' => time(),
    'shop_cipher' => $shopCipher,
];
$bodyJsonStock = json_encode($bodyStock);
$strStock = '';
ksort($queryParamsStock);
foreach ($queryParamsStock as $k => $v) { $strStock .= $k . $v; }
$baseStringStock = config('services.tiktok.app_secret') . $pathStock . $strStock . $bodyJsonStock . config('services.tiktok.app_secret');
$queryParamsStock['sign'] = hash_hmac('sha256', $baseStringStock, config('services.tiktok.app_secret'));
$queryParamsStock['access_token'] = $accessToken;

$chStock = curl_init('https://open-api.tiktokglobalshop.com' . $pathStock . '?' . http_build_query($queryParamsStock));
curl_setopt($chStock, CURLOPT_RETURNTRANSFER, true);
curl_setopt($chStock, CURLOPT_POST, true);
curl_setopt($chStock, CURLOPT_POSTFIELDS, $bodyJsonStock);
curl_setopt($chStock, CURLOPT_HTTPHEADER, [
    'x-tts-access-token: ' . $accessToken,
    'Content-Type: application/json'
]);
echo "STOCK UPDATE: " . curl_exec($chStock) . "\n";
