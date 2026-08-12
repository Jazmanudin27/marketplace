<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;
use App\Models\Order;
use App\Services\TiktokService;

$orderId = $argv[1] ?? '585293879388046348';

echo "========================================================\n";
echo "MENEMBAK LANGSUNG TIKTOK SHOP OPEN API UNTUK ORDER: {$orderId}\n";
echo "========================================================\n\n";

$tiktokService = app(TiktokService::class);

// Cek apakah order sudah ada di database ERP untuk menentukan tokonya
$dbOrder = Order::where('order_marketplace_id', (string)$orderId)
    ->orWhere('invoice_number', (string)$orderId)
    ->first();

if ($dbOrder && $dbOrder->store) {
    $stores = collect([$dbOrder->store]);
    echo "📌 Order ditemukan di DB ERP pada Toko: {$dbOrder->store->store_name} (ID: {$dbOrder->store->id})\n\n";
} else {
    $stores = Store::whereHas('channel', function ($q) {
        $q->whereIn('code', ['tiktok', 'tokopedia']);
    })->get();
    echo "📌 Order belum ada di DB / mencari toko TikTok yang sesuai...\n\n";
}

$found = false;

foreach ($stores as $store) {
    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher = $store->shop_cipher;

        if (empty($shopCipher)) {
            continue;
        }

        $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$orderId]);
        
        $ordersList = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];

        if (!empty($ordersList)) {
            $found = true;
            echo "========================================================\n";
            echo "✅ TOKO MATCHING: {$store->store_name} (ID: {$store->id})\n";
            echo "========================================================\n";
            echo "HASIL MENTAH DARI API TIKTOK SHOP [/order/202309/orders]:\n\n";
            echo json_encode($ordersList[0], JSON_PRETTY_PRINT) . "\n\n";
            break;
        }
    } catch (\Exception $e) {
        // Lanjutkan pencarian ke toko berikutnya jika bukan toko pemilik order
    }
}

if (!$found) {
    echo "⚠️ Order ID '{$orderId}' tidak ditemukan di API TikTok Shop toko manapun yang terhubung, atau token toko perlu direfresh.\n";
}
