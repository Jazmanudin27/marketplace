<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;
use App\Services\TiktokService;

$orderId = $argv[1] ?? '585293879388046348';

echo "========================================================\n";
echo "MENEMBAK LANGSUNG TIKTOK SHOP OPEN API UNTUK ORDER: {$orderId}\n";
echo "========================================================\n\n";

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
            echo "   ⚠️ shop_cipher toko ini belum diisi di ERP.\n";
            continue;
        }

        echo "\n1. HASIL MENTAH DARI API TIKTOK SHOP [/order/202309/orders]:\n";
        $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$orderId]);
        echo json_encode($detailRes, JSON_PRETTY_PRINT) . "\n\n";

        if (!empty($detailRes['orders'])) {
            echo "========================================\n";
            echo "✅ BERHASIL MENGAMBIL DATA MENTAH DARI TIKTOK SHOP OPEN API!\n";
            echo "========================================\n";
        }
    } catch (\Exception $e) {
        echo "⚠️ " . $e->getMessage() . "\n";
    }
}
