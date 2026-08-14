<?php

/**
 * ============================================================
 * FIX TGL LEPAS (completed_at) DARI DATA TIKTOK SELLER CENTER
 * ============================================================
 * Script ini mengupdate kolom completed_at di ERP berdasarkan
 * kolom "Waktu pembayaran pesanan" dari export TikTok Seller Center.
 *
 * Cara pakai:
 *   php fix_tgl_lepas_tiktok.php              -> langsung update
 *   php fix_tgl_lepas_tiktok.php --dry-run    -> preview saja
 *
 * Untuk batch baru: edit array $settlementData di bawah
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use Illuminate\Support\Facades\Log;

$args     = array_slice($argv, 1);
$isDryRun = in_array('--dry-run', $args);

// ============================================================
// DATA DARI TIKTOK SELLER CENTER
// Format: 'ORDER_ID' => 'TANGGAL_LEPAS (Waktu pembayaran pesanan)'
// ============================================================
$settlementData = [
    // ── Cair Tgl 2026-08-02 ──────────────────────────────────
    '585252154366002810' => '2026-08-02',
    '585251707782006565' => '2026-08-02',
    '585249052003632941' => '2026-08-02',
    '585248548648289939' => '2026-08-02',
    '585245785735464521' => '2026-08-02',
    '585245457517282862' => '2026-08-02',
    '585226023099074086' => '2026-08-02',
    '585220190288053450' => '2026-08-02',
    '585203536001336926' => '2026-08-02',
    '585192027787659219' => '2026-08-02',
    '585164605968647177' => '2026-08-02',
    '585163321304778400' => '2026-08-02',
    '585159130737116660' => '2026-08-02',
    '585157783246177596' => '2026-08-02',
    '585154469289886864' => '2026-08-02',
    '585152409980209148' => '2026-08-02',
    '585147402674210292' => '2026-08-02',
    '585146829792118057' => '2026-08-02',
    '585125822203791343' => '2026-08-02',
    '585121983927125677' => '2026-08-02', // ← order yang dimaksud
    '585038139942668107' => '2026-08-02',
    '585036681069036776' => '2026-08-02',

    // ── Cair Tgl 2026-08-01 ──────────────────────────────────
    '585248575970313907' => '2026-08-01',
    '585244928204178440' => '2026-08-01',
    '585244344598955033' => '2026-08-01',
    '585237373037545202' => '2026-08-01',
    '585234160472590032' => '2026-08-01',
    '585232323473409597' => '2026-08-01',
    '585227553832470010' => '2026-08-01',
    '585224410968983175' => '2026-08-01',
    '585218150781650068' => '2026-08-01',
    '585218150781912212' => '2026-08-01',
    '585217249205257836' => '2026-08-01',
    '585216451571516495' => '2026-08-01',
    '585215244293670084' => '2026-08-01',
    '585211242108782456' => '2026-08-01',
    '585209720473224706' => '2026-08-01',
    '585209344436176618' => '2026-08-01',
    '585209123663152343' => '2026-08-01',
    '585203129802131261' => '2026-08-01',
    '585201781780940332' => '2026-08-01',
    '585201436761163144' => '2026-08-01',
    '585167457730267099' => '2026-08-01',
    '585166763085694495' => '2026-08-01',
    '585164662671705423' => '2026-08-01',
    '585153539068036850' => '2026-08-01',
    '585149389360563605' => '2026-08-01',
    '585148749367051476' => '2026-08-01',
    '585102216205992990' => '2026-08-01',
    '585057782486172916' => '2026-08-01',
    '585052996251845745' => '2026-08-01',
    '585033697508885735' => '2026-08-01',
    '585033694168646887' => '2026-08-01',
    '585033642160456935' => '2026-08-01',
    '585033615287420135' => '2026-08-01',
    '585033574201197799' => '2026-08-01',
    '585031345415095417' => '2026-08-01',
];

// ── Banner ─────────────────────────────────────────────────────
echo "\n";
echo "======================================================================\n";
echo "  FIX TGL LEPAS (completed_at) DARI TIKTOK SELLER CENTER\n";
echo "======================================================================\n";
echo "  Mode   : " . ($isDryRun ? "DRY-RUN (tidak ada yang disimpan)" : "LIVE (perubahan disimpan ke DB)") . "\n";
echo "  Total  : " . count($settlementData) . " order dari TikTok Seller Center\n";
echo "======================================================================\n\n";

$found       = 0;
$updated     = 0;
$alreadyOk   = 0;
$notInErp    = 0;

foreach ($settlementData as $orderId => $settlementDate) {

    $order = Order::where('order_marketplace_id', $orderId)->first();

    if (!$order) {
        echo "  TIDAK ADA DI ERP : {$orderId} (tgl lepas seharusnya {$settlementDate})\n";
        $notInErp++;
        continue;
    }

    $found++;

    // Format completed_at yang benar: pakai jam 00:00:00 di tanggal settlement
    // (karena TikTok Seller Center hanya kasih tanggal, bukan jam pasti)
    $correctCompletedAt = $settlementDate . ' 00:00:00';

    // Cek apakah completed_at sudah benar
    $currentDate = $order->completed_at
        ? (is_string($order->completed_at)
            ? substr($order->completed_at, 0, 10)
            : $order->completed_at->format('Y-m-d'))
        : null;

    $needUpdate = ($currentDate !== $settlementDate);

    $statusLine = "  [{$orderId}] ERP completed_at: " . ($currentDate ?? 'NULL') . " -> settlement: {$settlementDate}";

    if (!$needUpdate) {
        echo "  [OK] [{$orderId}] completed_at sudah benar: {$settlementDate}\n";
        $alreadyOk++;
        continue;
    }

    echo $statusLine;

    if ($isDryRun) {
        echo " [DRY-RUN]\n";
        $updated++;
        continue;
    }

    // Update completed_at + pastikan status COMPLETED
    $updateData = ['completed_at' => $correctCompletedAt];

    // Kalau status bukan COMPLETED padahal sudah cair, fix juga statusnya
    if (!in_array($order->order_status, ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])) {
        $updateData['order_status'] = 'COMPLETED';
        echo " [+ fix status: {$order->order_status} -> COMPLETED]";
    }

    Order::where('id', $order->id)->update($updateData);
    echo " -> DIUPDATE\n";

    Log::info('[FixTglLepas] completed_at diperbarui dari data Seller Center', [
        'order_id'             => $order->id,
        'order_marketplace_id' => $orderId,
        'old_completed_at'     => $currentDate,
        'new_completed_at'     => $correctCompletedAt,
        'order_status'         => $order->order_status,
    ]);

    $updated++;
}

// ── Ringkasan ─────────────────────────────────────────────────
echo "\n======================================================================\n";
echo "  RINGKASAN\n";
echo "======================================================================\n";
echo "  Dari Seller Center     : " . count($settlementData) . " order\n";
echo "  Ditemukan di ERP       : {$found}\n";
echo "  Tidak ada di ERP       : {$notInErp}\n";
echo "  Sudah benar (skip)     : {$alreadyOk}\n";
echo "  Tgl lepas diupdate     : {$updated}\n";
echo "======================================================================\n";

if ($isDryRun && $updated > 0) {
    echo "\nJalankan tanpa --dry-run untuk menyimpan:\n";
    echo "  php fix_tgl_lepas_tiktok.php\n";
}

if ($notInErp > 0) {
    echo "\nINFO: {$notInErp} order dari Seller Center tidak ada di ERP.\n";
    echo "Kemungkinan belum pernah di-pull. Jalankan:\n";
    echo "  php resync_tiktok_status.php --days=90\n";
}

echo "\nSelesai!\n\n";
