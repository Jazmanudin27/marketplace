<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;
use Carbon\Carbon;

$orderMarketplaceId = $argv[1] ?? '584593202672862475';

echo "=======================================================\n";
echo "🔍 PENGECEKAN DATA API TIME STAMP TIKTOK ORDER: {$orderMarketplaceId}\n";
echo "=======================================================\n\n";

$order = Order::where('order_marketplace_id', $orderMarketplaceId)->first();

if (!$order) {
    echo "❌ Order ID {$orderMarketplaceId} tidak ditemukan di database ERP lokal.\n";
    // Cari store tiktok pertama untuk tes API
    $store = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
        ->where('status', '!=', 'disconnected')
        ->whereNotNull('access_token')
        ->first();
} else {
    $store = $order->store;
}

if (!$store) {
    echo "❌ Toko TikTok tidak ditemukan.\n";
    exit(1);
}

$tiktokService = app(TiktokService::class);
$accessToken = $store->getValidAccessToken();
$shopCipher = $store->shop_cipher;

echo "🏬 Toko: {$store->store_name} (ID: {$store->id})\n";
echo "-------------------------------------------------------\n";

// 1. CEK API ORDER DETAIL TIKTOK
try {
    $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$orderMarketplaceId]);
    $tOrder = $detailRes['orders'][0] ?? $detailRes['order_list'][0] ?? [];

    if (empty($tOrder)) {
        echo "⚠️ Order tidak ditemukan di API getOrderDetail.\n";
    } else {
        echo "📊 1. TIMESTAMPS DARIPADA API ORDER DETAIL TIKTOK:\n";
        echo "  • Order Status API : " . ($tOrder['status'] ?? $tOrder['order_status'] ?? '-') . "\n";
        
        $fields = [
            'create_time' => 'Tanggal Pemesanan (Dibuat)',
            'paid_time' => 'Tanggal Pembayaran (Paid)',
            'rts_time' => 'Tanggal Siap Kirim (RTS)',
            'shipped_time' => 'Tanggal Dikirim (Shipped)',
            'delivered_time' => 'Tanggal Paket Diterima (Delivered)',
            'finish_time' => 'Tanggal Selesai / Completed (Finish)',
            'update_time' => 'Tanggal Terakhir Update (Update)',
            'cancel_time' => 'Tanggal Dibatalkan (Cancel)',
        ];

        foreach ($fields as $key => $label) {
            $ts = $tOrder[$key] ?? null;
            if ($ts) {
                $tsSec = (is_numeric($ts) && strlen((string)$ts) >= 13) ? (int)($ts / 1000) : (int)$ts;
                $wibDate = Carbon::createFromTimestamp($tsSec, 'Asia/Jakarta')->format('Y-m-d H:i:s');
                $utcDate = Carbon::createFromTimestamp($tsSec, 'UTC')->format('Y-m-d H:i:s');
                echo "  • {$label} [{$key}]:\n";
                echo "     - Timestamp Raw : {$ts}\n";
                echo "     - Jam WIB (GMT+7): {$wibDate} (Asia/Jakarta)\n";
                echo "     - Jam UTC (Server): {$utcDate} (UTC)\n";
            } else {
                echo "  • {$label} [{$key}]: (Kosong / Null di API)\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ Error API Order Detail: " . $e->getMessage() . "\n";
}

echo "\n-------------------------------------------------------\n";

// 2. CEK API FINANCE STATEMENT TIKTOK
try {
    $stmtData = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $orderMarketplaceId);
    $stmtList = $stmtData['statement_transactions'] ?? $stmtData['statement_transaction_list'] ?? $stmtData['transactions'] ?? [];

    echo "💰 2. TIMESTAMPS DARIPADA API STATEMENT FINANCE TIKTOK:\n";
    if (empty($stmtList)) {
        echo "  • Statement Transactions : Kosong / Belum Cair\n";
    } else {
        foreach ($stmtList as $idx => $st) {
            echo "  [Statement Item #" . ($idx + 1) . "]:\n";
            echo "     - Statement ID     : " . ($st['statement_id'] ?? '-') . "\n";
            echo "     - Status           : " . ($st['status'] ?? '-') . "\n";
            echo "     - Fee Amount       : Rp " . number_format(abs((float)($st['fee_amount'] ?? 0)), 0, ',', '.') . "\n";
            echo "     - Settlement Net   : Rp " . number_format((float)($st['settlement_amount'] ?? 0), 0, ',', '.') . "\n";
            
            $stmtTime = $st['statement_time'] ?? $st['create_time'] ?? null;
            if ($stmtTime) {
                $stSec = (is_numeric($stmtTime) && strlen((string)$stmtTime) >= 13) ? (int)($stmtTime / 1000) : (int)$stmtTime;
                $wibStmt = Carbon::createFromTimestamp($stSec, 'Asia/Jakarta')->format('Y-m-d H:i:s');
                $utcStmt = Carbon::createFromTimestamp($stSec, 'UTC')->format('Y-m-d H:i:s');
                echo "     - Statement Time   : {$stmtTime}\n";
                echo "     - Jam Cair WIB     : {$wibStmt} (Asia/Jakarta)\n";
                echo "     - Jam Cair UTC     : {$utcStmt} (UTC)\n";
            }
        }
    }
} catch (\Exception $e) {
    echo "❌ Error API Finance Statement: " . $e->getMessage() . "\n";
}

echo "\n-------------------------------------------------------\n";
if ($order) {
    echo "💾 3. DATA TANGGAL DI DATABASE ERP LOKAL SAAT INI:\n";
    echo "  • order_date (ERP)   : " . ($order->order_date ? $order->order_date->format('Y-m-d H:i:s') : '-') . "\n";
    echo "  • completed_at (ERP) : " . ($order->completed_at ? $order->completed_at->format('Y-m-d H:i:s') : '-') . "\n";
}
echo "=======================================================\n";
