<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;
use App\Models\Order;
use App\Services\TiktokService;

echo "========================================================\n";
echo "MENEMBAK LANGSUNG TIKTOK SHOP OPEN API UNTUK SINKRONISASI\n";
echo "========================================================\n\n";

$orderSn = $argv[1] ?? null;

$stores = Store::whereHas('channel', function ($q) {
    $q->whereIn('code', ['tiktok', 'tokopedia']);
})->get();

if ($stores->isEmpty()) {
    echo "❌ Tidak ada toko TikTok/Tokopedia yang terhubung di ERP.\n";
    exit;
}

$tiktokService = app(TiktokService::class);

foreach ($stores as $store) {
    echo "Toko: {$store->store_name} (ID: {$store->id})\n";
    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher = $store->shop_cipher;

        if (empty($shopCipher)) {
            echo "   ⚠️ shop_cipher belum diisi.\n";
            continue;
        }

        $ordersQuery = Order::where('store_id', $store->id)->whereNotNull('order_marketplace_id');
        if ($orderSn) {
            $ordersQuery->where('order_marketplace_id', $orderSn);
        }

        $orders = $ordersQuery->take(10)->get();
        if ($orders->isEmpty()) {
            echo "   ⚠️ Tidak ada orderan di DB.\n";
            continue;
        }

        $orderIds = $orders->pluck('order_marketplace_id')->toArray();
        $res = $tiktokService->getOrderDetail($accessToken, $shopCipher, $orderIds);

        echo "1. HASIL MENTAH DARI API TIKTOK [/order/202309/orders]:\n";
        echo json_encode($res, JSON_PRETTY_PRINT) . "\n\n";

        foreach ($res['orders'] ?? [] as $tOrder) {
            $mId = $tOrder['id'] ?? $tOrder['order_id'] ?? '';
            $dbOrder = $orders->firstWhere('order_marketplace_id', $mId);
            if ($dbOrder) {
                echo "2. RINCIAN BIAYA TERUPDATE UNTUK ORDER: {$mId}\n";
                echo "   Status: " . ($tOrder['status'] ?? 'COMPLETED') . "\n";
                echo "   Omset: Rp " . number_format($dbOrder->total_amount, 0, ',', '.') . "\n";
                echo "   Rincian Fee: " . json_encode($dbOrder->fee_breakdown_details, JSON_PRETTY_PRINT) . "\n";
                echo "   Net Amount: Rp " . number_format($dbOrder->net_amount, 0, ',', '.') . "\n";
                echo "========================================\n";
            }
        }

    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}
