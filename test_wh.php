<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
$store = \App\Models\Store::where('store_name', 'Baraya Snack Tasikmalaya')->first();
$service = new \App\Services\TiktokService();
print_r($service->getWarehouses($store->access_token, $store->shop_cipher));
