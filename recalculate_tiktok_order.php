<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

$orderMarketplaceId = $argv[1] ?? '585293879388046348';

echo "========================================================\n";
echo "REKALKULASI DRIVER OMSET KOTOR & OMSET BERSIH TIKTOK\n";
echo "Nomor Order Marketplace: {$orderMarketplaceId}\n";
echo "========================================================\n\n";

$order = Order::where('order_marketplace_id', (string)$orderMarketplaceId)->first();

if (!$order) {
    echo "❌ Order dengan ID '{$orderMarketplaceId}' belum ada di database lokal ERP.\n";
    exit;
}

echo "DATA LAMA DI ERP:\n";
echo "- Total Amount (Omset Kotor) : Rp " . number_format($order->total_amount, 2, '.', ',') . "\n";
echo "- Marketplace Fee            : Rp " . number_format($order->marketplace_fee, 2, '.', ',') . "\n";
echo "- Net Amount (Omset Bersih)  : Rp " . number_format($order->net_amount, 2, '.', ',') . "\n\n";

// Menggunakan data settlement presisi TikTok jika ada di financial_breakdown atau kalkulasi baru
$fb = $order->financial_breakdown ?? [];

// Omset kotor = Subtotal Produk
$itemSubtotal = $order->items->sum('total_price');
if ($itemSubtotal > 0) {
    $order->total_amount = $itemSubtotal;
}

if (!empty($fb['escrow_amount']) && (float)$fb['escrow_amount'] > 0) {
    $order->net_amount = (float)$fb['escrow_amount'];
    $order->marketplace_fee = max(0.0, (float)$order->total_amount - (float)$order->net_amount);
} elseif (!empty($fb['net_platform_commission'])) {
    $fees = (float)($fb['net_platform_commission'] ?? 0)
          + (float)($fb['preorder_service_fee'] ?? 0)
          + (float)($fb['dynamic_commission'] ?? 0)
          + (float)($fb['growth_xtra_fee'] ?? 0)
          + (float)($fb['order_processing_fee'] ?? 0);
    $order->marketplace_fee = $fees;
    $order->net_amount = max(0.0, (float)$order->total_amount - $fees);
}

$order->save();

echo "DATA HASIL PERBAIKAN DI ERP:\n";
echo "- Total Amount (Omset Kotor) : Rp " . number_format($order->total_amount, 2, '.', ',') . "\n";
echo "- Marketplace Fee            : Rp " . number_format($order->marketplace_fee, 2, '.', ',') . "\n";
echo "- Net Amount (Omset Bersih)  : Rp " . number_format($order->net_amount, 2, '.', ',') . "\n";
echo "========================================================\n";
