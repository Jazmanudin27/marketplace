<?php
/**
 * SCRIPT DIAGNOSTIK PRESIISI TOKO #30 (Nusantara seragam)
 * Dijalankan via Terminal Server: php rekap_juli_2026.php
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\Store;

echo "======================================================================\n";
echo " 🎵 DIAGNOSTIK LAPORAN PENJUALAN DILEPAS (DANA CAIR) TOKO #30\n";
echo "======================================================================\n\n";

$storeId  = 30;
$dateFrom = '2026-07-01 00:00:00';
$dateTo   = '2026-07-31 23:59:59';

$store = Store::with('channel')->find($storeId);

if (!$store) {
    echo "[!] Toko ID #{$storeId} tidak ditemukan.\n";
    exit;
}

echo "Nama Toko  : {$store->store_name} (ID #{$store->id})\n";
echo "Channel    : " . ($store->channel->name ?? 'TikTok') . "\n";
echo "Periode    : 01 s/d 31 Juli 2026\n";
echo "----------------------------------------------------------------------\n\n";

function printTableForOrders($title, $orders) {
    $dbCount        = $orders->count();
    $dbOmset        = 0.0;
    $dbRefund       = 0.0;
    $dbPlatformFee  = 0.0;
    $dbFreeShipFee  = 0.0;
    $dbServiceFee   = 0.0;
    $dbPromoFee     = 0.0;
    $dbOtherFee     = 0.0;
    $dbTotalFee     = 0.0;
    $dbNetReleased  = 0.0;

    $apiGrossTotal      = 0.0;
    $apiRefundTotal     = 0.0;
    $apiFeeTotal        = 0.0;
    $apiSettlementTotal = 0.0;
    $countWithApiData   = 0;

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

        $dbOmset       += $omset;
        $dbRefund      += $ref;
        $dbPlatformFee += $pFee;
        $dbFreeShipFee += $fShip;
        $dbServiceFee  += $sFee;
        $dbPromoFee    += $prFee;
        $dbOtherFee    += $oFee;
        $dbTotalFee    += $totFee;
        $dbNetReleased += $net;

        $fb = $o->financial_breakdown ?? [];
        $stmtList = $fb['statement_transactions'] ?? $fb['statement_transaction_list'] ?? $fb['transactions'] ?? [];

        if (!empty($stmtList) && is_array($stmtList)) {
            $countWithApiData++;
            foreach ($stmtList as $st) {
                $apiGrossTotal += (float) ($st['gross_sales_amount'] ?? $st['after_seller_discounts_subtotal_amount'] ?? $st['revenue_amount'] ?? 0);
                $rAmt = abs((float) ($st['customer_refund_amount'] ?? $st['gross_sales_refund_amount'] ?? $st['customer_order_refund_amount'] ?? 0));
                $apiRefundTotal += $rAmt;
                
                if ($rAmt == 0 && isset($st['fee_amount'])) {
                    $apiFeeTotal += abs((float) $st['fee_amount']);
                }
                
                if (isset($st['settlement_amount'])) {
                    $apiSettlementTotal += (float) $st['settlement_amount'];
                }
            }
        }
    }

    echo "======================================================================\n";
    echo " 📊 {$title}\n";
    echo "======================================================================\n\n";

    printf(" %-32s | %-20s | %-20s\n", "KOMPONEN KEUANGAN", "DI DATABASE ERP", "DARI STATEMENT API");
    echo "----------------------------------------------------------------------\n";
    printf(" %-32s | %-20s | %-20s\n", "Jumlah Pesanan", number_format($dbCount, 0, ',', '.') . " order", number_format($countWithApiData, 0, ',', '.') . " order synced");
    printf(" %-32s | Rp %-17s | Rp %-17s\n", "1. Omset Kotor", number_format($dbOmset, 0, ',', '.'), number_format($apiGrossTotal > 0 ? $apiGrossTotal : $dbOmset, 0, ',', '.'));
    printf(" %-32s | -Rp %-16s | -Rp %-16s\n", "2. Total Refund / Retur", number_format($dbRefund, 0, ',', '.'), number_format($apiRefundTotal, 0, ',', '.'));
    echo "----------------------------------------------------------------------\n";
    printf(" %-32s | -Rp %-16s | -\n", "   - Biaya Platform", number_format($dbPlatformFee, 0, ',', '.'));
    printf(" %-32s | -Rp %-16s | -\n", "   - Biaya Gratis Ongkir", number_format($dbFreeShipFee, 0, ',', '.'));
    printf(" %-32s | -Rp %-16s | -\n", "   - Biaya Layanan", number_format($dbServiceFee, 0, ',', '.'));
    printf(" %-32s | -Rp %-16s | -\n", "   - Biaya Promosi", number_format($dbPromoFee, 0, ',', '.'));
    printf(" %-32s | -Rp %-16s | -\n", "   - Biaya Lainnya", number_format($dbOtherFee, 0, ',', '.'));
    printf(" %-32s | -Rp %-16s | -Rp %-16s\n", "3. Total Potongan Fee Admin", number_format($dbTotalFee, 0, ',', '.'), number_format($apiFeeTotal, 0, ',', '.'));
    echo "----------------------------------------------------------------------\n";
    printf(" %-32s | Rp %-17s | Rp %-17s\n", "4. DANA CAIR NET (Settlement)", number_format($dbNetReleased, 0, ',', '.'), number_format($apiSettlementTotal > 0 ? $apiSettlementTotal : $dbNetReleased, 0, ',', '.'));
    echo "======================================================================\n\n";
}

// A. QUERY BERDASARKAN TANGGAL PENJUALAN DILEPAS / DANA CAIR (completed_at)
$completedOrders = Order::where('store_id', $storeId)
    ->whereNotNull('completed_at')
    ->whereBetween('completed_at', [$dateFrom, $dateTo])
    ->with(['items', 'returnOrder'])
    ->get();

printTableForOrders("MODE A: FILTER TANGGAL DANA CAIR / PENJUALAN DILEPAS (completed_at)", $completedOrders);

// B. QUERY BERDASARKAN TANGGAL ORDER DIBUAT (order_date)
$createdOrders = Order::where('store_id', $storeId)
    ->whereBetween('order_date', [$dateFrom, $dateTo])
    ->with(['items', 'returnOrder'])
    ->get();

printTableForOrders("MODE B: FILTER TANGGAL PESANAN DIBUAT (order_date)", $createdOrders);
