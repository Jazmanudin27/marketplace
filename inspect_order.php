<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\ReturnOrder;

$sn = $argv[1] ?? '260714MK2GEVAK';

echo "======================================================================\n";
echo "  INSPEKSI DETAIL ORDER: {$sn}\n";
echo "======================================================================\n\n";

$order = Order::with(['store', 'items', 'returnOrder'])
    ->where('order_marketplace_id', $sn)
    ->orWhere('invoice_number', $sn)
    ->first();

if (!$order) {
    echo "❌ Order dengan Marketplace SN / Invoice '{$sn}' tidak ditemukan di database ERP.\n";
    exit(1);
}

echo "1. DATA ORDER DI ERP:\n";
echo "   ID Database ERP   : #{$order->id}\n";
echo "   Marketplace Order : {$order->order_marketplace_id}\n";
echo "   Invoice Number    : {$order->invoice_number}\n";
echo "   Toko              : " . ($order->store->name ?? 'N/A') . " (" . ($order->store->channel->code ?? 'N/A') . ")\n";
echo "   Status Order ERP  : {$order->order_status}\n";
echo "   Tanggal Order     : {$order->order_date}\n";
echo "   Tanggal Selesai   : " . ($order->completed_at ?? 'NULL') . "\n";
echo "   Total Transaksi   : Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
echo "   Potongan Marketp  : Rp " . number_format($order->marketplace_fee, 0, ',', '.') . "\n";
echo "   Dana Cair (Net)   : Rp " . number_format($order->net_amount, 0, ',', '.') . "\n";
echo "   Cancel/Retur Rsn  : " . ($order->cancel_reason ?: 'Tidak ada') . "\n";

echo "\n2. RINCIAN ITEM BARANG DI ERP (" . $order->items->count() . " item):\n";
foreach ($order->items as $idx => $item) {
    echo "   " . ($idx + 1) . ". " . $item->product_name . "\n";
    echo "      SKU      : " . ($item->sku ?: 'KOSONG') . "\n";
    echo "      Harga    : Rp " . number_format($item->price, 0, ',', '.') . "\n";
    echo "      Jumlah   : " . $item->quantity . "\n";
    echo "      Subtotal : Rp " . number_format($item->price * $item->quantity, 0, ',', '.') . "\n";
}

echo "\n3. DATA DI MODUL RETUR (return_orders):\n";
$returns = ReturnOrder::where('order_id', $order->id)->get();
if ($returns->count() > 0) {
    foreach ($returns as $ret) {
        echo "   [ADA RETUR] ID #{$ret->id} | Return SN: {$ret->return_sn} | Status: {$ret->status} | Nominal Refund: Rp " . number_format($ret->refund_amount, 0, ',', '.') . " | Alasan: {$ret->reason}\n";
    }
} else {
    echo "   ✅ Tidak ada catatan di tabel return_orders.\n";
}

echo "\n4. MEMANGGIL API LIVE MARKETPLACE (VERIFIKASI REAL-TIME)...\n";

try {
    $store = $order->store;
    $channelCode = strtolower($store->channel->code ?? '');

    if ($channelCode === 'shopee') {
        $shopeeService = app(\App\Services\ShopeeService::class);
        $accessToken = $store->getValidAccessToken();
        $shopId = (int) ($store->marketplace_store_id ?: $store->shopee_shop_id);

        $res = $shopeeService->getOrderDetail($accessToken, $shopId, [$order->order_marketplace_id]);
        $shopeeOrder = $res['order_list'][0] ?? null;

        if ($shopeeOrder) {
            echo "   Status Shopee API  : " . ($shopeeOrder['order_status'] ?? 'N/A') . "\n";
            echo "   Alasan Cancel API  : " . ($shopeeOrder['cancel_reason'] ?? 'N/A') . "\n";
            echo "   Jumlah Item API    : " . count($shopeeOrder['item_list'] ?? []) . " item\n";
            foreach ($shopeeOrder['item_list'] ?? [] as $iIdx => $sItem) {
                echo "      " . ($iIdx + 1) . ". " . $sItem['item_name'] . " (" . ($sItem['model_name'] ?? '') . ")\n";
                echo "         SKU: " . ($sItem['model_sku'] ?: ($sItem['item_sku'] ?? 'N/A')) . " | Qty: " . ($sItem['model_quantity_purchased'] ?? 1) . " | Price: Rp " . number_format($sItem['model_discounted_price'] ?? 0, 0, ',', '.') . "\n";
            }
        }
    } elseif ($channelCode === 'tiktok' || $channelCode === 'tokopedia') {
        $tiktokService = app(\App\Services\TiktokService::class);
        $accessToken = $store->getValidAccessToken();
        $shopCipher = $store->shop_cipher;

        $res = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$order->order_marketplace_id]);
        $tiktokOrder = ($res['order_list'] ?? [])[0] ?? null;

        if ($tiktokOrder) {
            echo "   Status TikTok API  : " . ($tiktokOrder['order_status'] ?? 'N/A') . "\n";
            echo "   Alasan Cancel API  : " . ($tiktokOrder['cancel_reason'] ?? 'N/A') . "\n";
        }
    }
} catch (\Throwable $e) {
    echo "   [INFO API] " . $e->getMessage() . "\n";
}

echo "\n======================================================================\n";
