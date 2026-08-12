<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;
use App\Models\Order;
use App\Services\ShopeeService;

$orderSn = $argv[1] ?? '2607315PUR3M2W';

echo "========================================================\n";
echo "MENEMBAK LANGSUNG SHOPEE OPEN API UNTUK ORDER: {$orderSn}\n";
echo "========================================================\n\n";

$stores = Store::whereHas('channel', function ($q) {
    $q->where('code', 'shopee');
})->get();

if ($stores->isEmpty()) {
    echo "❌ Tidak ada toko Shopee yang terhubung di ERP.\n";
    exit;
}

$shopeeService = app(ShopeeService::class);

foreach ($stores as $store) {
    echo "Toko: {$store->store_name} | Shop ID Shopee: {$store->marketplace_store_id}\n";
    try {
        $accessToken = $store->getValidAccessToken();
        $shopId = (int) $store->marketplace_store_id;

        echo "\n1. HASIL MENTAH DARI API SHOPEE [/api/v2/order/get_order_detail]:\n";
        $orderRes = $shopeeService->getOrderDetail($accessToken, $shopId, [$orderSn]);
        echo json_encode($orderRes, JSON_PRETTY_PRINT) . "\n\n";

        echo "2. HASIL MENTAH DARI API SHOPEE [/api/v2/payment/get_escrow_detail]:\n";
        $escrowRes = $shopeeService->getEscrowDetail($accessToken, $shopId, $orderSn);
        echo json_encode($escrowRes, JSON_PRETTY_PRINT) . "\n\n";

        if (!empty($escrowRes['order_income'])) {
            echo "========================================\n";
            echo "✅ BERHASIL MENGAMBIL DATA RAW DARI SHOPEE OPEN API!\n";
            echo "========================================\n";
        }
    } catch (\Exception $e) {
        echo "⚠️ " . $e->getMessage() . "\n";
    }
}
