<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Services\TiktokService;

$orderSn = '585200777628452396';
$order = Order::where('order_marketplace_id', $orderSn)->first();

if (!$order) {
    echo "❌ Order ID '{$orderSn}' tidak ditemukan!\n";
    exit;
}

$store = $order->store;
$tiktokService = app(TiktokService::class);
$accessToken = $store->getValidAccessToken();
$shopCipher = $store->shop_cipher;

echo "=======================================================\n";
echo "🔍 DETAIL DUMP MENTAH STATEMENT TIKTOK ORDER: {$orderSn}\n";
echo "=======================================================\n\n";

try {
    $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$orderSn]);
    echo "1. RAW payment_info DARI getOrderDetail:\n";
    $pInfo = $detailRes['orders'][0]['payment_info'] ?? $detailRes['order_list'][0]['payment_info'] ?? [];
    echo json_encode($pInfo, JSON_PRETTY_PRINT) . "\n\n";
} catch (\Exception $e) {
    echo "Error getOrderDetail: " . $e->getMessage() . "\n\n";
}

try {
    $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $orderSn);
    echo "2. RAW statement_transactions DARI TIKTOK FINANCE API:\n";
    echo json_encode($stmtData, JSON_PRETTY_PRINT) . "\n\n";
} catch (\Exception $e) {
    echo "Error statement_transactions: " . $e->getMessage() . "\n\n";
}
