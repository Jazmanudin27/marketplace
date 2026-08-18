<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;

$zeroItemOrders = Order::whereDoesntHave('items')->get();

echo "=======================================================\n";
echo "📊 PESANAN TANPA ITEM DI DATABASE ERP:\n";
echo "Total Pesanan Tanpa Item: " . $zeroItemOrders->count() . "\n";
echo "=======================================================\n";

foreach ($zeroItemOrders->take(10) as $ord) {
    echo "- Order ID: {$ord->order_marketplace_id} | Store: " . ($ord->store->store_name ?? '-') . " | Status: {$ord->order_status} | Date: {$ord->order_date}\n";
}
