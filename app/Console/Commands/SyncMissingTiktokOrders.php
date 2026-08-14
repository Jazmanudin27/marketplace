<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\Order;
use App\Jobs\PullOrdersFromTiktok;
use App\Services\TiktokService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMissingTiktokOrders extends Command
{
    /**
     * Signature command artisan
     * Contoh:
     *   php artisan tiktok:sync-missing
     *   php artisan tiktok:sync-missing --store=30 --days=90
     *   php artisan tiktok:sync-missing --from=2026-06-15 --to=2026-07-14
     */
    protected $signature = 'tiktok:sync-missing 
                            {--store= : ID Toko TikTok (Opsional, jika kosong semua toko)}
                            {--days=90 : Jumlah hari ke belakang (maksimal 90)}
                            {--from= : Tanggal awal YYYY-MM-DD}
                            {--to= : Tanggal akhir YYYY-MM-DD}';

    protected $description = 'Menarik semua pesanan TikTok yang belum ada di database ERP';

    public function handle()
    {
        $storeId  = $this->option('store') ? (int) $this->option('store') : null;
        $days     = max(1, min(90, (int) ($this->option('days') ?? 90)));
        $fromDate = $this->option('from');
        $toDate   = $this->option('to');

        if ($fromDate && $toDate) {
            $startTs = strtotime($fromDate . ' 00:00:00');
            $endTs   = strtotime($toDate   . ' 23:59:59');
        } else {
            $startTs = strtotime("-{$days} days 00:00:00");
            $endTs   = strtotime('today 23:59:59');
        }

        if (!$startTs || !$endTs || $startTs > $endTs) {
            $this->error('Format tanggal dari/sampai tidak valid.');
            return 1;
        }

        $totalDays = (int)(($endTs - $startTs) / 86400) + 1;

        $this->info("======================================================================");
        $this->info("  SINKRONISASI PESANAN TIKTOK YANG BELUM MASUK ERP");
        $this->info("======================================================================");
        $this->info("  Periode : " . date('d-m-Y', $startTs) . " s/d " . date('d-m-Y', $endTs) . " ({$totalDays} hari)");
        $this->info("  Toko    : " . ($storeId ? "Store ID #{$storeId}" : "Semua toko TikTok aktif"));
        $this->info("======================================================================\n");

        $storeQuery = Store::whereHas('channel', fn($q) => $q->where('code', 'tiktok'))
            ->where('status', '!=', 'disconnected')
            ->whereNotNull('access_token');

        if ($storeId) $storeQuery->where('id', $storeId);
        $stores = $storeQuery->get();

        if ($stores->isEmpty()) {
            $this->error("Tidak ada toko TikTok aktif yang sesuai.");
            return 1;
        }

        $tiktokService = app(TiktokService::class);

        $grandNew    = 0;
        $grandExists = 0;
        $grandError  = 0;

        $stepSeconds = 30 * 86400; // 30 hari per batch API

        foreach ($stores as $store) {
            $this->info("--------------------------------------------------------------------");
            $this->info("TOKO: {$store->store_name} (ID: {$store->id})");
            $this->info("--------------------------------------------------------------------");

            try {
                $accessToken = $store->getValidAccessToken();
                $shopCipher  = $store->shop_cipher;

                if (empty($shopCipher)) {
                    $this->warn("  SKIP: shop_cipher kosong.");
                    continue;
                }

                $jobInstance   = new PullOrdersFromTiktok($store, $startTs, $endTs);
                $reflection    = new \ReflectionClass($jobInstance);
                $processMethod = $reflection->getMethod('processOrder');
                $processMethod->setAccessible(true);

                $storeNew    = 0;
                $storeExists = 0;
                $storeError  = 0;

                $chunkStart = $startTs;

                while ($chunkStart <= $endTs) {
                    $chunkEnd  = min($chunkStart + $stepSeconds - 1, $endTs);
                    $labelFrom = date('Y-m-d', $chunkStart);
                    $labelTo   = date('Y-m-d', $chunkEnd);

                    $this->output->write("  [{$labelFrom} s/d {$labelTo}] Fetch API... ");

                    $tiktokOrderMap = [];
                    $cursor     = '';
                    $pageCount  = 0;

                    do {
                        try {
                            $resp = $tiktokService->getOrderList($accessToken, $shopCipher, $chunkStart, $chunkEnd, $cursor);
                        } catch (\Exception $e) {
                            $this->error("API Error: " . $e->getMessage());
                            $storeError++;
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

                    if (empty($tiktokOrderMap)) {
                        $this->line("<comment>0 order</comment>");
                        $chunkStart = $chunkEnd + 1;
                        continue;
                    }

                    $tiktokIds = array_keys($tiktokOrderMap);

                    $existingIds = Order::where('store_id', $store->id)
                        ->whereIn('order_marketplace_id', $tiktokIds)
                        ->pluck('order_marketplace_id')
                        ->toArray();

                    $missingIds = array_diff($tiktokIds, $existingIds);
                    $totalChunk = count($tiktokIds);
                    $missingCnt = count($missingIds);

                    $storeExists += count($existingIds);

                    if ($missingCnt === 0) {
                        $this->line("<info>TikTok={$totalChunk}, ERP=" . count($existingIds) . " (Semua sudah ada)</info>");
                        $chunkStart = $chunkEnd + 1;
                        continue;
                    }

                    $this->line("<comment>TikTok={$totalChunk}, ERP=" . count($existingIds) . ", BELUM ADA={$missingCnt}</comment>");

                    $missingArr = array_values($missingIds);
                    $detailMap  = [];

                    if ($chunkEnd >= strtotime('-30 days')) {
                        $chunks = array_chunk($missingArr, 50);
                        foreach ($chunks as $chunk) {
                            try {
                                $detailResp = $tiktokService->getOrderDetail($accessToken, $shopCipher, $chunk);
                                $detailList = $detailResp['order_list'] ?? [];
                                foreach ($detailList as $d) {
                                    $did = (string)($d['order_id'] ?? $d['id'] ?? null);
                                    if ($did) $detailMap[$did] = $d;
                                }
                            } catch (\Exception $e) {}
                        }
                    }

                    // DB Transaction + Realtime Output
                    DB::beginTransaction();
                    $batchSuccessCount = 0;

                    try {
                        foreach ($missingArr as $mid) {
                            $orderData = $detailMap[$mid] ?? $tiktokOrderMap[$mid] ?? null;
                            if (!$orderData) continue;

                            try {
                                $processMethod->invoke($jobInstance, $orderData);
                                $batchSuccessCount++;
                                $this->line("    <info>[+] Saved: {$mid}</info>");
                            } catch (\Exception $e) {
                                $this->error("    [ERROR] {$mid}: " . $e->getMessage());
                                $storeError++;
                            }
                        }

                        DB::commit();
                        $storeNew += $batchSuccessCount;
                        $this->info("    -> Batch Selesai: {$batchSuccessCount} order tersimpan.");

                    } catch (\Exception $eTx) {
                        DB::rollBack();
                        $this->error("    [TRANSACTION ERROR] Gagal simpan batch: " . $eTx->getMessage());
                        $storeError += count($missingArr);
                    }

                    $chunkStart = $chunkEnd + 1;
                }

                $this->info("  Hasil [{$store->store_name}]: Sudah Ada={$storeExists} | Baru Ditambah={$storeNew} | Error={$storeError}\n");

                $grandNew    += $storeNew;
                $grandExists += $storeExists;
                $grandError  += $storeError;

            } catch (\Exception $e) {
                $this->error("  ERROR toko: " . $e->getMessage());
                $grandError++;
            }
        }

        $this->info("======================================================================");
        $this->info("  RINGKASAN SINKRONISASI AKHIR");
        $this->info("======================================================================");
        $this->info("  Sudah Ada di ERP          : {$grandExists} order");
        $this->info("  Berhasil Ditambahkan      : {$grandNew} order");
        $this->info("  Gagal / Error             : {$grandError} order");
        $this->info("======================================================================\n");

        return 0;
    }
}
