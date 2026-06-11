<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::first();
$shopee = app(App\Services\ShopeeService::class);
try {
    $shopee->updateStock($store->access_token, (int)$store->marketplace_store_id, 844142209, 50);
    echo "SUCCESS";
} catch (\Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
