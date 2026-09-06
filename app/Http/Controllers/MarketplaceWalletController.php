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
                Cache::forget("store_pending_balance_{$s->id}");
                Cache::forget("store_shopee_fee_ratio_{$s->id}");
                Cache::forget("store_tiktok_fee_ratio_{$s->id}");
            }
        }

        $storeBalances = [];
        $totalWalletBalance = 0.0;
        $totalPendingBalance = 0.0;
        $totalPendingCount = 0;

        foreach ($stores as $store) {
            $walletCacheKey  = "store_wallet_balance_v3_{$store->id}";
            $pendingCacheKey = "store_pending_balance_v3_{$store->id}";
            
            if ($request->boolean('refresh') || $request->has('refresh')) {
                Cache::forget("store_wallet_balance_{$store->id}");
                Cache::forget("store_pending_balance_{$store->id}");
                Cache::forget("store_wallet_balance_v2_{$store->id}");
                Cache::forget("store_pending_balance_v2_{$store->id}");
                Cache::forget($walletCacheKey);
                Cache::forget($pendingCacheKey);
                Cache::forget("store_shopee_fee_ratio_{$store->id}");
                Cache::forget("store_tiktok_fee_ratio_{$store->id}");
                Cache::forget("store_shopee_fee_ratio_v2_{$store->id}");
                Cache::forget("store_tiktok_fee_ratio_v2_{$store->id}");
                Cache::forget("store_shopee_fee_ratio_v3_{$store->id}");
                Cache::forget("store_tiktok_fee_ratio_v3_{$store->id}");
            }
            
            // 1. Saldo Dompet (Dapat Ditarik) diambil langsung dari API
            $balanceData = Cache::remember($walletCacheKey, now()->addMinutes(2), function () use ($store) {
                try {
                    $accessToken = $store->getValidAccessToken();
                    
                    if ($store->channel->code === 'shopee') {
                        $shopId = (int) $store->marketplace_store_id;
                        $currentBalance  = null;
                        $withdrawBalance = null;
                        $apiSuccess = false;
                        
                        // 1. Coba ambil dari Shopee API get_wallet_balance (Live Real-Time)
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

                        // 2. Fallback mutasi dompet jika get_wallet_balance kosong
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

            // 2. Saldo Pending (Akan Dilepas) diambil LANGSUNG REAL-TIME dari API Shopee & TikTok
            $pendingData = Cache::remember($pendingCacheKey, now()->addMinutes(2), function () use ($store) {
                return $this->getLivePendingFromApi($store);
            });

            // Saldo dompet riil yang siap ditarik
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
            Cache::forget("store_pending_balance_{$store->id}");

            return back()->with('success', '✅ Sinkronisasi data mutasi dompet toko ' . $store->store_name . ' berhasil diselesaikan.');
        } catch (\Throwable $e) {
            Log::error("Manual sync failed for store {$store->store_name}", [
                'message' => $e->getMessage()
            ]);
            return back()->with('error', '❌ Gagal melakukan sinkronisasi: ' . $e->getMessage());
        }
    }

    /**
     * Ambil data pesanan pending (Akan Dilepas / To Settle) LANGSUNG dari API Marketplace
     */
    protected function getLivePendingFromApi(Store $store): array
    {
        $pendingBalance = 0.0;
        $pendingCount   = 0;
        $apiSuccess     = false;

        try {
            if ($store->status === 'connected') {
                $accessToken = $store->getValidAccessToken();

                if ($store->channel->code === 'shopee') {
                    $shopId = (int) $store->marketplace_store_id;

                    $activeSns = [];

                    // Hitung rasio fee toko dari database lokal pesanan selesai terakhir toko ini
                    $feeRatio = Cache::remember("store_shopee_fee_ratio_v3_{$store->id}", now()->addHours(6), function () use ($store) {
                        // Cek dari pesanan selesai yang memiliki data rincian finansial resmi dari API
                        $completedWithBreakdown = Order::where('store_id', $store->id)
                            ->whereIn('order_status', ['COMPLETED', 'SELESAI'])
                            ->where('total_amount', '>', 0)
                            ->whereNotNull('financial_breakdown')
                            ->orderBy('id', 'desc')
                            ->limit(50)
                            ->get();

                        $totalGross = 0.0;
                        $totalDeductions = 0.0;

                        foreach ($completedWithBreakdown as $ord) {
                            $gross = (float) $ord->total_amount;
                            if ($gross <= 0) continue;

                            $fb = $ord->financial_breakdown;
                            if (is_string($fb)) $fb = json_decode($fb, true) ?? [];

                            $escrowAmt = (float) ($fb['escrow_amount_after_adjustment'] ?? $fb['escrow_amount'] ?? 0);
                            if ($escrowAmt > 0 && $gross > $escrowAmt) {
                                $totalGross += $gross;
                                $totalDeductions += ($gross - $escrowAmt);
                            } else {
                                $totFee = abs($ord->fee_breakdown_details['total_fee'] ?? 0);
                                if ($totFee > 0 && $totFee < $gross) {
                                    $totalGross += $gross;
                                    $totalDeductions += $totFee;
                                }
                            }
                        }

                        if ($totalGross > 0) {
                            $realRatio = $totalDeductions / $totalGross;
                            if ($realRatio >= 0.18 && $realRatio <= 0.35) {
                                return round($realRatio, 4);
                            }
                        }

                        // Default persentase potongan biaya resmi Shopee Star/Star+ Fashion (~23.0%)
                        return 0.2300;
                    });

                    // Shopee membatasi rentang get_order_list maksimal < 15 hari per request.
                    // Buat 2 jendela waktu (30 hari terakhir) agar mencakup 1 bulan penuh tanpa melanggar batas Shopee API:
                    $ranges = [
                        ['from' => now()->subDays(15)->timestamp, 'to' => now()->timestamp],
                        ['from' => now()->subDays(30)->timestamp, 'to' => now()->subDays(15)->timestamp - 1],
                    ];

                    // Status resmi Shopee yang dananya tertahan di Escrow ("Akan Dilepas"):
                    // 1. READY_TO_SHIP   : Pesanan baru dibayar pembeli, siap diproses/dikirim
                    // 2. PROCESSED       : Pesanan sedang dikemas / pengiriman telah diatur
                    // 3. SHIPPED         : Pesanan sedang dalam perjalanan kurir
                    // 4. TO_CONFIRM_RECEIVE: Pesanan telah tiba, menunggu konfirmasi pembeli / masa garansi Shopee
                    // CATATAN: IN_CANCEL & TO_RETURN TIDAK dimasukkan karena dananya dibekukan/dalam proses pembatalan
                    $statusesToFetch = ['READY_TO_SHIP', 'PROCESSED', 'SHIPPED', 'TO_CONFIRM_RECEIVE'];

                    foreach ($statusesToFetch as $status) {
                        // READY_TO_SHIP & PROCESSED selalu dalam 15 hari terakhir (Shopee auto-cancel jika > 4 hari)
                        $targetRanges = in_array($status, ['READY_TO_SHIP', 'PROCESSED'])
                            ? [$ranges[0]]
                            : $ranges;

                        foreach ($targetRanges as $range) {
                            $cursor  = '';
                            $hasMore = true;
                            $page    = 0;

                            while ($hasMore && $page < 10) {
                                $page++;
                                try {
                                    $res = $this->shopeeService->getOrderList(
                                        $accessToken,
                                        $shopId,
                                        $range['from'],
                                        $range['to'],
                                        'create_time',
                                        $cursor,
                                        50,
                                        $status
                                    );

                                    foreach ($res['order_list'] ?? [] as $o) {
                                        if (!empty($o['order_sn'])) {
                                            $activeSns[] = $o['order_sn'];
                                        }
                                    }

                                    $hasMore = !empty($res['more']);
                                    $cursor  = (string)($res['next_cursor'] ?? '');
                                } catch (\Throwable $e) {
                                    Log::warning("Shopee getOrderList {$status} notice for {$store->store_name}: " . $e->getMessage());
                                    break;
                                }
                            }
                        }
                    }

                    // Scan tambahan berdasarkan update_time 15 hari terakhir (menangkap semua pesanan aktif yang statusnya baru diperbarui)
                    try {
                        foreach (['READY_TO_SHIP', 'PROCESSED', 'SHIPPED'] as $status) {
                            $cursor  = '';
                            $hasMore = true;
                            $page    = 0;
                            while ($hasMore && $page < 10) {
                                $page++;
                                $res = $this->shopeeService->getOrderList(
                                    $accessToken,
                                    $shopId,
                                    $ranges[0]['from'],
                                    $ranges[0]['to'],
                                    'update_time',
                                    $cursor,
                                    50,
                                    $status
                                );
                                foreach ($res['order_list'] ?? [] as $o) {
                                    if (!empty($o['order_sn'])) {
                                        $activeSns[] = $o['order_sn'];
                                    }
                                }
                                $hasMore = !empty($res['more']);
                                $cursor  = (string)($res['next_cursor'] ?? '');
                            }
                        }
                    } catch (\Throwable $e) {
                        Log::warning("Shopee update_time scan notice for {$store->store_name}: " . $e->getMessage());
                    }

                    $activeSns = array_values(array_unique($activeSns));
                    $totalEscrow = 0.0;
                    $validPendingCount = 0;

                    // Ambil detail escrow resmi Shopee secara batch untuk pesanan aktif
                    $batchEscrowMap = [];
                    if (!empty($activeSns)) {
                        $escrowChunks = array_chunk($activeSns, 20);
                        foreach ($escrowChunks as $echunk) {
                            try {
                                $batchList = $this->shopeeService->getEscrowDetailBatch($accessToken, $shopId, $echunk);
                                if (is_array($batchList)) {
                                    if (isset($batchList['order_income']) && is_array($batchList['order_income'])) {
                                        $batchList = $batchList['order_income'];
                                    }
                                    foreach ($batchList as $item) {
                                        if (!is_array($item)) continue;
                                        $sn = $item['order_sn'] ?? ($item['order_income']['order_sn'] ?? null);
                                        if (!$sn) continue;

                                        $amt = (float)(
                                            $item['escrow_amount_after_adjustment'] 
                                            ?? $item['escrow_amount'] 
                                            ?? $item['order_income']['escrow_amount_after_adjustment'] 
                                            ?? $item['order_income']['escrow_amount'] 
                                            ?? 0
                                        );
                                        if ($amt > 0) {
                                            $batchEscrowMap[$sn] = $amt;
                                        }
                                    }
                                }
                            } catch (\Throwable $e) {
                                Log::warning("Shopee getEscrowDetailBatch error for {$store->store_name}: " . $e->getMessage());
                            }
                        }
                    }

                    if (!empty($activeSns)) {
                        $chunks = array_chunk($activeSns, 50);
                        foreach ($chunks as $chunk) {
                            try {
                                $detailRes = $this->shopeeService->getOrderDetail($accessToken, $shopId, $chunk);
                                foreach ($detailRes['order_list'] ?? [] as $sOrder) {
                                    $rawStatus = strtoupper((string)($sOrder['order_status'] ?? ''));

                                    // Abaikan jika order ternyata sudah selesai, batal, atau dalam proses retur/batal di Shopee
                                    if (in_array($rawStatus, ['COMPLETED', 'SELESAI', 'CANCELLED', 'BATAL', 'REFUNDED', 'IN_CANCEL', 'TO_RETURN', 'RETURNED'])) {
                                        if (!empty($sOrder['order_sn'])) {
                                            Order::where('store_id', $store->id)
                                                ->where('order_marketplace_id', $sOrder['order_sn'])
                                                ->update(['order_status' => $rawStatus]);
                                        }
                                        continue;
                                    }

                                    $sn = (string)($sOrder['order_sn'] ?? '');
                                    $escrow = 0.0;

                                    // 1. Prioritas Utama: Nilai escrow resmi dari getEscrowDetailBatch API
                                    if (!empty($sn) && isset($batchEscrowMap[$sn]) && $batchEscrowMap[$sn] > 0) {
                                        $escrow = (float) $batchEscrowMap[$sn];
                                    }

                                    // 2. Prioritas Kedua: Nilai escrow dari getOrderDetail (jika tersedia)
                                    if ($escrow <= 0 && !empty($sOrder['escrow_amount']) && (float)$sOrder['escrow_amount'] > 0) {
                                        $escrow = (float) $sOrder['escrow_amount'];
                                    }

                                    // 3. Prioritas Ketiga: Nilai escrow resmi yang tersimpan di DB lokal pada financial_breakdown
                                    $localOrd = !empty($sn) ? Order::where('store_id', $store->id)
                                        ->where('order_marketplace_id', $sn)
                                        ->first() : null;

                                    if ($escrow <= 0 && $localOrd && !empty($localOrd->financial_breakdown)) {
                                        $fb = $localOrd->financial_breakdown;
                                        if (is_string($fb)) {
                                            $fb = json_decode($fb, true) ?? [];
                                        }
                                        if (is_array($fb)) {
                                            $officialEsc = (float)($fb['escrow_amount_after_adjustment'] ?? $fb['escrow_amount'] ?? 0);
                                            if ($officialEsc > 0) {
                                                $escrow = $officialEsc;
                                            }
                                        }
                                    }

                                    // 4. Prioritas Keempat: Jika pesanan baru (READY_TO_SHIP & PROCESSED) yang belum selesai/belum ada escrow di API,
                                    // Seller Center Shopee menampilkan estimasi dana bersih = Nilai omset kotor dipotong estimasi biaya resmi Shopee (~23.0%)
                                    if ($escrow <= 0) {
                                        $gross = 0.0;
                                        if (!empty($sOrder['item_list'])) {
                                            foreach ($sOrder['item_list'] as $item) {
                                                $price = (float) ($item['model_discounted_price'] ?? $item['model_original_price'] ?? 0);
                                                $qty   = (int) ($item['model_quantity_purchased'] ?? 1);
                                                $gross += ($price * $qty);
                                            }
                                        }

                                        if ($gross <= 0 && !empty($sOrder['total_amount'])) {
                                            $gross = (float) $sOrder['total_amount'];
                                        }

                                        if ($gross <= 0 && $localOrd && (float)$localOrd->total_amount > 0) {
                                            $gross = (float)$localOrd->total_amount;
                                        }

                                        $escrow = max(0.0, round($gross * (1.0 - $feeRatio)));
                                    }

                                    $totalEscrow += $escrow;
                                    $validPendingCount++;

                                    // Sinkronkan status pesanan dan nilai net_amount ke DB lokal
                                    if (!empty($sn)) {
                                        $saveStatus = in_array($rawStatus, ['PROCESSED', 'READY_TO_SHIP']) ? 'READY_TO_SHIP' : $rawStatus;
                                        $updateData = ['order_status' => $saveStatus];
                                        if ($escrow > 0) {
                                            $updateData['net_amount'] = $escrow;
                                            if ($localOrd && (float)$localOrd->total_amount > $escrow) {
                                                $updateData['marketplace_fee'] = (float)$localOrd->total_amount - $escrow;
                                            }
                                        }
                                        Order::where('store_id', $store->id)
                                            ->where('order_marketplace_id', $sn)
                                            ->update($updateData);
                                    }
                                }
                            } catch (\Throwable $e) {
                                Log::warning("Shopee getOrderDetail chunk notice for {$store->store_name}: " . $e->getMessage());
                            }
                        }
                    }

                    $pendingBalance = $totalEscrow;
                    $pendingCount   = $validPendingCount;
                    $apiSuccess     = true;
                } elseif ($store->channel->code === 'tiktok') {
                    $shopCipher = $store->shop_cipher ?? '';
                    if (!empty($shopCipher)) {
                        $timeFrom = now()->subDays(30)->timestamp;
                        $timeTo   = now()->timestamp;

                        // Hitung rasio fee toko dari database lokal pesanan selesai TikTok
                        $tiktokFeeRatio = Cache::remember("store_tiktok_fee_ratio_v3_{$store->id}", now()->addHours(6), function () use ($store) {
                            $completedWithBreakdown = Order::where('store_id', $store->id)
                                ->whereIn('order_status', ['COMPLETED', 'DELIVERED'])
                                ->where('total_amount', '>', 0)
                                ->whereNotNull('financial_breakdown')
                                ->orderBy('id', 'desc')
                                ->limit(50)
                                ->get();

                            $totalGross = 0.0;
                            $totalDeductions = 0.0;

                            foreach ($completedWithBreakdown as $ord) {
                                $gross = (float) $ord->total_amount;
                                if ($gross <= 0) continue;

                                $fb = $ord->financial_breakdown;
                                if (is_string($fb)) $fb = json_decode($fb, true) ?? [];

                                $escrowAmt = (float) ($fb['settlement_amount'] ?? $fb['escrow_amount'] ?? 0);
                                if ($escrowAmt > 0 && $gross > $escrowAmt) {
                                    $totalGross += $gross;
                                    $totalDeductions += ($gross - $escrowAmt);
                                } else {
                                    $totFee = abs($ord->fee_breakdown_details['total_fee'] ?? 0);
                                    if ($totFee > 0 && $totFee < $gross) {
                                        $totalGross += $gross;
                                        $totalDeductions += $totFee;
                                    }
                                }
                            }

                            if ($totalGross > 0) {
                                $realRatio = $totalDeductions / $totalGross;
                                if ($realRatio >= 0.08 && $realRatio <= 0.30) {
                                    return round($realRatio, 4);
                                }
                            }

                            return 0.1350; // Default estimasi potongan biaya TikTok Shop (~13.5%)
                        });

                        $cursor = '';
                        $hasMore = true;
                        $page = 0;
                        $activeOrderIds = [];

                        while ($hasMore && $page < 10) {
                            $page++;
                            try {
                                $res = $this->tiktokService->getOrderList($accessToken, $shopCipher, $timeFrom, $timeTo, $cursor);
                                $orders = $res['orders'] ?? $res['order_list'] ?? [];

                                foreach ($orders as $to) {
                                    $status = strtoupper((string)($to['status'] ?? $to['order_status'] ?? ''));
                                    // Pesanan aktif TikTok yang belum selesai / to settle
                                    if (in_array($status, [
                                        'AWAITING_SHIPMENT', '111',
                                        'AWAITING_COLLECTION', '112',
                                        'IN_TRANSIT', '121',
                                        'DELIVERED', '122'
                                    ])) {
                                        $id = $to['id'] ?? $to['order_id'] ?? null;
                                        if ($id) {
                                            $activeOrderIds[] = $id;
                                        }
                                    }
                                }

                                $hasMore = !empty($res['more']);
                                $cursor  = (string)($res['next_cursor'] ?? '');
                            } catch (\Throwable $e) {
                                Log::warning("TikTok getOrderList failed for {$store->store_name}: " . $e->getMessage());
                                break;
                            }
                        }

                        $activeOrderIds = array_values(array_unique($activeOrderIds));
                        $tiktokPending = 0.0;
                        $tiktokCount   = 0;

                        if (!empty($activeOrderIds)) {
                            $chunks = array_chunk($activeOrderIds, 50);
                            foreach ($chunks as $chunk) {
                                try {
                                    $detailRes = $this->tiktokService->getOrderDetail($accessToken, $shopCipher, $chunk);
                                    $orders = $detailRes['orders'] ?? $detailRes['order_list'] ?? [];

                                    foreach ($orders as $tOrder) {
                                        $status = strtoupper((string)($tOrder['status'] ?? $tOrder['order_status'] ?? ''));
                                        if (in_array($status, ['COMPLETED', 'CANCELLED', 'RETURNED'])) {
                                            continue;
                                        }

                                        $orderIdStr = (string)($tOrder['id'] ?? $tOrder['order_id'] ?? '');
                                        $payment = $tOrder['payment'] ?? $tOrder['payment_info'] ?? [];
                                        $escrow = (float) ($payment['settlement_amount'] ?? $payment['escrow_amount'] ?? 0);

                                        if ($escrow <= 0) {
                                            $localOrd = Order::where('store_id', $store->id)
                                                ->where('order_marketplace_id', $orderIdStr)
                                                ->first();

                                            $hasOfficial = false;
                                            if ($localOrd && !empty($localOrd->financial_breakdown)) {
                                                $fb = $localOrd->financial_breakdown;
                                                if (is_string($fb)) $fb = json_decode($fb, true) ?? [];
                                                $officialSettlement = (float) ($fb['settlement_amount'] ?? $fb['escrow_amount'] ?? 0);
                                                if ($officialSettlement > 0) {
                                                    $escrow = $officialSettlement;
                                                    $hasOfficial = true;
                                                }
                                            }

                                            if (!$hasOfficial) {
                                                $gross = (float) ($payment['subtotal_after_seller_discounts'] ?? $payment['total_amount'] ?? $payment['original_total_product_price'] ?? 0);
                                                if ($gross <= 0 && $localOrd && (float)$localOrd->total_amount > 0) {
                                                    $gross = (float)$localOrd->total_amount;
                                                }
                                                $escrow = max(0.0, round($gross * (1.0 - $tiktokFeeRatio)));
                                            }
                                        }

                                        $tiktokPending += $escrow;
                                        $tiktokCount++;
                                    }
                                } catch (\Throwable $e) {
                                    Log::warning("TikTok getOrderDetail failed for {$store->store_name}: " . $e->getMessage());
                                }
                            }
                        }

                        $pendingBalance = $tiktokPending;
                        $pendingCount   = $tiktokCount;
                        $apiSuccess     = true;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Live pending API fetch error for {$store->store_name}: " . $e->getMessage());
        }

        // Fallback ke database jika API gagal terkoneksi
        if (!$apiSuccess) {
            $pendingOrders = Order::where('store_id', $store->id)
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
                ->where('order_date', '>=', now()->subDays(30))
                ->get([
                    'id', 'store_id', 'order_marketplace_id', 'order_status',
                    'total_amount', 'marketplace_fee', 'net_amount',
                    'financial_breakdown', 'recon_status', 'order_date'
                ]);

            $pendingBalance = (float) $pendingOrders->sum(function ($ord) {
                return max(0.0, (float) $ord->net_amount);
            });
            $pendingCount = $pendingOrders->count();
        }

        return [
            'pending_balance' => $pendingBalance,
            'pending_count'   => $pendingCount,
            'is_live_api'     => $apiSuccess,
        ];
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
