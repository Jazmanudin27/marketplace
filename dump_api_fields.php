<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Services\ShopeeService;
use App\Services\TiktokService;

$orderSn = $argv[1] ?? null;

if (!$orderSn) {
    echo "=======================================================\n";
    echo "📋 CARA MENGGUNAKAN SCRIPT PEMBONGKAR FIELD API:\n";
    echo "=======================================================\n";
    echo "Jalankan dengan memasukkan Nomor Order:\n";
    echo "  • Order Shopee  : php dump_api_fields.php 260714MDB0NE33\n";
    echo "  • Order TikTok  : php dump_api_fields.php 585161404354365394\n\n";
    exit;
}

$order = Order::where('order_marketplace_id', $orderSn)
    ->orWhere('order_marketplace_id', 'LIKE', '%' . $orderSn . '%')
    ->first();

if (!$order) {
    echo "❌ Order ID '{$orderSn}' tidak ditemukan di Database ERP!\n";
    exit;
}

$store = $order->store;
$channelCode = strtolower($store->channel->code ?? '');

echo "=======================================================\n";
echo "🔍 PEMBONGKAR FIELD API UNTUK ORDER: {$order->order_marketplace_id}\n";
echo "=======================================================\n";
echo "Toko    : " . ($store->name ?? '-') . " (" . strtoupper($channelCode) . ")\n\n";

if ($channelCode === 'shopee' || $store->channel_id == 1) {
    echo "--- 🛍️ [SHOPEE API] RESPONSE RAW 'order_income' --- \n";
    try {
        $shopeeService = app(ShopeeService::class);
        $accessToken = $store->getValidAccessToken();
        $shopId = (int) ($store->marketplace_store_id ?: $store->shopee_shop_id);

        $escrowRes = $shopeeService->getEscrowDetail($accessToken, $shopId, $order->order_marketplace_id);
        echo json_encode($escrowRes['order_income'] ?? $escrowRes, JSON_PRETTY_PRINT) . "\n\n";
    } catch (\Exception $e) {
        echo "❌ Error Shopee API: " . $e->getMessage() . "\n";
    }
} elseif (in_array($channelCode, ['tiktok', 'tiktok_shop', 'tokopedia']) || $store->channel_id == 3) {
    echo "--- 🎵 [TIKTOK API] RESPONSE RAW 'payment_info' --- \n";
    try {
        $tiktokService = app(TiktokService::class);
        $accessToken = $store->getValidAccessToken();
        $shopCipher = $store->shop_cipher;

        $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$order->order_marketplace_id]);
        $tOrder = $detailRes['orders'][0] ?? $detailRes['order_list'][0] ?? [];
        echo json_encode($tOrder['payment_info'] ?? $tOrder['payment'] ?? $tOrder, JSON_PRETTY_PRINT) . "\n\n";

        echo "--- 🎵 [TIKTOK API] STATEMENT TRANSACTIONS --- \n";
        try {
            $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $order->order_marketplace_id);
            echo json_encode($stmtData, JSON_PRETTY_PRINT) . "\n\n";
        } catch (\Exception $exStmt) {
            echo "Statement Info: " . $exStmt->getMessage() . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error TikTok API: " . $e->getMessage() . "\n";
    }
}
