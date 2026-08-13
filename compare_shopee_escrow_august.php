<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;

echo "=========================================================================================================\n";
echo "PENGECEKAN & PERBANDINGAN KOMPARASI BIAYA ADMIN REAL SHOPEE VS ERP\n";
echo "Toko: NUSANTARA SERAGAM | Periode Dilepas: 01 Agustus 2026 s/d 02 Agustus 2026\n";
echo "=========================================================================================================\n\n";

$store = Store::where('store_name', 'like', '%Nusantara%Seragam%')
    ->whereHas('channel', function($q) { $q->where('code', 'shopee'); })
    ->first();

if (!$store) {
    echo "❌ Toko Shopee 'NUSANTARA SERAGAM' tidak ditemukan di database ERP.\n";
    exit;
}

echo "Toko Ditemukan: {$store->store_name} (ID: {$store->id}, Marketplace Store ID: {$store->marketplace_store_id})\n\n";

$shopeeService = app(ShopeeService::class);
$accessToken = $store->getValidAccessToken();

// Ambil order yang completed/dilepas pada tanggal 1-2 Agustus 2026
$orders = Order::where('store_id', $store->id)
    ->whereBetween('completed_at', ['2026-08-01 00:00:00', '2026-08-02 23:59:59'])
    ->get();

if ($orders->isEmpty()) {
    // Jika completed_at belum terisi, cari order yang dibuat akhir Juli dan cair 1-2 Agustus
    $orders = Order::where('store_id', $store->id)
        ->whereIn('order_status', ['COMPLETED', 'SELESAI', 'FINISHED'])
        ->whereBetween('order_date', ['2026-07-15 00:00:00', '2026-08-02 23:59:59'])
        ->get();
}

echo "Menemukan " . $orders->count() . " pesanan cair/selesai untuk toko ini pada periode tersebut.\n\n";

printf("%-3s | %-16s | %-10s | %-10s | %-10s | %-10s | %-10s | %-10s | %-10s\n", 
    "No", "No. Order Shopee", "Omset Kotor", "Platf. API", "Ongkir API", "Layan. API", "Promo API", "Lain. API", "Total Admin");
echo str_repeat("-", 110) . "\n";

$i = 1;
$totalOmsetGross = 0;
$totalPlatformFee = 0;
$totalFreeShipping = 0;
$totalServiceFee = 0;
$totalPromoFee = 0;
$totalOtherFee = 0;
$totalAdminFee = 0;
$totalNetReleased = 0;

foreach ($orders as $order) {
    $orderSn = trim($order->order_marketplace_id);
    if (empty($orderSn)) continue;

    $apiData = [];
    try {
        $escrowRes = $shopeeService->getEscrowDetail($accessToken, (int)$store->marketplace_store_id, $orderSn);
        $apiData = $escrowRes['response']['order_income'] ?? $escrowRes['order_income'] ?? [];
    } catch (\Exception $e) {}

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

    $totalOmsetGross  += $gross;
    $totalPlatformFee += $plat;
    $totalFreeShipping+= $ship;
    $totalServiceFee  += $serv;
    $totalPromoFee    += $prom;
    $totalOtherFee    += $othr;
    $totalAdminFee    += $tFee;
    $totalNetReleased += $net;

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
}

echo str_repeat("-", 110) . "\n";
printf("%-23s | Rp %-8s | -%-9s | -%-9s | -%-9s | -%-9s | -%-9s | -%-9s\n",
    "TOTAL REKAPITULASI:",
    number_format($totalOmsetGross),
    number_format($totalPlatformFee),
    number_format($totalFreeShipping),
    number_format($totalServiceFee),
    number_format($totalPromoFee),
    number_format($totalOtherFee),
    number_format($totalAdminFee)
);
echo "=========================================================================================================\n";
echo "DANA DILEPAS NET AKHIR (DITERIMA PENJUAL): Rp " . number_format($totalNetReleased, 2) . "\n";
echo "=========================================================================================================\n";
