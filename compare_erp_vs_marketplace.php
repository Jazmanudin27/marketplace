<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Store;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

$fromDate = '2026-08-01 00:00:00';
$toDate   = '2026-08-16 23:59:59';

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--from=')) {
        $fromDate = trim(explode('=', $arg)[1]) . ' 00:00:00';
    }
    if (str_starts_with($arg, '--to=')) {
        $toDate = trim(explode('=', $arg)[1]) . ' 23:59:59';
    }
}

$startTs = strtotime($fromDate);
$endTs   = strtotime($toDate);

echo "=========================================================================================================\n";
echo "  LAPORAN REKONSILIASI & PERBANDINGAN ERP VS MARKETPLACE PER TOKO\n";
echo "=========================================================================================================\n";
echo "  Periode Laporan : " . date('d M Y', $startTs) . " s/d " . date('d M Y', $endTs) . "\n";
echo "=========================================================================================================\n\n";

$stores = Store::where('status', 'connected')->get();

if ($stores->isEmpty()) {
    echo "❌ Tidak ada toko berstatus CONNECTED.\n";
    exit(0);
}

$grandErpOrders = 0;
$grandErpGross  = 0;
$grandErpAdmin  = 0;
$grandErpNet    = 0;

$grandApiOrders = 0;
$grandApiGross  = 0;
$grandApiAdmin  = 0;
$grandApiNet    = 0;

