<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;

$orderSn = '585293879388046348';
$order = Order::where('order_marketplace_id', 'LIKE', '%' . $orderSn . '%')->first();

if ($order) {
    echo "=======================================================\n";
    echo "✅ ORDER DITEMUKAN DI LOCAL DATABASE ERP:\n";
    echo "ID                        : {$order->id}\n";
    echo "order_marketplace_id     : {$order->order_marketplace_id}\n";
    echo "invoice_number           : {$order->invoice_number}\n";
    echo "store                    : " . ($order->store->store_name ?? '-') . "\n";
    echo "buyer_name               : {$order->buyer_name}\n";
    echo "buyer_phone              : {$order->buyer_phone}\n";
    echo "shipping_address         : {$order->shipping_address}\n";
    echo "total_amount             : Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
    echo "discount_amount          : Rp " . number_format($order->discount_amount, 0, ',', '.') . "\n";
    echo "marketplace_fee          : Rp " . number_format($order->marketplace_fee, 0, ',', '.') . "\n";
    echo "net_amount               : Rp " . number_format($order->net_amount, 0, ',', '.') . "\n";
    echo "paid_at                  : {$order->paid_at}\n";
    echo "completed_at             : {$order->completed_at}\n";
    echo "payment_method           : {$order->payment_method}\n";
    echo "package_id               : {$order->package_id}\n";
    echo "-------------------------------------------------------\n";
    echo "ITEMS:\n";
    foreach ($order->items as $it) {
        $skuStr = $it->seller_sku ? $it->seller_sku : $it->sku;
        echo "  - {$it->product_name} | SKU: {$skuStr} | Price: Rp " . number_format($it->price, 0, ',', '.') . " | Qty: {$it->quantity}\n";
    }
    echo "-------------------------------------------------------\n";
    echo "FINANCIAL BREAKDOWN JSON:\n";
    print_r($order->financial_breakdown);
} else {
    echo "❌ Order {$orderSn} belum ada di DB local marketplace. silakan jalankan di server srv903065.\n";
}
