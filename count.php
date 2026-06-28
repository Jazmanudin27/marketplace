<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$count = DB::table('orders')->count();
$latest = DB::table('orders')->orderBy('order_date', 'desc')->first();

echo "Total Orders: " . $count . PHP_EOL;
if ($latest) {
    echo "Latest Order ID: " . $latest->id . PHP_EOL;
    echo "Latest Order Date: " . $latest->order_date . PHP_EOL;
    echo "Latest Order Marketplace ID: " . $latest->order_marketplace_id . PHP_EOL;
} else {
    echo "No orders in database." . PHP_EOL;
}
