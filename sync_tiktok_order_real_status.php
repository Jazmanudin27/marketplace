<?php
/**
 * ============================================================
 * SINKRONISASI MANUAL STATUS RIIL TIKTOK -> ERP
 * ============================================================
 * Menarik status pesanan terbaru dari API TikTok dan menyimpannya 
 * ke database ERP secara presisi.
 *
 * Cara pakai:
 *   php sync_tiktok_order_real_status.php [ORDER_MARKETPLACE_ID]
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;

$orderMarketplaceId = $argv[1] ?? null;

if (!$orderMarketplaceId) {
    die("⚠️ Silakan masukkan Order ID. Contoh: php sync_tiktok_order_real_status.php 585691415370368929\n");
}

$order = Order::where('order_marketplace_id', (string)$orderMarketplaceId)->first();

if (!$order) {
    die("❌ Error: Order ID '$orderMarketplaceId' tidak ditemukan di database ERP.\n");
}

$store = $order->store;
if (!$store) {
    die("❌ Error: Toko pemilik order ini tidak ditemukan.\n");
}

try {
    $accessToken = $store->getValidAccessToken();
    $shopCipher = $store->shop_cipher;
    $tiktokService = app(TiktokService::class);
    
    echo "Menarik data order terbaru dari API TikTok...\n";
    $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [(string)$orderMarketplaceId]);
    $tiktokOrders = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];
    
    if (empty($tiktokOrders)) {
        die("❌ Error: Order tidak ditemukan di API TikTok.\n");
    }
    
    $tiktokOrder = $tiktokOrders[0];
    $statusRaw = $tiktokOrder['status'] ?? $tiktokOrder['order_status'] ?? 'UNPAID';
    
    $statusMap = [
        'UNPAID' => 'UNPAID',
        '100' => 'UNPAID',
        'AWAITING_SHIPMENT' => 'READY_TO_SHIP',
        '111' => 'READY_TO_SHIP',
        'AWAITING_COLLECTION' => 'READY_TO_SHIP',
        '112' => 'READY_TO_SHIP',
        'IN_TRANSIT' => 'SHIPPED',
        '121' => 'SHIPPED',
        'DELIVERED' => 'DELIVERED',
        '122' => 'DELIVERED',
        'COMPLETED' => 'COMPLETED',
        '130' => 'COMPLETED',
        'CANCELLED' => 'CANCELLED',
        '140' => 'CANCELLED',
    ];
    
    $erpStatus = $statusMap[strtoupper((string)$statusRaw)] ?? strtoupper((string)$statusRaw);
    
    echo "Status asli di TikTok: $statusRaw\n";
    echo "Status yang dipetakan ERP: $erpStatus\n";
    
    $order->order_status = $erpStatus;
    $order->saveQuietly();
    
    echo "✅ Berhasil memperbarui status order ke: $erpStatus\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
