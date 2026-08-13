<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;

$targetOrderSn = $argv[1] ?? '260729VW6JUHK2';

echo "=========================================================================================================\n";
echo "PENGECEKAN & PERBANDINGAN KOMPARASI BIAYA ADMIN REAL SHOPEE VS ERP\n";
echo "Target Order SN: {$targetOrderSn}\n";
echo "=========================================================================================================\n\n";

$store = Store::where('store_name', 'like', '%Nusantara%Seragam%')
    ->whereHas('channel', function($q) { $q->where('code', 'shopee'); })
    ->first();

if (!$store) {
    echo "❌ Toko Shopee 'NUSANTARA SERAGAM' tidak ditemukan di database ERP.\n";
    exit;
}

$shopeeService = app(ShopeeService::class);

echo "1. Memperbarui Access Token Shopee Toko {$store->store_name}... ";
try {
    $accessToken = $store->getValidAccessToken(true);
    echo "✅ Token Berhasil Di-refresh!\n\n";
} catch (\Exception $eToken) {
    echo "⚠️ Refresh Token Gagal, Mencoba Token Saat Ini... (" . $eToken->getMessage() . ")\n\n";
    $accessToken = $store->access_token;
}

$order = Order::where('order_marketplace_id', trim($targetOrderSn))->first();

if (!$order) {
    echo "❌ Order SN '{$targetOrderSn}' tidak ditemukan di DB ERP.\n";
    exit;
}

echo "Menghubungi Shopee Open API v2 /api/v2/payment/get_escrow_detail ...\n\n";

try {
    $escrowRes = $shopeeService->getEscrowDetail($accessToken, (int)$store->marketplace_store_id, trim($targetOrderSn));
    
    echo "--- 1. RAW API RESPONSE DARI SHOPEE ---:\n";
    print_r($escrowRes);
    echo "\n----------------------------------------\n\n";

    $income = $escrowRes['order_income'] ?? $escrowRes['response']['order_income'] ?? $escrowRes;
    
    echo "--- 2. EXTRACTED ORDER INCOME ---:\n";
    print_r($income);
    echo "\n----------------------------------------\n\n";

    // Simpan ke DB
    $order->financial_breakdown = $income;
    $order->save();

    // Reload dari DB
    $order = $order->fresh();
    $details = $order->fee_breakdown_details;

    echo "--- 3. PERHITUNGAN MODEL ERP SETELAH DI-SAVE ---:\n";
    echo "• Platform Fee  (commission_fee)         : - Rp " . number_format(abs($details['platform_fee'])) . "\n";
    echo "• Free Shipping (service_fee)            : - Rp " . number_format(abs($details['free_shipping'])) . "\n";
    echo "• Service Fee   (seller_order_proc)      : - Rp " . number_format(abs($details['service_fee'])) . "\n";
    echo "• Promo Fee     (voucher_from_seller)    : - Rp " . number_format(abs($details['promo_fee'])) . "\n";
    echo "• Other Fee     (seller_transaction_fee) : - Rp " . number_format(abs($details['other_fee'])) . "\n";
    echo "• TOTAL BIAYA ADMIN                      : - Rp " . number_format(abs($details['total_fee'])) . "\n";
    echo "• DANA CAIR NET AKHIR                    : Rp " . number_format($order->net_amount) . "\n";
    echo "--------------------------------------------------\n";

} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
}
