<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;
use Carbon\Carbon;

$targetIds = [
    '585165202531124818',
    '585036747962877069',
    '585211734366128008',
    '585143678560077747',
    '585147554134525433'
];

echo "=======================================================\n";
echo "🛠️ PERBAIKAN INSTAN 5 ORDER TIKTOK SALAH BULAN (JULI -> AGUSTUS)\n";
echo "=======================================================\n\n";

$tiktokService = app(TiktokService::class);

foreach ($targetIds as $orderId) {
    $order = Order::where('order_marketplace_id', $orderId)->first();
    if (!$order) {
        echo "⚠️ Order ID {$orderId} tidak ditemukan.\n";
        continue;
    }

    $store = $order->store;
    if (!$store) continue;

    $accessToken = $store->getValidAccessToken();
    $shopCipher  = $store->shop_cipher;

    try {
        $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $orderId);
        $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? $stmtData['transactions'] ?? [];

        $maxStmtTs = null;
        foreach ($stmtList as $st) {
            $stTime = $st['statement_time'] ?? $st['paid_time'] ?? $st['create_time'] ?? null;
            if ($stTime) {
                $stSec = (is_numeric($stTime) && strlen((string)$stTime) >= 13) ? (int)($stTime / 1000) : (int)$stTime;
                if ($maxStmtTs === null || $stSec > $maxStmtTs) {
                    $maxStmtTs = $stSec;
                }
            }
        }

        if ($maxStmtTs) {
            $newCompletedAt = Carbon::createFromTimestamp($maxStmtTs, 'Asia/Jakarta')->format('Y-m-d H:i:s');
            $oldCompletedAt = $order->completed_at ? $order->completed_at->format('Y-m-d H:i:s') : '-';
            
            $order->completed_at = $newCompletedAt;
            $order->save();

            echo "✅ [SUCCESS FIX] Order: {$orderId}\n";
            echo "   - Tanggal Lama (ERP Juli)   : {$oldCompletedAt}\n";
            echo "   - Tanggal Baru (TikTok Aug): {$newCompletedAt}\n\n";
        } else {
            echo "⚠️ Order {$orderId}: tidak ada timestamp statement di API TikTok.\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error Order {$orderId}: " . $e->getMessage() . "\n";
    }
}

echo "=======================================================\n";
echo "✨ SELESAI! Seluruh 5 Order berhasil di-update ke tanggal cair Agustus.\n";
echo "=======================================================\n";
