<?php

/**
 * ============================================================
 *  DETAIL PERBANDINGAN ERP vs API PER ORDER
 *  Termasuk deteksi & penanganan Retur / Pengembalian Dana
 * ============================================================
 *
 * CARA PAKAI:
 *   php detail_erp_vs_api_per_order.php
 *   php detail_erp_vs_api_per_order.php --from=2026-08-01 --to=2026-08-20
 *   php detail_erp_vs_api_per_order.php --store=5
 *   php detail_erp_vs_api_per_order.php --detail
 *   php detail_erp_vs_api_per_order.php --fix-returns
 */

require __DIR__ . '/vendor/autoload.php';
$app    = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Order;
use App\Models\ReturnOrder;
use App\Models\Store;

$fromDate    = '2026-08-01 00:00:00';
$toDate      = '2026-08-20 23:59:59';
$filterStore = null;
$showDetail  = false;
$fixReturns  = false;

foreach ($argv as $arg) {
    if (str_starts_with($arg, '--from='))  $fromDate    = trim(explode('=', $arg)[1]) . ' 00:00:00';
    if (str_starts_with($arg, '--to='))    $toDate      = trim(explode('=', $arg)[1]) . ' 23:59:59';
    if (str_starts_with($arg, '--store=')) $filterStore = (int) explode('=', $arg)[1];
    if ($arg === '--detail')               $showDetail  = true;
    if ($arg === '--fix-returns')          $fixReturns  = true;
}

$startTs = strtotime($fromDate);
$endTs   = strtotime($toDate);

function rp(float $v): string { return 'Rp ' . number_format($v, 0, ',', '.'); }

function diff_tag(float $d, float $tol = 500): string {
    if (abs($d) <= $tol) return '(=) SAMA';
    return $d > 0 ? '(+) ' . number_format($d, 0, ',', '.') : '(-) ' . number_format(abs($d), 0, ',', '.');
}

function isReturnStatus(string $s): bool {
    return in_array(strtoupper($s), ['RETURN','RETURNED','REFUNDED','REFUND','RETURN_APPROVED','RETURN_COMPLETED','PARTIAL_RETURN']);
}

echo "\n";
echo "===================================================================================\n";
echo "  DETAIL PERBANDINGAN ERP vs API MARKETPLACE — PER ORDER\n";
echo "===================================================================================\n";
echo "  Periode : " . date('d M Y', $startTs) . " s/d " . date('d M Y', $endTs) . "\n";
echo "  Mode    : " . ($showDetail ? 'DETAIL per baris order' : 'Ringkasan per toko') . "\n";
echo "===================================================================================\n\n";

$storeQuery = Store::where('status', 'connected');
if ($filterStore) $storeQuery->where('id', $filterStore);
$stores = $storeQuery->get();

if ($stores->isEmpty()) { echo "Tidak ada toko CONNECTED.\n"; exit(0); }

$grand = [
    'erp_orders'=>0,'erp_gross'=>0.0,'erp_admin'=>0.0,'erp_net'=>0.0,'erp_refund'=>0.0,
    'erp_ret_orders'=>0,'erp_ret_gross'=>0.0,
    'api_orders'=>0,'api_gross'=>0.0,'api_admin'=>0.0,'api_net'=>0.0,'api_refund'=>0.0,
    'api_ret_orders'=>0,'api_ret_amount'=>0.0,
    'fixed'=>0,
];

