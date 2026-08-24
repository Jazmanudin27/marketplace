<?php
/**
 * ============================================================
 * SINKRONISASI MASSAL STATUS AKTIF TIKTOK -> ERP
 * ============================================================
 * Menarik status terbaru dari API TikTok untuk SEMUA pesanan aktif 
 * (yang belum COMPLETED/CANCELLED) di database ERP.
 *
 * Cara pakai:
 *   php sync_tiktok_all_orders_status.php
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;

$tiktokService = app(TiktokService::class);

$stores = Store::whereHas('channel', fn($q) => $q->whereIn('code', ['tiktok', 'tokopedia']))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token')
    ->get();

if ($stores->isEmpty()) {
    die("⚠️ Tidak ada toko TikTok aktif yang terhubung.\n");
}

$statusMap = [
    'UNPAID' => 'UNPAID',
    '100' => 'UNPAID',
    'AWAITING_SHIPMENT' => 'READY_TO_SHIP',
    '111' => 'READY_TO_SHIP',
    'AWAITING_COLLECTION' => 'READY_TO_SHIP',
    '112' => 'READY_TO_SHIP',
    'IN_TRANSIT' => 'SHIPPED',
    '121' => 'SHIPPED',
    'DELIVERED' => 'DELIVERED',
    '122' => 'DELIVERED',
    'COMPLETED' => 'COMPLETED',
    '130' => 'COMPLETED',
    'CANCELLED' => 'CANCELLED',
    '140' => 'CANCELLED',
];

foreach ($stores as $store) {
    echo "========================================================\n";
    echo "Memproses Toko: {$store->store_name} (ID: {$store->id})\n";
    echo "========================================================\n";

    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher = $store->shop_cipher;

        if (empty($shopCipher)) {
            echo "  ⚠️ Skip: shop_cipher kosong.\n";
            continue;
        }

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

        // Chunk per 50 order (batas maks API getOrderDetail TikTok)
        $chunks = $activeOrders->chunk(50);
        $updatedCount = 0;

        foreach ($chunks as $chunk) {
            $ids = $chunk->pluck('order_marketplace_id')->toArray();
            
            try {
                $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, $ids);
                $tiktokOrders = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];

                foreach ($tiktokOrders as $tOrder) {
                    $mId = $tOrder['id'] ?? $tOrder['order_id'] ?? null;
                    if (!$mId) continue;

                    $dbOrder = $chunk->firstWhere('order_marketplace_id', $mId);
                    if (!$dbOrder) continue;

                    $statusRaw = $tOrder['status'] ?? $tOrder['order_status'] ?? 'UNPAID';
                    $erpStatus = $statusMap[strtoupper((string)$statusRaw)] ?? strtoupper((string)$statusRaw);

                    if ($dbOrder->order_status !== $erpStatus) {
                        $oldStatus = $dbOrder->order_status;
                        $dbOrder->order_status = $erpStatus;
                        
                        // Jika statusnya completed atau cancelled, set completed_at
                        if (in_array($erpStatus, ['COMPLETED', 'DELIVERED', 'CANCELLED'])) {
                            $ts = $tOrder['finish_time'] ?? $tOrder['delivered_time'] ?? $tOrder['complete_time'] ?? $tOrder['update_time'] ?? time();
                            $compTsSec = (is_numeric($ts) && strlen((string)$ts) >= 13) ? (int)($ts / 1000) : (int)$ts;
                            $dbOrder->completed_at = date('Y-m-d H:i:s', $compTsSec);
                        }
                        
                        $dbOrder->saveQuietly();
                        echo "  [UPDATE] Order $mId: $oldStatus -> $erpStatus\n";
                        $updatedCount++;
                    }
                }
            } catch (\Exception $exDetail) {
                echo "  ❌ Gagal menarik batch order detail: " . $exDetail->getMessage() . "\n";
            }
        }

        echo "  Selesai memproses toko. $updatedCount order berhasil diperbarui statusnya.\n\n";

    } catch (\Exception $e) {
        echo "❌ Gagal memproses toko {$store->store_name}: " . $e->getMessage() . "\n\n";
    }
}

echo "✨ Sinkronisasi massal status selesai!\n";
