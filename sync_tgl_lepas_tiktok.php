<?php

/**
 * ============================================================
 * SYNC TGL LEPAS OTOMATIS DARI TIKTOK FINANCE API -> ERP
 * ============================================================
 * Script ini mengambil tanggal "Waktu pembayaran pesanan"
 * langsung dari TikTok Finance API (bukan manual dari Seller
 * Center), lalu update completed_at di database ERP.
 *
 * Cara pakai:
 *   php sync_tgl_lepas_tiktok.php                 -> 7 hari terakhir
 *   php sync_tgl_lepas_tiktok.php --days=30       -> 30 hari terakhir
 *   php sync_tgl_lepas_tiktok.php --store=30      -> hanya 1 toko
 *   php sync_tgl_lepas_tiktok.php --dry-run       -> preview saja
 *   php sync_tgl_lepas_tiktok.php --debug         -> tampilkan raw API
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;
use Illuminate\Support\Facades\Log;

// ── Parse argumen CLI ─────────────────────────────────────────
$args     = array_slice($argv, 1);
$isDryRun = in_array('--dry-run', $args);
$isDebug  = in_array('--debug', $args);
$storeId  = null;
$days     = 7;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--store=')) $storeId = (int) str_replace('--store=', '', $arg);
    if (str_starts_with($arg, '--days='))  $days    = max(1, min(90, (int) str_replace('--days=', '', $arg)));
}

$timeFrom = strtotime("-{$days} days");
$timeTo   = time();

// ── Banner ─────────────────────────────────────────────────────
echo "\n";
echo "======================================================================\n";
echo "  SYNC TGL LEPAS OTOMATIS DARI TIKTOK FINANCE API -> ERP\n";
echo "======================================================================\n";
echo "  Mode    : " . ($isDryRun ? "DRY-RUN (preview saja)" : "LIVE (disimpan ke DB)") . "\n";
echo "  Rentang : " . date('d-m-Y', $timeFrom) . " s/d " . date('d-m-Y', $timeTo) . " ({$days} hari)\n";
echo "  Toko    : " . ($storeId ? "Store ID #{$storeId}" : "Semua toko TikTok") . "\n";
echo "======================================================================\n\n";

// ── Ambil toko TikTok aktif ───────────────────────────────────
$storeQuery = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token');

if ($storeId) $storeQuery->where('id', $storeId);

$stores = $storeQuery->get();

if ($stores->isEmpty()) {
    echo "ERROR: Tidak ada toko TikTok aktif.\n";
    exit(1);
}

echo "Ditemukan " . $stores->count() . " toko TikTok aktif.\n\n";

$tiktokService = app(TiktokService::class);

$totalTx     = 0;
$totalFixed  = 0;
$totalSkip   = 0;
$totalError  = 0;
$apiWorked   = false; // track apakah Finance API berhasil

foreach ($stores as $store) {
    echo "--------------------------------------------------------------------\n";
    echo "TOKO: {$store->store_name} (ID: {$store->id})\n";
    echo "--------------------------------------------------------------------\n";

    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher  = $store->shop_cipher;

        if (empty($shopCipher)) {
            echo "  SKIP: shop_cipher kosong.\n\n";
            continue;
        }

        // ── STEP 1: Tarik semua transaksi dari Finance API ─────────────
        echo "  Mengambil data dari TikTok Finance API...\n";

        // Map: order_id => tanggal_lepas (payment_time)
        $settlementMap = [];
        $pageToken     = '';
        $pageCount     = 0;
        $prevToken     = null;

        do {
            try {
                $financeResp = $tiktokService->getFinanceTransactions(
                    $accessToken,
                    $shopCipher,
                    $timeFrom,
                    $timeTo,
                    $pageToken
                );
            } catch (\Exception $e) {
                echo "  PERINGATAN: Finance API error: " . $e->getMessage() . "\n";
                echo "  Finance API mungkin belum aktif untuk toko ini.\n";
                break;
            }

            if ($isDebug) {
                echo "  [DEBUG] Finance API response keys: " . json_encode(array_keys($financeResp)) . "\n";
                $sampleTx = array_slice($financeResp['transactions'] ?? $financeResp['payment_list'] ?? [], 0, 1);
                if ($sampleTx) {
                    echo "  [DEBUG] Sample transaction keys: " . json_encode(array_keys($sampleTx[0])) . "\n";
                    echo "  [DEBUG] Sample: " . json_encode($sampleTx[0]) . "\n";
                }
            }

            // TikTok Finance API bisa return transactions di berbagai key
            $transactions = $financeResp['transactions']
                ?? $financeResp['payment_list']
                ?? $financeResp['items']
                ?? [];

            if (empty($transactions) && $pageCount === 0) {
                echo "  INFO: Finance API tidak mengembalikan transaksi untuk periode ini.\n";
                break;
            }

            $apiWorked = true;

            foreach ($transactions as $tx) {
                // Cari order_id di transaksi
                $orderId = (string)(
                    $tx['order_id']
                    ?? $tx['related_order_id']
                    ?? $tx['order_sn']
                    ?? null
                );

                if (empty($orderId)) continue;

                // Ambil tanggal pembayaran (payment_time / release_time / create_time)
                $paymentTs = $tx['payment_time']
                    ?? $tx['release_time']
                    ?? $tx['paid_time']
                    ?? $tx['create_time']
                    ?? null;

                if (!$paymentTs || !is_numeric($paymentTs)) continue;

                // Konversi milliseconds jika perlu
                if (strlen((string)$paymentTs) >= 13) {
                    $paymentTs = (int)($paymentTs / 1000);
                }

                $settlementMap[$orderId] = date('Y-m-d H:i:s', (int)$paymentTs);
            }

            $prevToken  = $pageToken;
            $pageToken  = $financeResp['next_page_token'] ?? '';
            $hasMore    = $financeResp['more'] ?? false;

            if ($pageToken === $prevToken || ++$pageCount > 20) break;
            usleep(200000);

        } while ($hasMore && $pageToken);

        $txCount = count($settlementMap);
        $totalTx += $txCount;
        echo "  Finance API: {$txCount} transaksi ditemukan dengan tanggal lepas.\n";

        if ($txCount === 0) {
            echo "\n";
            continue;
        }

        // ── STEP 2: Update completed_at di ERP ────────────────────────
        $orderIds  = array_keys($settlementMap);
        $erpOrders = Order::where('store_id', $store->id)
            ->whereIn('order_marketplace_id', $orderIds)
            ->get(['id', 'order_marketplace_id', 'order_status', 'completed_at']);

        echo "  ERP: " . $erpOrders->count() . " order cocok.\n";

        $storeFixed = 0;

        foreach ($erpOrders as $erpOrder) {
            $mid               = (string)$erpOrder->order_marketplace_id;
            $correctSettlement = $settlementMap[$mid] ?? null;

            if (!$correctSettlement) {
                $totalSkip++;
                continue;
            }

            // Cek apakah completed_at sudah benar (bandingkan tanggal saja)
            $currentDate = $erpOrder->completed_at
                ? (is_string($erpOrder->completed_at)
                    ? substr($erpOrder->completed_at, 0, 10)
                    : $erpOrder->completed_at->format('Y-m-d'))
                : null;
            $correctDate = substr($correctSettlement, 0, 10);

            if ($currentDate === $correctDate) {
                $totalSkip++;
                continue; // Sudah benar
            }

            echo "  [{$mid}] Tgl lepas: ERP=" . ($currentDate ?? 'NULL') . " -> Finance API={$correctDate}";

            if ($isDryRun) {
                echo " [DRY-RUN]\n";
                $totalFixed++;
                $storeFixed++;
                continue;
            }

            $updateData = ['completed_at' => $correctSettlement];

            // Pastikan status juga COMPLETED jika belum
            if (!in_array($erpOrder->order_status, ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])) {
                $updateData['order_status'] = 'COMPLETED';
                echo " [+ fix status -> COMPLETED]";
            }

            Order::where('id', $erpOrder->id)->update($updateData);
            echo " -> DIUPDATE\n";

            Log::info('[SyncTglLepas] completed_at diupdate dari Finance API', [
                'order_id'             => $erpOrder->id,
                'order_marketplace_id' => $mid,
                'old_completed_at'     => $currentDate,
                'new_completed_at'     => $correctSettlement,
            ]);

            $totalFixed++;
            $storeFixed++;
        }

        echo "  Hasil toko ini: {$storeFixed} tgl lepas diupdate.\n\n";

    } catch (\Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n\n";
        $totalError++;
    }
}

// ── Ringkasan ──────────────────────────────────────────────────
echo "======================================================================\n";
echo "  RINGKASAN " . ($isDryRun ? "(DRY-RUN)" : "") . "\n";
echo "======================================================================\n";
echo "  Transaksi dari Finance API : {$totalTx}\n";
echo "  Tgl lepas diupdate         : {$totalFixed}\n";
echo "  Sudah benar (dilewati)     : {$totalSkip}\n";
echo "  Error                      : {$totalError}\n";
echo "======================================================================\n";

if (!$apiWorked) {
    echo "\n";
    echo "CATATAN: TikTok Finance API tidak mengembalikan data.\n";
    echo "Kemungkinan penyebab:\n";
    echo "  1. Endpoint Finance API memerlukan izin khusus di TikTok App\n";
    echo "  2. Coba jalankan dengan --debug untuk melihat response API\n";
    echo "  3. Gunakan script manual: php fix_tgl_lepas_tiktok.php\n";
    echo "     (paste data dari TikTok Seller Center ke script tersebut)\n";
}

if ($isDryRun && $totalFixed > 0) {
    echo "\nJalankan tanpa --dry-run untuk menyimpan:\n";
    $cmd = "php sync_tgl_lepas_tiktok.php --days={$days}";
    if ($storeId) $cmd .= " --store={$storeId}";
    echo "  {$cmd}\n";
}

echo "\nSelesai!\n\n";
