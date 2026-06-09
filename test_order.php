<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::where('channel_id', 1)->first();
$shopee = app(App\Services\ShopeeService::class);

$path = '/api/v2/order/get_order_list';
$timestamp = time();
$sign = $shopee->signShopRequest($path, $timestamp, $store->access_token, (int)$store->marketplace_store_id);

$timeTo = time();
$timeFrom = $timeTo - (15 * 86400);

$response = Illuminate\Support\Facades\Http::get('https://openplatform.sandbox.test-stable.shopee.sg' . $path, [
    'partner_id' => (int)env('SHOPEE_PARTNER_ID'),
    'timestamp' => $timestamp,
    'sign' => $sign,
    'access_token' => $store->access_token,
    'shop_id' => (int)$store->marketplace_store_id,
    'time_range_field' => 'create_time',
    'time_from' => $timeFrom,
    'time_to' => $timeTo,
    'page_size' => 50,
    'cursor' => '',
]);

echo json_encode($response->json(), JSON_PRETTY_PRINT);
