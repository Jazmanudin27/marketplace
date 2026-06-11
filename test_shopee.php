<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$shopee = app(\App\Services\ShopeeService::class);
$store = \App\Models\Store::find(9);
$res = $shopee->getOrderDetail($store->access_token, (int)$store->marketplace_store_id, ['260609KVQNKAEV']);
print_r($res);
