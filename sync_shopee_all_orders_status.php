<?php
/**
 * ============================================================
 * SINKRONISASI MASSAL STATUS AKTIF SHOPEE -> ERP
 * ============================================================
 * Menarik status terbaru dari API Shopee untuk SEMUA pesanan aktif 
 * (yang belum COMPLETED/CANCELLED) di database ERP.
 *
 * Cara pakai:
 *   php sync_shopee_all_orders_status.php
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\ShopeeService;

$shopeeService = app(ShopeeService::class);

$stores = Store::whereHas('channel', fn($q) => $q->where('code', 'shopee'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token')
    ->get();

if ($stores->isEmpty()) {
    die("⚠️ Tidak ada toko Shopee aktif yang terhubung.\n");
}

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

foreach ($stores as $store) {
    echo "========================================================\n";
    echo "Memproses Toko: {$store->store_name} (ID: {$store->id})\n";
    echo "========================================================\n";

    try {
        $accessToken = $store->getValidAccessToken();

        // Ambil semua order aktif dari toko ini yang belum selesai / batal
        $activeOrders = Order::where('store_id', $store->id)
            ->whereNotNull('order_marketplace_id')
            ->whereNotIn('order_status', ['COMPLETED', 'CANCELLED', 'BATAL', 'CANCELED'])
            ->get();

        $totalCount = $activeOrders->count();
        echo "  Menemukan $totalCount pesanan aktif di database ERP.\n";

        if ($totalCount === 0) {
            continue;
        }

        // Chunk per 50 order (batas maks API Shopee)
        $chunks = $activeOrders->chunk(50);
        $updatedCount = 0;

        foreach ($chunks as $chunk) {
            $ids = $chunk->pluck('order_marketplace_id')->toArray();
            
            try {
                $detailRes = $shopeeService->getOrderDetail(
                    $accessToken,
                    (int) $store->marketplace_store_id,
                    $ids
                );
                
                $ordersList = $detailRes['order_list'] ?? [];

                foreach ($ordersList as $shopeeOrder) {
                    $orderSn = $shopeeOrder['order_sn'] ?? null;
                    if (!$orderSn) continue;

                    $dbOrder = $chunk->firstWhere('order_marketplace_id', $orderSn);
                    if (!$dbOrder) continue;

                    $statusRaw = strtoupper((string)($shopeeOrder['order_status'] ?? $dbOrder->order_status));
                    $correctStatus = $shopeeStatusMap[$statusRaw] ?? $statusRaw;

                    if ($dbOrder->order_status !== $correctStatus) {
                        $oldSt = $dbOrder->order_status;
                        $dbOrder->order_status = $correctStatus;
                        
                        if (in_array($correctStatus, ['CANCELLED', 'BATAL'])) {
                            $dbOrder->cancel_reason = $shopeeOrder['cancel_reason'] ?? 'Cancelled on Shopee';
                            $dbOrder->cancelled_by = 'Shopee / System';
                        }
                        
                        if (!empty($shopeeOrder['update_time'])) {
                            $dbOrder->completed_at = date('Y-m-d H:i:s', $shopeeOrder['update_time']);
                        }
                        
                        $dbOrder->saveQuietly();
                        echo "  [UPDATE] Order $orderSn: $oldSt -> $correctStatus\n";
                        $updatedCount++;
                    }
                }
            } catch (\Exception $exDetail) {
                echo "  ❌ Gagal menarik batch order detail: " . $exDetail->getMessage() . "\n";
            }
        }

        echo "  Selesai memproses toko. $updatedCount order Shopee berhasil diperbarui statusnya.\n\n";

    } catch (\Exception $e) {
        echo "❌ Gagal memproses toko {$store->store_name}: " . $e->getMessage() . "\n\n";
    }
}

echo "✨ Sinkronisasi massal status Shopee selesai!\n";
