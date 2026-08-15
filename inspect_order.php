<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;

$sn = $argv[1] ?? '585554122547168568';

echo "======================================================================\n";
echo "  PERBANDINGAN PRESISI ERP VS API MARKETPLACE: {$sn}\n";
echo "======================================================================\n\n";

$order = Order::with(['store.channel', 'items'])
    ->where('order_marketplace_id', $sn)
    ->orWhere('invoice_number', $sn)
    ->first();

if (!$order) {
    echo "❌ Order '{$sn}' tidak ditemukan di database ERP.\n";
    exit(1);
}

echo "1. DATA PESANAN DI ERP (DATABASE LOCAL):\n";
echo "   ID Database ERP   : #{$order->id}\n";
echo "   Marketplace Order : {$order->order_marketplace_id}\n";
echo "   Toko ID & Nama    : #" . $order->store_id . " - " . ($order->store->name ?? 'NO_STORE / DELETED') . "\n";
echo "   Channel           : " . ($order->store->channel->code ?? 'N/A') . "\n";
echo "   Status Order ERP  : {$order->order_status}\n";
echo "   Tanggal Order     : {$order->order_date}\n";
echo "   Tanggal Selesai   : " . ($order->completed_at ?? 'NULL') . "\n";
echo "   Pembeli (Buyer)   : " . ($order->buyer_name ?: 'N/A') . "\n";
echo "   No HP Pembeli     : " . ($order->buyer_phone ?: 'N/A') . "\n";
echo "   Total Transaksi   : Rp " . number_format($order->total_amount, 0, ',', '.') . "\n";
echo "   Potongan Marketp  : Rp " . number_format($order->marketplace_fee, 0, ',', '.') . "\n";
echo "   Dana Cair (Net)   : Rp " . number_format($order->net_amount, 0, ',', '.') . "\n";

echo "\n   RINCIAN ITEM DI ERP (" . $order->items->count() . " item):\n";
if ($order->items->count() > 0) {
    foreach ($order->items as $idx => $item) {
        echo "   [" . ($idx + 1) . "] " . $item->product_name . "\n";
        echo "       SKU      : " . ($item->sku ?: 'KOSONG') . "\n";
        echo "       Harga    : Rp " . number_format($item->price, 0, ',', '.') . "\n";
        echo "       Jumlah   : " . $item->quantity . "\n";
        echo "       Subtotal : Rp " . number_format($item->price * $item->quantity, 0, ',', '.') . "\n";
    }
} else {
    echo "   ⚠️ [STILL 0 ITEM] Di database ERP saat ini belum ada item barang.\n";
}

echo "\n2. PENCARIAN & PERBANDINGAN LIVE DARI API MARKETPLACE (SHOPEE & TIKTOK)...\n";

$allStores = Store::where('status', '!=', 'disconnected')->get();
$foundInStore = null;
$apiOrderData = null;

$channelCode = strtolower($order->store->channel->code ?? 'tiktok');

