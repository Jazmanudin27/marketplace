<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\ReturnOrder;

$sn = $argv[1] ?? '260714MK2GEVAK';

echo "======================================================================\n";
echo "  INSPEKSI DETAIL ORDER & VERIFIKASI RETUR LIVE SHOPEE API: {$sn}\n";
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

echo "\n3. DATA DI MODUL RETUR ERP (return_orders):\n";
$returns = ReturnOrder::where('order_id', $order->id)->get();
if ($returns->count() > 0) {
    foreach ($returns as $ret) {
        echo "   [ADA RETUR DI ERP] ID #{$ret->id} | Return SN: {$ret->return_sn} | Status: {$ret->status} | Nominal Refund: Rp " . number_format($ret->refund_amount, 0, ',', '.') . " | Alasan: {$ret->reason}\n";
    }
} else {
    echo "   ✅ Tidak ada catatan di tabel return_orders.\n";
}

echo "\n4. MEMANGGIL LIVE SHOPEE / TIKTOK API (VERIFIKASI RETUR REAL-TIME)...\n";

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
            echo "   Status Order Shopee API : " . ($shopeeOrder['order_status'] ?? 'N/A') . "\n";
            echo "   Alasan Batal API        : " . ($shopeeOrder['cancel_reason'] ?? 'Tidak Ada Batal') . "\n";
        }

        // Cek Langsung ke Shopee Return API untuk Return SN ini
        if ($returns->count() > 0) {
            foreach ($returns as $ret) {
                if ($ret->return_sn && !str_starts_with($ret->return_sn, 'RET-')) {
                    echo "\n   🔍 MENGECEK RETURN SN REST SHOPEE API ({$ret->return_sn})...\n";
                    try {
                        $retDetail = $shopeeService->getReturnDetail($accessToken, $shopId, $ret->return_sn);
                        if (!empty($retDetail)) {
                            echo "   ✅ API SHOPEE MENGKONFIRMASI RETUR ADA:\n";
                            echo "      Return SN API  : " . ($retDetail['return_sn'] ?? $ret->return_sn) . "\n";
                            echo "      Status Retur    : " . ($retDetail['status'] ?? 'N/A') . "\n";
                            echo "      Nominal Refund : Rp " . number_format($retDetail['refund_amount'] ?? 0, 0, ',', '.') . "\n";
                            echo "      Alasan Retur   : " . ($retDetail['reason'] ?? 'N/A') . "\n";
                            echo "      Text Penjelasan: " . ($retDetail['text_reason'] ?? 'N/A') . "\n";
                        } else {
                            echo "   ⚠️ API Shopee mengembalikan respons kosong untuk Return SN {$ret->return_sn}.\n";
                        }
                    } catch (\Throwable $retE) {
                        echo "   ❌ Error Shopee Return API: " . $retE->getMessage() . "\n";
                    }
                } else {
                    echo "   ℹ️ Catatan retur di ERP ini dibuat manual/rekonsiliasi internal (Return SN: {$ret->return_sn}).\n";
                }
            }
        }
    }
} catch (\Throwable $e) {
    echo "   [ERROR API] " . $e->getMessage() . "\n";
}

echo "\n======================================================================\n";
