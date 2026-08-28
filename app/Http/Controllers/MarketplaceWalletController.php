<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarketplaceWalletController extends Controller
{
    protected ShopeeService $shopeeService;
    protected TiktokService $tiktokService;

    public function __construct(ShopeeService $shopeeService, TiktokService $tiktokService)
    {
        $this->shopeeService = $shopeeService;
        $this->tiktokService = $tiktokService;
    }

    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        
        // Ambil semua toko yang memiliki channel Shopee atau TikTok
        $stores = Store::where('tenant_id', $tenantId)
            ->whereHas('channel', function ($q) {
                $q->whereIn('code', ['shopee', 'tiktok']);
            })
            ->with('channel')
            ->get();

        $storeBalances = [];

        foreach ($stores as $store) {
            $cacheKey = "store_wallet_balance_{$store->id}";
            
            // Cache selama 2 menit
            $balanceData = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($store) {
                try {
                    $accessToken = $store->getValidAccessToken();
                    
                    if ($store->channel->code === 'shopee') {
                        $shopId = (int) $store->marketplace_store_id;
                        $res = $this->shopeeService->getWalletBalance($accessToken, $shopId);
                        
                        return [
                            'success'          => true,
                            'current_balance'  => (float) ($res['current_balance'] ?? 0),
                            'withdraw_balance' => (float) ($res['withdraw_balance'] ?? 0),
                            'error_message'    => null
                        ];
                    } elseif ($store->channel->code === 'tiktok') {
                        $shopCipher = $store->shop_cipher ?? '';
                        $startTime = now()->subDays(30)->timestamp;
                        $endTime = now()->timestamp;
                        
                        $res = $this->tiktokService->getFinanceTransactions($accessToken, $shopCipher, $startTime, $endTime);
                        $transactions = $res['payment_list'] ?? $res['payments'] ?? [];
                        
                        $totalSettled = 0;
                        foreach ($transactions as $tx) {
                            $status = strtoupper($tx['status'] ?? $tx['payment_status'] ?? '');
                            if ($status === 'PAID' || $status === 'SETTLED' || $status === 'SUCCESS' || $status === 'COMPLETED') {
                                $totalSettled += (float) ($tx['amount']['value'] ?? $tx['amount'] ?? 0);
                            }
                        }

                        return [
                            'success'          => true,
                            'current_balance'  => $totalSettled, // Estimasi dana cair 30 hari terakhir
                            'withdraw_balance' => $totalSettled,
                            'is_estimated'     => true,
                            'error_message'    => null
                        ];
                    }
                } catch (\Throwable $e) {
                    Log::error("Failed to fetch wallet balance for store {$store->store_name}", [
                        'message' => $e->getMessage()
                    ]);
                    
                    return [
                        'success'          => false,
                        'current_balance'  => 0,
                        'withdraw_balance' => 0,
                        'error_message'    => $e->getMessage()
                    ];
                }
            });

            $storeBalances[] = [
                'store'    => $store,
                'balance'  => $balanceData,
            ];
        }

        return view('finance.marketplace_wallets.index', compact('storeBalances'));
    }

    public function mutasi(Request $request, Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        
        $dateFrom = $request->input('date_from', now()->subDays(15)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        
        $startTimestamp = strtotime($dateFrom . ' 00:00:00');
        $endTimestamp = strtotime($dateTo . ' 23:59:59');

        $mutasiList = [];
        $error = null;

        try {
            $accessToken = $store->getValidAccessToken();

            if ($store->channel->code === 'shopee') {
                $shopId = (int) $store->marketplace_store_id;
                $res = $this->shopeeService->getWalletTransactionList(
                    $accessToken,
                    $shopId,
                    1,
                    100,
                    $startTimestamp,
                    $endTimestamp
                );
                
                $rawList = $res['transaction_list'] ?? [];
                
                foreach ($rawList as $tx) {
                    $amount = (float) ($tx['amount'] ?? 0);
                    $mutasiList[] = [
                        'id'              => $tx['transaction_id'] ?? '—',
                        'date'            => isset($tx['create_time']) ? date('Y-m-d H:i:s', $tx['create_time']) : '—',
                        'type'            => $this->mapShopeeTxType($tx['wallet_type'] ?? ''),
                        'description'     => $tx['description'] ?? '—',
                        'amount'          => $amount,
                        'direction'       => $amount >= 0 ? 'in' : 'out',
                        'current_balance' => (float) ($tx['current_balance'] ?? 0),
                    ];
                }
            } elseif ($store->channel->code === 'tiktok') {
                $shopCipher = $store->shop_cipher ?? '';
                $res = $this->tiktokService->getFinanceTransactions(
                    $accessToken,
                    $shopCipher,
                    $startTimestamp,
                    $endTimestamp
                );
                
                $rawList = $res['payment_list'] ?? $res['payments'] ?? [];
                
                foreach ($rawList as $tx) {
                    $amount = (float) ($tx['amount']['value'] ?? $tx['amount'] ?? 0);
                    $status = $tx['status'] ?? $tx['payment_status'] ?? '—';
                    $txType = $tx['payment_type'] ?? $tx['type'] ?? 'SETTLEMENT';
                    
                    $mutasiList[] = [
                        'id'              => $tx['id'] ?? $tx['payment_id'] ?? '—',
                        'date'            => isset($tx['create_time']) ? date('Y-m-d H:i:s', $tx['create_time']) : '—',
                        'type'            => $txType,
                        'description'     => 'Status: ' . $status . (!empty($tx['order_id']) ? ' | Order ID: ' . $tx['order_id'] : ''),
                        'amount'          => $amount,
                        'direction'       => 'in',
                        'current_balance' => null,
                    ];
                }
            }
        } catch (\Throwable $e) {
            Log::error("Failed to fetch wallet transactions for store {$store->store_name}", [
                'message' => $e->getMessage()
            ]);
            $error = $e->getMessage();
        }

        // Sort mutasi by date descending
        usort($mutasiList, function($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        return view('finance.marketplace_wallets.mutasi', compact('store', 'mutasiList', 'dateFrom', 'dateTo', 'error'));
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
