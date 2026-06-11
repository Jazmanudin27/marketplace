<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;

$store = Store::whereHas('channel', function($q) {
    $q->where('code', 'tiktok');
})->first();

$productId = '1735843179446044440';
$skuId = '1735843157050165016';

$path = '/product/202309/products/' . $productId . '/prices/update';
        
$body1 = [
    'skus' => [
        [
            'id' => $skuId,
            'price' => [
                'currency' => 'IDR',
                'amount' => '5000000'
            ]
        ]
    ]
];

$body2 = [
    'skus' => [
        [
            'id' => $skuId,
            'price' => [
                'amount' => '5000000'
            ]
        ]
    ]
];

function testApi($body, $path, $store) {
    $queryParams = [
        'app_key' => config('services.tiktok.app_key'),
        'timestamp' => time(),
        'shop_cipher' => $store->shop_cipher,
    ];

    $bodyJson = json_encode($body);
    ksort($queryParams);
    $str = '';
    foreach ($queryParams as $k => $v) { $str .= $k . $v; }
    $baseString = config('services.tiktok.app_secret') . $path . $str . $bodyJson . config('services.tiktok.app_secret');
    $sign = hash_hmac('sha256', $baseString, config('services.tiktok.app_secret'));
    $queryParams['sign'] = $sign;
    $queryParams['access_token'] = $store->access_token;

    $ch = curl_init('https://open-api.tiktokglobalshop.com' . $path . '?' . http_build_query($queryParams));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-tts-access-token: ' . $store->access_token,
        'Content-Type: application/json'
    ]);
    return curl_exec($ch);
}

echo "PRICE UPDATE 1: " . testApi($body1, $path, $store) . "\n";
echo "PRICE UPDATE 2: " . testApi($body2, $path, $store) . "\n";
