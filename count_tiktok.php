<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$count = \App\Models\MarketplaceProduct::where('store_id', 12)->count();
echo 'Total Produk TikTok: ' . $count . PHP_EOL;
