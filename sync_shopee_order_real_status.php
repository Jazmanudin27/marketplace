<?php
/**
 * ============================================================
 * SINKRONISASI MANUAL STATUS RIIL SHOPEE -> ERP
 * ============================================================
 * Menarik status pesanan terbaru dari API Shopee dan menyimpannya 
 * ke database ERP secara presisi.
 *
 * Cara pakai:
 *   php sync_shopee_order_real_status.php [ORDER_MARKETPLACE_ID]
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;

$orderMarketplaceId = $argv[1] ?? null;

if (!$orderMarketplaceId) {
    die("⚠️ Silakan masukkan Order ID. Contoh: php sync_shopee_order_real_status.php 240824XXXXXX\n");
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
    $shopeeService = app(ShopeeService::class);
    
    echo "Menarik data order terbaru dari API Shopee...\n";
    $detailRes = $shopeeService->getOrderDetail(
        $accessToken,
        (int) $store->marketplace_store_id,
        [(string)$orderMarketplaceId]
    );
    
    $ordersList = $detailRes['order_list'] ?? [];
    
    if (empty($ordersList)) {
        die("❌ Error: Order tidak ditemukan di API Shopee.\n");
    }
    
    $shopeeOrder = $ordersList[0];
    $statusRaw = strtoupper((string)($shopeeOrder['order_status'] ?? $order->order_status));
    
    $shopeeStatusMap = [
        'UNPAID'             => 'UNPAID',
        'READY_TO_SHIP'      => 'READY_TO_SHIP',
        'PROCESSED'          => 'READY_TO_SHIP',
        'RETRY_SHIP'         => 'READY_TO_SHIP',
        'TO_RETRY_LOGISTICS' => 'READY_TO_SHIP',
        'SHIPPED'            => 'SHIPPED',
        'TO_CONFIRM_RECEIVE' => 'SHIPPED',
        'DELIVERED'          => 'DELIVERED',
        'COMPLETED'          => 'COMPLETED',
        'CANCELLED'          => 'CANCELLED',
        'IN_CANCEL'          => 'CANCELLED',
    ];
    
    $correctStatus = $shopeeStatusMap[$statusRaw] ?? $statusRaw;
    
    echo "Status asli di Shopee: $statusRaw\n";
    echo "Status yang dipetakan ERP: $correctStatus\n";
    
    $order->order_status = $correctStatus;
    
    if (in_array($correctStatus, ['CANCELLED', 'BATAL'])) {
        $order->cancel_reason = $shopeeOrder['cancel_reason'] ?? 'Cancelled on Shopee';
        $order->cancelled_by = 'Shopee / System';
    }
    
    if (!empty($shopeeOrder['update_time'])) {
        $order->completed_at = date('Y-m-d H:i:s', $shopeeOrder['update_time']);
    }
    
    $order->saveQuietly();
    
    echo "✅ Berhasil memperbarui status order Shopee ke: $correctStatus\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
