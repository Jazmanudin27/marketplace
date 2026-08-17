<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;
use App\Services\TiktokService;

$orderSn = $argv[1] ?? null;

if (!$orderSn) {
    echo "=======================================================\n";
    echo "📋 CARA MENGGUNAKAN SCRIPT PEMBONGKAR FIELD API:\n";
    echo "=======================================================\n";
    echo "Jalankan dengan memasukkan Nomor Order:\n";
    echo "  • Order Shopee  : php dump_api_fields.php 260714MDB0NE33\n";
    echo "  • Order TikTok  : php dump_api_fields.php 585165338047579282\n\n";
    exit;
}

echo "=======================================================\n";
echo "🔍 PEMBONGKAR FIELD API UNTUK ORDER: {$orderSn}\n";
echo "=======================================================\n\n";

$order = Order::where('order_marketplace_id', $orderSn)
    ->orWhere('order_marketplace_id', 'LIKE', '%' . $orderSn . '%')
    ->first();

$store = $order ? $order->store : null;
$tiktokService = app(TiktokService::class);

if ($store) {
    $channelCode = strtolower($store->channel->code ?? '');
    echo "Toko ERP : " . ($store->store_name ?? '-') . " (ID #{$store->id} - " . strtoupper($channelCode) . ")\n\n";

    if (in_array($channelCode, ['tiktok', 'tiktok_shop', 'tokopedia']) || $store->channel_id == 3) {
        dumpTiktokOrder($tiktokService, $store, $orderSn);
    } elseif ($channelCode === 'shopee' || $store->channel_id == 1) {
        dumpShopeeOrder($store, $orderSn);
    }
} else {
    echo "⚠️ Order ID '{$orderSn}' belum tersimpan di DB lokal. Mencari langsung ke TikTok API di seluruh toko terhubung...\n\n";
    $stores = Store::whereHas('channel', fn($q) => $q->whereIn('code', ['tiktok', 'tiktok_shop', 'tokopedia']))->get();

    $found = false;
    foreach ($stores as $st) {
        try {
            $accessToken = $st->getValidAccessToken();
            $shopCipher = $st->shop_cipher;

            if (empty($shopCipher)) continue;

            $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$orderSn]);
            $tiktokOrders = $detailRes['orders'] ?? $detailRes['order_list'] ?? [];

            if (!empty($tiktokOrders)) {
                echo "✅ Order DITEMUKAN di Toko TikTok: {$st->store_name} (ID #{$st->id})!\n\n";
                dumpTiktokOrder($tiktokService, $st, $orderSn);
                $found = true;
                break;
            }
        } catch (\Exception $e) {
            // Lanjut cari ke toko berikutnya
        }
    }

    if (!$found) {
        echo "❌ Order ID '{$orderSn}' tidak ditemukan di API TikTok toko manapun.\n";
    }
}

function dumpTiktokOrder($tiktokService, $store, $orderSn) {
    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher = $store->shop_cipher;

        $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$orderSn]);
        $tOrder = $detailRes['orders'][0] ?? $detailRes['order_list'][0] ?? [];

        echo "--- 🎵 [TIKTOK API] RESPONSE RAW 'payment_info' --- \n";
        echo json_encode($tOrder['payment_info'] ?? $tOrder['payment'] ?? [], JSON_PRETTY_PRINT) . "\n\n";

        echo "--- 🎵 [TIKTOK API] STATEMENT TRANSACTIONS --- \n";
        try {
            $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $orderSn);
            echo json_encode($stmtData, JSON_PRETTY_PRINT) . "\n\n";
        } catch (\Exception $exStmt) {
            echo "Statement Info: " . $exStmt->getMessage() . "\n\n";
        }

        echo "--- 🎵 [TIKTOK API] DETAIL PESANAN LENGKAP (ORDER OBJECT) --- \n";
        echo json_encode($tOrder, JSON_PRETTY_PRINT) . "\n\n";

    } catch (\Exception $e) {
        echo "❌ Error TikTok API: " . $e->getMessage() . "\n";
    }
}

function dumpShopeeOrder($store, $orderSn) {
    try {
        $shopeeService = app(ShopeeService::class);
        $accessToken = $store->getValidAccessToken();
        $shopId = (int) ($store->marketplace_store_id ?: $store->shopee_shop_id);

        echo "--- 🛍️ [SHOPEE API] RESPONSE RAW 'order_income' --- \n";
        $escrowRes = $shopeeService->getEscrowDetail($accessToken, $shopId, $orderSn);
        echo json_encode($escrowRes['order_income'] ?? $escrowRes, JSON_PRETTY_PRINT) . "\n\n";
    } catch (\Exception $e) {
        echo "❌ Error Shopee API: " . $e->getMessage() . "\n";
    }
}
