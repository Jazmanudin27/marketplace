<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$store = \App\Models\Store::whereHas('channel', function($q) { $q->where('code', 'tiktok'); })->first();
var_dump($store->shop_cipher);
