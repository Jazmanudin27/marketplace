<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Models\Order;
use App\Jobs\PullOrdersFromShopee;
use App\Services\ShopeeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncMissingShopeeOrders extends Command
{
    /**
     * Signature command artisan
     * Contoh:
     *   php artisan shopee:sync-missing
     *   php artisan shopee:sync-missing --store=15 --days=90
     *   php artisan shopee:sync-missing --from=2026-06-01 --to=2026-07-31
     */
    protected $signature = 'shopee:sync-missing 
                            {--store= : ID Toko Shopee (Opsional)}
                            {--days=90 : Jumlah hari ke belakang (maksimal 90)}
                            {--from= : Tanggal awal YYYY-MM-DD}
                            {--to= : Tanggal akhir YYYY-MM-DD}';

    protected $description = 'Menarik semua pesanan Shopee yang belum ada atau tertinggal di database ERP';

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
            $this->error('Format tanggal tidak valid.');
            return 1;
        }

        $totalDays = (int)(($endTs - $startTs) / 86400) + 1;

        $this->info("======================================================================");
        $this->info("  SINKRONISASI PESANAN SHOPEE YANG BELUM MASUK ERP");
        $this->info("======================================================================");
        $this->info("  Periode : " . date('d-m-Y', $startTs) . " s/d " . date('d-m-Y', $endTs) . " ({$totalDays} hari)");
        $this->info("  Toko    : " . ($storeId ? "Store ID #{$storeId}" : "Semua toko Shopee aktif"));
        $this->info("======================================================================\n");

        $storeQuery = Store::whereHas('channel', fn($q) => $q->where('code', 'shopee'))
            ->where('status', '!=', 'disconnected')
            ->whereNotNull('access_token');

        if ($storeId) $storeQuery->where('id', $storeId);
        $stores = $storeQuery->get();

        if ($stores->isEmpty()) {
            $this->error("Tidak ada toko Shopee aktif.");
            return 1;
        }

        $shopeeService = app(ShopeeService::class);

        $grandNew    = 0;
        $grandExists = 0;
        $grandError  = 0;

        // Shopee API membatasi maksimal 15 hari per request query
        $stepSeconds = 15 * 86400;

        foreach ($stores as $store) {
            $this->info("--------------------------------------------------------------------");
            $this->info("TOKO: {$store->store_name} (ID: {$store->id})");
            $this->info("--------------------------------------------------------------------");

            try {
                $accessToken = $store->getValidAccessToken();
                $shopId      = (int) $store->marketplace_store_id;

                if (!$shopId) {
                    $this->warn("  SKIP: marketplace_store_id kosong.");
                    continue;
                }

                // Reflection access to saveOrder in PullOrdersFromShopee job
                $jobInstance   = new PullOrdersFromShopee($store, $startTs, $endTs);
                $reflection    = new \ReflectionClass($jobInstance);
                $saveMethod    = $reflection->getMethod('saveOrder');
                $saveMethod->setAccessible(true);

                $storeNew    = 0;
                $storeExists = 0;
                $storeError  = 0;

                $chunkStart = $startTs;

                while ($chunkStart <= $endTs) {
                    $chunkEnd  = min($chunkStart + $stepSeconds - 1, $endTs);
                    $labelFrom = date('Y-m-d', $chunkStart);
                    $labelTo   = date('Y-m-d', $chunkEnd);

                    $this->output->write("  [{$labelFrom} s/d {$labelTo}] Fetch API... ");

                    $allOrderSn = [];

                    // Single scan by create_time
                    $cursor  = '';
                    $hasMore = true;
                    $pageCount = 0;

                    while ($hasMore) {
                        try {
                            $resp = $shopeeService->getOrderList($accessToken, $shopId, $chunkStart, $chunkEnd, 'create_time', $cursor);
                        } catch (\Exception $e) {
                            $this->error("API Error: " . $e->getMessage());
                            $storeError++;
                            break;
                        }

                        $orderList = $resp['order_list'] ?? [];
                        if (empty($orderList)) break;

                        foreach ($orderList as $o) {
                            if (!empty($o['order_sn'])) {
                                $allOrderSn[] = $o['order_sn'];
                            }
                        }

                        $hasMore = $resp['more'] ?? false;
                        $cursor  = $resp['next_cursor'] ?? '';
                        if (++$pageCount > 50) break;
                    }

                    $allOrderSn = array_unique($allOrderSn);

                    if (empty($allOrderSn)) {
                        $this->line("<comment>0 order</comment>");
                        $chunkStart = $chunkEnd + 1;
                        continue;
                    }

                    // Cek mana yang sudah ada di DB dengan status final
                    $existingIds = Order::where('store_id', $store->id)
                        ->whereIn('order_marketplace_id', $allOrderSn)
                        ->whereIn('order_status', ['COMPLETED', 'CANCELLED', 'SELESAI', 'FINISHED', 'BATAL'])
                        ->pluck('order_marketplace_id')
                        ->toArray();

                    $missingIds = array_diff($allOrderSn, $existingIds);
                    $totalChunk = count($allOrderSn);
                    $missingCnt = count($missingIds);

                    $storeExists += count($existingIds);

                    if ($missingCnt === 0) {
                        $this->line("<info>Shopee={$totalChunk}, ERP=" . count($existingIds) . " (Semua sudah ada)</info>");
                        $chunkStart = $chunkEnd + 1;
                        continue;
                    }

                    $this->line("<comment>Shopee={$totalChunk}, ERP=" . count($existingIds) . ", PERLU PULL={$missingCnt}</comment>");

                    $missingArr = array_values($missingIds);

                    // Fetch detail in chunks of 50
                    $chunks = array_chunk($missingArr, 50);

                    foreach ($chunks as $chunk) {
                        try {
                            $detailResp = $shopeeService->getOrderDetail($accessToken, $shopId, $chunk);
                            $detailList = $detailResp['order_list'] ?? [];

                            foreach ($detailList as $shopeeOrder) {
                                $orderSn = $shopeeOrder['order_sn'] ?? null;
                                if (!$orderSn) continue;

                                try {
                                    retry(4, function() use ($saveMethod, $jobInstance, $shopeeOrder) {
                                        DB::transaction(function() use ($saveMethod, $jobInstance, $shopeeOrder) {
                                            $saveMethod->invoke($jobInstance, $shopeeOrder);
                                        });
                                    }, 150);

                                    $storeNew++;
                                    $this->line("    <info>[+] Saved & Committed: {$orderSn}</info>");
                                } catch (\Exception $e) {
                                    $this->error("    [ERROR] {$orderSn}: " . $e->getMessage());
                                    $storeError++;
                                }
                            }
                        } catch (\Exception $e) {
                            $this->error("    [DETAIL API ERROR]: " . $e->getMessage());
                            $storeError += count($chunk);
                        }
                    }

                    $chunkStart = $chunkEnd + 1;
                }

                $this->info("  Hasil [{$store->store_name}]: Sudah Ada={$storeExists} | Baru/Update={$storeNew} | Error={$storeError}\n");

                $grandNew    += $storeNew;
                $grandExists += $storeExists;
                $grandError  += $storeError;

            } catch (\Exception $e) {
                $this->error("  ERROR toko: " . $e->getMessage());
                $grandError++;
            }
        }

        $this->info("======================================================================");
        $this->info("  RINGKASAN SINKRONISASI SHOPEE AKHIR");
        $this->info("======================================================================");
        $this->info("  Sudah Ada di ERP          : {$grandExists} order");
        $this->info("  Berhasil Ditambahkan/Update: {$grandNew} order");
        $this->info("  Gagal / Error             : {$grandError} order");
        $this->info("======================================================================\n");

        return 0;
    }
}
