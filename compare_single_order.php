<?php
/**
 * SCRIPT ADU SISI (SIDE-BY-SIDE COMPARISON) SINGLE ORDER
 * Dijalankan via Terminal Server: php compare_single_order.php 585165338047579282
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;

$orderSn = $argv[1] ?? '585165338047579282';

echo "======================================================================\n";
echo " 🔍 ADU DATA SISI-DEMI-SISI ORDER: {$orderSn}\n";
echo "======================================================================\n\n";

$order = Order::where('order_marketplace_id', $orderSn)
    ->orWhere('order_marketplace_id', 'LIKE', '%' . $orderSn . '%')
    ->first();

if (!$order) {
    echo "❌ Order '{$orderSn}' belum tersimpan di DB ERP. Menjalankan penarikan API...\n";
    \Illuminate\Support\Facades\Artisan::call('tiktok:sync-escrow', ['--order_id' => $orderSn]);
    $order = Order::where('order_marketplace_id', $orderSn)->first();
}

if (!$order) {
    echo "❌ Order tidak ditemukan di API TikTok maupun DB.\n";
    exit;
}

$store = $order->store;
$tiktokService = app(TiktokService::class);

$apiGross = 0.0;
$apiSellerDisc = 0.0;
$apiRefund = 0.0;
$apiPlatformFee = 0.0;
$apiServiceFee = 0.0;
$apiPromoFee = 0.0;
$apiOtherFee = 0.0;
$apiTotalFee = 0.0;
$apiSettlement = 0.0;

try {
    $accessToken = $store->getValidAccessToken();
    $shopCipher = $store->shop_cipher;

    $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $order->order_marketplace_id);
    $stmtList = $stmtData['statement_transactions'] ?? [];
    $st0 = $stmtList[0] ?? [];

    $apiGross       = (float) ($st0['gross_sales_amount'] ?? $st0['revenue_amount'] ?? 0);
    $apiSellerDisc  = abs((float) ($st0['seller_discount_amount'] ?? 0));
    $apiRefund      = abs((float) ($st0['customer_refund_amount'] ?? $st0['gross_sales_refund_amount'] ?? 0));

    $apiPlatformFee = abs((float) ($st0['platform_commission_amount'] ?? 0));
    $apiServiceFee  = abs((float) ($st0['preorder_service_fee_amount'] ?? 0)) + abs((float) ($st0['transaction_fee_amount'] ?? 0));
    $apiPromoFee    = abs((float) ($st0['affiliate_commission_amount'] ?? 0)) + abs((float) ($st0['dynamic_commission_amount'] ?? 0));
    $apiOtherFee    = abs((float) ($st0['shipping_cost_amount'] ?? $st0['actual_shipping_fee_amount'] ?? 0));

    $apiTotalFee    = abs((float) ($st0['fee_amount'] ?? 0));
    if ($apiTotalFee == 0) {
        $apiTotalFee = $apiPlatformFee + $apiServiceFee + $apiPromoFee + $apiOtherFee;
    }

    $apiSettlement  = (float) ($st0['settlement_amount'] ?? 0);

} catch (\Exception $e) {
    echo "⚠️ Error API TikTok Statement: " . $e->getMessage() . "\n";
}

// Data dari DB ERP
$dbOmset      = (float) $order->total_amount;
$dbRefund     = (float) $order->refund_amount;

$dt           = $order->fee_breakdown_details;
$dbPlatform   = abs($dt['platform_fee'] ?? 0);
$dbFreeShip   = abs($dt['free_shipping'] ?? 0);
$dbService    = abs($dt['service_fee'] ?? 0);
$dbPromo      = abs($dt['promo_fee'] ?? 0);
$dbOther      = abs($dt['other_fee'] ?? 0);
$dbTotalFee   = abs($dt['total_fee'] ?? 0);
$dbNet        = (float) $order->net_amount;

echo "Toko        : " . ($store->store_name ?? '-') . " (ID #{$store->id})\n";
echo "Status DB   : {$order->order_status}\n";
echo "----------------------------------------------------------------------\n\n";

printf(" %-28s | %-18s | %-18s | %-8s\n", "KOMPONEN KEUANGAN", "DI DATABASE ERP", "LANGSUNG API TIKTOK", "MATCH?");
echo "----------------------------------------------------------------------\n";

function printRow($label, $dbVal, $apiVal, $isCurrency = true) {
    $dbStr  = $isCurrency ? "Rp " . number_format($dbVal, 0, ',', '.') : $dbVal;
    $apiStr = $isCurrency ? "Rp " . number_format($apiVal, 0, ',', '.') : $apiVal;
    
    $match = (abs($dbVal - $apiVal) < 1.0) ? "✅ SAME" : "❌ DIFF";
    printf(" %-28s | %-18s | %-18s | %-8s\n", $label, $dbStr, $apiStr, $match);
}

printRow("1. Omset Produk", $dbOmset, $apiGross > 0 ? $apiGross : $dbOmset);
printRow("2. Total Refund / Retur", $dbRefund, $apiRefund);
echo "----------------------------------------------------------------------\n";
printRow("   - Biaya Platform", $dbPlatform, $apiPlatformFee);
printRow("   - Biaya Layanan", $dbService, $apiServiceFee);
printRow("   - Biaya Promosi", $dbPromo, $apiPromoFee);
printRow("   - Biaya Logistik/Ongkir", $dbOther, $apiOtherFee);
printRow("3. TOTAL BIAYA ADMIN", $dbTotalFee, $apiTotalFee);
echo "----------------------------------------------------------------------\n";
printRow("4. DANA CAIR NET (SETTLEMENT)", $dbNet, $apiSettlement);
echo "======================================================================\n\n";

echo "📌 VERIFIKASI PRESISI:\n";
if (abs($dbNet - $apiSettlement) < 1.0) {
    echo "  🎉 PERFEK! Nominal Dana Cair Net di ERP DB (Rp " . number_format($dbNet, 0, ',', '.') . ") 100% IDENTIK DENGAN API TIKTOK (Rp " . number_format($apiSettlement, 0, ',', '.') . ")!\n\n";
} else {
    echo "  ⚠️ Ada selisih nominal pencairan Rp " . number_format(abs($dbNet - $apiSettlement), 0, ',', '.') . ". Jalankan 'php artisan tiktok:sync-escrow --order_id={$orderSn}' untuk menyinkronkan.\n\n";
}
