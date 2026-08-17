<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;

$store = Store::find(45);
echo "Toko #45: " . ($store ? $store->store_name : 'N/A') . "\n";

$orders = Order::where('store_id', 45)->latest()->take(5)->get();
if ($orders->isEmpty()) {
    $orders = Order::latest()->take(5)->get();
}

foreach ($orders as $order) {
    echo "=======================================================\n";
    echo "Order Marketplace ID : {$order->order_marketplace_id}\n";
    echo "total_amount (DB)    : Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
    echo "discount_amount (DB) : Rp " . number_format($order->discount_amount, 0, ',', '.') . "\n";
    echo "marketplace_fee (DB) : Rp " . number_format($order->marketplace_fee, 0, ',', '.') . "\n";
    echo "net_amount (DB)      : Rp " . number_format($order->net_amount, 0, ',', '.') . "\n";
    echo "Items Count          : " . $order->items->count() . "\n";
    foreach ($order->items as $it) {
        echo "  - {$it->product_name} (SKU: {$it->sku})\n";
        echo "    price: Rp " . number_format($it->price, 0, ',', '.') . "\n";
        echo "    original_price: Rp " . number_format($it->original_price, 0, ',', '.') . "\n";
        echo "    seller_discount: Rp " . number_format($it->seller_discount, 0, ',', '.') . "\n";
        echo "    total_price: Rp " . number_format($it->total_price, 0, ',', '.') . "\n";
    }
}
