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

        // Jika user klik Refresh Saldo Real-Time, kosongkan cache semua toko
        if ($request->boolean('refresh') || $request->has('refresh')) {
            foreach ($stores as $s) {
                Cache::forget("store_wallet_balance_{$s->id}");
            }
        }

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
                        $currentBalance  = null;
                        $withdrawBalance = null;
                        $apiSuccess = false;
                        
                        // 1. Coba ambil dari Shopee API get_wallet_balance
                        try {
                            $res = $this->shopeeService->getWalletBalance($accessToken, $shopId);
                            if (is_array($res) && (isset($res['current_balance']) || isset($res['withdraw_balance']))) {
                                $currentBalance  = (float) ($res['current_balance'] ?? 0);
                                $withdrawBalance = isset($res['withdraw_balance']) ? (float) $res['withdraw_balance'] : $currentBalance;
                                $apiSuccess = true;
                            }
                        } catch (\Throwable $e) {
                            Log::warning("Shopee API getWalletBalance failed for {$store->store_name}: " . $e->getMessage());
                        }

                        // 2. Jika get_wallet_balance tidak mengembalikan data, coba dari transaksi dompet terbaru (get_wallet_transaction_list)
                        if (!$apiSuccess) {
                            try {
                                $dateFrom = now()->subDays(15)->startOfDay()->timestamp;
                                $dateTo   = now()->endOfDay()->timestamp;
                                $txRes = $this->shopeeService->getWalletTransactionList($accessToken, $shopId, 1, 10, $dateFrom, $dateTo);
                                $txList = $txRes['transaction_list'] ?? [];
                                if (!empty($txList) && isset($txList[0]['current_balance'])) {
                                    $currentBalance  = (float) $txList[0]['current_balance'];
                                    $withdrawBalance = $currentBalance;
                                    $apiSuccess = true;

                                    // Simpan transaksi terakhir ke DB agar sinkron
                                    $latestRaw = $txList[0];
                                    if (!empty($latestRaw['transaction_id'])) {
                                        $amt = (float) ($latestRaw['amount'] ?? 0);
                                        MarketplaceWalletTransaction::updateOrCreate([
                                            'store_id'       => $store->id,
                                            'transaction_id' => $latestRaw['transaction_id'],
                                        ], [
                                            'tenant_id'        => $store->tenant_id,
                                            'transaction_date' => date('Y-m-d H:i:s', $latestRaw['create_time']),
                                            'type'             => $this->mapShopeeTxType($latestRaw['wallet_type'] ?? ''),
                                            'description'      => $latestRaw['description'] ?? '—',
                                            'amount'           => abs($amt),
                                            'direction'        => $amt >= 0 ? 'in' : 'out',
                                            'current_balance'  => $currentBalance,
                                            'raw_data'         => $latestRaw,
                                        ]);
                                    }
                                }
                            } catch (\Throwable $e) {
                                Log::warning("Shopee getWalletTransactionList fallback failed for {$store->store_name}: " . $e->getMessage());
                            }
                        }

                        // 3. Fallback database HANYA jika API benar-benar gagal terkoneksi (bukan karena saldo 0)
                        if (!$apiSuccess) {
                            $latestTx = MarketplaceWalletTransaction::where('store_id', $store->id)
                                ->orderBy('transaction_date', 'desc')
                                ->orderBy('id', 'desc')
                                ->first();
                            if ($latestTx) {
                                $currentBalance  = (float) $latestTx->current_balance;
                                $withdrawBalance = $currentBalance;
                                $apiSuccess = true;
                            } else {
                                $currentBalance  = 0.0;
                                $withdrawBalance = 0.0;
                            }
                        }

                        return [
                            'success'          => $apiSuccess,
                            'current_balance'  => $currentBalance ?? 0.0,
                            'withdraw_balance' => $withdrawBalance ?? $currentBalance ?? 0.0,
                            'error_message'    => $apiSuccess ? null : 'Gagal memuat saldo dari API Shopee'
                        ];
                    } elseif ($store->channel->code === 'tiktok') {
                        // Jika user meminta refresh, jalankan sync TikTok 30 hari terakhir untuk mendapatkan saldo akurat
                        if (request()->boolean('refresh')) {
                            try {
                                Artisan::call('marketplace:sync-wallets', [
                                    '--store_id' => $store->id,
                                    '--days'     => 30,
                                ]);
                            } catch (\Throwable $e) {
                                Log::warning("TikTok sync on refresh failed for {$store->store_name}: " . $e->getMessage());
                            }
                        }

                        // Ambil saldo TikTok dari transaksi terakhir di database yang memiliki nilai current_balance
                        $latestTx = MarketplaceWalletTransaction::where('store_id', $store->id)
                            ->whereNotNull('current_balance')
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
                        'current_balance'  => 0.0,
                        'withdraw_balance' => 0.0,
                        'error_message'    => $e->getMessage()
                    ];
                }
            });

            // Hitung Saldo Pending (Estimasi Penghasilan Belum Selesai / Akan Dilepas di marketplace)
            // Di Shopee Seller Center ("Akan Dilepas") & TikTok ("To Settle"), dana pending HANYA berasal dari
            // pesanan yang AKTIF dan BELUM SELESAI (READY_TO_SHIP, PROCESSED, SHIPPED, DELIVERED).
            // Pesanan COMPLETED / SELESAI sudah masuk ke Saldo Penjual / mutasi dompet (tidak boleh dihitung dobel).
            // Pesanan CANCELLED, UNPAID, RETURNED tidak menghasilkan saldo.
            $pendingOrdersQuery = Order::where('store_id', $store->id)
                ->whereIn('order_status', [
                    'READY_TO_SHIP', 'PROCESSED', 'RETRY_SHIP', 'TO_RETRY_LOGISTICS',
                    'SHIPPED', 'TO_CONFIRM_RECEIVE', 'IN_TRANSIT', 'DELIVERED',
                    'AWAITING_SHIPMENT', 'AWAITING_COLLECTION'
                ])
                ->whereNotIn('order_status', [
                    'COMPLETED', 'SELESAI', 'FINISHED',
                    'CANCELLED', 'BATAL', 'CANCELED', 'IN_CANCEL',
                    'UNPAID', 'PENDING_PAYMENT',
                    'RETURNED', 'REFUNDED', 'RETURN', 'REFUND', 'RETURN_APPROVED', 'RETURN_COMPLETED', 'RETUR'
                ])
                ->where('order_date', '>=', now()->subDays(30));

            // Jika user menekan tombol Refresh Saldo Real-Time, cek dan perbarui status pesanan pending terkini langsung dari API
            if ($request->boolean('refresh')) {
                try {
                    $pendingOrdersToSync = (clone $pendingOrdersQuery)->limit(50)->get();
                    if ($pendingOrdersToSync->isNotEmpty()) {
                        $orderMarketplaceIds = $pendingOrdersToSync->pluck('order_marketplace_id')->filter()->values()->all();

                        if ($store->channel->code === 'shopee') {
                            $accessToken = $store->getValidAccessToken();
                            $res = $this->shopeeService->getOrderDetail($accessToken, (int) $store->marketplace_store_id, $orderMarketplaceIds);
                            $shopeeOrders = $res['order_list'] ?? [];
                            foreach ($shopeeOrders as $sOrder) {
                                $sn = $sOrder['order_sn'] ?? null;
                                if (!$sn) continue;
                                $statusRaw = strtoupper((string)($sOrder['order_status'] ?? ''));
                                $shopeeStatusMap = [
                                    'UNPAID'             => 'UNPAID',
                                    'READY_TO_SHIP'      => 'READY_TO_SHIP',
                                    'PROCESSED'          => 'READY_TO_SHIP',
                                    'RETRY_SHIP'         => 'READY_TO_SHIP',
                                    'TO_RETRY_LOGISTICS' => 'READY_TO_SHIP',
                                    'SHIPPED'            => 'SHIPPED',
                                    'TO_CONFIRM_RECEIVE' => 'SHIPPED',
                                    'DELIVERED'          => 'DELIVERED',
                                    'COMPLETED'          => 'COMPLETED',
                                    'CANCELLED'          => 'CANCELLED',
                                    'IN_CANCEL'          => 'CANCELLED',
                                ];
                                $newStatus = $shopeeStatusMap[$statusRaw] ?? $statusRaw;
                                $updateData = ['order_status' => $newStatus];
                                if (isset($sOrder['escrow_amount']) && (float)$sOrder['escrow_amount'] > 0) {
                                    $updateData['net_amount'] = (float)$sOrder['escrow_amount'];
                                }
                                Order::where('store_id', $store->id)->where('order_marketplace_id', $sn)->update($updateData);
                            }
                        } elseif ($store->channel->code === 'tiktok') {
                            $shopCipher = $store->shop_cipher ?? '';
                            if (!empty($shopCipher)) {
                                $accessToken = $store->getValidAccessToken();
                                $res = $this->tiktokService->getOrderDetail($accessToken, $shopCipher, $orderMarketplaceIds);
                                $tiktokOrders = $res['order_list'] ?? [];
                                foreach ($tiktokOrders as $tOrder) {
                                    $oId = $tOrder['id'] ?? null;
                                    if (!$oId) continue;
                                    $statusRaw = strtoupper((string)($tOrder['status'] ?? $tOrder['order_status'] ?? ''));
                                    $tiktokStatusMap = [
                                        'UNPAID'              => 'UNPAID',
                                        '100'                 => 'UNPAID',
                                        'AWAITING_SHIPMENT'   => 'READY_TO_SHIP',
                                        '111'                 => 'READY_TO_SHIP',
                                        'AWAITING_COLLECTION' => 'READY_TO_SHIP',
                                        '112'                 => 'READY_TO_SHIP',
                                        'IN_TRANSIT'          => 'SHIPPED',
                                        '121'                 => 'SHIPPED',
                                        'DELIVERED'           => 'DELIVERED',
                                        '122'                 => 'DELIVERED',
                                        'COMPLETED'           => 'COMPLETED',
                                        '130'                 => 'COMPLETED',
                                        'CANCELLED'           => 'CANCELLED',
                                        '140'                 => 'CANCELLED',
                                    ];
                                    $newStatus = $tiktokStatusMap[$statusRaw] ?? $statusRaw;
                                    Order::where('store_id', $store->id)->where('order_marketplace_id', $oId)->update(['order_status' => $newStatus]);
                                }
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    Log::warning("Real-time pending orders sync failed for {$store->store_name}: " . $e->getMessage());
                }
            }

            // Ambil daftar pesanan pending terkini setelah sinkronisasi
            $pendingOrders = (clone $pendingOrdersQuery)->get([
                'id', 'store_id', 'order_marketplace_id', 'order_status',
                'total_amount', 'marketplace_fee', 'net_amount',
                'financial_breakdown', 'refund_amount', 'recon_status', 'order_date'
            ]);

            $pendingBalance = (float) $pendingOrders->sum(function ($ord) {
                return max(0.0, (float) $ord->net_amount);
            });
            $pendingCount = $pendingOrders->count();

            // Saldo dompet riil yang siap ditarik
            $readyBalance = (float) ($balanceData['withdraw_balance'] ?? $balanceData['current_balance'] ?? 0);

            $balanceData['pending_balance'] = $pendingBalance;
            $balanceData['pending_count']   = $pendingCount;
            $balanceData['total_estimated'] = $readyBalance + $pendingBalance;

            $totalWalletBalance += $readyBalance;
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

            // Hapus cache saldo agar saldo langsung terupdate di dashboard
            Cache::forget("store_wallet_balance_{$store->id}");

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
