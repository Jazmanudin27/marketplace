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
    protected $signature = 'marketplace:sync-wallets {--store_id=} {--days=15}';

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
                            
                            $amount = (float) ($tx['settlement_amount'] ?? 0);
                            $status = $tx['payment_status'] ?? $tx['status'] ?? '—';
                            $txType = 'SETTLEMENT';
                            
                            // Handling timestamp yang fleksibel (detik / milidetik / tanggal terformat)
                            $timeRaw = $tx['payment_time'] ?? $tx['statement_time'] ?? time();
                            if (is_numeric($timeRaw)) {
                                if (strlen((string)$timeRaw) > 10) {
                                    $timeRaw = (int)($timeRaw / 1000);
                                }
                                $transactionDate = date('Y-m-d H:i:s', $timeRaw);
                            } else {
                                $transactionDate = date('Y-m-d H:i:s', strtotime($timeRaw));
                            }

                            // Build a descriptive details string
                            $description = 'Status: ' . $status;
                            if (isset($tx['revenue_amount'])) {
                                $description .= ' | Revenue: Rp ' . number_format((float)$tx['revenue_amount'], 0, ',', '.');
                            }
                            if (isset($tx['fee_amount'])) {
                                $description .= ' | Fee: Rp ' . number_format((float)$tx['fee_amount'], 0, ',', '.');
                            }
                            if (isset($tx['adjustment_amount']) && (float)$tx['adjustment_amount'] != 0) {
                                $description .= ' | Adjustment: Rp ' . number_format((float)$tx['adjustment_amount'], 0, ',', '.');
                            }

                            MarketplaceWalletTransaction::updateOrCreate([
                                'store_id'       => $store->id,
                                'transaction_id' => $txId,
                            ], [
                                'tenant_id'        => $store->tenant_id,
                                'transaction_date' => $transactionDate,
                                'type'             => $txType,
                                'description'      => $description,
                                'amount'           => abs($amount),
                                'direction'        => $amount >= 0 ? 'in' : 'out',
                                'current_balance'  => null,
                                'raw_data'         => $tx,
                            ]);
                        }
                        
                        $currentStart = $currentEnd + 1;
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
