<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;
use App\Services\TiktokService;

$store = Store::whereHas('channel', function($q) {
    $q->where('code', 'tiktok');
})->first();

if (!$store) {
    echo "Toko TikTok tidak ditemukan.\n";
    exit;
}

$tiktokService = app(TiktokService::class);

echo "Mencoba ambil produk dari API untuk toko: " . $store->store_name . "\n";

try {
    $path = '/product/202309/products/search';
    $appKey = config('services.tiktok.app_key');
    $appSecret = config('services.tiktok.app_secret');
    $accessToken = $store->access_token;
    $shopCipher = $store->shop_cipher;

    $body = [];
    $bodyJson = json_encode($body);

    $queryParams = [
        'app_key' => $appKey,
        'timestamp' => time(),
        'shop_cipher' => $shopCipher,
        'page_size' => 50,
    ];

    ksort($queryParams);
    $str = '';
    foreach ($queryParams as $k => $v) {
        $str .= $k . $v;
    }
    $baseString = $appSecret . $path . $str . $bodyJson . $appSecret;
    $sign = hash_hmac('sha256', $baseString, $appSecret);
    $queryParams['sign'] = $sign;
    $queryParams['access_token'] = $accessToken;

    $url = 'https://open-api.tiktokglobalshop.com' . $path . '?' . http_build_query($queryParams);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $bodyJson);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'x-tts-access-token: ' . $accessToken,
        'Content-Type: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    echo "RESULT:\n$result\n";
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
