<?php

/**
 * ============================================================
 * LIST & EXPORT DAFTAR ORDER TIKTOK YANG BELUM MASUK ERP
 * ============================================================
 * Script ini menampilkan rincian order yang ada di TikTok API
 * tapi BELUM ADA di database ERP, serta menyimpannya ke CSV.
 *
 * Cara pakai:
 *   php list_missing_tiktok_orders.php --store=30 --days=90
 *   php list_missing_tiktok_orders.php --store=30 --from=2026-06-15 --to=2026-07-14
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;

// ── Parse argumen ──────────────────────────────────────────────
$args     = array_slice($argv, 1);
$storeId  = 30; // default store 30 kalau tidak ditentukan
$days     = 90;
$fromDate = null;
$toDate   = null;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--store=')) $storeId  = (int) str_replace('--store=', '', $arg);
    if (str_starts_with($arg, '--days='))  $days     = max(1, min(90, (int) str_replace('--days=', '', $arg)));
    if (str_starts_with($arg, '--from='))  $fromDate = str_replace('--from=', '', $arg);
    if (str_starts_with($arg, '--to='))    $toDate   = str_replace('--to=', '', $arg);
}

if ($fromDate && $toDate) {
    $startTs = strtotime($fromDate . ' 00:00:00');
    $endTs   = strtotime($toDate   . ' 23:59:59');
} else {
    $startTs = strtotime("-{$days} days 00:00:00");
    $endTs   = strtotime('today 23:59:59');
}

$statusMapping = [
    '100' => 'UNPAID', '111' => 'READY_TO_SHIP', '112' => 'SHIPPED',
    '121' => 'SHIPPED', '122' => 'DELIVERED', '130' => 'COMPLETED', '140' => 'CANCELLED',
    'UNPAID' => 'UNPAID', 'AWAITING_SHIPMENT' => 'READY_TO_SHIP',
    'AWAITING_COLLECTION' => 'SHIPPED', 'PARTIALLY_SHIPPING' => 'SHIPPED',
    'IN_TRANSIT' => 'SHIPPED', 'DELIVERED' => 'DELIVERED',
    'COMPLETED' => 'COMPLETED', 'CANCELLED' => 'CANCELLED', 'IN_CANCEL' => 'CANCELLED',
];

$store = Store::find($storeId);
if (!$store) {
    echo "ERROR: Toko dengan ID #{$storeId} tidak ditemukan.\n";
    exit(1);
}

echo "\n";
echo "======================================================================\n";
echo "  DAFTAR ORDER TIKTOK YANG BELUM ADA DI ERP\n";
echo "======================================================================\n";
echo "  Toko    : {$store->store_name} (ID: {$store->id})\n";
echo "  Periode : " . date('d-m-Y', $startTs) . " s/d " . date('d-m-Y', $endTs) . "\n";
echo "======================================================================\n\n";

$tiktokService = app(TiktokService::class);
$accessToken   = $store->getValidAccessToken();
$shopCipher    = $store->shop_cipher;

$stepSeconds = 30 * 86400;
$chunkStart  = $startTs;

$missingList = []; // Simpan semua data missing

while ($chunkStart <= $endTs) {
    $chunkEnd  = min($chunkStart + $stepSeconds - 1, $endTs);
    $labelFrom = date('Y-m-d', $chunkStart);
    $labelTo   = date('Y-m-d', $chunkEnd);

    echo "Fetching TikTok API ({$labelFrom} s/d {$labelTo})... ";

    $tiktokOrderMap = [];
    $cursor     = '';
    $pageCount  = 0;

    do {
        try {
            $resp = $tiktokService->getOrderList($accessToken, $shopCipher, $chunkStart, $chunkEnd, $cursor);
        } catch (\Exception $e) {
            echo "API Error: " . $e->getMessage() . "\n";
            break;
        }

        $orders = $resp['orders'] ?? [];
        foreach ($orders as $o) {
            $oid = (string)($o['id'] ?? $o['order_id'] ?? null);
            if ($oid) $tiktokOrderMap[$oid] = $o;
        }

        $cursor  = $resp['next_cursor'] ?? '';
        $hasMore = $resp['more'] ?? false;
        if (++$pageCount > 50) break;

    } while ($hasMore && $cursor);

    $tiktokIds = array_keys($tiktokOrderMap);

    if (empty($tiktokIds)) {
        echo "0 order.\n";
        $chunkStart = $chunkEnd + 1;
        continue;
    }

    // Cek yang sudah ada di ERP
    $existingIds = Order::where('store_id', $store->id)
        ->whereIn('order_marketplace_id', $tiktokIds)
        ->pluck('order_marketplace_id')
        ->toArray();

    $missingIds = array_diff($tiktokIds, $existingIds);

    echo "TikTok=" . count($tiktokIds) . ", ERP=" . count($existingIds) . ", BELUM ADA=" . count($missingIds) . "\n";

    foreach ($missingIds as $mid) {
        $raw = $tiktokOrderMap[$mid] ?? [];
        $rawStatus = strtoupper((string)($raw['order_status'] ?? $raw['status'] ?? 'UNKNOWN'));
        $createTs  = $raw['create_time'] ?? $raw['create_time_ge'] ?? null;

        if ($createTs && is_numeric($createTs) && strlen((string)$createTs) >= 13) {
            $createTs = (int)($createTs / 1000);
        }

        $orderDate = $createTs ? date('Y-m-d H:i:s', (int)$createTs) : '-';
        $status    = $statusMapping[$rawStatus] ?? $rawStatus;
        $recipient = $raw['recipient_address'] ?? [];
        $buyer     = $recipient['name'] ?? $recipient['recipient_name'] ?? 'Buyer TikTok';

        $paymentInfo = $raw['payment_info'] ?? $raw['payment'] ?? [];
        $total = (float)($paymentInfo['total_amount'] ?? $paymentInfo['total'] ?? $paymentInfo['original_total_product_price'] ?? 0);

        $missingList[] = [
            'order_id'    => $mid,
            'order_date'  => $orderDate,
            'status'      => $status,
            'buyer_name'  => $buyer,
            'total_amount'=> $total,
        ];
    }

    $chunkStart = $chunkEnd + 1;
}

echo "\n======================================================================\n";
echo "  HASIL PENGECEKAN: " . count($missingList) . " ORDER BELUM ADA DI ERP\n";
echo "======================================================================\n\n";

if (empty($missingList)) {
    echo "✨ Semua order TikTok di periode ini SUDAH ADA di ERP!\n\n";
    exit(0);
}

// Tampilkan 20 data pertama di terminal
echo str_pad("NO", 4) . " | " . str_pad("ORDER ID TIKTOK", 20) . " | " . str_pad("TANGGAL ORDER", 19) . " | " . str_pad("STATUS", 12) . " | " . str_pad("TOTAL (Rp)", 12) . " | BUYER\n";
echo str_repeat("-", 90) . "\n";

$no = 1;
foreach ($missingList as $item) {
    echo str_pad($no++, 4) . " | "
       . str_pad($item['order_id'], 20) . " | "
       . str_pad($item['order_date'], 19) . " | "
       . str_pad($item['status'], 12) . " | "
       . str_pad(number_format($item['total_amount'], 0, ',', '.'), 12, ' ', STR_PAD_LEFT) . " | "
       . $item['buyer_name'] . "\n";

    if ($no > 25) {
        $remaining = count($missingList) - 25;
        echo "... dan {$remaining} order lainnya.\n";
        break;
    }
}

// Export ke file CSV
$csvFile = __DIR__ . "/missing_orders_store_{$storeId}.csv";
$fp = fopen($csvFile, 'w');
fputcsv($fp, ['No', 'Order ID TikTok', 'Tanggal Order', 'Status TikTok', 'Total Pembayaran', 'Nama Pembeli']);

$i = 1;
foreach ($missingList as $item) {
    fputcsv($fp, [
        $i++,
        $item['order_id'],
        $item['order_date'],
        $item['status'],
        $item['total_amount'],
        $item['buyer_name']
    ]);
}
fclose($fp);

echo "\n======================================================================\n";
echo "📄 DAFTAR LENGKAP DIPANTAU & DISIMPAN KE CSV:\n";
echo "  File: {$csvFile}\n";
echo "======================================================================\n\n";
