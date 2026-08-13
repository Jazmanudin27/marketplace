<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

$orderId = '585293879388046348';

$dbOrder = Order::with(['items', 'store.channel'])->where('order_marketplace_id', (string)$orderId)->first();
if ($dbOrder) {
    echo "========================================================\n";
    echo "ID: {$dbOrder->id}\n";
    echo "Order MP ID: {$dbOrder->order_marketplace_id}\n";
    echo "Store: " . ($dbOrder->store->store_name ?? 'N/A') . "\n";
    echo "Status: {$dbOrder->order_status}\n";
    echo "Total Amount (Omset Kotor ERP): Rp " . number_format($dbOrder->total_amount, 2, '.', ',') . "\n";
    echo "Shipping Fee: Rp " . number_format($dbOrder->shipping_fee, 2, '.', ',') . "\n";
    echo "Discount Amount: Rp " . number_format($dbOrder->discount_amount, 2, '.', ',') . "\n";
    echo "Marketplace Fee: Rp " . number_format($dbOrder->marketplace_fee, 2, '.', ',') . "\n";
    echo "Net Amount (Omset Bersih ERP): Rp " . number_format($dbOrder->net_amount, 2, '.', ',') . "\n";
    echo "--------------------------------------------------------\n";
    echo "5 Komponen Fee Breakdown Details (Order.php):\n";
    print_r($dbOrder->fee_breakdown_details);
    echo "--------------------------------------------------------\n";
    echo "Financial Breakdown JSON Raw:\n";
    print_r($dbOrder->financial_breakdown);
    echo "--------------------------------------------------------\n";
    echo "Items:\n";
    foreach ($dbOrder->items as $item) {
        echo "  - {$item->product_name} | Qty: {$item->quantity} | Price: Rp " . number_format($item->price, 2, '.', ',') . " | Total: Rp " . number_format($item->total_price, 2, '.', ',') . "\n";
    }
    echo "========================================================\n";
} else {
    echo "❌ Order dengan ID '{$orderId}' tidak ditemukan di DB ERP!\n";
}
