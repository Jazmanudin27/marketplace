<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Store;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use App\Services\LazadaService;
use Illuminate\Support\Facades\Log;

$stores = Store::with('channel')->get();

echo "\n=======================================================\n";
echo "           DEBUG MARKETPLACE ORDERS SYNC               \n";
echo "=======================================================\n\n";

if ($stores->isEmpty()) {
    echo "❌ Tidak ada toko yang terhubung di database.\n";
    exit;
}

$timeTo = time();
$timeFrom = strtotime('-14 days', $timeTo);

echo "Rentang Waktu Pencarian Order (UTC/GMT):\n";
echo "- Dari : " . date('Y-m-d H:i:s', $timeFrom) . " (" . $timeFrom . ")\n";
echo "- Ke   : " . date('Y-m-d H:i:s', $timeTo) . " (" . $timeTo . ")\n\n";

foreach ($stores as $store) {
    echo "-------------------------------------------------------\n";
    echo "Toko: " . $store->store_name . " (ID: " . $store->id . ")\n";
    echo "Channel: " . ($store->channel->name ?? 'Unknown') . " (" . ($store->channel->code ?? 'N/A') . ")\n";
    echo "Status di DB: " . $store->status . "\n";
    echo "Expired Token di DB: " . ($store->token_expires_at ? $store->token_expires_at->toDateTimeString() : 'Belum diset') . "\n";

    try {
        echo "Menguji validasi & refresh token...\n";
        $accessToken = $store->getValidAccessToken();
        echo "✅ Access Token Baru/Aktif: " . substr($accessToken, 0, 15) . "...\n";
    } catch (\Throwable $e) {
        echo "❌ GAGAL REFRESH TOKEN: " . $e->getMessage() . "\n";
        continue;
    }

    $channelCode = $store->channel->code ?? '';

    if ($channelCode === 'shopee') {
        try {
            echo "Memanggil Shopee getOrderList...\n";
            $shopeeService = app(ShopeeService::class);
            $response = $shopeeService->getOrderList(
                $accessToken,
                (int) $store->marketplace_store_id,
                $timeFrom,
                $timeTo
            );

            if (empty($response['order_list'])) {
                echo "ℹ️ Shopee API merespon sukses, tetapi 0 order ditemukan untuk periode ini.\n";
                echo "Response info: " . json_encode($response) . "\n";
            } else {
                $count = count($response['order_list']);
                echo "✅ BERHASIL menemukan " . $count . " order dari Shopee!\n";
                echo "Daftar ID Order: " . implode(', ', array_column($response['order_list'], 'order_sn')) . "\n";
            }
        } catch (\Throwable $e) {
            echo "❌ ERROR API SHOPEE: " . $e->getMessage() . "\n";
        }
    } elseif ($channelCode === 'tiktok' || $channelCode === 'tokopedia') {
        try {
            echo "Memanggil TikTok getOrderList...\n";
            $tiktokService = app(TiktokService::class);
            $shopCipher = $store->shop_cipher;

            if (empty($shopCipher)) {
                echo "❌ ERROR: shop_cipher kosong di DB untuk toko TikTok ini.\n";
                continue;
            }

            $response = $tiktokService->getOrderList(
                $accessToken,
                $shopCipher,
                $timeFrom,
                $timeTo
            );

            if (empty($response['orders'])) {
                echo "ℹ️ TikTok API merespon sukses, tetapi 0 order ditemukan untuk periode ini.\n";
                echo "Response info: " . json_encode($response) . "\n";
            } else {
                $count = count($response['orders']);
                echo "✅ BERHASIL menemukan " . $count . " order dari TikTok!\n";
                $ids = [];
                foreach ($response['orders'] as $o) {
                    $ids[] = $o['id'] ?? $o['order_id'] ?? 'unknown';
                }
                echo "Daftar ID Order: " . implode(', ', $ids) . "\n";
            }
        } catch (\Throwable $e) {
            echo "❌ ERROR API TIKTOK: " . $e->getMessage() . "\n";
        }
    } elseif ($channelCode === 'lazada') {
        try {
            echo "Memanggil Lazada getOrderList...\n";
            $lazadaService = app(LazadaService::class);
            
            // Lazada expects ISO 8601 strings for date filters
            $createdAfter = date('c', $timeFrom);
            $createdBefore = date('c', $timeTo);
            
            // Lazada service doesn't have an order list function, let's verify if LazadaService has getOrders
            // We'll call the service method. Let's see what methods LazadaService has.
            if (method_exists($lazadaService, 'getOrders')) {
                $response = $lazadaService->getOrders($accessToken, $createdAfter, $createdBefore);
                if (empty($response['orders'])) {
                    echo "ℹ️ Lazada API merespon sukses, tetapi 0 order ditemukan untuk periode ini.\n";
                } else {
                    $count = count($response['orders']);
                    echo "✅ BERHASIL menemukan " . $count . " order dari Lazada!\n";
                    echo "Daftar ID Order: " . implode(', ', array_column($response['orders'], 'order_id')) . "\n";
                }
            } else {
                echo "ℹ️ LazadaService tidak memiliki metode getOrders, lewati debug getOrderList Lazada.\n";
            }
        } catch (\Throwable $e) {
            echo "❌ ERROR API LAZADA: " . $e->getMessage() . "\n";
        }
    } else {
        echo "⚠️ Channel '{$channelCode}' belum didukung untuk skrip debug ini.\n";
    }
}

echo "\n=======================================================\n";
echo "                    SELESAI                            \n";
echo "=======================================================\n\n";
