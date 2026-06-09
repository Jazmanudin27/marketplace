<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::where('channel_id', 1)->first();
$shopee = app(App\Services\ShopeeService::class);
$list = $shopee->getItemList($store->access_token, (int)$store->marketplace_store_id, 0, 50);
if (!empty($list['item'])) {
    $itemIds = array_column($list['item'], 'item_id');
    $items = $shopee->getItemBaseInfo($store->access_token, (int)$store->marketplace_store_id, $itemIds);
    $results = [];
    foreach ($items['item_list'] as $item) {
        $results[] = [
            'item_id' => $item['item_id'],
            'has_model' => $item['has_model'],
            'has_price_info' => isset($item['price_info']),
            'has_stock_info' => isset($item['stock_info_v2']),
        ];
    }
    echo json_encode($results, JSON_PRETTY_PRINT);
} else {
    echo "No items found.";
}
