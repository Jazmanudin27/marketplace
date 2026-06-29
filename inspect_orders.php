<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;

echo "=== COUNT ORDERS IN DATABASE ===\n";
foreach (\App\Models\Store::all() as $s) {
    $count = Order::where('store_id', $s->id)->count();
    echo "Store ID: {$s->id} | Name: {$s->store_name} | Orders count in DB: {$count}\n";
}
