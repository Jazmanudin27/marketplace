<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;

$orderSn = $argv[1] ?? '2608018602RNJ2';

echo "========================================================\n";
echo "SINKRONISASI SINGLE ORDER SHOPEE LANGSUNG DARI API\n";
echo "Nomor Order Shopee: {$orderSn}\n";
echo "========================================================\n\n";

$shopeeService = app(ShopeeService::class);
$shopeeStores = Store::whereHas('channel', function ($q) {
    $q->where('code', 'shopee');
})->get();

$found = false;

foreach ($shopeeStores as $store) {
    echo "Checking Toko: {$store->store_name} (ID: {$store->id})... ";
    try {
        $accessToken = $store->getValidAccessToken();
        $detailRes = $shopeeService->getOrderDetail(
            $accessToken,
            (int) $store->marketplace_store_id,
            [$orderSn]
        );

        $ordersList = $detailRes['order_list'] ?? [];
        if (empty($ordersList)) {
            echo "ℹ️ Order tidak ditemukan di toko ini.\n";
            continue;
        }

        $found = true;
        $shopeeOrder = $ordersList[0];
        echo "✅ MATCHING FOUND!\n\n";

        echo "Status di Shopee API Saat Ini: " . ($shopeeOrder['order_status'] ?? 'UNKNOWN') . "\n";
        echo "Alasan Batal (jika ada): " . ($shopeeOrder['cancel_reason'] ?? '-') . "\n\n";

        // Dispatch Job untuk memperbarui DB ERP
        $job = new \App\Jobs\PullOrdersFromShopee($store, time() - 86400, time());
        $reflection = new \ReflectionClass($job);
        $method = $reflection->getMethod('saveOrder');
        $method->setAccessible(true);
        $method->invoke($job, $shopeeOrder);

        $dbOrder = Order::where('order_marketplace_id', (string)$orderSn)->first();
        if ($dbOrder) {
            echo "========================================================\n";
            echo "✅ STATUS DI ERP BERHASIL DIPERBARUI!\n";
            echo "   • ERP Order ID        : {$dbOrder->id}\n";
            echo "   • Status ERP Baru     : {$dbOrder->order_status}\n";
            echo "   • Alasan Pembatalan   : " . ($dbOrder->cancel_reason ?? '-') . "\n";
            echo "========================================================\n";
        }
        break;
    } catch (\Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
}

if (!$found) {
    echo "\n⚠️ Order SN '{$orderSn}' tidak ditemukan di akun toko Shopee manapun.\n";
}
