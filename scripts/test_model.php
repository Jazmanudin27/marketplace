<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::where('channel_id', 1)->first();
$shopee = app(App\Services\ShopeeService::class);
$list = $shopee->getItemList($store->access_token, (int)$store->marketplace_store_id, 0, 1);
if (!empty($list['item'])) {
    $itemId = $list['item'][0]['item_id'];
    $models = $shopee->getModelList($store->access_token, (int)$store->marketplace_store_id, $itemId);
    echo json_encode($models, JSON_PRETTY_PRINT);
} else {
    echo "No items found.";
}
