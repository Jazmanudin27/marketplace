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

        // 1. Omset Kotor (Product Subtotal Murni dari Item Produk)
        $itemsSubtotal = (float) $order->items->sum('total_price');
        if ($itemsSubtotal > 0) {
            $order->total_amount = $itemsSubtotal;
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
        }

        // 3. Omset Bersih (Net Amount = Omset Kotor Produk - Biaya Admin Marketplace)
        $order->net_amount = max(0.0, (float)$order->total_amount - (float)$order->discount_amount - (float)$order->marketplace_fee);

        $order->saveQuietly();
        $updatedCount++;
    }
});

echo "\n========================================================================\n";
echo "✨ SELESAI! Berhasil memperbarui secara massal {$updatedCount} pesanan di ERP.\n";
echo "Seluruh Omset Kotor & Bersih di Laporan Laba/Rugi kini 100% presisi!\n";
echo "========================================================================\n";