foreach ($stores as $store) {
    $ch = strtolower($store->channel->code ?? 'n/a');

    echo "-----------------------------------------------------------------------------------\n";
    echo "TOKO #{$store->id} — {$store->name}  [" . strtoupper($ch) . "]\n";
    echo "-----------------------------------------------------------------------------------\n";

    // 1. ERP
    $erpOrders  = Order::where('store_id', $store->id)
        ->whereBetween('order_date', [$fromDate, $toDate])
        ->where('order_status', '!=', 'CANCELLED')
        ->with('returnOrder')
        ->orderBy('order_date')
        ->get();

    $erpNormal  = $erpOrders->filter(fn($o) => !isReturnStatus($o->order_status));
    $erpRets    = $erpOrders->filter(fn($o) =>  isReturnStatus($o->order_status));

    $erpCnt  = $erpNormal->count();
    $erpG    = (float)$erpNormal->sum('total_amount');
    $erpA    = (float)$erpNormal->sum('marketplace_fee');
    $erpN    = (float)$erpNormal->sum('net_amount');
    $erpRC   = $erpRets->count();
    $erpRG   = (float)$erpRets->sum('total_amount');
    $erpWith = $erpNormal->filter(fn($o) => $o->returnOrder !== null);
    $erpRL   = $erpWith->sum(fn($o) => (float)$o->returnOrder->refund_amount);
    // ERP Total Refund = nilai order berstatus RETURN + partial return terhubung
    $erpRefund = $erpRG + $erpRL;

    // 2. API
    $apiCnt   = 0; $apiG = 0.0; $apiA = 0.0; $apiN = 0.0; $apiRefund = 0.0;
    $apiRC    = 0; $apiRA = 0.0;
    $apiMap   = [];

    // TikTok
    if (in_array($ch, ['tiktok','tiktok_shop','tokopedia']) || $store->channel_id == 3) {
        try {
            $svc    = app(\App\Services\TiktokService::class);
            $token  = $store->getValidAccessToken();
            $cipher = $store->shop_cipher;
            if ($token && $cipher) {
                $cursor = ''; $ids = []; $pc = 0;
                do {
                    $r = $svc->getOrderList($token, $cipher, $startTs, $endTs, $cursor);
                    foreach ($r['orders'] ?? $r['order_list'] ?? [] as $o) {
                        $st = strtoupper($o['status'] ?? $o['order_status'] ?? '');
                        if (!in_array($st, ['CANCELLED','140'])) {
                            $id = $o['id'] ?? $o['order_id'] ?? null;
                            if ($id) $ids[$id] = $st;
                        }
                    }
                    $cursor = $r['next_cursor'] ?? '';
                } while (($r['more'] ?? false) && $cursor && ++$pc < 20);

                foreach (array_chunk(array_keys($ids), 50) as $chunk) {
                    $dr = $svc->getOrderDetail($token, $cipher, $chunk);
                    foreach ($dr['orders'] ?? $dr['order_list'] ?? [] as $to) {
                        $oid  = $to['id'] ?? $to['order_id'] ?? null;
                        $st   = strtoupper($to['status'] ?? $to['order_status'] ?? '');
                        $pay  = $to['payment_info'] ?? $to['payment'] ?? [];
                        $sub  = 0.0;
                        foreach ($to['line_items'] ?? $to['item_list'] ?? [] as $it)
                            $sub += (float)($it['original_price'] ?? $it['sale_price'] ?? 0) * (int)($it['quantity'] ?? 1);
                        $tot = $sub > 0 ? $sub : (float)($pay['original_total_product_price'] ?? $pay['total_amount'] ?? 0);
                        $net = (float)($pay['escrow_amount'] ?? $pay['settlement_amount'] ?? 0);
                        if ($net <= 0) $net = $tot * 0.915;
                        $adm = max(0.0, $tot - $net);
                        $ra  = (float)($pay['customer_refund_amount'] ?? $pay['seller_return_refund'] ?? $pay['return_amount'] ?? 0);
                        $isR = isReturnStatus($st) || $ra > 0;
                        if ($isR) {
                            $raActual = $ra > 0 ? $ra : $tot;
                            $apiRC++; $apiRA += $raActual; $apiRefund += $raActual;
                            $apiMap[$oid] = ['gross'=>$tot,'admin'=>$adm,'net'=>max(0,$net-$ra),'status'=>$st,'return_amount'=>$raActual,'is_return'=>true];
                        } else {
                            // Cek juga refund parsial di order normal
                            if ($ra > 0) $apiRefund += $ra;
                            $apiCnt++; $apiG += $tot; $apiA += $adm; $apiN += $net;
                            $apiMap[$oid] = ['gross'=>$tot,'admin'=>$adm,'net'=>$net,'status'=>$st,'return_amount'=>$ra,'is_return'=>false];
                        }
                    }
                }
            }
        } catch (\Throwable $e) { echo "  [TikTok API error] " . $e->getMessage() . "\n"; }

    // Shopee
    } elseif ($ch === 'shopee' || $store->channel_id == 1) {
        try {
            $svc   = app(\App\Services\ShopeeService::class);
            $token = $store->getValidAccessToken();
            $sid   = (int)($store->marketplace_store_id ?: $store->shopee_shop_id);
            if ($token && $sid) {
                $sns = []; $cursor = ''; $pc = 0;
                do {
                    $r = $svc->getOrderList($token, $sid, $startTs, $endTs, 'create_time', $cursor, 50);
                    foreach ($r['order_list'] ?? [] as $o)
                        if (!empty($o['order_sn'])) $sns[$o['order_sn']] = strtoupper($o['order_status'] ?? '');
                    $cursor = $r['next_cursor'] ?? '';
                } while (($r['more'] ?? false) && $cursor && ++$pc < 20);

                foreach (array_chunk(array_keys($sns), 50) as $chunk) {
                    $dr = $svc->getOrderDetail($token, $sid, $chunk);
                    foreach ($dr['order_list'] ?? [] as $so) {
                        $osn = $so['order_sn']; $st = strtoupper($so['order_status'] ?? '');
                        if ($st === 'CANCELLED') continue;
                        $sub = 0.0;
                        foreach ($so['item_list'] ?? [] as $it)
                            $sub += (float)($it['model_discounted_price'] ?? $it['model_original_price'] ?? 0) * (int)($it['model_quantity_purchased'] ?? 1);
                        $tot = $sub > 0 ? $sub : (float)($so['total_amount'] ?? 0);
                        $esc = 0.0; $adm = 0.0; $ra = 0.0;
                        try {
                            $er  = $svc->getEscrowDetail($token, $sid, $osn);
                            $inc = $er['order_income'] ?? [];
                            $esc = (float)($inc['escrow_amount'] ?? 0);
                            $adm = (float)($inc['commission_fee'] ?? 0) + (float)($inc['service_fee'] ?? 0) + (float)($inc['seller_transaction_fee'] ?? 0);
                            $ra  = (float)($inc['buyer_return_refund_amount'] ?? $inc['refund_amount'] ?? $inc['return_amount'] ?? 0);
                        } catch (\Throwable $e) {}
                        if ($esc <= 0) { $adm = round($tot*0.095); $esc = max(0,$tot-$adm); }
                        $isR = isReturnStatus($st) || $ra > 0;
                        if ($isR) {
                            $raActual = $ra > 0 ? $ra : $tot;
                            $apiRC++; $apiRA += $raActual; $apiRefund += $raActual;
                            $apiMap[$osn] = ['gross'=>$tot,'admin'=>$adm,'net'=>max(0,$esc-$ra),'status'=>$st,'return_amount'=>$raActual,'is_return'=>true];
                        } else {
                            if ($ra > 0) $apiRefund += $ra;
                            $apiCnt++; $apiG += $tot; $apiA += $adm; $apiN += $esc;
                            $apiMap[$osn] = ['gross'=>$tot,'admin'=>$adm,'net'=>$esc,'status'=>$st,'return_amount'=>$ra,'is_return'=>false];
                        }
                    }
                }
            }
        } catch (\Throwable $e) { echo "  [Shopee API error] " . $e->getMessage() . "\n"; }
    }

    // 3. Tabel ringkasan
    $dO  = $erpCnt    - $apiCnt;
    $dG  = $erpG      - $apiG;
    $dA  = $erpA      - $apiA;
    $dN  = $erpN      - $apiN;
    $dR  = $erpRefund - $apiRefund;
    $matchSt = (abs($dO) == 0 && abs($dN) < 500) ? "[SINKRON]" : "[ADA SELISIH]";

    $W = 100;
    echo "\n";
    echo "  " . str_repeat("=", $W) . "\n";
    echo sprintf("  | %-25s | %s |\n", "STATUS REKONSILIASI", str_pad($matchSt, $W - 33));
    echo "  " . str_repeat("=", $W) . "\n";
    echo sprintf("  | %-28s | %-18s | %-18s | %-18s |\n",
        "METRIK", "ERP DATABASE", "API MARKETPLACE", "SELISIH (ERP-API)");
    echo "  " . str_repeat("-", $W) . "\n";
    echo sprintf("  | %-28s | %-18s | %-18s | %-18s |\n",
        "Jumlah Order (Normal)",
        $erpCnt . " order",
        $apiCnt . " order",
        diff_tag($dO));
    echo sprintf("  | %-28s | %-18s | %-18s | %-18s |\n",
        "Total Omset Kotor",
        rp($erpG), rp($apiG), diff_tag($dG));
    echo sprintf("  | %-28s | %-18s | %-18s | %-18s |\n",
        "Total Biaya Admin",
        rp($erpA), rp($apiA), diff_tag($dA));
    echo sprintf("  | %-28s | %-18s | %-18s | %-18s |\n",
        "Total Omset Bersih (Net)",
        rp($erpN), rp($apiN), diff_tag($dN));
    echo "  " . str_repeat("-", $W) . "\n";
    echo sprintf("  | %-28s | %-18s | %-18s | %-18s |\n",
        "Total Refund / Retur",
        rp($erpRefund), rp($apiRefund), diff_tag($dR));
    echo "  | " . str_pad("  Rincian ERP : order berstatus RETURN = ".rp($erpRG).", partial return terhubung = ".rp($erpRL), $W - 4) . " |\n";
    echo "  | " . str_pad("  Rincian API : order retur = ".number_format($apiRC)." order, total return_amount = ".rp($apiRA), $W - 4) . " |\n";
    echo "  " . str_repeat("=", $W) . "\n";

    // Diagnosis
    if (abs($dN) >= 500 || $erpRC != $apiRC || abs($dR) > 500) {
        echo "\n  [DIAGNOSIS SELISIH]\n";
        if ($erpRC > $apiRC)
            echo "  -> ERP punya ".($erpRC-$apiRC)." order retur lebih banyak dari API.\n"
               . "     Kemungkinan tanggal retur berbeda dengan tanggal order. Solusi: php pull_missing_returns.php\n";
        if ($erpRC < $apiRC)
            echo "  -> API punya ".($apiRC-$erpRC)." order retur lebih banyak dari ERP.\n"
               . "     Solusi: php pull_missing_returns.php\n";
        if ($erpWith->count() > 0)
            echo "  -> Ada ".$erpWith->count()." order normal dgn partial return. Net ERP sudah dikurangi refund partial.\n";
        if (abs($dR) > 500)
            echo "  -> Selisih refund ERP vs API = ".rp(abs($dR))."\n"
               . "     Jalankan: php detail_erp_vs_api_per_order.php --fix-returns --detail\n";
        if (abs($dG) > 500 && $erpRC == 0 && $apiRC == 0)
            echo "  -> Selisih omset tanpa retur. Cek tanggal order atau order yang belum di-pull.\n";
    }

    // 4. Detail per order
    if ($showDetail) {
        $fixedCount = 0;
        echo "\n  [DETAIL PER ORDER — ERP vs API dengan kolom Refund]\n";
        $LW = 160;
        echo "  " . str_repeat("-", $LW) . "\n";
        // Header kolom: No Order | Tgl | ERP Gross | ERP Admin | ERP Net | ERP Refund | API Gross | API Admin | API Net | API Refund | Status | Ket
        echo sprintf("  %-28s | %-16s | %11s | %11s | %11s | %11s | %11s | %11s | %11s | %11s | %-9s | %s\n",
            "No. Order", "Tgl Order",
            "ERP Gross", "ERP Admin", "ERP Net", "ERP Refund",
            "API Gross", "API Admin", "API Net", "API Refund",
            "Status", "Ket.");
        echo "  " . str_repeat("-", $LW) . "\n";

        foreach ($erpOrders as $order) {
            $oid    = $order->order_marketplace_id ?? ('ERP#' . $order->id);
            $isRet  = isReturnStatus($order->order_status);
            $row    = $apiMap[$oid] ?? null;

            // Nilai ERP
            $erpGross  = (float)$order->total_amount;
            $erpAdmin  = (float)$order->marketplace_fee;
            $erpNet    = (float)$order->net_amount;
            // ERP Refund: cek returnOrder.refund_amount dulu, fallback ke getRefundAmountAttribute
            $erpRef    = 0.0;
            if ($isRet) {
                $erpRef = $erpGross; // order full return
            } elseif ($order->returnOrder) {
                $erpRef = (float)$order->returnOrder->refund_amount;
            } else {
                // Cek financial_breakdown
                $fb = $order->financial_breakdown ?? [];
                $refKeys = ['customer_refund_amount','gross_sales_refund_amount','seller_return_refund','refund_amount','return_amount'];
                foreach ($refKeys as $k) {
                    if (!empty($fb[$k])) { $erpRef = abs((float)$fb[$k]); break; }
                }
            }

            // Nilai API
            $apiGross  = $row ? $row['gross']         : 0.0;
            $apiAdmin  = $row ? $row['admin']         : 0.0;
            $apiNet    = $row ? $row['net']           : 0.0;
            $apiRef    = $row ? $row['return_amount'] : 0.0;

            // Flag & keterangan
            $flag = ''; $ket = '';
            if ($isRet) {
                $flag = '[RETUR]';
                $ket  = 'Full return — excl. dari total normal';
            } elseif (!$row) {
                $flag = '[NO API]';
                $ket  = 'Tidak ada di API';
            } else {
                $nd  = $erpNet - $apiNet;
                $ndr = $erpRef - $apiRef;
                if ($apiRef > 0 || $erpRef > 0) {
                    $flag = '[REFUND]';
                    $ket  = abs($ndr) < 500
                        ? 'Refund sinkron'
                        : 'Selisih refund='.rp(abs($ndr));
                    if ($fixReturns && abs($ndr) > 100) {
                        $fb = $order->financial_breakdown ?? [];
                        $fb['customer_refund_amount'] = $apiRef;
                        $order->financial_breakdown = $fb;
                        $order->save();
                        $flag = '[FIXED]'; $ket .= ' -> ERP diperbarui';
                        $fixedCount++;
                    }
                } elseif (abs($nd) > 500) {
                    $flag = abs($nd) > 5000 ? '[BEDA!]' : '[SELISIH]';
                    $ket  = 'Net delta='.rp(abs($nd));
                } else {
                    $flag = '[OK]';
                }
            }

            echo sprintf("  %-28s | %-16s | %11s | %11s | %11s | %11s | %11s | %11s | %11s | %11s | %-9s | %s %s\n",
                substr($oid, 0, 28),
                date('d-m-Y H:i', strtotime($order->order_date)),
                rp($erpGross), rp($erpAdmin), rp($erpNet), rp($erpRef),
                $row ? rp($apiGross) : '-',
                $row ? rp($apiAdmin) : '-',
                $row ? rp($apiNet)   : '-',
                $row ? rp($apiRef)   : '-',
                substr($order->order_status, 0, 9),
                $flag, $ket);
        }
        echo "  " . str_repeat("-", $LW) . "\n";
        if ($fixReturns && $fixedCount > 0) {
            echo "\n  [SYNC] Total order yang diperbarui: {$fixedCount} order.\n";
            $grand['fixed'] += $fixedCount;
        }
    }

    echo "\n";
    $grand['erp_orders']     += $erpCnt;  $grand['erp_gross']  += $erpG; $grand['erp_admin'] += $erpA; $grand['erp_net'] += $erpN; $grand['erp_refund'] += $erpRefund;
    $grand['erp_ret_orders'] += $erpRC;   $grand['erp_ret_gross'] += $erpRG;
    $grand['api_orders']     += $apiCnt;  $grand['api_gross']  += $apiG; $grand['api_admin'] += $apiA; $grand['api_net'] += $apiN; $grand['api_refund'] += $apiRefund;
    $grand['api_ret_orders'] += $apiRC;   $grand['api_ret_amount'] += $apiRA;
}

