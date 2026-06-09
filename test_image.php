<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = App\Models\Store::first();
$shopee = app(App\Services\ShopeeService::class);
$data = $shopee->getItemBaseInfo($store->access_token, (int)$store->marketplace_store_id, [844142209]);
echo json_encode($data['item_list'][0]['image'] ?? []);
