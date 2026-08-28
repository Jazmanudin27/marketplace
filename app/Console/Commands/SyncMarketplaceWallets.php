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
                    
                    // Kumpulkan semua transaksi mentah dari semua chunk
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
                            $allRawTxs[] = $tx;
                        }
                        
                        $currentStart = $currentEnd + 1;
                    }
                    
                    // Urutkan dari terlama ke terbaru untuk menghitung running balance secara berurutan
                    usort($allRawTxs, function($a, $b) {
                        $ta = $a['statement_time'] ?? $a['payment_time'] ?? 0;
                        $tb = $b['statement_time'] ?? $b['payment_time'] ?? 0;
                        return $ta <=> $tb;
                    });
                    
                    // Tentukan saldo awal
                    $runningSum = 0.0;
                    if (!empty($allRawTxs)) {
                        $firstTx   = reset($allRawTxs);
                        $firstTime = $firstTx['statement_time'] ?? $firstTx['payment_time'] ?? time();
                        $firstDate = date('Y-m-d H:i:s', is_numeric($firstTime) ? (strlen((string)$firstTime) > 10 ? (int)($firstTime / 1000) : $firstTime) : strtotime($firstTime));
                        $prevBal   = MarketplaceWalletTransaction::where('store_id', $store->id)
                            ->where('transaction_date', '<', $firstDate)
                            ->orderBy('transaction_date', 'desc')
                            ->value('current_balance');
                        $runningSum = (float) ($prevBal ?? 0.0);
                    }
                    
                    foreach ($allRawTxs as $tx) {
                        $txId   = $tx['id'] ?? $tx['payment_id'] ?? null;
                        $status = $tx['payment_status'] ?? $tx['status'] ?? '—';
                        
                        // Gunakan statement_time sebagai tanggal utama (konsisten dengan Seller Center)
                        $timeRaw = $tx['statement_time'] ?? $tx['payment_time'] ?? time();
                        if (!is_numeric($timeRaw)) {
                            $timeRaw = strtotime($timeRaw);
                        } elseif (strlen((string)$timeRaw) > 10) {
                            $timeRaw = (int)($timeRaw / 1000);
                        }
                        $txDate = date('Y-m-d H:i:s', $timeRaw);
                        
                        // settlement_amount = net earnings yang diterima (= "Earnings" di Excel/Seller Center)
                        // Ini SELALU positif/masuk (IN) — dana bersih setelah fee & adj yang diterima di akun TikTok
                        $settlement = (float) ($tx['settlement_amount'] ?? 0);
                        $revenue    = (float) ($tx['revenue_amount'] ?? 0);
                        $fee        = (float) ($tx['fee_amount'] ?? 0);
                        $adj        = (float) ($tx['adjustment_amount'] ?? 0);
                        
                        // Hapus entri lama dengan format berbeda untuk statement_id ini
                        MarketplaceWalletTransaction::where('store_id', $store->id)
                            ->where(function($q) use ($txId) {
                                $q->where('transaction_id', $txId)
                                  ->orWhere('transaction_id', $txId . '-REV')
                                  ->orWhere('transaction_id', $txId . '-FEE')
                                  ->orWhere('transaction_id', $txId . '-ADJ')
                                  ->orWhere('transaction_id', $txId . '-OUT');
                            })
                            ->delete();
                        
                        // Bangun deskripsi rinci
                        $desc = 'Earnings (Dana Bersih Settlement) | Status: ' . $status;
                        if ($revenue != 0)  $desc .= ' | Revenue: Rp ' . number_format(abs($revenue), 0, ',', '.');
                        if ($fee != 0)      $desc .= ' | Fee: Rp ' . number_format(abs($fee), 0, ',', '.');
                        if ($adj != 0)      $desc .= ' | Adj: Rp ' . number_format(abs($adj), 0, ',', '.');
                        
                        if ($settlement != 0) {
                            // settlement_amount = Earnings = masuk (IN), selalu positif
                            $runningSum += abs($settlement);
                            
                            MarketplaceWalletTransaction::updateOrCreate([
                                'store_id'       => $store->id,
                                'transaction_id' => $txId,
                            ], [
                                'tenant_id'        => $store->tenant_id,
                                'transaction_date' => $txDate,
                                'type'             => 'Earnings',
                                'description'      => $desc,
                                'amount'           => abs($settlement),
                                'direction'        => 'in',
                                'current_balance'  => $runningSum,
                                'raw_data'         => $tx,
                            ]);
                        }
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
