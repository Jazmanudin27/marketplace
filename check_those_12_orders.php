<?php

/**
 * ============================================================
 * DIAGNOSIS 12 ORDER DI PERIOD 16-30 JULI
 * ============================================================
 * Menjelaskan kenapa 12 order ini terdeteksi "PERLU PULL"
 * (Ternyata 12 order ini SUDAH ADA di ERP dengan status SHIPPED/READY_TO_SHIP)
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;

$storeId = 33;
$store = Store::find($storeId);
$accessToken = $store->getValidAccessToken();
$shopId = (int)$store->marketplace_store_id;

$startTs = strtotime('2026-07-16 00:00:00');
$endTs   = strtotime('2026-07-30 23:59:59');

$shopeeService = app(ShopeeService::class);

echo "\n";
echo "======================================================================\n";
echo "  PEMERIKSAAN 12 ORDER SHOPEE TANGGAL 16-30 JULI 2026\n";
echo "======================================================================\n\n";

$cursor = '';
$allOrderSn = [];
do {
    $resp = $shopeeService->getOrderList($accessToken, $shopId, $startTs, $endTs, 'create_time', $cursor);
    $list = $resp['order_list'] ?? [];
    foreach ($list as $o) {
        if (!empty($o['order_sn'])) $allOrderSn[] = $o['order_sn'];
    }
    $cursor = $resp['next_cursor'] ?? '';
} while (!empty($cursor));

$allOrderSn = array_unique($allOrderSn);

echo "Total Order di Shopee API (16-30 Juli) : " . count($allOrderSn) . " order\n";

// Cek keberadaan di ERP (SEMUA STATUS)
$existingInErp = Order::where('store_id', $storeId)
    ->whereIn('order_marketplace_id', $allOrderSn)
    ->get();

echo "Total Order SUDAH ADA DI ERP           : " . $existingInErp->count() . " order\n\n";

echo "--- RINCIAN STATUS 395 ORDER TERSEBUT DI ERP ---\n";
$byStatus = $existingInErp->groupBy('order_status');
foreach ($byStatus as $st => $orders) {
    echo "  • Status '{$st}' : " . $orders->count() . " order\n";
}

$missingTotal = count($allOrderSn) - $existingInErp->count();
echo "\nOrder yang BENAR-BENAR BELUM ADA di ERP (Missing Total) : {$missingTotal} order\n";
echo "======================================================================\n\n";
