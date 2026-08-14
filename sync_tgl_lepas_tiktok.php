<?php

/**
 * ============================================================
 * SYNC TGL LEPAS (completed_at) DARI TIKTOK STATEMENT API -> ERP
 * ============================================================
 * Mengambil tanggal lepas/cair per-order dari:
 * GET /finance/202309/orders/{order_id}/statement_transactions
 *
 * Cara pakai:
 *   php sync_tgl_lepas_tiktok.php                  -> semua toko, 30 hari
 *   php sync_tgl_lepas_tiktok.php --days=7         -> 7 hari terakhir
 *   php sync_tgl_lepas_tiktok.php --store=30       -> 1 toko saja
 *   php sync_tgl_lepas_tiktok.php --dry-run        -> preview saja
 *   php sync_tgl_lepas_tiktok.php --debug          -> lihat raw API
 *   php sync_tgl_lepas_tiktok.php --probe          -> cek 1 order dulu utk lihat struktur response
 * ============================================================
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Order;
use App\Models\Store;
use App\Services\TiktokService;
use Illuminate\Support\Facades\Log;

// ── Parse argumen ──────────────────────────────────────────────
$args     = array_slice($argv, 1);
$isDryRun = in_array('--dry-run', $args);
$isDebug  = in_array('--debug', $args);
$isProbe  = in_array('--probe', $args);  // cek 1 order saja untuk lihat struktur
$storeId  = null;
$days     = 30;

foreach ($args as $arg) {
    if (str_starts_with($arg, '--store=')) $storeId = (int) str_replace('--store=', '', $arg);
    if (str_starts_with($arg, '--days='))  $days    = max(1, min(90, (int) str_replace('--days=', '', $arg)));
}

// ── Fungsi: Ekstrak tanggal release dari statement transactions ─
function extractReleaseDate(array $stmtData, bool $debug = false): ?string
{
    // TikTok mengembalikan list transaksi, kita cari yang tipe RELEASE / ESCROW_RELEASE
    $transactions = $stmtData['statement_transactions']
        ?? $stmtData['statement_transaction_list']
        ?? $stmtData['transactions']
        ?? $stmtData['items']
        ?? [];

    if ($debug) {
        echo "    [DEBUG] statement keys: " . json_encode(array_keys($stmtData)) . "\n";
        echo "    [DEBUG] jumlah transaksi: " . count($transactions) . "\n";
        if (!empty($transactions)) {
            echo "    [DEBUG] sample tx keys: " . json_encode(array_keys($transactions[0])) . "\n";
            echo "    [DEBUG] sample tx: " . json_encode($transactions[0]) . "\n";
        }
    }

    if (empty($transactions)) {
        // Jika tidak ada transaksi, coba cari langsung di root response
        $ts = $stmtData['release_time']
            ?? $stmtData['payment_time']
            ?? $stmtData['escrow_release_time']
            ?? $stmtData['settlement_time']
            ?? null;

        if ($ts && is_numeric($ts)) {
            if (strlen((string)$ts) >= 13) $ts = (int)($ts / 1000);
            return date('Y-m-d H:i:s', (int)$ts);
        }
        return null;
    }

    // Cari transaksi tipe RELEASE (dana cair ke penjual)
    $releaseTypes = ['RELEASE', 'ESCROW_RELEASE', 'SELLER_RELEASE', 'PAYOUT', 'WITHDRAWAL', 'DISBURSE'];

    $latestTs = null;

    foreach ($transactions as $tx) {
        $txType = strtoupper($tx['transaction_type'] ?? $tx['type'] ?? $tx['statement_type'] ?? '');
        $isRelease = empty($txType) || in_array($txType, $releaseTypes);

        if (!$isRelease) continue;

        // Cari timestamp
        $ts = $tx['transaction_time']
            ?? $tx['create_time']
            ?? $tx['release_time']
            ?? $tx['payment_time']
            ?? $tx['settlement_time']
            ?? null;

        if (!$ts || !is_numeric($ts)) continue;

        if (strlen((string)$ts) >= 13) $ts = (int)($ts / 1000);

        // Ambil yang terbaru
        if ($latestTs === null || (int)$ts > $latestTs) {
            $latestTs = (int)$ts;
        }
    }

    // Jika tidak ada transaksi RELEASE, ambil timestamp transaksi apapun
    if ($latestTs === null) {
        foreach ($transactions as $tx) {
            $ts = $tx['transaction_time']
                ?? $tx['create_time']
                ?? $tx['release_time']
                ?? null;

            if (!$ts || !is_numeric($ts)) continue;
            if (strlen((string)$ts) >= 13) $ts = (int)($ts / 1000);
            if ($latestTs === null || (int)$ts > $latestTs) {
                $latestTs = (int)$ts;
            }
        }
    }

    return $latestTs ? date('Y-m-d H:i:s', $latestTs) : null;
}

// ── Banner ─────────────────────────────────────────────────────
echo "\n";
echo "======================================================================\n";
echo "  SYNC TGL LEPAS (completed_at) DARI TIKTOK STATEMENT API -> ERP\n";
echo "======================================================================\n";
echo "  Mode    : " . ($isDryRun ? "DRY-RUN" : ($isProbe ? "PROBE (cek 1 order)" : "LIVE")) . "\n";
echo "  Rentang : " . date('d-m-Y', strtotime("-{$days} days")) . " s/d " . date('d-m-Y') . " ({$days} hari)\n";
echo "  Toko    : " . ($storeId ? "Store ID #{$storeId}" : "Semua toko TikTok") . "\n";
echo "======================================================================\n\n";

// ── Ambil toko ────────────────────────────────────────────────
$storeQuery = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
    ->where('status', '!=', 'disconnected')
    ->whereNotNull('access_token');
if ($storeId) $storeQuery->where('id', $storeId);
$stores = $storeQuery->get();

if ($stores->isEmpty()) { echo "ERROR: Tidak ada toko TikTok aktif.\n"; exit(1); }
echo "Ditemukan " . $stores->count() . " toko TikTok aktif.\n\n";

$tiktokService = app(TiktokService::class);

$totalChecked = 0;
$totalFixed   = 0;
$totalSkip    = 0;
$totalError   = 0;
$apiWorks     = false;

foreach ($stores as $store) {
    echo "--------------------------------------------------------------------\n";
    echo "TOKO: {$store->store_name} (ID: {$store->id})\n";
    echo "--------------------------------------------------------------------\n";

    try {
        $accessToken = $store->getValidAccessToken();
        $shopCipher  = $store->shop_cipher;
        if (empty($shopCipher)) { echo "  SKIP: shop_cipher kosong.\n\n"; continue; }

        // Ambil order COMPLETED yang perlu dicek tgl lepasnya
        // Fokus: order yang completed_at-nya NULL atau mungkin salah
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $erpOrders = Order::where('store_id', $store->id)
            ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
            ->where(function ($q) use ($cutoffDate) {
                $q->whereNull('completed_at')                          // NULL -> pasti perlu fix
                  ->orWhere('completed_at', '>=', $cutoffDate);        // atau masih dalam range
            })
            ->whereNotNull('order_marketplace_id')
            ->orderByDesc('created_at')
            ->get(['id', 'order_marketplace_id', 'order_status', 'completed_at', 'created_at']);

        if ($erpOrders->isEmpty()) {
            echo "  Tidak ada order yang perlu dicek.\n\n";
            continue;
        }

        echo "  " . $erpOrders->count() . " order akan dicek tgl lepasnya dari Finance API...\n";
        if ($isProbe) echo "  (PROBE: hanya cek 1 order pertama)\n";

        $storeFixed = 0;

        foreach ($erpOrders as $erpOrder) {
            $mid = (string)$erpOrder->order_marketplace_id;
            $totalChecked++;

            try {
                // Panggil Statement Transactions API per-order
                $stmtData     = $tiktokService->getOrderStatementTransactions($accessToken, $shopCipher, $mid);
                $releaseDate  = extractReleaseDate($stmtData, $isDebug);
                $apiWorks     = true;

                if ($isProbe) {
                    echo "\n  [PROBE] Order ID: {$mid}\n";
                    echo "  [PROBE] Raw response:\n";
                    echo json_encode($stmtData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
                    echo "  [PROBE] Tanggal release yang diekstrak: " . ($releaseDate ?? 'NULL') . "\n";
                    echo "\n  Selesai probe. Gunakan tanpa --probe untuk proses semua order.\n\n";
                    break 2; // Hentikan semua loop
                }

                if (!$releaseDate) {
                    if ($isDebug) echo "  [{$mid}] Finance API tidak return tanggal release. Skip.\n";
                    $totalSkip++;
                    usleep(150000);
                    continue;
                }

                // Bandingkan dengan ERP
                $currentDate = $erpOrder->completed_at
                    ? (is_string($erpOrder->completed_at)
                        ? substr($erpOrder->completed_at, 0, 10)
                        : $erpOrder->completed_at->format('Y-m-d'))
                    : null;
                $correctDate = substr($releaseDate, 0, 10);

                if ($currentDate === $correctDate) {
                    $totalSkip++;
                    usleep(150000);
                    continue; // Sudah benar
                }

                echo "  [{$mid}] Tgl lepas: ERP=" . ($currentDate ?? 'NULL') . " -> API={$correctDate}";

                if ($isDryRun) {
                    echo " [DRY-RUN]\n";
                    $totalFixed++;
                    $storeFixed++;
                    usleep(150000);
                    continue;
                }

                Order::where('id', $erpOrder->id)->update(['completed_at' => $releaseDate]);
                echo " -> DIUPDATE\n";

                Log::info('[SyncTglLepas] completed_at dari Statement API', [
                    'order_id'             => $erpOrder->id,
                    'order_marketplace_id' => $mid,
                    'old_completed_at'     => $currentDate,
                    'new_completed_at'     => $releaseDate,
                ]);

                $totalFixed++;
                $storeFixed++;

            } catch (\Exception $e) {
                if ($isDebug) echo "  [{$mid}] Error Finance API: " . $e->getMessage() . "\n";
                $totalError++;
            }

            usleep(150000); // 150ms jeda antar order (rate limit)
        }

        echo "  Selesai toko ini: {$storeFixed} tgl lepas difix.\n\n";

    } catch (\Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n\n";
        $totalError++;
    }
}

// ── Ringkasan ──────────────────────────────────────────────────
echo "======================================================================\n";
echo "  RINGKASAN " . ($isDryRun ? "(DRY-RUN)" : "") . "\n";
echo "======================================================================\n";
echo "  Order dicek           : {$totalChecked}\n";
echo "  Tgl lepas difix       : {$totalFixed}\n";
echo "  Sudah benar (skip)    : {$totalSkip}\n";
echo "  Error Finance API     : {$totalError}\n";
echo "======================================================================\n";

if (!$apiWorks && !$isProbe) {
    echo "\nINFO: Finance API tidak mengembalikan data sama sekali.\n";
    echo "Coba jalankan --probe untuk lihat raw response:\n";
    $cmd = "php sync_tgl_lepas_tiktok.php --probe";
    if ($storeId) $cmd .= " --store={$storeId}";
    echo "  {$cmd}\n";
    echo "\nAlternatif: gunakan script manual:\n";
    echo "  php fix_tgl_lepas_tiktok.php\n";
}

if ($isDryRun && $totalFixed > 0) {
    echo "\nJalankan tanpa --dry-run untuk menyimpan:\n";
    $cmd = "php sync_tgl_lepas_tiktok.php --days={$days}";
    if ($storeId) $cmd .= " --store={$storeId}";
    echo "  {$cmd}\n";
}

echo "\nSelesai!\n\n";
