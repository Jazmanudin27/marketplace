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

// Test get_size_chart_detail
echo "=== GET SIZE CHART DETAIL (1104825612) ===\n";
try {
    $res = $shopeeService->getSizeChartDetail($accessToken, (int)$store->marketplace_store_id, 1104825612);
    echo "Detail Response: " . json_encode($res, JSON_PRETTY_PRINT) . "\n\n";
} catch (\Exception $e) {
    echo "ERROR Detail: " . $e->getMessage() . "\n\n";
}




$imageId = "sg-11134201-8259m-mqoi1a7oj11f74"; // Default fallback

// 1. Download dummy image and upload with scene = size_chart
echo "=== UPLOADING TEST IMAGE WITH SCENE = SIZE_CHART ===\n";
try {
    $tempFile = tempnam(sys_get_temp_dir(), 'size_chart_');
    file_put_contents($tempFile, file_get_contents('https://placehold.co/600x600.png'));
    
    $resData = $shopeeService->uploadImage($accessToken, (int)$store->marketplace_store_id, $tempFile, 'size_chart');
    
    @unlink($tempFile);
    
    $sizeChartImageId = $resData['image_info']['image_id'] ?? null;
    echo "Berhasil upload size chart image! ID: $sizeChartImageId\n\n";
    if ($sizeChartImageId) {
        $imageId = $sizeChartImageId;
    }
} catch (\Exception $e) {
    echo "ERROR Upload: " . $e->getMessage() . "\n\n";
}


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
        'label' => 'Cat 101760 + size_chart as plain string (image_id)',
        'cat_id' => 101760,
        'extra' => ['size_chart' => $imageId]
    ],
    [
        'label' => 'Cat 101760 + size_chart_id as plain string (image_id)',
        'cat_id' => 101760,
        'extra' => ['size_chart_id' => $imageId]
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

