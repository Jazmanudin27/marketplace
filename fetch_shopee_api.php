<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;

$orderSn = $argv[1] ?? '2608125QTH6TUE';

echo "========================================================\n";
echo "MENEMBAK LANGSUNG SHOPEE OPEN API UNTUK ORDER: {$orderSn}\n";
echo "========================================================\n\n";

$shopeeService = app(ShopeeService::class);
$order = Order::where('order_marketplace_id', (string)$orderSn)->first();

$matchingStore = null;

if ($order && $order->store) {
    $matchingStore = $order->store;
    echo "📌 Order ditemukan di DB ERP pada Toko: {$matchingStore->store_name} (ID Toko: {$matchingStore->id}, Shop ID: {$matchingStore->marketplace_store_id})\n\n";
} else {
    echo "🔍 Order belum ada di DB lokal. Mencari toko Shopee yang terhubung di ERP...\n";
    $stores = Store::whereHas('channel', function ($q) {
        $q->where('code', 'shopee');
    })->get();

    foreach ($stores as $s) {
        try {
            $token = $s->getValidAccessToken();
            $shopId = (int)$s->marketplace_store_id;
            $res = $shopeeService->getOrderDetail($token, $shopId, [$orderSn]);
            if (!empty($res['order_list'])) {
                $matchingStore = $s;
                break;
            }
        } catch (\Exception $e) {}
    }
}

if (!$matchingStore) {
    echo "❌ Order SN '{$orderSn}' tidak ditemukan di seluruh toko Shopee terhubung.\n";
    exit;
}

try {
    $accessToken = $matchingStore->getValidAccessToken();
    $shopId = (int)$matchingStore->marketplace_store_id;

    echo "========================================================\n";
    echo "✅ TOKO MATCHING: {$matchingStore->store_name} (Shop ID: {$shopId})\n";
    echo "========================================================\n\n";

    echo "--- 1. HASIL MENTAH DARI SHOPEE API [/api/v2/order/get_order_detail] ---\n";
    $detailRes = $shopeeService->getOrderDetail($accessToken, $shopId, [$orderSn]);
    $shopeeOrder = $detailRes['order_list'][0] ?? [];
    echo json_encode($shopeeOrder, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";

    echo "--- 2. HASIL MENTAH DARI SHOPEE API [/api/v2/payment/get_escrow_detail] ---\n";
    try {
        $escrowRes = $shopeeService->getEscrowDetail($accessToken, $shopId, $orderSn);
        $income = $escrowRes['order_income'] ?? $escrowRes ?? [];
        echo json_encode($income, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n";
    } catch (\Exception $exEscrow) {
        echo "⚠️ Gagal mengambil Escrow Detail (Mungkin order belum selesai/cair): " . $exEscrow->getMessage() . "\n\n";
        $income = [];
    }

    echo "========================================================\n";
    echo "📊 RINGKASAN DATA API SHOPEE UTK ORDER {$orderSn}:\n";
    echo "========================================================\n";

    $productSubtotal = 0.0;
    if (!empty($shopeeOrder['item_list'])) {
        foreach ($shopeeOrder['item_list'] as $item) {
            $price = (float) ($item['model_discounted_price'] ?? $item['model_original_price'] ?? 0);
            $qty = (int) ($item['model_quantity_purchased'] ?? 1);
            $productSubtotal += ($price * $qty);
        }
    }

    $cogs = (float) ($income['cost_of_goods_sold'] ?? $income['order_selling_price'] ?? $productSubtotal);
    $sellerDiscount = (float) ($shopeeOrder['seller_discount_amount'] ?? $income['seller_discount'] ?? 0);
    $escrowAmount = (float) ($income['escrow_amount'] ?? 0);

    $commissionFee = (float) ($income['commission_fee'] ?? 0);
    $serviceFee    = (float) ($income['service_fee'] ?? 0);
    $transFee      = (float) ($income['seller_transaction_fee'] ?? 0);
    $amsFee        = (float) ($income['ams_commission_fee'] ?? 0);
    $coinCashback  = (float) ($income['seller_coin_cash_back'] ?? 0);
    
    $totalApiFees = $commissionFee + $serviceFee + $transFee + $amsFee + $coinCashback;

    echo "• Subtotal Produk (Omset Kotor)   : Rp " . number_format($cogs, 2, '.', ',') . "\n";
    echo "• Diskon Penjual                   : Rp " . number_format($sellerDiscount, 2, '.', ',') . "\n";
    echo "• Biaya Komisi (Commission Fee)    : Rp " . number_format($commissionFee, 2, '.', ',') . "\n";
    echo "• Biaya Layanan (Service Fee/XTRA) : Rp " . number_format($serviceFee, 2, '.', ',') . "\n";
    echo "• Biaya Transaksi (Transaction)   : Rp " . number_format($transFee, 2, '.', ',') . "\n";
    echo "• Biaya AMS / Afiliasi             : Rp " . number_format($amsFee, 2, '.', ',') . "\n";
    echo "• Total Potongan Biaya Shopee      : Rp " . number_format($totalApiFees > 0 ? $totalApiFees : max(0.0, $cogs - $escrowAmount), 2, '.', ',') . "\n";
    echo "• Dana Cair Bersih (Escrow Amount) : Rp " . number_format($escrowAmount, 2, '.', ',') . "\n";
    echo "========================================================\n";

} catch (\Exception $e) {
    echo "❌ Gagal menembak API Shopee: " . $e->getMessage() . "\n";
}
