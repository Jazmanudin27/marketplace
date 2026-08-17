<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;

$orderSn = '585218964645775196';
$order = Order::where('order_marketplace_id', 'LIKE', '%' . $orderSn . '%')->first();

if (!$order) {
    echo "❌ Order '{$orderSn}' tidak ditemukan di DB local.\n";
    exit;
}

echo "=======================================================\n";
echo "Order: {$order->order_marketplace_id}\n";
echo "Financial Breakdown JSON:\n";
print_r($order->financial_breakdown);
