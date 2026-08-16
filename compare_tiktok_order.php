<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Services\TiktokService;

$orderSn = $argv[1] ?? '585200777628452396';

$order = Order::where('order_marketplace_id', $orderSn)
    ->orWhere('order_marketplace_id', 'LIKE', '%' . $orderSn . '%')
    ->first();

if (!$order) {
    echo "❌ Order SN TikTok '{$orderSn}' tidak ditemukan di Database ERP!\n";
    exit;
}

$store = $order->store;

echo "=======================================================\n";
echo "🎵 PERBANDINGAN RINCIAN ORDER TIKTOK: {$order->order_marketplace_id}\n";
echo "=======================================================\n";
echo "Toko             : " . ($store->name ?? '-') . " (" . strtoupper($store->channel->code ?? '-') . ")\n";
echo "Status Order ERP : " . $order->order_status . "\n";
echo "Tanggal Order    : " . $order->order_date . "\n";
echo "Tanggal Selesai  : " . ($order->completed_at ?: '-') . "\n";
echo "Nama Pembeli     : " . ($order->buyer_name ?: '-') . "\n\n";

echo "-------------------------------------------------------\n";
echo "1. DATA DI DATABASE ERP LOKAL SAAT INI:\n";
echo "-------------------------------------------------------\n";
echo "  • Omset Kotor (total_amount)   : Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
echo "  • Biaya Admin (marketplace_fee): Rp " . number_format($order->marketplace_fee, 0, ',', '.') . "\n";
echo "  • Omset Bersih (net_amount)    : Rp " . number_format($order->net_amount, 0, ',', '.') . "\n";
echo "  • Stored Financial Breakdown   : " . json_encode($order->financial_breakdown ?? []) . "\n\n";

echo "-------------------------------------------------------\n";
echo "2. DATA LIVE API TIKTOK SHOP RESMI:\n";
echo "-------------------------------------------------------\n";

