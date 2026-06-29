<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Order;

echo "=== LATEST 5 ORDERS FOR EMRAPRESS/EMRACOLLECT ===\n";
$orders = Order::where('store_id', 18)
               ->orderByDesc('order_date')
               ->limit(5)
               ->get();

if ($orders->isEmpty()) {
    echo "Tidak ada order untuk store ID 18 di DB.\n";
} else {
    foreach ($orders as $o) {
        echo "Order ID: {$o->id} | Invoice: {$o->invoice_number} | MP ID: {$o->order_marketplace_id} | Date: {$o->order_date} | Status: {$o->order_status} | Net: {$o->net_amount}\n";
    }
}
