<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;
use App\Models\Order;
use App\Services\TiktokService;

$orderId = '585293879388046348';

echo "========================================================\n";
echo "1. DATA PADA DATABASE ERP:\n";
echo "========================================================\n";
$dbOrder = Order::with('items')->where('order_marketplace_id', (string)$orderId)->first();
if ($dbOrder) {
    echo "ID: {$dbOrder->id}\n";
    echo "Order MP ID: {$dbOrder->order_marketplace_id}\n";
    echo "Total Amount (Omset Kotor ERP): {$dbOrder->total_amount}\n";
    echo "Shipping Fee: {$dbOrder->shipping_fee}\n";
    echo "Discount Amount: {$dbOrder->discount_amount}\n";
    echo "Marketplace Fee: {$dbOrder->marketplace_fee}\n";
    echo "Net Amount (Omset Bersih ERP): {$dbOrder->net_amount}\n";
    echo "Financial Breakdown:\n";
    print_r($dbOrder->financial_breakdown);
    echo "Items:\n";
    foreach ($dbOrder->items as $item) {
        echo "  - {$item->product_name} | Qty: {$item->quantity} | Unit Price: {$item->price} | Total: {$item->total_price}\n";
    }
} else {
    echo "❌ Order tidak ditemukan di DB ERP!\n";
}

echo "\n========================================================\n";
echo "2. DATA MENTAH DARI API TIKTOK SHOP OPEN API:\n";
echo "========================================================\n";

$tiktokService = app(TiktokService::class);
$stores = Store::whereHas('channel', function ($q) {
    $q->whereIn('code', ['tiktok', 'tokopedia']);
})->get();

$found = false;
foreach ($stores as $store) {
    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher = $store->shop_cipher;

        if (empty($shopCipher)) continue;

        $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$orderId]);
        $ordersList = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];

        if (!empty($ordersList)) {
            $found = true;
            echo "TOKO MATCH: {$store->store_name}\n";
            echo json_encode($ordersList[0], JSON_PRETTY_PRINT) . "\n";
            break;
        }
    } catch (\Exception $e) {
        echo "Error on store {$store->store_name}: " . $e->getMessage() . "\n";
    }
}

if (!$found) {
    echo "❌ Order tidak ditemukan via TikTok API.\n";
}
