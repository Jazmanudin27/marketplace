<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;

$store = Store::whereHas('channel', function($q) {
    $q->where('code', 'tiktok');
})->first();

$path = '/product/202309/products/1735843179446044440';
$appKey = config('services.tiktok.app_key');
$appSecret = config('services.tiktok.app_secret');
$accessToken = $store->access_token;
$shopCipher = $store->shop_cipher;

$queryParams = [
    'app_key' => $appKey,
    'timestamp' => time(),
    'shop_cipher' => $shopCipher,
];

ksort($queryParams);
$str = '';
foreach ($queryParams as $k => $v) {
    $str .= $k . $v;
}
$baseString = $appSecret . $path . $str . $appSecret;
$sign = hash_hmac('sha256', $baseString, $appSecret);
$queryParams['sign'] = $sign;
$queryParams['access_token'] = $accessToken;

$url = 'https://open-api.tiktokglobalshop.com' . $path . '?' . http_build_query($queryParams);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'x-tts-access-token: ' . $accessToken,
    'Content-Type: application/json'
]);

$result = curl_exec($ch);
echo "DETAIL RESULT:\n$result\n";
