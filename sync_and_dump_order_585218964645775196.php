<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;

$orderSn = '585218964645775196';
$order = Order::where('order_marketplace_id', 'LIKE', '%' . $orderSn . '%')->first();

if (!$order) {
    echo "❌ Order {$orderSn} belum ada di DB local. Mencari store TikTok...\n";
    $store = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%tiktok%'))->where('is_active', true)->first();
} else {
    $store = $order->store;
}

if (!$store) {
    echo "❌ Store TikTok tidak ditemukan.\n";
    exit;
}

echo "=======================================================\n";
echo "🔍 CEK & SYNC STATEMENT API TIKTOK UNTUK ORDER: {$orderSn}\n";
echo "Toko: {$store->store_name} (ID: {$store->id})\n";
echo "=======================================================\n";

$client = new \GuzzleHttp\Client(['timeout' => 30]);
$appKey = trim($store->app_key ?? '');
$appSecret = trim($store->app_secret ?? '');
$accessToken = trim($store->access_token ?? '');
$shopCipher = trim($store->seller_id ?? $store->shop_cipher ?? '');

if (empty($appKey) || empty($accessToken)) {
    echo "⚠️ App Key / Access Token kosong untuk store ini.\n";
    exit;
}

function signTiktokReq($path, $params, $appSecret) {
    ksort($params);
    $stringToBeSigned = $path;
    foreach ($params as $k => $v) {
        if ($k !== 'sign' && $k !== 'access_token') {
            $stringToBeSigned .= $k . $v;
        }
    }
    $stringToBeSigned = $appSecret . $stringToBeSigned . $appSecret;
    return hash_hmac('sha256', $stringToBeSigned, $appSecret);
}

$path = "/finance/202309/statements/transactions";
$timestamp = time();
$params = [
    'app_key' => $appKey,
    'timestamp' => $timestamp,
    'shop_cipher' => $shopCipher,
];
$params['sign'] = signTiktokReq($path, $params, $appSecret);

$url = "https://open-api.tiktokglobalshop.com{$path}?" . http_build_query($params);
try {
    $res = $client->post($url, [
        'headers' => [
            'x-tts-access-token' => $accessToken,
            'content-type' => 'application/json',
        ],
        'json' => [
            'order_id' => $orderSn,
        ]
    ]);
    $json = json_decode($res->getBody()->getContents(), true);
    echo "RESPONSE STATEMENT TIKTOK API:\n";
    print_r($json);

    if ($order && !empty($json['data']['statement_transactions'])) {
        $stList = $json['data']['statement_transactions'];
        $st0 = $stList[0] ?? [];
        
        $fb = $order->financial_breakdown ?? [];
        $fb['statement_transactions'] = $stList;
        foreach ($st0 as $k => $v) {
            $fb[$k] = $v;
        }

        $order->financial_breakdown = $fb;
        $order->save();
        echo "\n✅ Berhasil menyimpan data statement API terbaru ke database ERP untuk order {$orderSn}!\n";
    }

} catch (\Exception $e) {
    echo "❌ Error API: " . $e->getMessage() . "\n";
}