// Grand total
$GW = 100;
$gDO = $grand['erp_orders'] - $grand['api_orders'];
$gDG = $grand['erp_gross']  - $grand['api_gross'];
$gDA = $grand['erp_admin']  - $grand['api_admin'];
$gDN = $grand['erp_net']    - $grand['api_net'];
$gDR = $grand['erp_refund'] - $grand['api_refund'];

echo "\n";
echo str_repeat("=", $GW) . "\n";
echo "  GRAND TOTAL — SELURUH TOKO TERHUBUNG\n";
echo str_repeat("=", $GW) . "\n";
echo sprintf("  | %-28s | %-18s | %-18s | %-18s |\n",
    "METRIK", "ERP DATABASE", "API MARKETPLACE", "SELISIH (ERP-API)");
echo str_repeat("-", $GW) . "\n";
echo sprintf("  | %-28s | %-18s | %-18s | %-18s |\n",
    "Total Order Normal",
    $grand['erp_orders']." order", $grand['api_orders']." order", diff_tag($gDO));
echo sprintf("  | %-28s | %-18s | %-18s | %-18s |\n",
    "Total Omset Kotor",
    rp($grand['erp_gross']), rp($grand['api_gross']), diff_tag($gDG));
echo sprintf("  | %-28s | %-18s | %-18s | %-18s |\n",
    "Total Biaya Admin",
    rp($grand['erp_admin']), rp($grand['api_admin']), diff_tag($gDA));
