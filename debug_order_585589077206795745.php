<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;

$orderSn = '585589077206795745';
$order = Order::where('order_marketplace_id', 'LIKE', '%' . $orderSn . '%')->first();

if (!$order) {
    echo "❌ Order ID '{$orderSn}' tidak ditemukan di DB.\n";
    exit;
}

echo "======================================================================\n";
echo "🔍 DIAGNOSTIK ANGKA KEUANGAN ORDER: {$orderSn}\n";
echo "======================================================================\n";
echo "Toko                     : " . ($order->store->store_name ?? '-') . "\n";
echo "total_amount (DB)        : Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
echo "discount_amount (DB)     : Rp " . number_format($order->discount_amount, 0, ',', '.') . "\n";
echo "marketplace_fee (DB)     : Rp " . number_format($order->marketplace_fee, 0, ',', '.') . "\n";
echo "refund_amount (DB)       : Rp " . number_format($order->refund_amount, 0, ',', '.') . "\n";
echo "net_amount (DB)          : Rp " . number_format($order->net_amount, 0, ',', '.') . "\n";
echo "----------------------------------------------------------------------\n";
echo "ITEM PESANAN DI DB:\n";
foreach ($order->items as $it) {
    echo "  - {$it->product_name} (SKU: {$it->sku})\n";
    echo "    price: Rp " . number_format($it->price, 0, ',', '.') . "\n";
    echo "    original_price: Rp " . number_format($it->original_price, 0, ',', '.') . "\n";
    echo "    seller_discount: Rp " . number_format($it->seller_discount, 0, ',', '.') . "\n";
    echo "    platform_discount: Rp " . number_format($it->platform_discount, 0, ',', '.') . "\n";
    echo "    quantity: {$it->quantity}\n";
    echo "    total_price: Rp " . number_format($it->total_price, 0, ',', '.') . "\n";
}
echo "----------------------------------------------------------------------\n";
echo "FINANCIAL BREAKDOWN (JSON Raw API TikTok):\n";
print_r($order->financial_breakdown);
echo "======================================================================\n";
