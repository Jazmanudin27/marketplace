<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Services\ShopeeService;

$orderSn = '260714MDB0NE33';
$order = Order::where('order_marketplace_id', $orderSn)->first();

if (!$order) {
    echo "❌ Order SN '{$orderSn}' tidak ditemukan!\n";
    exit;
}

$store = $order->store;
$shopeeService = app(ShopeeService::class);
$accessToken = $store->getValidAccessToken();
$shopId = (int) ($store->marketplace_store_id ?: $store->shopee_shop_id);

$escrowRes = $shopeeService->getEscrowDetail($accessToken, $shopId, $orderSn);
$income = $escrowRes['order_income'] ?? [];

echo "=======================================================\n";
echo "🔍 DETAIL MENTAH API SHOPEE ORDER: {$orderSn}\n";
echo "=======================================================\n\n";

echo "RAW JSON RESPONSE FROM SHOPEE API (order_income):\n";
echo json_encode($income, JSON_PRETTY_PRINT) . "\n\n";

$subtotal = (float) ($income['cost_of_goods_sold'] ?? $income['order_selling_price'] ?? $income['order_original_price'] ?? $order->total_amount);
$escrowAmount = (float) ($income['escrow_amount'] ?? 0);
$actualFee = max(0.0, $subtotal - $escrowAmount);

echo "-------------------------------------------------------\n";
echo "📊 HASSIL KALKULASI DANA CAIR RESMI SHOPEE:\n";
echo "-------------------------------------------------------\n";
echo "  • Subtotal Harga Produk      : Rp " . number_format($subtotal, 0, ',', '.') . "\n";
echo "  • Dana Cair Ke Rekening Seller : Rp " . number_format($escrowAmount, 0, ',', '.') . "\n";
echo "  • BIAYA ADMIN RESMI SHOPEE    : Rp " . number_format($actualFee, 0, ',', '.') . " (" . round(($actualFee / ($subtotal ?: 1)) * 100, 2) . "%)\n";
echo "-------------------------------------------------------\n";