echo sprintf("  | %-28s | %-18s | %-18s | %-18s |\n",
    "Total Omset Bersih (Net)",
    rp($grand['erp_net']), rp($grand['api_net']), diff_tag($gDN));
echo str_repeat("-", $GW) . "\n";
echo sprintf("  | %-28s | %-18s | %-18s | %-18s |\n",
    "Total Refund / Retur",
    rp($grand['erp_refund']), rp($grand['api_refund']), diff_tag($gDR));
echo sprintf("  | %-28s | %-18s | %-18s |\n",
    "  Jml Order Retur (ERP/API)",
    $grand['erp_ret_orders']." order",
    $grand['api_ret_orders']." order");
echo str_repeat("=", $GW) . "\n";
echo "\n";
echo "  CATATAN — CARA SINKRONISASI RETUR:\n";
echo "  1. API: order retur -> status RETURN/REFUNDED, net=0, return_amount=nilai refund.\n";
echo "     ERP: harus status RETURN agar net dihitung 0, DAN refund_amount terisi.\n";
echo "  2. Jika ERP belum diupdate -> net ERP masih dihitung -> selisih dengan API!\n";
echo "\n";
echo "  SOLUSI SINKRONISASI:\n";
echo "  a. php pull_missing_returns.php          -- pull & update status retur dari API ke ERP\n";
echo "  b. php detail_erp_vs_api_per_order.php --fix-returns  -- isi refund_amount di ERP dari API\n";
echo "  c. php detail_erp_vs_api_per_order.php --detail       -- lihat detail per baris order\n";
if ($grand['fixed'] > 0)
    echo "\n  [SYNC] Total order diperbarui sesi ini: " . $grand['fixed'] . " order.\n";
echo str_repeat("=", $GW) . "\n\n";
