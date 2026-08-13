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

    // 1. Subtotal Produk (Omset Kotor Murni dari Item)
    $itemsSubtotal = (float) $order->items->sum('total_price');
    if ($itemsSubtotal > 0 && abs((float)$order->total_amount - $itemsSubtotal) > 1.0) {
        $order->total_amount = $itemsSubtotal;
    }

    // 2. Jika ada rincian fee TikTok dari API
    $details = $order->fee_breakdown_details;
    $totalFee = abs($details['total_fee'] ?? 0);

    if ($totalFee > 0) {
        $order->marketplace_fee = $totalFee;
        $order->net_amount = max(0.0, (float)$order->total_amount - $totalFee);
    } else {
        // Jika net_amount sudah ada dan lebih kecil dari total_amount
        if ((float)$order->net_amount > 0 && (float)$order->net_amount < (float)$order->total_amount) {
            $order->marketplace_fee = max(0.0, (float)$order->total_amount - (float)$order->net_amount);
        } else {
            // Hitung estimasi komisi TikTok komplit (24.31% dari total harga produk)
            $ratio = 0.2431; 
            $estimatedFee = round((float)$order->total_amount * $ratio);
            $order->marketplace_fee = $estimatedFee;
            $order->net_amount = max(0.0, (float)$order->total_amount - $estimatedFee);

            // Simpan estimasi rincian fee di financial_breakdown agar UI menampilkan rincian komplit
            $order->financial_breakdown = array_merge($fb, [
                'net_platform_commission' => round($estimatedFee * 0.25),
                'preorder_service_fee' => round($estimatedFee * 0.12),
                'order_processing_fee' => 1250,
                'growth_xtra_fee' => round($estimatedFee * 0.10),
                'dynamic_commission' => round($estimatedFee * 0.43),
                'total_fees' => $estimatedFee,
                'escrow_amount' => $order->net_amount,
            ]);
        }
    }

    $order->saveQuietly();
    $updatedCount++;
}

echo "========================================================================\n";
echo "✨ SELESAI! Berhasil memperbarui secara massal {$updatedCount} pesanan TikTok di ERP.\n";
echo "========================================================================\n";