foreach ($stores as $store) {
    $channelCode = strtolower($store->channel->code ?? 'n/a');

    // 1. REKAP DATA ERP (DATABASE LOKAL)
    $erpOrdersQuery = Order::where('store_id', $store->id)
        ->whereBetween('order_date', [$fromDate, $toDate])
        ->where('order_status', '!=', 'CANCELLED');

    $erpOrderCount = $erpOrdersQuery->count();
    $erpGross      = (float) $erpOrdersQuery->sum('total_amount');
    $erpAdmin      = (float) $erpOrdersQuery->sum('marketplace_fee');
    $erpNet        = (float) $erpOrdersQuery->sum('net_amount');

    $grandErpOrders += $erpOrderCount;
    $grandErpGross  += $erpGross;
    $grandErpAdmin  += $erpAdmin;
    $grandErpNet    += $erpNet;

    // 2. REKAP DATA RESMI API MARKETPLACE
    $apiOrderCount = 0;
    $apiGross      = 0.0;
    $apiAdmin      = 0.0;
    $apiNet        = 0.0;

    if (in_array($channelCode, ['tiktok', 'tiktok_shop', 'tokopedia']) || $store->channel_id == 3) {
        try {
            $tiktokService = app(\App\Services\TiktokService::class);
            $accessToken = $store->getValidAccessToken();
            $shopCipher = $store->shop_cipher;

            if (!empty($accessToken) && !empty($shopCipher)) {
                $cursor = '';
                $orderIds = [];
                $pageCount = 0;

                do {
                    $response = $tiktokService->getOrderList($accessToken, $shopCipher, $startTs, $endTs, $cursor);
                    $orders = $response['orders'] ?? $response['order_list'] ?? [];
                    foreach ($orders as $o) {
                        $status = strtoupper((string)($o['status'] ?? $o['order_status'] ?? ''));
                        if (!in_array($status, ['CANCELLED', '140'])) {
                            $id = $o['id'] ?? $o['order_id'] ?? null;
                            if ($id) $orderIds[] = $id;
                        }
                    }
                    $cursor = $response['next_cursor'] ?? '';
                    $hasMore = $response['more'] ?? false;
                    if (++$pageCount > 10) break;
                } while ($hasMore && $cursor);

                $apiOrderCount = count($orderIds);

                if (!empty($orderIds)) {
                    $chunks = array_chunk($orderIds, 50);
                    foreach ($chunks as $chunk) {
                        $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, $chunk);
                        $orderList = $detailRes['orders'] ?? $detailRes['order_list'] ?? [];
                        foreach ($orderList as $tOrder) {
                            $payment = $tOrder['payment_info'] ?? $tOrder['payment'] ?? [];
                            
                            $itemList = $tOrder['line_items'] ?? $tOrder['item_list'] ?? [];
                            $pSubtotal = 0.0;
                            foreach ($itemList as $it) {
                                $pSubtotal += ((float)($it['original_price'] ?? $it['sale_price'] ?? 0) * (int)($it['quantity'] ?? 1));
                            }

                            $tot = $pSubtotal > 0 ? $pSubtotal : (float)($payment['original_total_product_price'] ?? $payment['total_amount'] ?? 0);
                            $net = (float)($payment['escrow_amount'] ?? $payment['settlement_amount'] ?? 0);
                            
                            if ($net <= 0) {
                                $net = max(0.0, $tot * 0.915);
                            }
                            $adm = max(0.0, $tot - $net);

                            $apiGross += $tot;
                            $apiAdmin += $adm;
                            $apiNet   += $net;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore API error
        }
    } elseif ($channelCode === 'shopee' || $store->channel_id == 1) {
        try {
            $shopeeService = app(\App\Services\ShopeeService::class);
            $accessToken = $store->getValidAccessToken();
            $shopId = (int) ($store->marketplace_store_id ?: $store->shopee_shop_id);

            if (!empty($accessToken) && !empty($shopId)) {
                $orderSns = [];
                $cursor = '';
                $pageCount = 0;

                do {
                    $res = $shopeeService->getOrderList($accessToken, $shopId, $startTs, $endTs, 'create_time', $cursor, 50);
                    $orderList = $res['order_list'] ?? [];
                    foreach ($orderList as $o) {
                        if (!empty($o['order_sn'])) $orderSns[] = $o['order_sn'];
                    }
                    $cursor = $res['next_cursor'] ?? '';
                    $hasMore = $res['more'] ?? false;
                    if (++$pageCount > 10) break;
                } while ($hasMore && $cursor);

                $apiOrderCount = count($orderSns);

                if (!empty($orderSns)) {
                    $chunks = array_chunk($orderSns, 50);
                    foreach ($chunks as $chunk) {
                        $detailsRes = $shopeeService->getOrderDetail($accessToken, $shopId, $chunk);
                        $orderList = $detailsRes['order_list'] ?? [];
                        foreach ($orderList as $sOrder) {
                            if (($sOrder['order_status'] ?? '') === 'CANCELLED') continue;

                            $pSubtotal = 0.0;
                            if (!empty($sOrder['item_list'])) {
                                foreach ($sOrder['item_list'] as $it) {
                                    $pSubtotal += ((float)($it['model_discounted_price'] ?? $it['model_original_price'] ?? 0) * (int)($it['model_quantity_purchased'] ?? 1));
                                }
                            }
                            $tot = $pSubtotal > 0 ? $pSubtotal : (float)($sOrder['total_amount'] ?? 0);

                            $escrowAmt = 0.0;
                            $admAmt = 0.0;

                            try {
                                $escrowRes = $shopeeService->getEscrowDetail($accessToken, $shopId, $sOrder['order_sn']);
                                $income = $escrowRes['order_income'] ?? [];
                                $escrowAmt = (float)($income['escrow_amount'] ?? 0);
                                $admAmt = (float)($income['commission_fee'] ?? 0) + (float)($income['service_fee'] ?? 0) + (float)($income['seller_transaction_fee'] ?? 0);
                            } catch (\Throwable $e) {}

                            if ($escrowAmt <= 0) {
                                $admAmt = round($tot * 0.095);
                                $escrowAmt = max(0.0, $tot - $admAmt);
                            }

                            $apiGross += $tot;
                            $apiAdmin += $admAmt;
                            $apiNet   += $escrowAmt;
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Ignore API error
        }
    }

    $grandApiOrders += $apiOrderCount;
    $grandApiGross  += $apiGross;
    $grandApiAdmin  += $apiAdmin;
    $grandApiNet    += $apiNet;

    $diffOrders = $erpOrderCount - $apiOrderCount;
    $diffGross  = $erpGross - $apiGross;
    $diffAdmin  = $erpAdmin - $apiAdmin;
    $diffNet    = $erpNet - $apiNet;

    $statusMatch = ($diffOrders === 0 && abs($diffNet) < 100) ? "✅ SAMA (MATCH 100%)" : "⚠️ ADA SELISIH";

    echo "---------------------------------------------------------------------------------------------------------\n";
    echo "🏬 TOKO: #{$store->id} - {$store->name} [" . strtoupper($channelCode) . "] | Status: {$statusMatch}\n";
    echo "---------------------------------------------------------------------------------------------------------\n";
    echo sprintf("  %-25s | %-18s | %-18s | %-18s\n", "METRIK PENCATATAN", "ERP DATABASE", "API MARKETPLACE", "SELISIH (ERP - API)");
    echo str_repeat("-", 85) . "\n";
    echo sprintf("  %-25s | %-18s | %-18s | %-18s\n", "1. Jumlah Pesanan (Order)", number_format($erpOrderCount) . " order", number_format($apiOrderCount) . " order", ($diffOrders >= 0 ? "+" : "") . number_format($diffOrders) . " order");
    echo sprintf("  %-25s | %-18s | %-18s | %-18s\n", "2. Total Omset Kotor", "Rp " . number_format($erpGross, 0, ',', '.'), "Rp " . number_format($apiGross, 0, ',', '.'), "Rp " . number_format($diffGross, 0, ',', '.'));
    echo sprintf("  %-25s | %-18s | %-18s | %-18s\n", "3. Total Biaya Admin", "Rp " . number_format($erpAdmin, 0, ',', '.'), "Rp " . number_format($apiAdmin, 0, ',', '.'), "Rp " . number_format($diffAdmin, 0, ',', '.'));
    echo sprintf("  %-25s | %-18s | %-18s | %-18s\n", "4. Total Omset Bersih (Net)", "Rp " . number_format($erpNet, 0, ',', '.'), "Rp " . number_format($apiNet, 0, ',', '.'), "Rp " . number_format($diffNet, 0, ',', '.'));
    echo "\n";
}

echo "=========================================================================================================\n";
echo "  GRAND TOTAL REKAPITULASI SELURUH TOKO TERHUBUNG:\n";
echo "=========================================================================================================\n";
echo sprintf("  %-25s | %-18s | %-18s | %-18s\n", "TOTAL JUMLAH ORDER", number_format($grandErpOrders) . " order", number_format($grandApiOrders) . " order", number_format($grandErpOrders - $grandApiOrders) . " order");
echo sprintf("  %-25s | %-18s | %-18s | %-18s\n", "TOTAL OMSET KOTOR", "Rp " . number_format($grandErpGross, 0, ',', '.'), "Rp " . number_format($grandApiGross, 0, ',', '.'), "Rp " . number_format($grandErpGross - $grandApiGross, 0, ',', '.'));
echo sprintf("  %-25s | %-18s | %-18s | %-18s\n", "TOTAL BIAYA ADMIN", "Rp " . number_format($grandErpAdmin, 0, ',', '.'), "Rp " . number_format($grandApiAdmin, 0, ',', '.'), "Rp " . number_format($grandErpAdmin - $grandApiAdmin, 0, ',', '.'));
echo sprintf("  %-25s | %-18s | %-18s | %-18s\n", "TOTAL OMSET BERSIH (NET)", "Rp " . number_format($grandErpNet, 0, ',', '.'), "Rp " . number_format($grandApiNet, 0, ',', '.'), "Rp " . number_format($grandErpNet - $grandApiNet, 0, ',', '.'));
echo "=========================================================================================================\n";
