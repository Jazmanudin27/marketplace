<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use App\Models\MasterProduct;
use App\Services\ShopeeService;

$store = Store::where('store_name', 'like', '%1442414542%')->first();
if (!$store) {
    echo "Toko tidak ditemukan!\n";
    exit;
}

$product = MasterProduct::first();
if (!$product) {
    echo "Produk tidak ditemukan!\n";
    exit;
}

$shopeeService = app(ShopeeService::class);
$accessToken = $store->getValidAccessToken();

echo "Toko: {$store->store_name} (Shop ID: {$store->marketplace_store_id})\n";
echo "Testing Shopee addItem with various size_chart payloads...\n\n";

$imageId = "sg-11134201-8259m-mqoi1a7oj11f74"; // Valid uploaded image ID

$channels = $shopeeService->getChannelList($accessToken, (int)$store->marketplace_store_id);
$logisticInfo = [];
foreach ($channels as $chan) {
    if (!empty($chan['enabled'])) {
        $logisticInfo[] = [
            'logistic_id' => (int) $chan['logistics_channel_id'],
            'enabled' => true
        ];
    }
}
echo "Jasa Kirim Aktif Ditemukan: " . count($logisticInfo) . " channel (" . json_encode(array_column($logisticInfo, 'logistic_id')) . ")\n\n";

$baseItemData = [
    'original_price' => (float) 50000,
    'item_name' => 'TEST PUBLISH HARAP ABAIKAN ' . rand(100, 999),
    'description' => 'Ini adalah tes deskripsi produk baju batik anak untuk pengujian API Shopee yang valid dan lengkap.',
    'weight' => 0.1,
    'item_status' => 'UNLISTED',
    'logistic_info' => $logisticInfo,
    'brand' => [
        'brand_id' => 0
    ],
    'seller_stock' => [
        ['stock' => 10]
    ],
    'image' => [
        'image_id_list' => [$imageId]
    ]
];

$tests = [
    [
        'label' => 'Cat 101760 + size_chart_id (int)',
        'cat_id' => 101760,
        'extra' => ['size_chart_id' => 1104825612]
    ],
    [
        'label' => 'Cat 101757 (Kaos) + size_chart_id (int)',
        'cat_id' => 101757,
        'extra' => ['size_chart_id' => 1104825612]
    ],
    [
        'label' => 'Cat 101760 + size_chart (int)',
        'cat_id' => 101760,
        'extra' => ['size_chart' => 1104825612]
    ],
    [
        'label' => 'Cat 101760 + size_chart_template_id (int)',
        'cat_id' => 101760,
        'extra' => ['size_chart_template_id' => 1104825612]
    ],
    [
        'label' => 'Cat 101760 + size_chart image_id object',
        'cat_id' => 101760,
        'extra' => ['size_chart' => ['image_id' => $imageId]]
    ],
];

foreach ($tests as $t) {
    echo "=== TEST: {$t['label']} ===\n";
    $payload = array_merge($baseItemData, ['category_id' => $t['cat_id']], $t['extra']);
    try {
        $res = $shopeeService->addItem($accessToken, (int)$store->marketplace_store_id, $payload);
        echo "SUCCESS! Item ID: " . ($res['item_id'] ?? json_encode($res)) . "\n";
    } catch (\Exception $e) {
        echo "FAILED: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

