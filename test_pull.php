<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Store;
use App\Services\TiktokService;
use App\Services\ShopeeService;

echo "=== DIAGNOSTIC ORDER SYNC ===\n";

$stores = Store::where('status', 'connected')->get();
if ($stores->isEmpty()) {
    echo "Tidak ada toko yang berstatus 'connected' di database.\n";
    exit;
}

foreach ($stores as $store) {
    echo "\n----------------------------------------\n";
    echo "Toko: {$store->store_name} (ID: {$store->id}) | Channel: {$store->channel->code}\n";
    
    $timeTo = time();
    $timeFrom = strtotime('-15 days', $timeTo); // 15 hari terakhir
    
    if (in_array($store->channel->code, ['tiktok', 'tokopedia'])) {
        $tiktok = app(TiktokService::class);
        try {
            $token = $store->getValidAccessToken();
            $cipher = $store->shop_cipher;
            echo "Access Token: " . substr($token, 0, 15) . "...\n";
            echo "Shop Cipher: " . $cipher . "\n";
            
            echo "Memanggil getOrderList (15 hari terakhir)...\n";
            $response = $tiktok->getOrderList($token, $cipher, $timeFrom, $timeTo);
            
            $orders = $response['orders'] ?? [];
            echo "Jumlah order ID yang ditemukan: " . count($orders) . "\n";
            
            if (count($orders) > 0) {
                echo "Order IDs: " . implode(', ', array_map(fn($o) => $o['id'] ?? $o['order_id'], array_slice($orders, 0, 5))) . "...\n";
                
                $orderIds = [];
                foreach ($orders as $o) {
                    $id = $o['id'] ?? $o['order_id'] ?? null;
                    if ($id) $orderIds[] = $id;
                }
                
                echo "Memanggil getOrderDetail untuk chunk pertama...\n";
                $chunk = array_slice($orderIds, 0, 10);
                $detailResponse = $tiktok->getOrderDetail($token, $cipher, $chunk);
                $orderList = $detailResponse['order_list'] ?? [];
                echo "Jumlah detail order yang diterima: " . count($orderList) . "\n";
            }
        } catch (\Exception $e) {
            echo "ERROR TIKTOK: " . $e->getMessage() . "\n";
        }
    } elseif ($store->channel->code === 'shopee') {
        $shopee = app(ShopeeService::class);
        try {
            $token = $store->getValidAccessToken();
            $shopId = (int) $store->marketplace_store_id;
            echo "Access Token: " . substr($token, 0, 15) . "...\n";
            echo "Shop ID: " . $shopId . "\n";
            
            echo "Memanggil getOrderList (15 hari terakhir)...\n";
            $response = $shopee->getOrderList($token, $shopId, $timeFrom, $timeTo, 'create_time', '');
            
            $orders = $response['order_list'] ?? [];
            echo "Jumlah order ID yang ditemukan: " . count($orders) . "\n";
            
            if (count($orders) > 0) {
                $orderSns = array_column($orders, 'order_sn');
                echo "Order SNs: " . implode(', ', array_slice($orderSns, 0, 5)) . "...\n";
                
                echo "Memanggil getOrderDetail...\n";
                $chunk = array_slice($orderSns, 0, 10);
                $detailsResponse = $shopee->getOrderDetail($token, $shopId, $chunk);
                $orderList = $detailsResponse['order_list'] ?? [];
                echo "Jumlah detail order yang diterima: " . count($orderList) . "\n";
            }
        } catch (\Exception $e) {
            echo "ERROR SHOPEE: " . $e->getMessage() . "\n";
        }
    }
}
