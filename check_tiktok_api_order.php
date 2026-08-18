<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use GuzzleHttp\Client;

$orderSn = $argv[1] ?? '585293879388046348';

echo "======================================================================\n";
echo "🎵 CEK & PULL API LIVE TIKTOK UNTUK ORDER ID: {$orderSn}\n";
echo "======================================================================\n\n";

$stores = Store::whereHas('channel', function($q) {
    $q->where('code', 'LIKE', '%tiktok%');
})->get();

function signTikTokRequest($path, $params, $appSecret) {
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
    $appKey = trim($store->app_key ? $store->app_key : '');
    $appSecret = trim($store->app_secret ? $store->app_secret : '');
    $accessToken = trim($store->access_token ? $store->access_token : '');
    $shopCipher = trim($store->seller_id ? $store->seller_id : ($store->shop_cipher ? $store->shop_cipher : ''));

    if (empty($appKey) || empty($accessToken)) {
        continue;
    }

    // 1. Fetch Order Info
    $pathOrder = "/order/202309/orders";
    $paramsOrder = [
        'app_key' => $appKey,
        'timestamp' => time(),
        'shop_cipher' => $shopCipher,
        'order_ids' => $orderSn
    ];
    $paramsOrder['sign'] = signTikTokRequest($pathOrder, $paramsOrder, $appSecret);
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
            $orderRaw = $dataOrder['data']['orders'][0];
            
            echo "✅ DITEMUKAN DI TOKO: {$store->store_name} (ID #{$store->id})\n";
            echo "----------------------------------------------------------------------\n";
            echo "📦 HASIL API MENTAH 'payment_info' & PRODUK PESANAN:\n";
            echo json_encode([
                'id' => $orderRaw['id'] ?? null,
                'status' => $orderRaw['status'] ?? null,
                'create_time' => isset($orderRaw['create_time']) ? date('Y-m-d H:i:s', $orderRaw['create_time']) : null,
                'paid_time' => isset($orderRaw['paid_time']) ? date('Y-m-d H:i:s', $orderRaw['paid_time']) : null,
                'payment_info' => $orderRaw['payment'] ?? $orderRaw['payment_info'] ?? null,
                'item_list' => $orderRaw['item_list'] ?? null,
                'buyer_email' => $orderRaw['buyer_email'] ?? null,
                'buyer_message' => $orderRaw['buyer_message'] ?? null,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";

            // 2. Fetch Statement Transactions (Escrow/Keuangan)
            $pathStmt = "/finance/202309/statements/transactions";
            $paramsStmt = [
                'app_key' => $appKey,
                'timestamp' => time(),
                'shop_cipher' => $shopCipher,
            ];
            $paramsStmt['sign'] = signTikTokRequest($pathStmt, $paramsStmt, $appSecret);
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
                echo "💰 HASIL API MENTAH STATEMENT TRANSACTIONS (KEUANGAN TIKTOK):\n";
                echo json_encode($dataStmt['data'] ?? $dataStmt, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n\n";

                // Save or sync to DB
                $existingOrder = Order::where('order_marketplace_id', $orderSn)->first();
                if ($existingOrder) {
                    $fb = $existingOrder->financial_breakdown ? $existingOrder->financial_breakdown : [];
                    if (!empty($dataStmt['data']['statement_transactions'])) {
                        $fb['statement_transactions'] = $dataStmt['data']['statement_transactions'];
                    }
                    $existingOrder->financial_breakdown = $fb;
                    $existingOrder->save();
                    echo "✅ Data statement API berhasil disinkronkan ke database ERP!\n";
                }

            } catch (\Exception $exStmt) {
                echo "⚠️ Gagal mengambil API Statement: " . $exStmt->getMessage() . "\n";
            }

            break;
        }
    } catch (\Exception $e) {
        // Continue searching other stores
    }
}

if (!$found) {
    echo "❌ Order ID '{$orderSn}' tidak dapat ditemukan di API 10 Toko TikTok.\n";
}
