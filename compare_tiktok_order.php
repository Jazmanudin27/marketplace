<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Services\TiktokService;

$orderSn = '585492149817410871';

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

            $totalAmount = $productSubtotal > 0 ? $productSubtotal : (float) ($paymentInfo['total_amount'] ?? $tOrder['total_amount'] ?? $order->total_amount);
            $buyerPaidTotal = (float) ($paymentInfo['total_amount'] ?? $paymentInfo['total'] ?? $totalAmount);
            $escrowAmount = (float) ($paymentInfo['settlement_amount'] ?? $paymentInfo['escrow_amount'] ?? 0);
            
            $platformCommission = (float) ($paymentInfo['platform_commission'] ?? 0);
            $growthXtraFee = (float) ($paymentInfo['growth_xtra_fee'] ?? 0);
            $orderProcessingFee = (float) ($paymentInfo['order_processing_fee'] ?? $paymentInfo['transaction_fee'] ?? 0);

            // Tembak Statement API jika settlement_amount belum ada
            if ($escrowAmount <= 0) {
                try {
                    $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $order->order_marketplace_id);
                    $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? [];
                    foreach ($stmtList as $st) {
                        $amt = (float) ($st['amount'] ?? $st['settlement_amount'] ?? 0);
                        $type = strtoupper((string)($st['type'] ?? $st['fee_type'] ?? ''));
                        if (str_contains($type, 'SETTLEMENT') || str_contains($type, 'ESCROW') || str_contains($type, 'REVENUE')) {
                            if ($amt > 0) $escrowAmount = $amt;
                        } elseif (str_contains($type, 'COMMISSION') || str_contains($type, 'PLATFORM')) {
                            $platformCommission = abs($amt);
                        } elseif (str_contains($type, 'PROCESSING') || str_contains($type, 'TRANSACTION')) {
                            $orderProcessingFee = abs($amt);
                        }
                    }
                } catch (\Exception $exStmt) {}
            }

            $totalTiktokFees = $platformCommission + $growthXtraFee + $orderProcessingFee;
            if ($totalTiktokFees <= 0 && $escrowAmount > 0) {
                $totalTiktokFees = max(0.0, $totalAmount - $escrowAmount);
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
