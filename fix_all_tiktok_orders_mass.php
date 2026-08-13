<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;

echo "========================================================================\n";
echo "REKALKULASI MASSAL (BULK UPDATE) SELURUH PESANAN TIKTOK SHOP DI ERP\n";
echo "========================================================================\n\n";

$tiktokOrders = Order::whereHas('store.channel', function($q) {
    $q->whereIn('code', ['tiktok', 'tokopedia']);
})->get();

echo "Menemukan " . $tiktokOrders->count() . " total pesanan TikTok Shop di database ERP.\n\n";

$updatedCount = 0;

foreach ($tiktokOrders as $order) {
    $fb = $order->financial_breakdown ?? [];

    // 1. Subtotal Produk (Omset Kotor Murni)
    $itemsSubtotal = (float) $order->items->sum('total_price');
    if ($itemsSubtotal > 0 && abs((float)$order->total_amount - $itemsSubtotal) > 1.0) {
        $order->total_amount = $itemsSubtotal;
    }

    // 2. Jika order 585293879388046348 atau rincian fee TikTok belum ada di DB
    if (!empty($fb['net_platform_commission']) || !empty($fb['platform_commission']) || !empty($fb['service_fee'])) {
        $details = $order->fee_breakdown_details;
        $totalFee = abs($details['total_fee'] ?? 0);

        if ($totalFee > 0) {
            $order->marketplace_fee = $totalFee;
            $order->net_amount = max(0.0, (float)$order->total_amount - $totalFee);
        }
    } else {
        // Skema persentase komisi TikTok (Rata-rata 24.3% dari omset kotor untuk TikTok Shop TikTok)
        // Atau hitung dari selisih jika buyer_paid_total tercatat
        $buyerPaid = (float)($fb['buyer_paid_total'] ?? $order->total_amount);
        $totalFee = (float)($fb['total_fees'] ?? 0);

        if ($totalFee <= 0) {
            // Hitung dari perbandingan rasio settlement
            $ratio = 0.2431; // 24.31% total TikTok fees
            $totalFee = round($order->total_amount * $ratio);
        }

        $order->marketplace_fee = $totalFee;
        $order->net_amount = max(0.0, (float)$order->total_amount - $totalFee);
    }

    $order->saveQuietly();
    $updatedCount++;
}

echo "========================================================================\n";
echo "✨ SELESAI! Berhasil memperbarui secara massal {$updatedCount} pesanan TikTok di ERP.\n";
echo "========================================================================\n";
