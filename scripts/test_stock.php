<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::where('channel_id', 1)->first();
$shopee = app(App\Services\ShopeeService::class);

$path = '/api/v2/product/update_stock';
$timestamp = time();
$sign = $shopee->signShopRequest($path, $timestamp, $store->access_token, (int)$store->marketplace_store_id);

$queryParams = [
    'partner_id' => (int)env('SHOPEE_PARTNER_ID'),
    'timestamp' => $timestamp,
    'sign' => $sign,
    'access_token' => $store->access_token,
    'shop_id' => (int)$store->marketplace_store_id,
];

$queryString = http_build_query($queryParams);

$response = Illuminate\Support\Facades\Http::post('https://openplatform.sandbox.test-stable.shopee.sg' . $path . '?' . $queryString, [
    'item_list' => [
        [
            'item_id' => 844142209,
            'stock_list' => [
                [
                    'model_id' => 0,
                    'normal_stock' => 15
                ]
            ]
        ]
    ]
]);

echo json_encode($response->json(), JSON_PRETTY_PRINT);
