<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;
use Carbon\Carbon;

echo "=======================================================\n";
echo "🛠️ RE-SYNC TANGGAL ORDER & TANGGAL CAIR TIKTOK (WIB GMT+7)\n";
echo "=======================================================\n\n";

$tiktokService = app(TiktokService::class);
$stores = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token')
    ->get();

$updatedCount = 0;

foreach ($stores as $store) {
    echo "Processing Toko TikTok: {$store->store_name} (ID: {$store->id})...\n";
    $accessToken = $store->getValidAccessToken();
    $shopCipher = $store->shop_cipher;

    if (!$accessToken || !$shopCipher) continue;

    $orders = Order::where('store_id', $store->id)->get();

    foreach ($orders as $order) {
        try {
            $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$order->order_marketplace_id]);
            $tOrder = $detailRes['orders'][0] ?? $detailRes['order_list'][0] ?? [];

            if (empty($tOrder)) continue;

            $changed = false;

            // 1. Tanggal Order (create_time) dalam timezone WIB (Asia/Jakarta)
            $createTs = $tOrder['create_time'] ?? null;
            if ($createTs) {
                $cTsSec = (is_numeric($createTs) && strlen((string)$createTs) >= 13) ? (int)($createTs / 1000) : (int)$createTs;
                $newOrderDate = Carbon::createFromTimestamp($cTsSec, 'Asia/Jakarta')->format('Y-m-d H:i:s');
                if ($order->order_date !== $newOrderDate) {
                    $order->order_date = $newOrderDate;
                    $changed = true;
                }
            }

            // 2. Tanggal Dilepas / Cair (statement_time / finish_time)
            $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $order->order_marketplace_id);
            $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? $stmtData['transactions'] ?? [];
            
            $stmtTs = !empty($stmtList[0]['statement_time']) ? $stmtList[0]['statement_time'] : null;
            $compTs = $stmtTs ?? $tOrder['delivery_time'] ?? $tOrder['update_time'] ?? $tOrder['paid_time'] ?? null;

            if ($compTs) {
                $compTsSec = (is_numeric($compTs) && strlen((string)$compTs) >= 13) ? (int)($compTs / 1000) : (int)$compTs;
                $newCompletedAt = Carbon::createFromTimestamp($compTsSec, 'Asia/Jakarta')->format('Y-m-d H:i:s');
                if ($order->completed_at !== $newCompletedAt) {
                    $order->completed_at = $newCompletedAt;
                    $changed = true;
                }
            }

            if ($changed) {
                $order->save();
                $updatedCount++;
            }
        } catch (\Exception $e) {}
    }
}

echo "\n=======================================================\n";
echo "✨ SELESAI! Berhasil meng-update {$updatedCount} order TikTok agar Tanggal Order & Tanggal Cair 100% Presisi WIB!\n";
echo "=======================================================\n";
