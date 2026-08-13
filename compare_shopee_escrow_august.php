<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;

$targetOrderSn = $argv[1] ?? null;

echo "=========================================================================================================\n";
echo "PENGECEKAN & PERBANDINGAN KOMPARASI BIAYA ADMIN REAL SHOPEE VS ERP\n";
if ($targetOrderSn) {
    echo "Target Order SN: {$targetOrderSn}\n";
} else {
    echo "Toko: NUSANTARA SERAGAM | Periode Dilepas: 01 Agustus 2026 s/d 02 Agustus 2026\n";
}
echo "=========================================================================================================\n\n";

$store = Store::where('store_name', 'like', '%Nusantara%Seragam%')
    ->whereHas('channel', function($q) { $q->where('code', 'shopee'); })
    ->first();

if (!$store) {
    echo "❌ Toko Shopee 'NUSANTARA SERAGAM' tidak ditemukan di database ERP.\n";
    exit;
}

$shopeeService = app(ShopeeService::class);
$accessToken = $store->getValidAccessToken();

if ($targetOrderSn) {
    $orders = Order::where('store_id', $store->id)
        ->where('order_marketplace_id', trim($targetOrderSn))
        ->get();
    
    if ($orders->isEmpty()) {
        $orders = Order::where('order_marketplace_id', trim($targetOrderSn))->get();
    }
} else {
    // Ambil order yang completed/dilepas pada tanggal 1-2 Agustus 2026
    $orders = Order::where('store_id', $store->id)
        ->whereBetween('completed_at', ['2026-08-01 00:00:00', '2026-08-02 23:59:59'])
        ->get();

    if ($orders->isEmpty()) {
        $orders = Order::where('store_id', $store->id)
            ->whereIn('order_status', ['COMPLETED', 'SELESAI', 'FINISHED'])
            ->whereBetween('order_date', ['2026-07-15 00:00:00', '2026-08-02 23:59:59'])
            ->get();
    }
}

if ($orders->isEmpty()) {
    echo "❌ Order SN '{$targetOrderSn}' tidak ditemukan di database ERP.\n";
    exit;
}

echo "Menemukan " . $orders->count() . " pesanan untuk diuji.\n\n";

printf("%-3s | %-16s | %-10s | %-10s | %-10s | %-10s | %-10s | %-10s | %-10s\n", 
    "No", "No. Order Shopee", "Omset Kotor", "Platf. API", "Ongkir API", "Layan. API", "Promo API", "Lain. API", "Total Admin");
echo str_repeat("-", 110) . "\n";

$i = 1;
foreach ($orders as $order) {
    $orderSn = trim($order->order_marketplace_id);
    if (empty($orderSn)) continue;

    $apiData = [];
    try {
        $escrowRes = $shopeeService->getEscrowDetail($accessToken, (int)$store->marketplace_store_id, $orderSn);
        $apiData = $escrowRes['order_income'] ?? $escrowRes['response']['order_income'] ?? $escrowRes;
    } catch (\Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }

    // Simpan API Escrow real ke DB
    if (!empty($apiData)) {
        $order->financial_breakdown = array_merge($order->financial_breakdown ?? [], $apiData);
        $details = $order->fee_breakdown_details;

        $order->fee_platform_amount = abs($details['platform_fee'] ?? 0);
        $order->fee_free_shipping_amount = abs($details['free_shipping'] ?? 0);
        $order->fee_service_amount = abs($details['service_fee'] ?? 0);
        $order->fee_promo_amount = abs($details['promo_fee'] ?? 0);
        $order->fee_other_amount = abs($details['other_fee'] ?? 0);
        $order->marketplace_fee = abs($details['total_fee'] ?? 0);
        $order->net_amount = max(0.0, (float)$order->total_amount - (float)$order->marketplace_fee);
        $order->save();
    }

    $details = $order->fee_breakdown_details;

    $plat  = abs($details['platform_fee'] ?? 0);
    $ship  = abs($details['free_shipping'] ?? 0);
    $serv  = abs($details['service_fee'] ?? 0);
    $prom  = abs($details['promo_fee'] ?? 0);
    $othr  = abs($details['other_fee'] ?? 0);
    $tFee  = abs($details['total_fee'] ?? 0);
    $gross = (float) $order->total_amount;
    $net   = (float) $order->net_amount;

    printf("%-3d | %-16s | Rp %-8s | -%-9s | -%-9s | -%-9s | -%-9s | -%-9s | -%-9s\n",
        $i++,
        $orderSn,
        number_format($gross),
        number_format($plat),
        number_format($ship),
        number_format($serv),
        number_format($prom),
        number_format($othr),
        number_format($tFee)
    );

    echo "\n---------------------------------------------------------------------------------------------------------\n";
    echo "📌 KUALIFIKASI POPULASI DATA REAL API SHOPEE (ORDER {$orderSn}):\n";
    echo "   • Subtotal Omset Kotor   : Rp " . number_format($gross) . "\n";
    echo "   • Biaya Platform (8.405) : - Rp " . number_format($plat) . "\n";
    echo "   • Gratis Ongkir (4.373)  : - Rp " . number_format($ship) . "\n";
    echo "   • Biaya Layanan (0)      : - Rp " . number_format($serv) . "\n";
    echo "   • Biaya Promosi (1.324)  : - Rp " . number_format($prom) . "\n";
    echo "   • Biaya Lainnya (3.133)  : - Rp " . number_format($othr) . "\n";
    echo "   • TOTAL POTONGAN ADMIN   : - Rp " . number_format($tFee) . " (Target Excel: 17.235)\n";
    echo "   • DANA CAIR NET AKHIR    : Rp " . number_format($net) . " (Target Excel: 62.265)\n";
    echo "---------------------------------------------------------------------------------------------------------\n";
}
