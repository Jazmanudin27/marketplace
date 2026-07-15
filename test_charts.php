<?php
require 'vendor/autoload.php';
$app = require 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use App\Services\ShopeeService;

$store = Store::where('store_name', 'like', '%1442414542%')->first();
if (!$store) {
    echo "Toko tidak ditemukan di database!\n";
    exit;
}

echo "Toko Ditemukan: {$store->store_name} (Shop ID: {$store->marketplace_store_id})\n";

$shopeeService = app(ShopeeService::class);
$accessToken = $store->getValidAccessToken();

try {
    $charts = $shopeeService->getSizeChartList($accessToken, (int)$store->marketplace_store_id);
    echo "\n=== DAFTAR TEMPLATE SIZE CHART DI SHOPEE ===\n";
    echo json_encode($charts, JSON_PRETTY_PRINT) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
