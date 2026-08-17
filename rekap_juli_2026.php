<?php
/**
 * SCRIPT REKAPITULASI DANA CAIR TIKTOK SHOP PERIODE JULI 2026
 * Dijalankan langsung via Terminal Server: php rekap_juli_2026.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;

echo "======================================================================\n";
echo " 📊 REKAPITULASI REAL DATABASE ERP - TIKTOK SHOP (01-31 JULI 2026)\n";
echo "======================================================================\n\n";

$dateFrom = '2026-07-01 00:00:00';
$dateTo   = '2026-07-31 23:59:59';

$stores = Store::whereHas('channel', function ($q) {
    $q->whereIn('code', ['tiktok', 'tiktok_shop', 'tokopedia']);
})->get();

if ($stores->isEmpty()) {
    echo "[!] Tidak ada toko TikTok / Tokopedia ditemukan.\n";
    exit;
}

echo "Toko Terhubung (" . $stores->count() . " toko):\n";
foreach ($stores as $s) {
    echo "  - ID #{$s->id}: {$s->store_name}\n";
}
echo "----------------------------------------------------------------------\n";

$orders = Order::whereIn('store_id', $stores->pluck('id'))
    ->where(function($q) use ($dateFrom, $dateTo) {
        $q->whereBetween('completed_at', [$dateFrom, $dateTo])
          ->orWhereBetween('order_date', [$dateFrom, $dateTo]);
    })
    ->with(['items', 'returnOrder', 'store.channel'])
    ->get();

$totalCount     = $orders->count();
$sumOmset       = 0.0;
$sumRefund      = 0.0;
$sumPlatform    = 0.0;
$sumFreeShip    = 0.0;
$sumService     = 0.0;
$sumPromo       = 0.0;
$sumOther       = 0.0;
$sumTotalFee    = 0.0;
$sumNetReleased = 0.0;

foreach ($orders as $o) {
    $omset = (float) $o->total_amount;
    $ref   = (float) $o->refund_amount;
    $dt    = $o->fee_breakdown_details;

    $pFee  = abs($dt['platform_fee'] ?? 0);
    $fShip = abs($dt['free_shipping'] ?? 0);
    $sFee  = abs($dt['service_fee'] ?? 0);
    $prFee = abs($dt['promo_fee'] ?? 0);
    $oFee  = abs($dt['other_fee'] ?? 0);
    $totFee= abs($dt['total_fee'] ?? 0);

    if ($ref >= $omset && $omset > 0) {
        $net = 0.0;
    } else {
        $net = max(0.0, $omset - $ref - $totFee);
    }

    $sumOmset       += $omset;
    $sumRefund      += $ref;
    $sumPlatform    += $pFee;
    $sumFreeShip    += $fShip;
    $sumService     += $sFee;
    $sumPromo       += $prFee;
    $sumOther       += $oFee;
    $sumTotalFee    += $totFee;
    $sumNetReleased += $net;
}

echo "HASIL PERHITUNGAN REAL DI DATABASE SERVER SAAT INI:\n\n";
echo "1. Jumlah Pesanan         : " . number_format($totalCount, 0, ',', '.') . " order\n";
echo "2. Total Pendapatan Kotor : Rp " . number_format($sumOmset, 0, ',', '.') . "\n";
echo "3. Total Retur / Refund   : -Rp " . number_format($sumRefund, 0, ',', '.') . "\n";
echo "----------------------------------------------------------------------\n";
echo "4. RINCIAN BIAYA POTONGAN MARKETPLACE:\n";
echo "   - Biaya Platform       : -Rp " . number_format($sumPlatform, 0, ',', '.') . "\n";
echo "   - Biaya Gratis Ongkir  : -Rp " . number_format($sumFreeShip, 0, ',', '.') . "\n";
echo "   - Biaya Layanan        : -Rp " . number_format($sumService, 0, ',', '.') . "\n";
echo "   - Biaya Promosi        : -Rp " . number_format($sumPromo, 0, ',', '.') . "\n";
echo "   - Biaya Lainnya        : -Rp " . number_format($sumOther, 0, ',', '.') . "\n";
echo "   -> TOTAL POTONGAN FEE  : -Rp " . number_format($sumTotalFee, 0, ',', '.') . "\n";
echo "----------------------------------------------------------------------\n";
echo "5. OMSET BERSIH DITERIMA  : Rp " . number_format($sumNetReleased, 0, ',', '.') . " (Dana Cair Net)\n";
echo "======================================================================\n\n";

echo "📌 RUMUS VERIFIKASI DANA CAIR:\n";
echo "   Omset Bersih (" . number_format($sumNetReleased, 0, ',', '.') . ") = Omset Kotor (" . number_format($sumOmset, 0, ',', '.') . ") - Refund (" . number_format($sumRefund, 0, ',', '.') . ") - Total Fee (" . number_format($sumTotalFee, 0, ',', '.') . ")\n\n";
