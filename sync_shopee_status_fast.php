<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;
use App\Jobs\PullOrdersFromShopee;

echo "========================================================================\n";
echo "⚡ SINKRONISASI KILAT STATUS & ESCROW PESANAN SHOPEE (KHUSUS SHOPEE)\n";
echo "========================================================================\n\n";

$shopeeService = app(ShopeeService::class);

$shopeeStores = Store::whereHas('channel', function ($q) {
    $q->where('code', 'shopee');
})->get();

$totalUpdated = 0;

foreach ($shopeeStores as $store) {
    // Ambil semua order Shopee di DB 60 hari terakhir untuk mencocokkan status asli dari Shopee API
    $activeOrders = Order::where('store_id', $store->id)
        ->where('order_date', '>=', now()->subDays(60))
        ->get();

    if ($activeOrders->isEmpty()) {
        echo "📌 Toko {$store->store_name}: Tidak ada pesanan Shopee 60 hari terakhir.\n";
        continue;
    }

    echo "⚡ Toko {$store->store_name}: Memproses " . $activeOrders->count() . " pesanan Shopee aktif... ";

    try {
        $accessToken = $store->getValidAccessToken();
        $orderSns = $activeOrders->pluck('order_marketplace_id')->toArray();
        $chunks = array_chunk($orderSns, 50);

        foreach ($chunks as $chunk) {
            $detailRes = $shopeeService->getOrderDetail(
                $accessToken,
                (int) $store->marketplace_store_id,
                $chunk
            );

            $ordersList = $detailRes['order_list'] ?? [];
            foreach ($ordersList as $shopeeOrder) {
                $orderSn = $shopeeOrder['order_sn'] ?? null;
                if (!$orderSn) continue;

                $dbOrder = $activeOrders->firstWhere('order_marketplace_id', $orderSn);
                if (!$dbOrder) continue;

                $statusRaw = strtoupper((string)($shopeeOrder['order_status'] ?? $dbOrder->order_status));
                $shopeeStatusMap = [
                    'UNPAID'             => 'UNPAID',
                    'READY_TO_SHIP'      => 'READY_TO_SHIP',
                    'PROCESSED'          => 'READY_TO_SHIP',
                    'RETRY_SHIP'         => 'READY_TO_SHIP',
                    'TO_RETRY_LOGISTICS' => 'READY_TO_SHIP',
                    'SHIPPED'            => 'SHIPPED',
                    'TO_CONFIRM_RECEIVE' => 'SHIPPED',
                    'DELIVERED'          => 'DELIVERED',
                    'COMPLETED'          => 'COMPLETED',
                    'CANCELLED'          => 'CANCELLED',
                    'IN_CANCEL'          => 'CANCELLED',
                ];
                $status = $shopeeStatusMap[$statusRaw] ?? $statusRaw;
                $dbOrder->order_status = $status;

                if (in_array($status, ['CANCELLED', 'BATAL'])) {
                    $dbOrder->cancel_reason = $shopeeOrder['cancel_reason'] ?? 'Cancelled on Shopee';
                    $dbOrder->cancelled_by = 'Shopee / System';
                }

                if (in_array($status, ['COMPLETED', 'FINISHED', 'SELESAI'])) {
                    try {
                        $escrowRes = $shopeeService->getEscrowDetail($accessToken, (int)$store->marketplace_store_id, $orderSn);
                        $income = $escrowRes['response']['order_income'] ?? $escrowRes['order_income'] ?? [];
                        if (!empty($income)) {
                            $dbOrder->financial_breakdown = array_merge($dbOrder->financial_breakdown ?? [], $income);
                        }
                    } catch (\Exception $exEsc) {}
                }

                $dbOrder->save();
                $totalUpdated++;
            }
        }
        echo "✅ SELESAI\n";
    } catch (\Exception $e) {
        echo "❌ Exception: " . $e->getMessage() . "\n";
    }
}

echo "\n========================================================================\n";
echo "✨ SELESAI KILAT! Berhasil memperbarui status {$totalUpdated} pesanan Shopee di ERP.\n";
echo "========================================================================\n";
