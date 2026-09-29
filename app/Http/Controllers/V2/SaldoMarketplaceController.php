<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Str;

class SaldoMarketplaceController extends Controller
{
    protected ShopeeService $shopeeService;
    protected TiktokService $tiktokService;

    public function __construct(ShopeeService $shopeeService, TiktokService $tiktokService)
    {
        $this->shopeeService = $shopeeService;
        $this->tiktokService = $tiktokService;
    }

    /**
     * Display V2 Saldo Marketplace Index page.
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        // Ambil semua toko yang memiliki channel Shopee atau TikTok
        $stores = Store::where('tenant_id', $tenantId)
            ->whereHas('channel', function ($q) {
                $q->whereIn('code', ['shopee', 'tiktok']);
            })
            ->with('channel')
            ->get();

        // Jika user klik Refresh Saldo Real-Time, lakukan sinkronisasi live
        if ($request->boolean('refresh') || $request->has('refresh')) {
            foreach ($stores as $s) {
                Cache::forget("store_wallet_balance_v3_{$s->id}");
                Cache::forget("store_pending_balance_v3_{$s->id}");
                Cache::forget("store_wallet_balance_v4_{$s->id}");
                Cache::forget("store_pending_balance_v4_{$s->id}");
                Cache::forget("store_wallet_balance_v5_{$s->id}");
                Cache::forget("store_pending_balance_v5_{$s->id}");
                Cache::forget("store_shopee_fee_ratio_{$s->id}");
                Cache::forget("store_tiktok_fee_ratio_{$s->id}");
                Cache::forget("store_shopee_fee_ratio_v3_{$s->id}");
                Cache::forget("store_tiktok_fee_ratio_v3_{$s->id}");

                try {
                    if ($s->status === 'connected') {
                        $accessToken = $s->getValidAccessToken();
                        if ($s->channel->code === 'shopee') {
                            $shopId = (int) $s->marketplace_store_id;
                            $res = $this->shopeeService->getWalletBalance($accessToken, $shopId);
                            if (is_array($res) && (isset($res['current_balance']) || isset($res['withdraw_balance']))) {
                                $currentBal = (float) ($res['current_balance'] ?? 0);
                                $withdrawBal = isset($res['withdraw_balance']) ? (float) $res['withdraw_balance'] : $currentBal;
                                Cache::put("store_wallet_balance_v5_{$s->id}", [
                                    'success'          => true,
                                    'current_balance'  => $currentBal,
                                    'withdraw_balance' => $withdrawBal,
                                    'error_message'    => null,
                                ], now()->addMinutes(15));
                            }
                        } elseif ($s->channel->code === 'tiktok') {
                            Artisan::call('marketplace:sync-wallets', [
                                '--store_id' => $s->id,
                                '--days'     => 30,
                            ]);
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Real-time refresh error for store {$s->store_name}: " . $e->getMessage());
                }
            }

            return redirect()->route('v2.saldo_marketplace.index')
                ->with('success', '✅ Saldo real-time dan mutasi dompet berhasil diperbarui dari marketplace.');
        }

        $storeBalances = [];
        $totalWalletBalance = 0.0;
        $totalPendingBalance = 0.0;
        $totalPendingCount = 0;

        foreach ($stores as $store) {
            $walletCacheKey  = "store_wallet_balance_v5_{$store->id}";
            $pendingCacheKey = "store_pending_balance_v5_{$store->id}";
            
            // 1. Saldo Dompet (Dapat Ditarik)
            $balanceData = Cache::remember($walletCacheKey, now()->addMinutes(15), function () use ($store) {
                try {
                    $currentBalance  = null;
                    $withdrawBalance = null;
                    $apiSuccess      = false;
                    $errorMessage    = null;

                    if ($store->status === 'connected') {
                        $accessToken = $store->getValidAccessToken();
                        if ($store->channel->code === 'shopee') {
                            $shopId = (int) $store->marketplace_store_id;
                            $res = $this->shopeeService->getWalletBalance($accessToken, $shopId);
                            if (is_array($res) && (isset($res['current_balance']) || isset($res['withdraw_balance']))) {
                                $currentBalance  = (float) ($res['current_balance'] ?? 0);
                                $withdrawBalance = isset($res['withdraw_balance']) ? (float) $res['withdraw_balance'] : $currentBalance;
                                $apiSuccess      = true;
                            } else {
                                $errorMessage = $res['message'] ?? 'Respon API Shopee tidak valid';
                            }
                        } elseif ($store->channel->code === 'tiktok') {
                            $latestTx = MarketplaceWalletTransaction::where('store_id', $store->id)
                                ->orderBy('transaction_date', 'desc')
                                ->orderBy('id', 'desc')
                                ->first();

                            if ($latestTx && $latestTx->current_balance !== null) {
                                $currentBalance  = (float) $latestTx->current_balance;
                                $withdrawBalance = $currentBalance;
                                $apiSuccess      = true;
                            } else {
                                $errorMessage = 'Belum ada transaksi mutasi dompet TikTok terdata';
                            }
                        }
                    } else {
                        $errorMessage = 'Toko belum terhubung ke API Marketplace';
                    }

                    if (!$apiSuccess && $currentBalance === null) {
                        $latestTx = MarketplaceWalletTransaction::where('store_id', $store->id)
                            ->orderBy('transaction_date', 'desc')
                            ->first();
                        $currentBalance  = $latestTx ? (float) ($latestTx->current_balance ?? 0) : 0.0;
                        $withdrawBalance = $currentBalance;
                    }

                    return [
                        'success'          => $apiSuccess,
                        'current_balance'  => $currentBalance ?? 0.0,
                        'withdraw_balance' => $withdrawBalance ?? 0.0,
                        'error_message'    => $errorMessage,
                    ];
                } catch (\Throwable $e) {
                    return [
                        'success'          => false,
                        'current_balance'  => 0.0,
                        'withdraw_balance' => 0.0,
                        'error_message'    => $e->getMessage(),
                    ];
                }
            });

            // 2. Saldo Pending (Akan Dilepas / Escrow)
            $pendingData = Cache::remember($pendingCacheKey, now()->addMinutes(15), function () use ($store) {
                return $this->getPendingOrdersData($store);
            });

            $readyBalance   = (float) ($balanceData['withdraw_balance'] ?? $balanceData['current_balance'] ?? 0);
            $pendingBalance = (float) ($pendingData['pending_balance'] ?? 0);
            $pendingCount   = (int)   ($pendingData['pending_count'] ?? 0);

            $balanceData['pending_balance'] = $pendingBalance;
            $balanceData['pending_count']   = $pendingCount;
            $balanceData['total_estimated'] = $readyBalance + $pendingBalance;
            $balanceData['is_live_pending'] = $pendingData['is_live_api'] ?? false;

            $totalWalletBalance  += $readyBalance;
            $totalPendingBalance += $pendingBalance;
            $totalPendingCount   += $pendingCount;

            $storeBalances[] = [
                'store'    => $store,
                'balance'  => $balanceData,
            ];
        }

        return view('v2.saldo_marketplace.index', compact(
            'storeBalances',
            'totalWalletBalance',
            'totalPendingBalance',
            'totalPendingCount'
        ));
    }

    /**
     * Display V2 Mutasi Dompet per Toko.
     */
    public function mutasi(Request $request, Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        
        $dateFrom = $request->input('date_from', now()->subDays(15)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));

