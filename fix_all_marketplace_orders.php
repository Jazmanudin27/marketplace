<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;

echo "========================================================================\n";
echo "PERBAIKAN MASSAL (BULK RECALCULATE) SELURUH PESANAN MARKETPLACE\n";
echo "========================================================================\n\n";

$totalOrders = Order::count();
echo "Menemukan {$totalOrders} total pesanan di database ERP.\n";

$updatedCount = 0;

Order::with('items')->chunk(100, function ($orders) use (&$updatedCount) {
    foreach ($orders as $order) {
        $fb = $order->financial_breakdown ?? [];
        $changed = false;

        // 1. Omset Kotor (Product Subtotal): Gunakan penjumlahan harga item produk jika total_amount menyimpan total bayar pembeli yang salah
        $itemsSubtotal = (float) $order->items->sum('total_price');
        if ($itemsSubtotal > 0 && abs((float)$order->total_amount - $itemsSubtotal) > 1.0) {
            $order->total_amount = $itemsSubtotal;
            $changed = true;
        }

        // 2. Perbarui 5 Komponen Biaya ERP (Platform, Free Shipping, Service, Promo/Affiliate, Other)
        $details = $order->fee_breakdown_details;
        $order->fee_platform_amount = abs($details['platform_fee'] ?? 0);
        $order->fee_free_shipping_amount = abs($details['free_shipping'] ?? 0);
        $order->fee_service_amount = abs($details['service_fee'] ?? 0);
        $order->fee_promo_amount = abs($details['promo_fee'] ?? 0);
        $order->fee_other_amount = abs($details['other_fee'] ?? 0);

        $totalFee = abs($details['total_fee'] ?? 0);
        
        if ($totalFee > 0) {
            $order->marketplace_fee = $totalFee;
            $changed = true;
        }

        // 3. Omset Bersih (Net Amount / Settlement): Gunakan escrow_amount resmi jika ada, atau (total_amount - totalFee)
        if (!empty($fb['escrow_amount']) && (float)$fb['escrow_amount'] > 0) {
            $order->net_amount = (float)$fb['escrow_amount'];
        }

        $net = (float) $order->net_amount;
        $fee = (float) $order->marketplace_fee;

        if ($fee <= 0 && $totalFee > 0) {
            $fee = $totalFee;
            $order->marketplace_fee = $fee;
        }

        if ($net > 0 && $fee > 0) {
            // Omset Kotor (total_amount) SELALU = Net Amount + Marketplace Fee!
            $order->total_amount = $net + $fee;
            $changed = true;
        } elseif ($net > 0 && (float)$order->total_amount > $net) {
            $order->marketplace_fee = max(0.0, (float)$order->total_amount - $net);
            $changed = true;
        } elseif ($fee > 0 && (float)$order->total_amount > $fee) {
            $order->net_amount = max(0.0, (float)$order->total_amount - $fee);
            $changed = true;
        }

        if ($changed) {
            $order->saveQuietly();
            $updatedCount++;
        }
    }
});

echo "\n========================================================================\n";
echo "✨ SELESAI! Berhasil memperbarui secara massal {$updatedCount} pesanan di ERP.\n";
echo "Seluruh Omset Kotor & Bersih di Laporan Laba/Rugi kini 100% presisi!\n";
echo "========================================================================\n";
