<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Store;
use App\Models\Order;
use App\Services\TiktokService;

$orderId = $argv[1] ?? '585293879388046348';

echo "========================================================\n";
echo "MENEMBAK LANGSUNG TIKTOK SHOP OPEN API UNTUK ORDER: {$orderId}\n";
echo "========================================================\n\n";

$tiktokService = app(TiktokService::class);

$allStores = Store::whereHas('channel', function ($q) {
    $q->whereIn('code', ['tiktok', 'tokopedia']);
})->get();

$dbOrder = Order::where('order_marketplace_id', (string)$orderId)
    ->orWhere('invoice_number', (string)$orderId)
    ->first();

if ($dbOrder && $dbOrder->store) {
    echo "📌 Order tercatat di DB ERP pada Toko: {$dbOrder->store->store_name} (ID Toko: {$dbOrder->store->id})\n";
    // Taruh toko DB di paling depan pencarian
    $allStores = $allStores->reject(fn($s) => $s->id == $dbOrder->store_id)->prepend($dbOrder->store);
}

echo "🔍 Memeriksa pencarian di " . $allStores->count() . " Toko TikTok yang terhubung...\n\n";

$found = false;

foreach ($allStores as $store) {
    echo "Checking Toko: {$store->store_name} (ID: {$store->id})... ";
    
    try {
        try {
            $accessToken = $store->getValidAccessToken();
        } catch (\Exception $eTok) {
            $accessToken = $store->getValidAccessToken(true);
        }

        $shopCipher = $store->shop_cipher;

        if (empty($shopCipher)) {
            echo "❌ Skip (shop_cipher kosong)\n";
            continue;
        }

        $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$orderId]);
        $ordersList = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];

        if (empty($ordersList)) {
            echo "ℹ️ Order tidak ada di toko ini\n";
            continue;
        }

        $found = true;
        $tOrder = $ordersList[0];

        echo "✅ MATCHING FOUND!\n\n";
        echo "========================================================\n";
        echo "✅ TOKO MATCHING: {$store->store_name} (ID: {$store->id})\n";
        echo "========================================================\n\n";
        echo "--- 1. HASIL MENTAH DARI API TIKTOK SHOP [/order/202309/orders] ---\n";
        echo json_encode($tOrder, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

        $paymentInfo = $tOrder['payment'] ?? $tOrder['payment_info'] ?? [];
        
        $productSubtotal = (float) ($paymentInfo['original_total_product_price'] 
            ?? $paymentInfo['sub_total'] 
            ?? $paymentInfo['subtotal_after_seller_discounts'] 
            ?? 0);

        if ($productSubtotal <= 0 && !empty($tOrder['line_items'])) {
            foreach ($tOrder['line_items'] as $lItem) {
                $itemPrice = (float) ($lItem['original_price'] ?? $lItem['sale_price'] ?? 0);
                $itemQty = (int) ($lItem['quantity'] ?? 1);
                $productSubtotal += ($itemPrice * $itemQty);
            }
        }

        $buyerPaidTotal = (float) ($paymentInfo['total_amount'] ?? $paymentInfo['total'] ?? 0);
        $sellerDiscount = (float) ($paymentInfo['seller_discount'] ?? $paymentInfo['discount_amount'] ?? 0);
        $escrowAmount   = (float) ($paymentInfo['settlement_amount'] ?? $paymentInfo['escrow_amount'] ?? 0);

        $platformCommission  = (float) ($paymentInfo['net_platform_commission'] ?? $paymentInfo['platform_commission'] ?? 0);
        $preorderServiceFee  = (float) ($paymentInfo['preorder_service_fee'] ?? 0);
        $orderProcessingFee  = (float) ($paymentInfo['order_processing_fee'] ?? $paymentInfo['transaction_fee'] ?? 0);
        $growthXtraFee       = (float) ($paymentInfo['growth_xtra_fee'] ?? 0);
        $affiliateCommission = (float) ($paymentInfo['dynamic_commission'] ?? $paymentInfo['affiliate_commission'] ?? 0);
        $shippingAdjustment  = (float) ($paymentInfo['shipping_fee_adjustment'] ?? 0);

        // Tembak API Finance Statement jika ada
        try {
            $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $orderId);
            $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? [];
            if (!empty($stmtList)) {
                echo "--- 2. HASIL MENTAH DARI TIKTOK FINANCE API [/finance/202309/orders/{order_id}/statement_transactions] ---\n";
                echo json_encode($stmtList, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
            }
        } catch (\Exception $exStmt) {}

        $totalTiktokFees = $platformCommission + $preorderServiceFee + $orderProcessingFee + $growthXtraFee + $affiliateCommission + $shippingAdjustment;

        echo "========================================================\n";
        echo "📊 RINGKASAN DATA API TIKTOK SHOP UTK ORDER {$orderId}:\n";
        echo "========================================================\n";
        echo "• Subtotal Produk (Omset Kotor)   : Rp " . number_format($productSubtotal, 2, '.', ',') . "\n";
        echo "• Total Bayar Pembeli (Buyer Paid): Rp " . number_format($buyerPaidTotal, 2, '.', ',') . "\n";
        echo "• Diskon Penjual                   : Rp " . number_format($sellerDiscount, 2, '.', ',') . "\n";
        echo "--------------------------------------------------------\n";
        echo "• Biaya Komisi Platform            : Rp " . number_format($platformCommission, 2, '.', ',') . "\n";
        echo "• Biaya Layanan Pre-Order          : Rp " . number_format($preorderServiceFee, 2, '.', ',') . "\n";
        echo "• Biaya Pemrosesan Pesanan         : Rp " . number_format($orderProcessingFee, 2, '.', ',') . "\n";
        echo "• Biaya Program Growth Xtra        : Rp " . number_format($growthXtraFee, 2, '.', ',') . "\n";
        echo "• Biaya Komisi Afiliasi / Live     : Rp " . number_format($affiliateCommission, 2, '.', ',') . "\n";
        echo "• Biaya Penyesuaian Ongkir         : Rp " . number_format($shippingAdjustment, 2, '.', ',') . "\n";
        echo "--------------------------------------------------------\n";
        echo "• Total Potongan Biaya TikTok API  : Rp " . number_format($totalTiktokFees, 2, '.', ',') . "\n";
        echo "• Dana Cair Bersih (Escrow Amount) : Rp " . number_format($escrowAmount > 0 ? $escrowAmount : ($productSubtotal - $totalTiktokFees), 2, '.', ',') . "\n";
        echo "========================================================\n";
        break;

    } catch (\Exception $e) {
        if (str_contains($e->getMessage(), 'Internal error')) {
            echo "ℹ️ Order tidak ditemukan di toko ini (TikTok API Code 105001)\n";
        } else {
            echo "❌ Exception: " . $e->getMessage() . "\n";
        }
    }
}

if (!$found) {
    echo "\n⚠️ Order ID '{$orderId}' tidak ditemukan di API TikTok Shop toko manapun yang terhubung.\n";
}
