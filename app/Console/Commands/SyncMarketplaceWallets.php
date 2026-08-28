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
                    
                    // 1. Kumpulkan data statement untuk detail deskripsi
                    $statementsMap = [];
                    while ($currentStart < $endTimestamp) {
                        $currentEnd = min($currentStart + $chunkSize - 1, $endTimestamp);
                        
                        try {
                            $res = $this->tiktokService->getFinanceTransactions(
                                $accessToken,
                                $shopCipher,
                                $currentStart,
                                $currentEnd
                            );
                            
                            $rawList = $res['statement_list'] ?? $res['statements'] ?? [];
                            foreach ($rawList as $tx) {
                                $paymentId = $tx['payment_id'] ?? null;
                                if ($paymentId) {
                                    $statementsMap[$paymentId] = $tx;
                                }
                            }
                        } catch (\Throwable $e) {
                            Log::warning("Failed to fetch statements for TikTok store {$store->store_name} in range {$currentStart}-{$currentEnd}: " . $e->getMessage());
                        }
                        
                        $currentStart = $currentEnd + 1;
                    }
                    
                    // 2. Kumpulkan data penarikan & settlement (WITHDRAW, SETTLE, TRANSFER, REVERSE)
                    $allWithdrawals = [];
                    $pageToken = '';
                    $consecutiveExisting = 0;
                    $maxConsecutive = 5;
                    
                    do {
                        $res = $this->tiktokService->getWithdrawalTransactions(
                            $accessToken,
                            $shopCipher,
                            $startTimestamp,
                            $endTimestamp,
                            $pageToken
                        );
                        
                        $wList = $res['withdrawals'] ?? [];
                        if (empty($wList)) {
                            break;
                        }
                        
                        foreach ($wList as $w) {
                            $allWithdrawals[] = $w;
                            
                            // Cek apakah data penarikan ini sudah tersimpan di database
                            $exists = MarketplaceWalletTransaction::where('store_id', $store->id)
                                ->where('transaction_id', $w['id'])
                                ->exists();
                                
                            if ($exists) {
                                $consecutiveExisting++;
                            } else {
                                $consecutiveExisting = 0; // reset
                            }
                        }
                        
                        // Jika sudah ada 5 transaksi berurutan yang sudah tersimpan, stop tarik halaman sebelumnya
                        if ($consecutiveExisting >= $maxConsecutive) {
                            break;
                        }
                        
                        $pageToken = $res['next_page_token'] ?? '';
                    } while ($pageToken);
                    
                    // 3. Urutkan dari terlama ke terbaru agar saldo berjalan dihitung berurutan
                    usort($allWithdrawals, function($a, $b) {
                        return $a['create_time'] <=> $b['create_time'];
                    });
                    
                    // Tentukan saldo awal sebelum transaksi tertua yang ditarik ini
                    $runningSum = 0.0;
                    if (!empty($allWithdrawals)) {
                        $firstTx   = reset($allWithdrawals);
                        $firstTime = $firstTx['create_time'];
                        $firstDate = date('Y-m-d H:i:s', $firstTime);
                        $prevBal   = MarketplaceWalletTransaction::where('store_id', $store->id)
                            ->where('transaction_date', '<', $firstDate)
                            ->orderBy('transaction_date', 'desc')
                            ->orderBy('id', 'desc')
                            ->value('current_balance');
                        $runningSum = (float) ($prevBal ?? 0.0);
                    }
                    
                    // 4. Simpan transaksi ke database dan hitung saldo berjalan
                    foreach ($allWithdrawals as $w) {
                        $wId      = $w['id'];
                        $wType    = $w['type'] ?? '';
                        $wAmount  = abs((float) ($w['amount'] ?? 0));
                        $wStatus  = $w['status'] ?? '—';
                        $txTime   = (int) $w['create_time'];
                        $txDate   = date('Y-m-d H:i:s', $txTime);
                        
                        // Mapping tipe transaksi & arah
                        $type = $wType;
                        $direction = 'in';
                        $desc = '';
                        
                        if ($wType === 'SETTLE') {
                            $type = 'Earnings';
                            $direction = 'in';
                            $runningSum += $wAmount;
                            
                            // Coba perkaya deskripsi dengan detail statement
                            $desc = 'Earnings (Dana Bersih Settlement) | Status: ' . $wStatus;
                            if (isset($statementsMap[$wId])) {
                                $stmt    = $statementsMap[$wId];
                                $revenue = (float) ($stmt['revenue_amount'] ?? 0);
                                $fee     = (float) ($stmt['fee_amount'] ?? 0);
                                $adj     = (float) ($stmt['adjustment_amount'] ?? 0);
                                
                                if ($revenue != 0)  $desc .= ' | Revenue: Rp ' . number_format(abs($revenue), 0, ',', '.');
                                if ($fee != 0)      $desc .= ' | Fee: Rp ' . number_format(abs($fee), 0, ',', '.');
                                if ($adj != 0)      $desc .= ' | Adj: Rp ' . number_format(abs($adj), 0, ',', '.');
                            }
                        } elseif ($wType === 'WITHDRAW') {
                            $type = 'Penarikan Dana';
                            $direction = 'out';
                            $runningSum -= $wAmount;
                            $desc = 'Transfer Dana Bersih ke Rekening Bank | Status: ' . $wStatus;
                        } elseif ($wType === 'TRANSFER') {
                            $type = 'Transfer';
                            $direction = 'out';
                            $runningSum -= $wAmount;
                            $desc = 'Transfer Saldo TikTok Shop | Status: ' . $wStatus;
                        } elseif ($wType === 'REVERSE') {
                            $type = 'Penyesuaian';
                            $direction = 'in';
                            $runningSum += $wAmount;
                            $desc = 'Reversal (Penarikan Dana Gagal/Retur) | Status: ' . $wStatus;
                        } else {
                            $direction = 'in';
                            $runningSum += $wAmount;
                            $desc = 'Transaksi Lain-lain (' . $wType . ') | Status: ' . $wStatus;
                        }
                        
                        // Hapus entri lama dengan format split agar tidak duplikat
                        MarketplaceWalletTransaction::where('store_id', $store->id)
                            ->where(function($q) use ($wId) {
                                $q->where('transaction_id', $wId)
                                  ->orWhere('transaction_id', $wId . '-REV')
                                  ->orWhere('transaction_id', $wId . '-FEE')
                                  ->orWhere('transaction_id', $wId . '-ADJ')
                                  ->orWhere('transaction_id', $wId . '-OUT');
                            })
                            ->delete();
                            
                        MarketplaceWalletTransaction::updateOrCreate([
                            'store_id'       => $store->id,
                            'transaction_id' => $wId,
                        ], [
                            'tenant_id'        => $store->tenant_id,
                            'transaction_date' => $txDate,
                            'type'             => $type,
                            'description'      => $desc,
                            'amount'           => $wAmount,
                            'direction'        => $direction,
                            'current_balance'  => $runningSum,
                            'raw_data'         => $w,
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
