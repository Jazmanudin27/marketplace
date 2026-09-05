<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\Order;
use App\Models\MarketplaceWalletTransaction;
use App\Services\ShopeeService;
use App\Services\TiktokService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

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
        $totalWalletBalance = 0.0;
        $totalPendingBalance = 0.0;
        $totalPendingCount = 0;

        foreach ($stores as $store) {
            $cacheKey = "store_wallet_balance_{$store->id}";
            
            // Cache selama 2 menit
            $balanceData = Cache::remember($cacheKey, now()->addMinutes(2), function () use ($store) {
                try {
                    $accessToken = $store->getValidAccessToken();
                    
                    if ($store->channel->code === 'shopee') {
                        $shopId = (int) $store->marketplace_store_id;
                        $apiBalance = 0;
                        $success = true;
                        
                        try {
                            $res = $this->shopeeService->getWalletBalance($accessToken, $shopId);
                            $apiBalance = (float) ($res['current_balance'] ?? 0);
                        } catch (\Throwable $e) {
                            Log::warning("Shopee API wallet balance failed for {$store->store_name}, falling back to DB: " . $e->getMessage());
                            $success = false;
                        }
                        
                        // Jika API mengembalikan 0 atau gagal, coba ambil saldo akhir dari transaksi terakhir di DB
                        if ($apiBalance <= 0) {
                            $latestTx = MarketplaceWalletTransaction::where('store_id', $store->id)
                                ->orderBy('transaction_date', 'desc')
                                ->first();
                            if ($latestTx) {
                                $apiBalance = (float) $latestTx->current_balance;
                                $success = true; // Kita berhasil mendapatkan data historis lokal
                            }
                        }
                        
                        return [
                            'success'          => $success,
                            'current_balance'  => $apiBalance,
                            'withdraw_balance' => $apiBalance,
                            'error_message'    => $success ? null : 'Gagal memuat saldo dari API Shopee'
                        ];
                    } elseif ($store->channel->code === 'tiktok') {
                        // Ambil saldo TikTok dari current_balance transaksi terakhir di database
                        $latestTx = MarketplaceWalletTransaction::where('store_id', $store->id)
                            ->orderBy('transaction_date', 'desc')
                            ->orderBy('id', 'desc')
                            ->first();
                            
                        $balance = $latestTx ? (float) $latestTx->current_balance : 0.0;
                        
                        return [
                            'success'          => true,
                            'current_balance'  => $balance,
                            'withdraw_balance' => $balance,
                            'is_estimated'     => false,
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

            // Hitung Saldo Pending (Pesanan aktif/berjalan yang dananya belum dilepas/reconciled)
            $pendingOrders = Order::where('store_id', $store->id)
                ->whereNotIn('order_status', [
                    'CANCELLED', 'BATAL', 'CANCELED', 'UNPAID', 'PENDING_PAYMENT', 'IN_CANCEL',
                    'RETURNED', 'REFUNDED', 'RETURN', 'REFUND', 'RETURN_APPROVED', 'RETURN_COMPLETED'
                ])
                ->where(function ($q) {
                    // Pesanan aktif yang belum selesai/settled (90 hari terakhir)
                    $q->whereNotIn('order_status', ['COMPLETED', 'SELESAI', 'FINISHED'])
                      ->where('order_date', '>=', now()->subDays(90))
                      // ATAU pesanan selesai/delivered tetapi belum berstatus RECONCILED dalam 45 hari terakhir
                      ->orWhere(function ($sub) {
                          $sub->whereIn('order_status', ['COMPLETED', 'SELESAI', 'FINISHED', 'DELIVERED'])
                              ->where(function ($rq) {
                                  $rq->whereNull('recon_status')->orWhere('recon_status', '!=', 'RECONCILED');
                              })
                              ->where('order_date', '>=', now()->subDays(45));
                      });
                })
                ->get(['id', 'total_amount', 'marketplace_fee', 'net_amount', 'financial_breakdown', 'recon_status']);

            $pendingBalance = (float) $pendingOrders->sum(function ($ord) {
                return max(0.0, (float) $ord->net_amount);
            });
            $pendingCount = $pendingOrders->count();

            $balanceData['pending_balance'] = $pendingBalance;
            $balanceData['pending_count']   = $pendingCount;
            $balanceData['total_estimated'] = ((float) ($balanceData['current_balance'] ?? 0)) + $pendingBalance;

            $totalWalletBalance += (float) ($balanceData['current_balance'] ?? 0);
            $totalPendingBalance += $pendingBalance;
            $totalPendingCount += $pendingCount;

            $storeBalances[] = [
                'store'    => $store,
                'balance'  => $balanceData,
            ];
        }

        return view('finance.marketplace_wallets.index', compact(
            'storeBalances',
            'totalWalletBalance',
            'totalPendingBalance',
            'totalPendingCount'
        ));
    }

    public function mutasi(Request $request, Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        
        $dateFrom = $request->input('date_from', now()->subDays(15)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        $txs = MarketplaceWalletTransaction::where('store_id', $store->id)
            ->whereBetween('transaction_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->orderBy('transaction_date', 'desc')
            ->get();

        $mutasiList = [];
        foreach ($txs as $tx) {
            $mutasiList[] = [
                'id'              => $tx->transaction_id,
                'date'            => $tx->transaction_date->format('Y-m-d H:i:s'),
                'type'            => $tx->type,
                'description'     => $tx->description,
                'amount'          => $tx->amount,
                'direction'       => $tx->direction,
                'current_balance' => $tx->current_balance,
            ];
        }

        $error = null;

        return view('finance.marketplace_wallets.mutasi', compact('store', 'mutasiList', 'dateFrom', 'dateTo', 'error'));
    }

    public function sync(Request $request, Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        
        $days = (int) $request->input('days', 90);

        try {
            Artisan::call('marketplace:sync-wallets', [
                '--store_id' => $store->id,
                '--days'     => $days
            ]);

            return back()->with('success', '✅ Sinkronisasi data mutasi dompet toko ' . $store->store_name . ' berhasil diselesaikan.');
        } catch (\Throwable $e) {
            Log::error("Manual sync failed for store {$store->store_name}", [
                'message' => $e->getMessage()
            ]);
            return back()->with('error', '❌ Gagal melakukan sinkronisasi: ' . $e->getMessage());
        }
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