        // Normalisasi saldo berjalan TikTok jika ada saldo minus
        if ($store->channel && $store->channel->code === 'tiktok') {
            $hasNegative = MarketplaceWalletTransaction::where('store_id', $store->id)
                ->where('current_balance', '<', 0)
                ->exists();

            if ($hasNegative) {
                $allTx = MarketplaceWalletTransaction::where('store_id', $store->id)
                    ->orderBy('transaction_date', 'asc')
                    ->orderBy('id', 'asc')
                    ->get();

                $rawSum = 0.0;
                $minSum = 0.0;
                foreach ($allTx as $t) {
                    $rawSum += ($t->direction === 'in') ? (float)$t->amount : -(float)$t->amount;
                    if ($rawSum < $minSum) $minSum = $rawSum;
                }

                $offset = ($minSum < 0) ? abs($minSum) : 0.0;
                $runBal = $offset;
                foreach ($allTx as $t) {
                    $runBal += ($t->direction === 'in') ? (float)$t->amount : -(float)$t->amount;
                    $t->current_balance = max(0.0, $runBal);
                    $t->save();
                }
            }
        }

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
                'current_balance' => $tx->current_balance !== null ? max(0.0, (float)$tx->current_balance) : null,
            ];
        }

        $error = null;

        return view('v2.saldo_marketplace.mutasi', compact('store', 'mutasiList', 'dateFrom', 'dateTo', 'error'));
    }

    /**
     * Display V2 Rincian Saldo Tertahan per Toko.
     */
    public function pending(Request $request, Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);

        $dateFrom = $request->input('date_from', now()->subDays(60)->format('Y-m-d'));
        $dateTo = $request->input('date_to', now()->format('Y-m-d'));
        $search = $request->input('search');
        $statusFilter = $request->input('status');

        $data = $this->getPendingOrdersData($store, $dateFrom, $dateTo, $search, $statusFilter);

        return view('v2.saldo_marketplace.pending', array_merge([
            'store'        => $store,
            'dateFrom'     => $dateFrom,
            'dateTo'       => $dateTo,
            'search'       => $search,
            'statusFilter' => $statusFilter,
        ], $data));
    }

    /**
     * Trigger sync data mutasi dompet toko dari API.
     */
    public function sync(Request $request, Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        
        $days = (int) $request->input('days', 90);

        try {
            Artisan::call('marketplace:sync-wallets', [
                '--store_id' => $store->id,
                '--days'     => $days
            ]);

            Cache::forget("store_wallet_balance_v5_{$store->id}");
            Cache::forget("store_pending_balance_v5_{$store->id}");

            return back()->with('success', '✅ Sinkronisasi data mutasi dompet toko ' . $store->store_name . ' berhasil diselesaikan.');
        } catch (\Throwable $e) {
            Log::error("Manual sync failed for store {$store->store_name}", [
                'message' => $e->getMessage()
            ]);
            return back()->with('error', '❌ Gagal melakukan sinkronisasi: ' . $e->getMessage());
        }
    }

    /**
     * Helper terpusat untuk mengambil dan menghitung pesanan pending tertahan
     */
    private function getPendingOrdersData(Store $store, ?string $dateFrom = null, ?string $dateTo = null, ?string $search = null, ?string $statusFilter = null): array
    {
        $dateFrom = $dateFrom ?: now()->subDays(60)->format('Y-m-d');
        $dateTo = $dateTo ?: now()->format('Y-m-d');

        $isTiktok = ($store->channel && $store->channel->code === 'tiktok');
        $feeRatio = $isTiktok ? 0.1350 : 0.2300;

        // Cari rasio potongan biaya riil dari pesanan yang sudah selesai di toko ini
        $recentCompleted = Order::where('store_id', $store->id)
            ->whereIn('order_status', ['COMPLETED', 'SELESAI', 'DELIVERED', 'FINISHED', '122'])
            ->where('total_amount', '>', 0)
            ->where('order_date', '>=', now()->subDays(60))
            ->limit(50)
            ->get(['total_amount', 'marketplace_fee', 'net_amount', 'financial_breakdown']);

        if ($recentCompleted->isNotEmpty()) {
            $totalGross = 0;
            $totalDeductions = 0;
            foreach ($recentCompleted as $ord) {
                $gross = (float)$ord->total_amount;
                $escrowAmt = 0;
                if (!empty($ord->financial_breakdown)) {
                    $fb = is_string($ord->financial_breakdown) ? json_decode($ord->financial_breakdown, true) : $ord->financial_breakdown;
                    if (is_array($fb)) {
                        $escrowAmt = (float)($fb['escrow_amount_after_adjustment'] ?? $fb['escrow_amount'] ?? $fb['settlement_amount'] ?? 0);
                    }
                }
                if ($escrowAmt <= 0 && (float)$ord->net_amount > 0 && (float)$ord->net_amount < $gross) {
                    $escrowAmt = (float)$ord->net_amount;
                }
                if ($escrowAmt > 0 && $gross > $escrowAmt) {
                    $totalGross += $gross;
                    $totalDeductions += ($gross - $escrowAmt);
                } elseif ($ord->marketplace_fee > 0 && $ord->marketplace_fee < $gross) {
                    $totalGross += $gross;
                    $totalDeductions += (float)$ord->marketplace_fee;
                }
            }
            if ($totalGross > 0) {
                $calcRatio = $totalDeductions / $totalGross;
                if ($calcRatio >= 0.05 && $calcRatio <= 0.40) {
                    $feeRatio = $calcRatio;
                }
            }
        }

        $activeStatuses = [
            'READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'IN_TRANSIT',
            'DELIVERED', 'UNPAID', 'ON_HOLD', '111', '112', '114',
            'PERLU_DIKIRIM', 'DIKIRIM', 'MENUNGGU_DIKIRIM',
        ];

        $availableStatuses = Order::where('store_id', $store->id)
            ->whereIn('order_status', $activeStatuses)
            ->distinct()
            ->pluck('order_status')
            ->toArray();

        $query = Order::where('store_id', $store->id)
            ->whereIn('order_status', $activeStatuses)
            ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        if (!empty($statusFilter)) {
            $query->where('order_status', $statusFilter);
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('order_date', 'desc')->get();

        $pendingList = [];
        $totalPendingAmount = 0.0;
        $totalGrossAmount   = 0.0;
        $totalFeeAmount     = 0.0;

        foreach ($orders as $ord) {
            $gross = (float) $ord->total_amount;
            $escrowEstimate = 0.0;
            $feeEstimate = 0.0;

            if (!empty($ord->financial_breakdown)) {
                $fb = is_string($ord->financial_breakdown) ? json_decode($ord->financial_breakdown, true) : $ord->financial_breakdown;
                if (is_array($fb)) {
                    $escrowEstimate = (float)($fb['escrow_amount_after_adjustment'] ?? $fb['escrow_amount'] ?? $fb['settlement_amount'] ?? 0);
                }
            }

            if ($escrowEstimate <= 0 && (float)$ord->net_amount > 0) {
                $escrowEstimate = (float)$ord->net_amount;
            }

            if ($escrowEstimate <= 0) {
                if ((float)$ord->marketplace_fee > 0) {
                    $feeEstimate = (float)$ord->marketplace_fee;
                    $escrowEstimate = max(0, $gross - $feeEstimate);
                } else {
                    $feeEstimate = round($gross * $feeRatio, 2);
                    $escrowEstimate = max(0, $gross - $feeEstimate);
                }
            } else {
                $feeEstimate = max(0, $gross - $escrowEstimate);
            }

            $totalGrossAmount   += $gross;
            $totalFeeAmount     += $feeEstimate;
            $totalPendingAmount += $escrowEstimate;

            $pendingList[] = [
                'order'            => $ord,
                'gross_amount'     => $gross,
                'fee_amount'       => $feeEstimate,
                'pending_amount'   => $escrowEstimate,
                'status_label'     => $ord->order_status,
                'date_formatted'   => $ord->order_date ? $ord->order_date->format('d/m/Y H:i') : '-',
            ];
        }

        return [
            'pending_balance'    => $totalPendingAmount,
            'pending_count'      => count($pendingList),
            'totalPendingAmount' => $totalPendingAmount,
            'totalGrossAmount'   => $totalGrossAmount,
            'totalFeeAmount'     => $totalFeeAmount,
            'pendingList'        => $pendingList,
            'availableStatuses'  => $availableStatuses,
            'is_live_api'        => false,
        ];
    }
}
