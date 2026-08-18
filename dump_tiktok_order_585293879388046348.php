<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use GuzzleHttp\Client;

$orderSn = '585293879388046348';

echo "======================================================================\n";
echo "🎵 CEK MEMAKAI API TIKTOK SHOP UNTUK ORDER ID: {$orderSn}\n";
echo "======================================================================\n\n";

$stores = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%tiktok%'))->get();

if ($stores->isEmpty()) {
    echo "❌ Tidak ada toko TikTok yang aktif.\n";
    exit;
}

function signReq($path, $params, $appSecret) {
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

$client = new Client(['timeout' => 30]);
$found = false;

foreach ($stores as $store) {
    $appKey = trim($store->app_key ?? '');
    $appSecret = trim($store->app_secret ?? '');
    $accessToken = trim($store->access_token ?? '');
    $shopCipher = trim($store->seller_id ?? $store->shop_cipher ?? '');

    if (empty($appKey) || empty($accessToken)) continue;

    // 1. Fetch Order Details API
    $pathOrder = "/order/202309/orders";
    $paramsOrder = [
        'app_key' => $appKey,
        'timestamp' => time(),
        'shop_cipher' => $shopCipher,
        'order_ids' => $orderSn
    ];
    $paramsOrder['sign'] = signReq($pathOrder, $paramsOrder, $appSecret);
    $urlOrder = "https://open-api.tiktokglobalshop.com{$pathOrder}?" . http_build_query($paramsOrder);

    try {
        $resOrder = $client->get($urlOrder, [
            'headers' => [
                'x-tts-access-token' => $accessToken,
                'content-type' => 'application/json'
            ]
        ]);
        $dataOrder = json_decode($resOrder->getBody()->getContents(), true);

        if (!empty($dataOrder['data']['orders'])) {
            $found = true;
            $tiktokOrder = $dataOrder['data']['orders'][0];
            
            echo "✅ DITEMUKAN DI TOKO: {$store->store_name} (ID #{$store->id})\n";
            echo "----------------------------------------------------------------------\n";
            echo "📦 RAW RESPONSE API 'orders' (Detail Order TikTok):\n";
            echo json_encode($tiktokOrder, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";

            // 2. Fetch Finance Statement Transactions API
            $pathStmt = "/finance/202309/statements/transactions";
            $paramsStmt = [
                'app_key' => $appKey,
                'timestamp' => time(),
                'shop_cipher' => $shopCipher,
            ];
            $paramsStmt['sign'] = signReq($pathStmt, $paramsStmt, $appSecret);
            $urlStmt = "https://open-api.tiktokglobalshop.com{$pathStmt}?" . http_build_query($paramsStmt);

            try {
                $resStmt = $client->post($urlStmt, [
                    'headers' => [
                        'x-tts-access-token' => $accessToken,
                        'content-type' => 'application/json'
                    ],
                    'json' => ['order_id' => $orderSn]
                ]);
                $dataStmt = json_decode($resStmt->getBody()->getContents(), true);

                echo "----------------------------------------------------------------------\n";
                echo "💰 RAW RESPONSE API 'statement_transactions' (Keuangan TikTok):\n";
                echo json_encode($dataStmt['data'] ?? $dataStmt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";

            } catch (\Exception $exStmt) {
                echo "⚠️ Gagal mengambil API Statement: " . $exStmt->getMessage() . "\n";
            }

            break;
        }
    } catch (\Exception $e) {
        // Continue checking next store
    }
}

if (!$found) {
    echo "❌ Order ID '{$orderSn}' tidak ditemukan di seluruh 10 Toko TikTok.\n";
}