if ($store && (in_array(strtolower($store->channel->code ?? ''), ['tiktok', 'tiktok_shop', 'tokopedia']) || $store->channel_id == 3)) {
    try {
        $tiktokService = app(TiktokService::class);
        $accessToken = $store->getValidAccessToken();
        $shopCipher = $store->shop_cipher;

        $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$order->order_marketplace_id]);
        $tOrder = $detailRes['orders'][0] ?? $detailRes['order_list'][0] ?? [];

        if (!empty($tOrder)) {
            $paymentInfo = $tOrder['payment_info'] ?? $tOrder['payment'] ?? [];
            $itemList = $tOrder['line_items'] ?? $tOrder['item_list'] ?? [];
            
            $productSubtotal = 0.0;
            foreach ($itemList as $it) {
                $productSubtotal += ((float)($it['original_price'] ?? $it['sale_price'] ?? 0) * (int)($it['quantity'] ?? 1));
            }

            $subtotalAfterSeller = (float) ($paymentInfo['subtotal_after_seller_discounts'] ?? $paymentInfo['after_seller_discounts_subtotal_amount'] ?? $paymentInfo['sub_total'] ?? $paymentInfo['subtotal'] ?? 0);
            $totalAmount = $subtotalAfterSeller > 0 ? $subtotalAfterSeller : ($productSubtotal > 0 ? $productSubtotal : (float) ($paymentInfo['total_amount'] ?? $tOrder['total_amount'] ?? $order->total_amount));
            $buyerPaidTotal = (float) ($paymentInfo['total_amount'] ?? $paymentInfo['total'] ?? $totalAmount);
            $escrowAmount = (float) ($paymentInfo['settlement_amount'] ?? $paymentInfo['escrow_amount'] ?? 0);
            
            $platformCommission = (float) ($paymentInfo['platform_commission'] ?? 0);
            $growthXtraFee = (float) ($paymentInfo['growth_xtra_fee'] ?? 0);
            $orderProcessingFee = (float) ($paymentInfo['order_processing_fee'] ?? $paymentInfo['transaction_fee'] ?? 0);

            // Tembak API Finance TikTok untuk mengambil data settlement transaksi resmi yang sudah cair
            $feeFromStmt = 0.0;
            $revenueFromStmt = 0.0;
            $settlementFromStmt = 0.0;

            try {
                $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $order->order_marketplace_id);
                $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? $stmtData['transactions'] ?? [];
                foreach ($stmtList as $st) {
                    if (isset($st['revenue_amount']) && (float)$st['revenue_amount'] > 0) {
                        $revenueFromStmt = (float)$st['revenue_amount'];
                    } elseif (isset($st['net_sales_amount']) && (float)$st['net_sales_amount'] > 0) {
                        $revenueFromStmt = (float)$st['net_sales_amount'];
                    }

                    if (isset($st['fee_amount']) && (float)$st['fee_amount'] != 0) {
                        $feeFromStmt = abs((float)$st['fee_amount']);
                    }

                    if (isset($st['settlement_amount']) && (float)$st['settlement_amount'] > 0) {
                        $settlementFromStmt = (float)$st['settlement_amount'];
                    }

                    if (isset($st['platform_commission_amount']) && (float)$st['platform_commission_amount'] != 0) {
                        $platformCommission = abs((float)$st['platform_commission_amount']);
                    }

                    if (isset($st['seller_discount_amount']) && (float)$st['seller_discount_amount'] != 0) {
                        $sellerDiscount = abs((float)$st['seller_discount_amount']);
                    }
                }
            } catch (\Exception $exStmt) {}

            if ($revenueFromStmt > 0) {
                $totalAmount = $revenueFromStmt;
            }

            if ($settlementFromStmt > 0) {
                $escrowAmount = $settlementFromStmt;
            }

            $sellerDiscount = (float) ($paymentInfo['seller_discount'] ?? $paymentInfo['discount_amount'] ?? $sellerDiscount ?? 0);
            $actualShipping = (float) ($paymentInfo['shipping_fee'] ?? $paymentInfo['actual_shipping_fee'] ?? 0);
            $shippingSubsidy = (float) ($paymentInfo['shipping_fee_subsidy'] ?? $paymentInfo['platform_shipping_discount'] ?? 0);
            $platformDiscount = (float) ($paymentInfo['platform_discount'] ?? 0);
            $withholdingTax = (float) ($paymentInfo['withholding_tax'] ?? $paymentInfo['tax_amount'] ?? 0);
            $sellerReturnRefund = (float) ($paymentInfo['refund_amount'] ?? $paymentInfo['return_amount'] ?? 0);
            $totalAdjustment = (float) ($paymentInfo['total_adjustment_amount'] ?? $paymentInfo['adjustment_amount'] ?? 0);
            $protectionFee = (float) ($paymentInfo['shipping_seller_protection_fee_amount'] ?? $protectionFee ?? 0);

            if ($feeFromStmt > 0) {
                $totalTiktokFees = $feeFromStmt;
            } elseif ($escrowAmount > 0 && $totalAmount > $escrowAmount) {
                $totalTiktokFees = max(0.0, $totalAmount - $escrowAmount);
            } else {
                $totalTiktokFees = $platformCommission + $growthXtraFee + $orderProcessingFee + $sellerDiscount + $withholdingTax + $sellerReturnRefund + $totalAdjustment + $protectionFee;
            }
            if ($totalTiktokFees <= 0) {
                $totalTiktokFees = round($totalAmount * 0.085);
            }

            if ($escrowAmount <= 0) {
                $escrowAmount = max(0.0, $totalAmount - $totalTiktokFees);
            }

            echo "  • Subtotal Produk (Gross)      : Rp " . number_format($totalAmount, 0, ',', '.') . "\n";
            echo "  • Total Bayar Pembeli          : Rp " . number_format($buyerPaidTotal, 0, ',', '.') . "\n";
            echo "  • Komisi Platform TikTok       : Rp " . number_format($platformCommission, 0, ',', '.') . "\n";
            echo "  • Biaya Program Growth/XTRA    : Rp " . number_format($growthXtraFee, 0, ',', '.') . "\n";
            echo "  • Biaya Transaksi / Processing  : Rp " . number_format($orderProcessingFee, 0, ',', '.') . "\n";
            echo "  • Diskon Seller (Voucher)      : Rp " . number_format($sellerDiscount, 0, ',', '.') . "\n";
            echo "  • Pajak / Withholding Tax      : Rp " . number_format($withholdingTax, 0, ',', '.') . "\n";
            echo "  • Return / Refund Seller       : Rp " . number_format($sellerReturnRefund, 0, ',', '.') . "\n";
            echo "  • TOTAL BIAYA ADMIN TIKTOK     : Rp " . number_format($totalTiktokFees, 0, ',', '.') . "\n";
            echo "  • DANA CAIR BERSIH ESCROW      : Rp " . number_format($escrowAmount, 0, ',', '.') . "\n\n";

            echo "-------------------------------------------------------\n";
            echo "3. ANALISA SELISIH HASIL PERBANDINGAN:\n";
            echo "-------------------------------------------------------\n";
            $diffGross = $order->total_amount - $totalAmount;
            $diffAdmin = $order->marketplace_fee - $totalTiktokFees;
            $diffNet   = $order->net_amount - $escrowAmount;

            echo "  • Selisih Omset Kotor : Rp " . number_format($diffGross, 0, ',', '.') . ($diffGross == 0 ? " (✅ MATCH)" : " (⚠️ BEDA)") . "\n";
            echo "  • Selisih Biaya Admin : Rp " . number_format($diffAdmin, 0, ',', '.') . ($diffAdmin == 0 ? " (✅ MATCH)" : " (⚠️ BEDA)") . "\n";
            echo "  • Selisih Dana Cair   : Rp " . number_format($diffNet, 0, ',', '.') . ($diffNet == 0 ? " (✅ MATCH)" : " (⚠️ BEDA)") . "\n";
        } else {
            echo "⚠️ Response API TikTok kosong untuk Order ID ini.\n";
        }
    } catch (\Exception $e) {
        echo "❌ Gagal memanggil API TikTok: " . $e->getMessage() . "\n";
    }
} else {
    echo "Order ini bukan dari TikTok Shop.\n";
}