if ($channelCode === 'tiktok' || $channelCode === 'tokopedia') {
    $tiktokService = app(\App\Services\TiktokService::class);
    $tiktokStores = $allStores->filter(fn($s) => in_array(strtolower($s->channel->code ?? ''), ['tiktok', 'tokopedia']));

    foreach ($tiktokStores as $tStore) {
        try {
            $accessToken = $tStore->getValidAccessToken();
            $shopCipher = $tStore->shop_cipher;

            if (empty($accessToken) || empty($shopCipher)) continue;

            $res = $tiktokService->getOrderDetail($accessToken, $shopCipher, [trim($sn)]);
            $orderList = $res['order_list'] ?? [];
            $tOrder = $orderList[0] ?? null;

            if ($tOrder) {
                $foundInStore = $tStore;
                $apiOrderData = $tOrder;
                break;
            }
        } catch (\Throwable $e) {
            // Continue search
        }
    }

    if ($foundInStore && $apiOrderData) {
        echo "   🎯 WOOOW! ORDERAN DITEMUKAN DI TIKTOK API!\n";
        echo "      Toko Asli Terhubung  : ID #{$foundInStore->id} - {$foundInStore->name}\n";
        echo "      Status Order di API  : " . ($apiOrderData['order_status'] ?? $apiOrderData['status'] ?? 'N/A') . "\n";
        
        $paymentInfo = $apiOrderData['payment_info'] ?? $apiOrderData['payment'] ?? [];
        $totalApi = (float) ($paymentInfo['total_amount'] ?? $apiOrderData['total_amount'] ?? 0);
        echo "      Total Transaksi API  : Rp " . number_format($totalApi, 0, ',', '.') . "\n";

        $itemList = $apiOrderData['item_list']
            ?? $apiOrderData['line_items']
            ?? $apiOrderData['sku_list']
            ?? $apiOrderData['items']
            ?? [];

        echo "\n   RINCIAN ITEM RESMI DARI TIKTOK API (" . count($itemList) . " item):\n";
        foreach ($itemList as $idx => $it) {
            $pName = $it['product_name'] ?? $it['item_name'] ?? 'Produk';
            $vName = $it['sku_name'] ?? $it['variant_name'] ?? '';
            $price = $it['sku_display_price'] ?? $it['sku_original_price'] ?? $it['price'] ?? 0;
            $qty = $it['quantity'] ?? 1;

            echo "   [" . ($idx + 1) . "] " . $pName . ($vName ? " ({$vName})" : "") . "\n";
            echo "       SKU API  : " . ($it['seller_sku'] ?? $it['sku'] ?? 'KOSONG') . "\n";
            echo "       Harga API: Rp " . number_format($price, 0, ',', '.') . "\n";
            echo "       Qty API  : " . $qty . "\n";
            echo "       Subtotal : Rp " . number_format($price * $qty, 0, ',', '.') . "\n";
        }
    } else {
        echo "   ⚠️ Order ID '{$sn}' tidak ditemukan di seluruh " . $tiktokStores->count() . " Toko TikTok terhubung saat ini.\n";
    }
} elseif ($channelCode === 'shopee') {
    $shopeeService = app(\App\Services\ShopeeService::class);
    $shopeeStores = $allStores->filter(fn($s) => strtolower($s->channel->code ?? '') === 'shopee');

    foreach ($shopeeStores as $sStore) {
        try {
            $accessToken = $sStore->getValidAccessToken();
            $shopId = (int) ($sStore->marketplace_store_id ?: $sStore->shopee_shop_id);

            if (empty($accessToken) || empty($shopId)) continue;

            $res = $shopeeService->getOrderDetail($accessToken, $shopId, [trim($sn)]);
            $sOrder = $res['order_list'][0] ?? null;

            if ($sOrder) {
                $foundInStore = $sStore;
                $apiOrderData = $sOrder;
                break;
            }
        } catch (\Throwable $e) {
            // Continue search
        }
    }

    if ($foundInStore && $apiOrderData) {
        echo "   🎯 WOOOW! ORDERAN DITEMUKAN DI SHOPEE API!\n";
        echo "      Toko Asli Terhubung  : ID #{$foundInStore->id} - {$foundInStore->name}\n";
        echo "      Status Order di API  : " . ($apiOrderData['order_status'] ?? 'N/A') . "\n";
        
        $itemList = $apiOrderData['item_list'] ?? [];
        echo "\n   RINCIAN ITEM RESMI DARI SHOPEE API (" . count($itemList) . " item):\n";
        foreach ($itemList as $idx => $it) {
            $price = $it['model_discounted_price'] ?? $it['model_original_price'] ?? 0;
            $qty = $it['model_quantity_purchased'] ?? 1;

            echo "   [" . ($idx + 1) . "] " . $it['item_name'] . ($it['model_name'] ? " ({$it['model_name']})" : "") . "\n";
            echo "       SKU API  : " . ($it['model_sku'] ?: ($it['item_sku'] ?? 'KOSONG')) . "\n";
            echo "       Harga API: Rp " . number_format($price, 0, ',', '.') . "\n";
            echo "       Qty API  : " . $qty . "\n";
            echo "       Subtotal : Rp " . number_format($price * $qty, 0, ',', '.') . "\n";
        }
    } else {
        echo "   ⚠️ Order SN '{$sn}' tidak ditemukan di seluruh " . $shopeeStores->count() . " Toko Shopee terhubung saat ini.\n";
    }
}

echo "\n======================================================================\n";
