<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Services\ShopeeService;
use App\Services\TiktokService;

$orderSn = '260714MDB0NE33';

$order = Order::where('order_marketplace_id', $orderSn)
    ->orWhere('order_marketplace_id', 'LIKE', '%' . $orderSn . '%')
    ->first();

if (!$order) {
    echo "❌ Order SN '{$orderSn}' tidak ditemukan di Database ERP!\n";
    exit;
}

$store = $order->store;

echo "=======================================================\n";
echo "📊 PERBANDINGAN RINCIAN ORDER: {$order->order_marketplace_id}\n";
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
echo "2. DATA LIVE API MARKETPLACE RESMI:\n";
echo "-------------------------------------------------------\n";

$channelCode = strtolower($store->channel->code ?? '');

if ($store && ($channelCode === 'shopee' || $store->channel_id == 1)) {
    try {
        $shopeeService = app(ShopeeService::class);
        $accessToken = $store->getValidAccessToken();
        $shopId = (int) ($store->marketplace_store_id ?: $store->shopee_shop_id);

        $escrowRes = $shopeeService->getEscrowDetail($accessToken, $shopId, $order->order_marketplace_id);
        $income = $escrowRes['order_income'] ?? [];

        if (!empty($income)) {
            $comm  = (float) ($income['commission_fee'] ?? 0);
            $serv  = (float) ($income['service_fee'] ?? 0);
            $trans = (float) ($income['seller_transaction_fee'] ?? 0);
            $totFee = $comm + $serv + $trans;
            $escrowAmt = (float) ($income['escrow_amount'] ?? 0);
            $grossApi = (float) ($income['cost_of_goods_sold'] ?? $income['order_original_price'] ?? $order->total_amount);

            if ($totFee <= 0 && $escrowAmt > 0) {
                $totFee = max(0.0, $grossApi - $escrowAmt);
            }

            echo "  • Omset Kotor API (Gross)      : Rp " . number_format($grossApi, 0, ',', '.') . "\n";
            echo "  • Rincian Komisi Shopee        : Rp " . number_format($comm, 0, ',', '.') . "\n";
            echo "  • Rincian Biaya Layanan XTRA   : Rp " . number_format($serv, 0, ',', '.') . "\n";
            echo "  • Rincian Biaya Transaksi      : Rp " . number_format($trans, 0, ',', '.') . "\n";
            echo "  • Total Biaya Admin API        : Rp " . number_format($totFee, 0, ',', '.') . "\n";
            echo "  • Dana Cair Bersih Escrow API  : Rp " . number_format($escrowAmt, 0, ',', '.') . "\n\n";

            echo "-------------------------------------------------------\n";
            echo "3. ANALISA HASIL PERBANDINGAN:\n";
            echo "-------------------------------------------------------\n";
            $diffGross = $order->total_amount - $grossApi;
            $diffAdmin = $order->marketplace_fee - $totFee;
            $diffNet   = $order->net_amount - $escrowAmt;

            echo "  • Selisih Omset Kotor : Rp " . number_format($diffGross, 0, ',', '.') . ($diffGross == 0 ? " (✅ MATCH)" : " (⚠️ BEDA)") . "\n";
            echo "  • Selisih Biaya Admin : Rp " . number_format($diffAdmin, 0, ',', '.') . ($diffAdmin == 0 ? " (✅ MATCH)" : " (⚠️ BEDA)") . "\n";
            echo "  • Selisih Dana Cair   : Rp " . number_format($diffNet, 0, ',', '.') . ($diffNet == 0 ? " (✅ MATCH)" : " (⚠️ BEDA)") . "\n";
        } else {
            echo "⚠️ Response API Escrow Shopee belum tersedia / belum rilis.\n";
        }
    } catch (\Exception $e) {
        echo "❌ Gagal memanggil API Shopee: " . $e->getMessage() . "\n";
    }
} elseif ($store && (in_array($channelCode, ['tiktok', 'tiktok_shop', 'tokopedia']) || $store->channel_id == 3)) {
    try {
        $tiktokService = app(TiktokService::class);
        $accessToken = $store->getValidAccessToken();
        $shopCipher = $store->shop_cipher;

        $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$order->order_marketplace_id]);
        $tOrder = $detailRes['orders'][0] ?? $detailRes['order_list'][0] ?? [];

        if (!empty($tOrder)) {
            $payment = $tOrder['payment_info'] ?? $tOrder['payment'] ?? [];
            $grossApi = (float) ($payment['original_total_product_price'] ?? $order->total_amount);
            $escrowAmt = (float) ($payment['escrow_amount'] ?? $payment['settlement_amount'] ?? 0);
            if ($escrowAmt <= 0) $escrowAmt = max(0.0, $grossApi * 0.915);
            $totFee = max(0.0, $grossApi - $escrowAmt);

            echo "  • Omset Kotor API (Gross)      : Rp " . number_format($grossApi, 0, ',', '.') . "\n";
            echo "  • Total Biaya Admin API        : Rp " . number_format($totFee, 0, ',', '.') . "\n";
            echo "  • Dana Cair Bersih Escrow API  : Rp " . number_format($escrowAmt, 0, ',', '.') . "\n\n";

            echo "-------------------------------------------------------\n";
            echo "3. ANALISA HASIL PERBANDINGAN:\n";
            echo "-------------------------------------------------------\n";
            $diffGross = $order->total_amount - $grossApi;
            $diffAdmin = $order->marketplace_fee - $totFee;
            $diffNet   = $order->net_amount - $escrowAmt;

            echo "  • Selisih Omset Kotor : Rp " . number_format($diffGross, 0, ',', '.') . ($diffGross == 0 ? " (✅ MATCH)" : " (⚠️ BEDA)") . "\n";
            echo "  • Selisih Biaya Admin : Rp " . number_format($diffAdmin, 0, ',', '.') . ($diffAdmin == 0 ? " (✅ MATCH)" : " (⚠️ BEDA)") . "\n";
            echo "  • Selisih Dana Cair   : Rp " . number_format($diffNet, 0, ',', '.') . ($diffNet == 0 ? " (✅ MATCH)" : " (⚠️ BEDA)") . "\n";
        }
    } catch (\Exception $e) {
        echo "❌ Gagal memanggil API TikTok: " . $e->getMessage() . "\n";
    }
}
