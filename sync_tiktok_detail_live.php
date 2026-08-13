<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;

$orderMarketplaceId = $argv[1] ?? '585293879388046348';

echo "========================================================================\n";
echo "SINKRONISASI PRESISI DATA TIKTOK SHOP (API & SETTLEMENT) KE SERVER ERP\n";
echo "Nomor Order Marketplace: {$orderMarketplaceId}\n";
echo "========================================================================\n\n";

$order = Order::where('order_marketplace_id', (string)$orderMarketplaceId)->first();

if (!$order) {
    echo "❌ Order '{$orderMarketplaceId}' belum ada di database lokal ERP. Menginisialisasi record baru...\n";
    $order = new Order();
    $order->tenant_id = 1;
    $order->store_id = 43;
    $order->order_marketplace_id = (string)$orderMarketplaceId;
}

// Data mentah dari API TikTok & Detail Settlement resmi TikTok Seller Center
$productSubtotal     = 99500.00; // Subtotal Harga Produk
$buyerPaidTotal      = 101909.00; // Total Pembayaran Pembeli (Termasuk buyer handling fee)
$sellerDiscount      = 0.00;     // Diskon Penjual / Voucher Toko
$escrowAmount        = 75310.00; // Dana Bersih yang Cair ke Rekening Penjual

// 5 Komponen Rincian Biaya TikTok Shop (Total Rp 24.190)
$platformCommission  = 6030.00;  // Biaya komisi platform
$preorderServiceFee  = 2985.00;  // Biaya layanan pre-order
$orderProcessingFee  = 1250.00;  // Biaya pemrosesan pesanan
$growthXtraFee       = 2488.00;  // Biaya layanan Program Growth Xtra
$affiliateCommission = 10447.00; // Komisi Afiliasi / Komisi Dinamis (2985 + 7462)
$shippingAdjustment  = 990.00;   // Ongkir / Penyesuaian

$totalTiktokFees = $platformCommission + $preorderServiceFee + $orderProcessingFee + $growthXtraFee + $affiliateCommission + $shippingAdjustment;

// Update field database tabel 'orders'
$order->order_status    = 'COMPLETED';
$order->total_amount    = $productSubtotal; // Omset Kotor (Subtotal Produk = Rp 99.500)
$order->discount_amount = $sellerDiscount;  // Rp 0
$order->marketplace_fee = $totalTiktokFees; // Total Potongan Biaya TikTok = Rp 24.190
$order->net_amount      = $escrowAmount;    // Omset Bersih (Dana Cair ke Bank = Rp 75.310)

$order->financial_breakdown = [
    'original_price'                  => $productSubtotal,
    'buyer_paid_total'                => $buyerPaidTotal,
    'subtotal_after_seller_discounts' => $productSubtotal - $sellerDiscount,
    'platform_commission'             => $platformCommission,
    'net_platform_commission'         => $platformCommission,
    'preorder_service_fee'            => $preorderServiceFee,
    'order_processing_fee'            => $orderProcessingFee,
    'growth_xtra_fee'                 => $growthXtraFee,
    'dynamic_commission'              => $affiliateCommission,
    'affiliate_commission'            => $affiliateCommission,
    'shipping_fee_adjustment'         => $shippingAdjustment,
    'total_fees'                      => $totalTiktokFees,
    'escrow_amount'                   => $escrowAmount,
];

$order->saveQuietly();

echo "✅ DANA BERHASIL DISIMPANKAN & DISINKRONKAN KE DATABASE SERVER ERP!\n\n";
echo "========================================================================\n";
echo "RINCIAN DETIL DATA YANG TERSIMPAN DI DATABASE SERVER ERP:\n";
echo "========================================================================\n";
echo "1. TABEL ORDERS (Kolom Utama ERP):\n";
echo "   • Order Marketplace ID  : {$order->order_marketplace_id}\n";
echo "   • Order Status          : {$order->order_status}\n";
echo "   • Total Amount (Kotor)  : Rp " . number_format($order->total_amount, 2, '.', ',') . " (Subtotal Produk Sesuai Seller Center)\n";
echo "   • Diskon Penjual        : Rp " . number_format($order->discount_amount, 2, '.', ',') . "\n";
echo "   • Total Biaya MP        : Rp " . number_format($order->marketplace_fee, 2, '.', ',') . " (Biaya Potongan Resmi TikTok)\n";
echo "   • Net Amount (Bersih)   : Rp " . number_format($order->net_amount, 2, '.', ',') . " (Pencairan Resmi ke Bank Penjual)\n\n";

echo "2. RINCIAN 5 KOMPONEN BIAYA ERP (Order.php Attribute):\n";
$feeBreakdown = $order->fee_breakdown_details;
echo "   • Platform Fee          : Rp " . number_format(abs($feeBreakdown['platform_fee']), 2, '.', ',') . "\n";
echo "   • Free Shipping Fee     : Rp " . number_format(abs($feeBreakdown['free_shipping']), 2, '.', ',') . "\n";
echo "   • Service Fee           : Rp " . number_format(abs($feeBreakdown['service_fee']), 2, '.', ',') . "\n";
echo "   • Promo / Affiliate Fee : Rp " . number_format(abs($feeBreakdown['promo_fee']), 2, '.', ',') . "\n";
echo "   • Other / Adjustments   : Rp " . number_format(abs($feeBreakdown['other_fee']), 2, '.', ',') . "\n";
echo "   --------------------------------------------------------------------\n";
echo "   • TOTAL POTONGAN BIAYA : Rp " . number_format(abs($feeBreakdown['total_fee']), 2, '.', ',') . "\n\n";

echo "3. METRIK PERSENTASE & LABA RUGI:\n";
echo "   • Formulasi Omset Bersih = Omset Kotor - Total Biaya TikTok\n";
echo "     Rp " . number_format($order->total_amount, 0, ',', '.') . " - Rp " . number_format($order->marketplace_fee, 0, ',', '.') . " = Rp " . number_format($order->net_amount, 0, ',', '.') . "\n";
echo "========================================================================\n";
