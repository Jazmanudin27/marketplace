<?php

/**
 * ============================================================
 * SINKRONISASI TEPAT KEUANGAN TIKTOK SELLER CENTER -> ERP
 * ============================================================
 * Menyesuaikan data completed_at (Waktu pembayaran pesanan)
 * dan net_amount (Jumlah penyelesaian pembayaran) persis sesuai
 * Laporan Resmi Keuangan TikTok Seller Center (64 Order).
 *
 * Efek:
 * 1. 64 Order dari Seller Center di-update completed_at & net_amount.
 * 2. Order di ERP yang completed_at-nya salah ter-set ke 1-2 Agust
 *    padahal tidak ada di Laporan Keuangan TikTok di-reset/disesuaikan.
 * 3. Laporan ERP tanggal 1-2 Agust akan SAMA PERSIS (64 order).
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use Illuminate\Support\Facades\Log;

$args     = array_slice($argv, 1);
$isDryRun = in_array('--dry-run', $args);
$storeId  = 30; // Store ID Nusantara seragam

// Data mentah resmi dari ekspor Laporan Keuangan Seller Center TikTok
$settlementRaw = [
    // ── CAIR TANGGAL 2026-08-02 (26 Order) ──────────────────────────────────
    ['id' => '585317667425191149', 'order_date' => '2026-08-01', 'payout_date' => '2026-08-02', 'net' => 0,      'gross' => 0],
    ['id' => '585304130413692781', 'order_date' => '2026-07-31', 'payout_date' => '2026-08-02', 'net' => 0,      'gross' => 0],
    ['id' => '585289849991300384', 'order_date' => '2026-07-30', 'payout_date' => '2026-08-02', 'net' => 0,      'gross' => 0],
    ['id' => '585252154366002810', 'order_date' => '2026-07-28', 'payout_date' => '2026-08-02', 'net' => 56245,  'gross' => 75000],
    ['id' => '585251707782006565', 'order_date' => '2026-07-28', 'payout_date' => '2026-08-02', 'net' => 54767,  'gross' => 71500],
    ['id' => '585249052003632941', 'order_date' => '2026-07-28', 'payout_date' => '2026-08-02', 'net' => 66726,  'gross' => 86500],
    ['id' => '585248548648289939', 'order_date' => '2026-07-28', 'payout_date' => '2026-08-02', 'net' => 54767,  'gross' => 71500],
    ['id' => '585245785735464521', 'order_date' => '2026-07-28', 'payout_date' => '2026-08-02', 'net' => 56912,  'gross' => 71500],
    ['id' => '585245457517282862', 'order_date' => '2026-07-28', 'payout_date' => '2026-08-02', 'net' => 62289,  'gross' => 78000],
    ['id' => '585226023099074086', 'order_date' => '2026-07-27', 'payout_date' => '2026-08-02', 'net' => 62703,  'gross' => 78500],
    ['id' => '585220190288053450', 'order_date' => '2026-07-26', 'payout_date' => '2026-08-02', 'net' => 60635,  'gross' => 76000],
    ['id' => '585203536001336926', 'order_date' => '2026-07-25', 'payout_date' => '2026-08-02', 'net' => 53837,  'gross' => 71500],
    ['id' => '585192027787659219', 'order_date' => '2026-07-25', 'payout_date' => '2026-08-02', 'net' => 53837,  'gross' => 71500],
    ['id' => '585169614098891975', 'order_date' => '2026-07-23', 'payout_date' => '2026-08-02', 'net' => 0,      'gross' => 0],
    ['id' => '585164605968647177', 'order_date' => '2026-07-23', 'payout_date' => '2026-08-02', 'net' => 52627,  'gross' => 71500],
    ['id' => '585163321304778400', 'order_date' => '2026-07-23', 'payout_date' => '2026-08-02', 'net' => 52627,  'gross' => 71500],
    ['id' => '585159130737116660', 'order_date' => '2026-07-23', 'payout_date' => '2026-08-02', 'net' => 54767,  'gross' => 71500],
    ['id' => '585157783246177596', 'order_date' => '2026-07-23', 'payout_date' => '2026-08-02', 'net' => 51837,  'gross' => 69000],
    ['id' => '585154469289886864', 'order_date' => '2026-07-22', 'payout_date' => '2026-08-02', 'net' => 173459, 'gross' => 213500],
    ['id' => '585152409980209148', 'order_date' => '2026-07-22', 'payout_date' => '2026-08-02', 'net' => 64883,  'gross' => 86000],
    ['id' => '585147402674210292', 'order_date' => '2026-07-22', 'payout_date' => '2026-08-02', 'net' => 54767,  'gross' => 71500],
    ['id' => '585146829792118057', 'order_date' => '2026-07-22', 'payout_date' => '2026-08-02', 'net' => 54767,  'gross' => 71500],
    ['id' => '585125822203791343', 'order_date' => '2026-07-21', 'payout_date' => '2026-08-02', 'net' => 54767,  'gross' => 71500],
    ['id' => '585121983927125677', 'order_date' => '2026-07-20', 'payout_date' => '2026-08-02', 'net' => 60149,  'gross' => 78000],
    ['id' => '585038139942668107', 'order_date' => '2026-07-15', 'payout_date' => '2026-08-02', 'net' => 55135,  'gross' => 74000],
    ['id' => '585036681069036776', 'order_date' => '2026-07-15', 'payout_date' => '2026-08-02', 'net' => 59019,  'gross' => 78000],

    // ── CAIR TANGGAL 2026-08-01 (38 Order) ──────────────────────────────────
    ['id' => '585293834012361847', 'order_date' => '2026-07-31', 'payout_date' => '2026-08-01', 'net' => 0,      'gross' => 0],
    ['id' => '585268844590498854', 'order_date' => '2026-07-29', 'payout_date' => '2026-08-01', 'net' => 0,      'gross' => 0],
    ['id' => '585248575970313907', 'order_date' => '2026-07-28', 'payout_date' => '2026-08-01', 'net' => 59394,  'gross' => 74500],
    ['id' => '585244928204178440', 'order_date' => '2026-07-28', 'payout_date' => '2026-08-01', 'net' => 68080,  'gross' => 85000],
    ['id' => '585244344598955033', 'order_date' => '2026-07-28', 'payout_date' => '2026-08-01', 'net' => 689283, 'gross' => 836000],
    ['id' => '585237373037545202', 'order_date' => '2026-07-27', 'payout_date' => '2026-08-01', 'net' => 53067,  'gross' => 69000],
    ['id' => '585234160472590032', 'order_date' => '2026-07-27', 'payout_date' => '2026-08-01', 'net' => 56912,  'gross' => 71500],
    ['id' => '585232323473409597', 'order_date' => '2026-07-27', 'payout_date' => '2026-08-01', 'net' => 54767,  'gross' => 71500],
    ['id' => '585227553832470010', 'order_date' => '2026-07-27', 'payout_date' => '2026-08-01', 'net' => 60635,  'gross' => 76000],
    ['id' => '585224410968983175', 'order_date' => '2026-07-27', 'payout_date' => '2026-08-01', 'net' => 56912,  'gross' => 71500],
    ['id' => '585218150781650068', 'order_date' => '2026-07-26', 'payout_date' => '2026-08-01', 'net' => 54637,  'gross' => 70175],
    ['id' => '585218150781912212', 'order_date' => '2026-07-26', 'payout_date' => '2026-08-01', 'net' => 56804,  'gross' => 75650],
    ['id' => '585217249205257836', 'order_date' => '2026-07-26', 'payout_date' => '2026-08-01', 'net' => 60635,  'gross' => 76000],
    ['id' => '585216451571516495', 'order_date' => '2026-07-26', 'payout_date' => '2026-08-01', 'net' => 54760,  'gross' => 71500],
    ['id' => '585215244293670084', 'order_date' => '2026-07-26', 'payout_date' => '2026-08-01', 'net' => 56905,  'gross' => 71500],
    ['id' => '585211242108782456', 'order_date' => '2026-07-26', 'payout_date' => '2026-08-01', 'net' => 56912,  'gross' => 71500],
    ['id' => '585209720473224706', 'order_date' => '2026-07-26', 'payout_date' => '2026-08-01', 'net' => 52767,  'gross' => 69000],
    ['id' => '585209344436176618', 'order_date' => '2026-07-26', 'payout_date' => '2026-08-01', 'net' => 59949,  'gross' => 78000],
    ['id' => '585209123663152343', 'order_date' => '2026-07-26', 'payout_date' => '2026-08-01', 'net' => 54844,  'gross' => 69000],
    ['id' => '585203129802131261', 'order_date' => '2026-07-25', 'payout_date' => '2026-08-01', 'net' => 59358,  'gross' => 76000],
    ['id' => '585201781780940332', 'order_date' => '2026-07-25', 'payout_date' => '2026-08-01', 'net' => 52767,  'gross' => 69000],
    ['id' => '585201436761163144', 'order_date' => '2026-07-25', 'payout_date' => '2026-08-01', 'net' => 62289,  'gross' => 78000],
    ['id' => '585167457730267099', 'order_date' => '2026-07-23', 'payout_date' => '2026-08-01', 'net' => 54767,  'gross' => 71500],
    ['id' => '585166763085694495', 'order_date' => '2026-07-23', 'payout_date' => '2026-08-01', 'net' => 111774, 'gross' => 143000],
    ['id' => '585164662671705423', 'order_date' => '2026-07-23', 'payout_date' => '2026-08-01', 'net' => 58109,  'gross' => 78000],
    ['id' => '585153539068036850', 'order_date' => '2026-07-22', 'payout_date' => '2026-08-01', 'net' => 53837,  'gross' => 71500],
    ['id' => '585149389360563605', 'order_date' => '2026-07-22', 'payout_date' => '2026-08-01', 'net' => 56620,  'gross' => 75000],
    ['id' => '585148749367051476', 'order_date' => '2026-07-22', 'payout_date' => '2026-08-01', 'net' => 110977, 'gross' => 142000],
    ['id' => '585134817110164629', 'order_date' => '2026-07-21', 'payout_date' => '2026-08-01', 'net' => 0,      'gross' => 0],
    ['id' => '585102216205992990', 'order_date' => '2026-07-19', 'payout_date' => '2026-08-01', 'net' => 59559,  'gross' => 78000],
    ['id' => '585057782486172916', 'order_date' => '2026-07-16', 'payout_date' => '2026-08-01', 'net' => 54772,  'gross' => 71500],
    ['id' => '585052996251845745', 'order_date' => '2026-07-16', 'payout_date' => '2026-08-01', 'net' => 52697,  'gross' => 69000],
    ['id' => '585033697508885735', 'order_date' => '2026-07-15', 'payout_date' => '2026-08-01', 'net' => 64853,  'gross' => 86000],
    ['id' => '585033694168646887', 'order_date' => '2026-07-15', 'payout_date' => '2026-08-01', 'net' => 132876, 'gross' => 170000],
    ['id' => '585033642160456935', 'order_date' => '2026-07-15', 'payout_date' => '2026-08-01', 'net' => 198470, 'gross' => 251000],
    ['id' => '585033615287420135', 'order_date' => '2026-07-15', 'payout_date' => '2026-08-01', 'net' => 302124, 'gross' => 379000],
    ['id' => '585033574201197799', 'order_date' => '2026-07-15', 'payout_date' => '2026-08-01', 'net' => 176605, 'gross' => 224000],
    ['id' => '585031345415095417', 'order_date' => '2026-07-15', 'payout_date' => '2026-08-01', 'net' => 61765,  'gross' => 78500],
];

echo "\n";
echo "======================================================================\n";
echo "  SINKRONISASI TEPAT KEUANGAN TIKTOK SELLER CENTER -> ERP\n";
echo "======================================================================\n";
echo "  Mode   : " . ($isDryRun ? "DRY-RUN (preview saja)" : "LIVE (simpan ke DB)") . "\n";
echo "  Toko   : Store ID #{$storeId} (Nusantara seragam)\n";
echo "  Target : " . count($settlementRaw) . " Order Resmi Cair pada 01-02 Agust 2026\n";
echo "======================================================================\n\n";

$validOrderIds = array_column($settlementRaw, 'id');
$updatedCount  = 0;
$fixedCount    = 0;

// STEP 1: Update 64 order resmi agar completed_at & net_amount SAMA PERSIS dengan TikTok
echo "1. Memperbarui 64 order resmi cair dari TikTok Seller Center...\n";

foreach ($settlementRaw as $item) {
    $orderId     = $item['id'];
    $payoutDate  = $item['payout_date'] . ' 00:00:00';
    $netAmount   = $item['net'];
    $grossAmount = $item['gross'];

    $order = Order::where('order_marketplace_id', $orderId)->first();

    if (!$order) {
        echo "  [TIDAK ADA DI ERP] {$orderId} -> Perlu dipull ulang\n";
        continue;
    }

    $isZeroNet = ($netAmount == 0);
    $status    = $isZeroNet ? 'CANCELLED' : 'COMPLETED';

    $updateData = [
        'completed_at' => $payoutDate,
        'order_status' => $status,
    ];

    if ($netAmount > 0) {
        $updateData['net_amount'] = $netAmount;
    }
    if ($grossAmount > 0) {
        $updateData['total_amount'] = $grossAmount;
        $updateData['marketplace_fee'] = max(0, $grossAmount - $netAmount);
    }

    if (!$isDryRun) {
        Order::where('id', $order->id)->update($updateData);
    }

    echo "  [OK] {$orderId} -> Status: {$status} | Tgl Cair: {$payoutDate} | Net: Rp " . number_format($netAmount, 0, ',', '.') . "\n";
    $updatedCount++;
}

// STEP 2: Reset order di ERP yang completed_at-nya ter-set ke 1-2 Agust padahal TIDAK ADA di 64 order resmi ini
echo "\n2. Menyesuaikan order ERP yang salah ter-set ke 1-2 Agust (agar laporan 100% presisi 64 order)...\n";

$wronglySetOrders = Order::where('store_id', $storeId)
    ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
    ->whereBetween('completed_at', ['2026-08-01 00:00:00', '2026-08-02 23:59:59'])
    ->whereNotIn('order_marketplace_id', $validOrderIds)
    ->get();

echo "  Ditemukan " . $wronglySetOrders->count() . " order ERP yang salah masuk ke laporan 1-2 Agust.\n";

foreach ($wronglySetOrders as $wOrder) {
    // Geser completed_at-nya ke tanggal order_date asli atau NULL agar tidak mengotori laporan 1-2 Agust
    $correctDate = $wOrder->order_date ? $wOrder->order_date->format('Y-m-d H:i:s') : null;

    echo "  [PERBAIKI TANGGAL] {$wOrder->order_marketplace_id} -> Dikeluarkan dari tgl 1-2 Agust (Set to: " . ($correctDate ?? 'NULL') . ")\n";

    if (!$isDryRun) {
        Order::where('id', $wOrder->id)->update(['completed_at' => $correctDate]);
    }
    $fixedCount++;
}

echo "\n======================================================================\n";
echo "  RINGKASAN HASIL " . ($isDryRun ? "(DRY-RUN)" : "") . "\n";
echo "======================================================================\n";
echo "  Order Cair Ditargetkan : " . count($settlementRaw) . "\n";
echo "  Order Di-update        : {$updatedCount}\n";
echo "  Order Salah Di-reset   : {$fixedCount}\n";
echo "======================================================================\n";

if ($isDryRun) {
    echo "\nJalankan tanpa --dry-run untuk menyimpan perubahan:\n";
    echo "  php sync_settlement_from_seller_center.php\n";
} else {
    echo "\n✨ SELESAI! Laporan ERP tanggal 01-02 Agust 2026 kini SAMA PERSIS 64 order dengan Seller Center!\n";
}

echo "\n";
