<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\MarketplaceWalletTransaction;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMarketplaceWallets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:sync-wallets {--store_id=} {--days=90}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync Shopee and TikTok Shop wallet transactions to ERP database';

    protected ShopeeService $shopeeService;
    protected TiktokService $tiktokService;

    public function __construct(ShopeeService $shopeeService, TiktokService $tiktokService)
    {
        parent::__construct();
        $this->shopeeService = $shopeeService;
        $this->tiktokService = $tiktokService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storeId = $this->option('store_id');
        $days = (int) $this->option('days');

        $query = Store::query()->whereHas('channel', function ($q) {
            $q->whereIn('code', ['shopee', 'tiktok']);
        });

        if ($storeId) {
            $query->where('id', $storeId);
        }

        $stores = $query->get();

        $this->info("Starting sync for " . $stores->count() . " stores...");

        foreach ($stores as $store) {
            $this->info("Syncing store: {$store->store_name} ({$store->channel->code})");
            try {
                $accessToken = $store->getValidAccessToken();
                
                $dateFrom = now()->subDays($days)->startOfDay();
                $dateTo = now()->endOfDay();
                
                $startTimestamp = $dateFrom->timestamp;
                $endTimestamp = $dateTo->timestamp;

                if ($store->channel->code === 'shopee') {
                    $shopId = (int) $store->marketplace_store_id;
                    
                    // Shopee membatasi 15 hari rentang query per request
                    $chunkSize = 15 * 24 * 3600;
                    $currentStart = $startTimestamp;
                    
                    while ($currentStart < $endTimestamp) {
                        $currentEnd = min($currentStart + $chunkSize - 1, $endTimestamp);
                        
                        $res = $this->shopeeService->getWalletTransactionList(
                            $accessToken,
                            $shopId,
                            1,
                            100,
                            $currentStart,
                            $currentEnd
                        );
                        
                        $rawList = $res['transaction_list'] ?? [];
                        foreach ($rawList as $tx) {
                            if (empty($tx['transaction_id'])) continue;
                            
                            $amount = (float) ($tx['amount'] ?? 0);
                            MarketplaceWalletTransaction::updateOrCreate([
                                'store_id'       => $store->id,
                                'transaction_id' => $tx['transaction_id'],
                            ], [
                                'tenant_id'        => $store->tenant_id,
                                'transaction_date' => date('Y-m-d H:i:s', $tx['create_time']),
                                'type'             => $this->mapShopeeTxType($tx['wallet_type'] ?? ''),
                                'description'      => $tx['description'] ?? '—',
                                'amount'           => abs($amount),
                                'direction'        => $amount >= 0 ? 'in' : 'out',
                                'current_balance'  => (float) ($tx['current_balance'] ?? 0),
                                'raw_data'         => $tx,
                            ]);
                        }
                        
                        $currentStart = $currentEnd + 1;
                    }
                } elseif ($store->channel->code === 'tiktok') {
                    $shopCipher = $store->shop_cipher ?? '';
                    
                    // TikTok membatasi 30 hari rentang query per request
                    $chunkSize = 30 * 24 * 3600;
                    $currentStart = $startTimestamp;
                    
                    // Kumpulkan semua transaksi mentah dari semua chunk terlebih dahulu
                    $allRawTxs = [];
                    
                    while ($currentStart < $endTimestamp) {
                        $currentEnd = min($currentStart + $chunkSize - 1, $endTimestamp);
                        
                        $res = $this->tiktokService->getFinanceTransactions(
                            $accessToken,
                            $shopCipher,
                            $currentStart,
                            $currentEnd
                        );
                        
                        $rawList = $res['statement_list'] ?? $res['statements'] ?? [];
                        foreach ($rawList as $tx) {
                            $txId = $tx['id'] ?? $tx['payment_id'] ?? null;
                            if (!$txId) continue;
                            
                            // Hapus entri format lama tanpa akhiran jika ada
                            MarketplaceWalletTransaction::where('store_id', $store->id)
                                ->where('transaction_id', $txId)
                                ->delete();
                                
                            $allRawTxs[] = $tx;
                        }
                        
                        $currentStart = $currentEnd + 1;
                    }
                    
                    // Bangun list transaksi split (Revenue, Fee, Adjustment)
                    $splitTxs = [];
                    foreach ($allRawTxs as $tx) {
                        $txId = $tx['id'] ?? $tx['payment_id'] ?? null;
                        $status = $tx['payment_status'] ?? $tx['status'] ?? '—';
                        
                        // Gunakan statement_time (tanggal settlement) sebagai acuan utama tanggal mutasi
                        // payment_time = tanggal transfer ke bank (bisa berbeda 1-2 hari dari statement_time)
                        $timeRaw = $tx['statement_time'] ?? $tx['payment_time'] ?? time();
                        if (is_numeric($timeRaw)) {
                            if (strlen((string)$timeRaw) > 10) {
                                $timeRaw = (int)($timeRaw / 1000);
                            }
                        } else {
                            $timeRaw = strtotime($timeRaw);
                        }
                        // Beri offset detik agar 4 baris split tampil berurutan di tabel mutasi:
                        // REV (+0s), FEE (+1s), ADJ (+2s), OUT/Penarikan (+3s)
                        $baseDateRev = date('Y-m-d H:i:s', $timeRaw + 0);
                        $baseDateFee = date('Y-m-d H:i:s', $timeRaw + 1);
                        $baseDateAdj = date('Y-m-d H:i:s', $timeRaw + 2);
                        $baseDateOut = date('Y-m-d H:i:s', $timeRaw + 3);

                        // 1. Revenue (uang masuk dari penjualan ke dompet TikTok)
                        $revenue = (float) ($tx['revenue_amount'] ?? 0);
                        if ($revenue != 0) {
                            $splitTxs[] = [
                                'transaction_id'   => $txId . '-REV',
                                'transaction_date' => $baseDateRev,
                                'type'             => 'Pelepasan Dana',
                                'description'      => 'Pelepasan Dana Penjualan Kotor | Status: ' . $status,
                                'amount'           => abs($revenue),
                                'direction'        => $revenue >= 0 ? 'in' : 'out',
                                'raw_data'         => $tx,
                            ];
                        }

                        // 2. Fee (potongan biaya layanan TikTok)
                        $fee = (float) ($tx['fee_amount'] ?? 0);
                        if ($fee != 0) {
                            $splitTxs[] = [
                                'transaction_id'   => $txId . '-FEE',
                                'transaction_date' => $baseDateFee,
                                'type'             => 'Biaya Layanan',
                                'description'      => 'Biaya Admin / Komisi TikTok Shop',
                                'amount'           => abs($fee),
                                'direction'        => $fee < 0 ? 'out' : 'in',
                                'raw_data'         => $tx,
                            ];
                        }

                        // 3. Adjustment (penyesuaian)
                        $adj = (float) ($tx['adjustment_amount'] ?? 0);
                        if ($adj != 0) {
                            $splitTxs[] = [
                                'transaction_id'   => $txId . '-ADJ',
                                'transaction_date' => $baseDateAdj,
                                'type'             => 'Penyesuaian',
                                'description'      => 'Penyesuaian Saldo oleh TikTok Shop',
                                'amount'           => abs($adj),
                                'direction'        => $adj >= 0 ? 'in' : 'out',
                                'raw_data'         => $tx,
                            ];
                        }

                        // 4. Penarikan Dana = settlement_amount (net yang ditransfer ke rekening bank)
                        $settlement = (float) ($tx['settlement_amount'] ?? 0);
                        if ($settlement != 0) {
                            $splitTxs[] = [
                                'transaction_id'   => $txId . '-OUT',
                                'transaction_date' => $baseDateOut,
                                'type'             => 'Penarikan Dana',
                                'description'      => 'Transfer Dana Bersih ke Rekening Bank | Status: ' . ($tx['payment_status'] ?? $status),
                                'amount'           => abs($settlement),
                                'direction'        => 'out',
                                'raw_data'         => $tx,
                            ];
                        }
                    }
                    
                    // Urutkan transaksi split berdasarkan tanggal ASCENDING agar saldo berjalan dihitung berurutan
                    usort($splitTxs, function($a, $b) {
                        return strcmp($a['transaction_date'], $b['transaction_date']);
                    });
                    
                    // Tentukan saldo awal sebelum transaksi tertua yang ditarik ini
                    $runningSum = 0.0;
                    if (!empty($splitTxs)) {
                        $oldestDate = $splitTxs[0]['transaction_date'];
                        $runningSum = (float) MarketplaceWalletTransaction::where('store_id', $store->id)
                            ->where('transaction_date', '<', $oldestDate)
                            ->orderBy('transaction_date', 'desc')
                            ->value('current_balance') ?? 0.0;
                    }
                    
                    // Simpan transaksi dan update running balance di database
                    foreach ($splitTxs as $txData) {
                        if ($txData['direction'] === 'in') {
                            $runningSum += $txData['amount'];
                        } else {
                            $runningSum -= $txData['amount'];
                        }
                        
                        MarketplaceWalletTransaction::updateOrCreate([
                            'store_id'       => $store->id,
                            'transaction_id' => $txData['transaction_id'],
                        ], [
                            'tenant_id'        => $store->tenant_id,
                            'transaction_date' => $txData['transaction_date'],
                            'type'             => $txData['type'],
                            'description'      => $txData['description'],
                            'amount'           => $txData['amount'],
                            'direction'        => $txData['direction'],
                            'current_balance'  => $runningSum,
                            'raw_data'         => $txData['raw_data'],
                        ]);
                    }
                }
                
                $this->info("✓ Store {$store->store_name} synced successfully.");
            } catch (\Throwable $e) {
                Log::error("Artisan Wallet Sync Error for store {$store->store_name}", [
                    'message' => $e->getMessage()
                ]);
                $this->error("✗ Store {$store->store_name} failed: " . $e->getMessage());
            }
        }

        $this->info("Wallet sync completed.");
    }

    private function mapShopeeTxType(string $type): string
    {
        return match ($type) {
            'WITHDRAW'      => 'Penarikan Dana',
            'RECONCILED'    => 'Pelepasan Dana Pesanan',
            'ADJUSTMENT'    => 'Penyesuaian Saldo',
            'REFUND'        => 'Pengembalian Dana',
            'SELLER_PAY'    => 'Pembayaran Penjual',
            default         => $type,
        };
    }
}
