<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;
use App\Jobs\PullOrdersFromShopee;

$orderSn = $argv[1] ?? '260715QBX82JJB';

echo "========================================================\n";
echo "SINKRONISASI SINGLE ORDER SHOPEE LANGSUNG DARI API\n";
echo "Nomor Order Shopee: {$orderSn}\n";
echo "========================================================\n\n";

$shopeeService = app(ShopeeService::class);
$shopeeStores = Store::whereHas('channel', function ($q) {
    $q->where('code', 'shopee');
})->get();

$found = false;

foreach ($shopeeStores as $store) {
    echo "Checking Toko: {$store->store_name} (ID: {$store->id})... ";
    try {
        $accessToken = $store->getValidAccessToken();
        $detailRes = $shopeeService->getOrderDetail(
            $accessToken,
            (int) $store->marketplace_store_id,
            [$orderSn]
        );

        $ordersList = $detailRes['order_list'] ?? [];
        if (empty($ordersList)) {
            echo "ℹ️ Order tidak ditemukan di toko ini.\n";
            continue;
        }

        $found = true;
        $shopeeOrder = $ordersList[0];
        echo "✅ MATCHING FOUND!\n\n";

        $status = $shopeeOrder['order_status'] ?? 'COMPLETED';
        echo "• Status di Shopee API Saat Ini : {$status}\n";

        // Panggil Job PullOrdersFromShopee secara langsung untuk memproses simpan lengkap
        $job = new PullOrdersFromShopee($store, time() - 86400, time());
        app()->call([$job, 'handle']);

        // Tarik detail escrow khusus jika sudah selesai
        try {
            $escrowRes = $shopeeService->getEscrowDetail($accessToken, (int)$store->marketplace_store_id, $orderSn);
            $responseOrder = $escrowRes['response']['order_income'] ?? $escrowRes['order_income'] ?? [];
            if (!empty($responseOrder)) {
                $shopeeOrder['financial_breakdown'] = $responseOrder;
            }
        } catch (\Exception $exEsc) {}

        $dbOrder = Order::where('order_marketplace_id', (string)$orderSn)->first();
        if ($dbOrder) {
            $dbOrder->order_status = $status;
            if (!empty($shopeeOrder['financial_breakdown'])) {
                $dbOrder->financial_breakdown = array_merge($dbOrder->financial_breakdown ?? [], $shopeeOrder['financial_breakdown']);
            }
            $dbOrder->save();
        }

        $dbOrder = Order::where('order_marketplace_id', (string)$orderSn)->first();

        echo "========================================================\n";
        echo "✅ STATUS & ESCROW DI DATABASE ERP BERHASIL DIPERBARUI!\n";
        echo "   • ERP Order ID        : " . ($dbOrder ? $dbOrder->id : '-') . "\n";
        echo "   • Status ERP Baru     : " . ($dbOrder ? $dbOrder->order_status : '-') . "\n";
        echo "   • Total Omset Kotor   : Rp " . number_format($dbOrder ? $dbOrder->total_amount : 0, 2) . "\n";
        echo "   • Total Biaya Admin   : Rp " . number_format($dbOrder ? $dbOrder->marketplace_fee : 0, 2) . "\n";
        echo "   • Dana Cair Bersih    : Rp " . number_format($dbOrder ? $dbOrder->net_amount : 0, 2) . "\n";
        echo "========================================================\n";
        break;
    } catch (\Exception $e) {
        if (str_contains($e->getMessage(), 'not found')) {
            echo "ℹ️ Order tidak ada di toko ini\n";
        } else {
            echo "❌ Exception: " . $e->getMessage() . "\n";
        }
    }
}

if (!$found) {
    echo "\n⚠️ Order SN '{$orderSn}' tidak ditemukan di akun toko Shopee manapun.\n";
}
