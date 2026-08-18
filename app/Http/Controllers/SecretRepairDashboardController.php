<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Store;
use App\Models\OrderItem;
use App\Services\TiktokService;
use App\Services\ShopeeService;
use App\Jobs\PullOrdersFromTiktok;
use App\Jobs\PullOrdersFromShopee;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SecretRepairDashboardController extends Controller
{
    /**
     * Secret path access token / signature verification
     */
    protected $secretKey = 'x8912';

    public function index(Request $request)
    {
        // Require auth
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Akses ditolak. Silakan login terlebih dahulu.');
        }

        // Stats summary for repair panel
        $ordersCount = Order::count();
        $missingItemsCount = Order::whereDoesntHave('items')
            ->whereNotNull('order_marketplace_id')
            ->where('order_marketplace_id', 'NOT LIKE', 'MANUAL-%')
            ->where('order_marketplace_id', 'NOT LIKE', 'SHOPEE-DEMO-%')
            ->where('order_marketplace_id', 'NOT LIKE', 'DS-%')
            ->count();

        $unreconciledCount = Order::where('recon_status', 'UNRECONCILED')->count();

        // 🟢 Status Breakdown Metrics
        $completedCount = Order::whereIn('order_status', ['COMPLETED', 'SELESAI', 'DELIVERED', 'FINISHED'])->count();
        $readyToShipCount = Order::whereIn('order_status', ['READY_TO_SHIP', 'UNPAID', 'PROCESSING', 'PENDING', 'PROSES'])->count();
        $shippedCount = Order::whereIn('order_status', ['SHIPPED', 'IN_TRANSIT', 'DIKIRIM'])->count();
        $cancelledCount = Order::whereIn('order_status', ['CANCELLED', 'BATAL', 'CANCELED'])->count();
        $returnedCount = Order::whereIn('order_status', ['RETURNED', 'REFUNDED', 'RETUR'])->count();

        // 🌐 Channel Comparison Metrics (ERP vs API)
        $tiktokStores = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%tiktok%'))->pluck('id');
        $shopeeStores = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%shopee%'))->pluck('id');

        // TikTok Stats
        $tiktokTotalOrders = Order::whereIn('store_id', $tiktokStores)->count();
        $tiktokCompleted = Order::whereIn('store_id', $tiktokStores)->whereIn('order_status', ['COMPLETED', 'SELESAI', 'DELIVERED', 'FINISHED'])->count();
        $tiktokCancelled = Order::whereIn('store_id', $tiktokStores)->whereIn('order_status', ['CANCELLED', 'BATAL'])->count();
        $tiktokMissingFees = Order::whereIn('store_id', $tiktokStores)->where(fn($q) => $q->where('marketplace_fee', 0)->orWhereNull('financial_breakdown'))->count();
        $tiktokMissingItems = Order::whereIn('store_id', $tiktokStores)->whereDoesntHave('items')->count();

        // Shopee Stats
        $shopeeTotalOrders = Order::whereIn('store_id', $shopeeStores)->count();
        $shopeeCompleted = Order::whereIn('store_id', $shopeeStores)->whereIn('order_status', ['COMPLETED', 'SELESAI', 'DELIVERED', 'FINISHED'])->count();
        $shopeeCancelled = Order::whereIn('store_id', $shopeeStores)->whereIn('order_status', ['CANCELLED', 'BATAL'])->count();
        $shopeeMissingFees = Order::whereIn('store_id', $shopeeStores)->where(fn($q) => $q->where('marketplace_fee', 0)->orWhereNull('financial_breakdown'))->count();
        $shopeeMissingItems = Order::whereIn('store_id', $shopeeStores)->whereDoesntHave('items')->count();

        // Manual Stats
        $manualTotalOrders = Order::whereNotIn('store_id', $tiktokStores->merge($shopeeStores))->count();

        return view('secret_repair_dashboard', compact(
            'ordersCount',
            'missingItemsCount',
            'unreconciledCount',
            'completedCount',
            'readyToShipCount',
            'shippedCount',
            'cancelledCount',
            'returnedCount',
            'tiktokStores',
            'shopeeStores',
            'tiktokTotalOrders',
            'tiktokCompleted',
            'tiktokCancelled',
            'tiktokMissingFees',
            'tiktokMissingItems',
            'shopeeTotalOrders',
            'shopeeCompleted',
            'shopeeCancelled',
            'shopeeMissingFees',
            'shopeeMissingItems',
            'manualTotalOrders'
        ));
    }

    public function runAction(Request $request)
    {
        $action = $request->input('action');
        $startTime = microtime(true);
        $output = '';

        try {
            switch ($action) {
                case 'fix_missing_items':
                    $output = $this->executeFixMissingItems();
                    break;

                case 'sync_tiktok_escrow':
                    Artisan::call('tiktok:sync-escrow');
                    $output = Artisan::output() ?: "✅ Command 'tiktok:sync-escrow' berhasil dijalankan.";
                    break;

                case 'sync_shopee_escrow':
                    if (Artisan::has('shopee:sync-escrow')) {
                        Artisan::call('shopee:sync-escrow');
                        $output = Artisan::output();
                    } else {
                        $output = "⚠️ Command 'shopee:sync-escrow' belum terdaftar di artisan.";
                    }
                    break;

                case 'pull_tiktok_orders':
                    $stores = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%tiktok%'))->get();
                    $timeTo = time();
                    $timeFrom = strtotime('-7 days', $timeTo);
                    $count = 0;

                    foreach ($stores as $st) {
                        PullOrdersFromTiktok::dispatch($st, $timeFrom, $timeTo);
                        $count++;
                    }
                    $output = "🚀 Berhasil mengirimkan Job Penarikan Pesanan TikTok (7 hari terakhir) untuk {$count} toko TikTok.";
                    break;

                case 'pull_shopee_orders':
                    $stores = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%shopee%'))->get();
                    $timeTo = time();
                    $timeFrom = strtotime('-7 days', $timeTo);
                    $count = 0;

                    foreach ($stores as $st) {
                        PullOrdersFromShopee::dispatch($st, $timeFrom, $timeTo);
                        $count++;
                    }
                    $output = "🚀 Berhasil mengirimkan Job Penarikan Pesanan Shopee (7 hari terakhir) untuk {$count} toko Shopee.";
                    break;

                case 'recalculate_reconciliation':
                    Cache::flush();
                    $orders = Order::whereNotNull('order_marketplace_id')->take(500)->get();
                    $updated = 0;

                    foreach ($orders as $ord) {
                        $netAmt = (float) $ord->net_amount;
                        $totAmt = (float) $ord->total_amount;
                        $mpFee  = (float) $ord->marketplace_fee;

                        if ($netAmt > 0 && ($totAmt - $mpFee) == $netAmt) {
                            $ord->recon_status = 'MATCHED';
                        } else {
                            $ord->recon_status = 'UNRECONCILED';
                        }
                        $ord->save();
                        $updated++;
                    }
                    $output = "⚖️ Berhasil memperbarui status rekonsiliasi untuk {$updated} pesanan ERP.";
                    break;

                case 'clear_system_cache':
                    Cache::flush();
                    Artisan::call('view:clear');
                    Artisan::call('route:clear');
                    $output = "🧹 Berhasil membersihkan seluruh Web Cache, View Cache, dan Memory Rekonsiliasi.";
                    break;

                case 'git_pull':
                    $basePath = base_path();
                    $gitDir   = $basePath . '/.git';

                    // Coba fix permission .git agar www-data bisa write
                    $webUser = trim(shell_exec('whoami 2>&1') ?: 'www-data');
                    shell_exec('chmod -R ug+rw ' . escapeshellarg($gitDir) . ' 2>&1');
                    @chmod($gitDir . '/FETCH_HEAD', 0664);
                    @touch($gitDir . '/FETCH_HEAD');

                    // Jalankan git pull
                    $homeDir  = '/var/www';
                    $gitOutput = shell_exec(
                        'HOME=' . escapeshellarg($homeDir) .
                        ' GIT_DIR=' . escapeshellarg($gitDir) .
                        ' GIT_WORK_TREE=' . escapeshellarg($basePath) .
                        ' git -C ' . escapeshellarg($basePath) . ' pull 2>&1'
                    );

                    // Fallback: coba dengan env HOME=/root
                    if (empty($gitOutput) || str_contains($gitOutput, 'Permission denied')) {
                        $gitOutput = shell_exec(
                            'HOME=/root git -C ' . escapeshellarg($basePath) . ' pull 2>&1'
                        );
                    }

                    $output = "🔄 Git Pull (user: {$webUser}):\n" . ($gitOutput ?: '(tidak ada output)');
                    break;

                case 'fix_git_permissions':
                    $basePath  = base_path();
                    $gitDir    = $basePath . '/.git';
                    $webUser   = trim(shell_exec('whoami 2>&1') ?: 'www-data');
                    $chownOut  = shell_exec('chown -R ' . escapeshellarg($webUser . ':' . $webUser) . ' ' . escapeshellarg($gitDir) . ' 2>&1');
                    $chmodOut  = shell_exec('chmod -R ug+rw ' . escapeshellarg($gitDir) . ' 2>&1');
                    $chmodOut2 = shell_exec('chmod -R ug+rw ' . escapeshellarg($basePath . '/storage') . ' 2>&1');
                    $chmodOut3 = shell_exec('chmod -R ug+rw ' . escapeshellarg($basePath . '/bootstrap/cache') . ' 2>&1');

                    $output  = "🔧 Fix Git & Storage Permissions (user: {$webUser}):\n";
                    $output .= "chown .git : " . ($chownOut  ?: '✅ OK') . "\n";
                    $output .= "chmod .git : " . ($chmodOut  ?: '✅ OK') . "\n";
                    $output .= "chmod storage : " . ($chmodOut2 ?: '✅ OK') . "\n";
                    $output .= "chmod bootstrap/cache : " . ($chmodOut3 ?: '✅ OK') . "\n";
                    $output .= "\n✅ Selesai! Coba Git Pull lagi.";
                    break;

                case 'artisan_optimize':
                    Artisan::call('optimize');
                    $optimizeOut = Artisan::output();
                    Artisan::call('view:clear');
                    Artisan::call('config:clear');
                    Artisan::call('route:clear');
                    Artisan::call('config:cache');
                    Artisan::call('route:cache');
                    Artisan::call('view:cache');
                    $output = "⚡ Artisan Optimize selesai:\n" . ($optimizeOut ?: 'Application optimized!');
                    $output .= "\n✅ Config cache, route cache, dan view cache berhasil di-rebuild.";
                    break;

                case 'artisan_migrate':
                    Artisan::call('migrate', ['--force' => true]);
                    $migrateOut = Artisan::output();
                    $output = "🗃️ Artisan Migrate Output:\n" . ($migrateOut ?: 'Nothing to migrate.');
                    break;

                default:
                    return response()->json(['success' => false, 'message' => 'Action tidak dikenali.'], 400);
            }

            $duration = round(microtime(true) - $startTime, 2);

            // Re-fetch current stats
            $missingItemsCount = Order::whereDoesntHave('items')
                ->whereNotNull('order_marketplace_id')
                ->where('order_marketplace_id', 'NOT LIKE', 'MANUAL-%')
                ->where('order_marketplace_id', 'NOT LIKE', 'SHOPEE-DEMO-%')
                ->where('order_marketplace_id', 'NOT LIKE', 'DS-%')
                ->count();

            return response()->json([
                'success' => true,
                'action' => $action,
                'output' => $output,
                'duration' => $duration . 's',
                'stats' => [
                    'missing_items' => $missingItemsCount,
                    'unreconciled'  => Order::where('recon_status', 'UNRECONCILED')->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'action'  => $action,
                'error'   => $e->getMessage(),
                'output'  => "❌ Terjadi error: " . $e->getMessage()
            ], 500);
        }
    }

    private function executeFixMissingItems()
    {
        $ordersWithoutItems = Order::whereDoesntHave('items')
            ->whereNotNull('order_marketplace_id')
            ->where('order_marketplace_id', 'NOT LIKE', 'MANUAL-%')
            ->where('order_marketplace_id', 'NOT LIKE', 'SHOPEE-DEMO-%')
            ->where('order_marketplace_id', 'NOT LIKE', 'DS-%')
            ->get();

        $log = [];
        $log[] = "======================================================================";
        $log[] = "🛠️ PERBAIKAN OTOMATIS ITEM PESANAN KOSONG";
        $log[] = "======================================================================";
        $log[] = "Ditemukan " . $ordersWithoutItems->count() . " pesanan tanpa item produk.";

        if ($ordersWithoutItems->isEmpty()) {
            $log[] = "✅ Semua pesanan marketplace di database ERP sudah memiliki item produk lengkap.";
            return implode("\n", $log);
        }

        $tiktokService = app(TiktokService::class);
        $shopeeService = app(ShopeeService::class);
        $fixedCount = 0;

        foreach ($ordersWithoutItems as $ord) {
            $store = $ord->store;
            if (!$store) continue;

            $channelCode = strtolower($store->channel->code ?? '');
            $log[] = "🔍 Memproses Order: {$ord->order_marketplace_id} | Toko: {$store->store_name} ({$channelCode})...";

            if (str_contains($channelCode, 'tiktok') || str_contains($channelCode, 'tokopedia')) {
                try {
                    $accessToken = $store->getValidAccessToken();
                    $shopCipher = $store->shop_cipher;

                    $res = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$ord->order_marketplace_id]);
                    $tOrders = $res['order_list'] ?? $res['orders'] ?? [];

                    if (!empty($tOrders[0])) {
                        $tOrder = $tOrders[0];
                        $itemList = $tOrder['line_items']
                            ?? $tOrder['item_list']
                            ?? $tOrder['order_line_list']
                            ?? $tOrder['sku_list']
                            ?? $tOrder['items']
                            ?? [];

                        if (empty($itemList) && !empty($tOrder['packages'])) {
                            foreach ($tOrder['packages'] as $pkg) {
                                if (!empty($pkg['items'])) $itemList = array_merge($itemList, $pkg['items']);
                                elseif (!empty($pkg['line_items'])) $itemList = array_merge($itemList, $pkg['line_items']);
                                elseif (!empty($pkg['item_list'])) $itemList = array_merge($itemList, $pkg['item_list']);
                            }
                        }

                        if (!empty($itemList)) {
                            foreach ($itemList as $item) {
                                $productId = (string)($item['product_id'] ?? '');
                                $skuId     = (string)($item['sku_id'] ?? '');
                                $sellerSku = $item['seller_sku'] ?? $item['sku'] ?? null;
                                $skuName   = $item['sku_name'] ?? $item['variation_name'] ?? null;
                                $origPrice = (float)($item['original_price'] ?? $item['price'] ?? 0);
                                $sDisc     = (float)($item['seller_discount'] ?? 0);
                                $pDisc     = (float)($item['platform_discount'] ?? 0);
                                $qty       = (int)($item['quantity'] ?? 1);
                                $unitPrice = (float)($item['sale_price'] ?? $item['sku_display_price'] ?? $item['price'] ?? $origPrice);
                                $pName     = $item['product_name'] ?? $item['item_name'] ?? 'Produk TikTok';
                                $vName     = $item['sku_name'] ?? $item['variant_name'] ?? '';

                                OrderItem::create([
                                    'order_id'               => $ord->id,
                                    'sku'                    => $sellerSku ?: $skuId,
                                    'seller_sku'             => $sellerSku,
                                    'sku_id'                 => $skuId,
                                    'sku_name'               => $skuName ?: $vName,
                                    'product_name'           => mb_substr($pName . ($vName ? ' - ' . $vName : ''), 0, 250),
                                    'price'                  => $unitPrice,
                                    'original_price'         => $origPrice,
                                    'seller_discount'        => $sDisc,
                                    'platform_discount'      => $pDisc,
                                    'quantity'               => $qty,
                                    'total_price'            => $unitPrice * $qty,
                                ]);
                            }
                            $fixedCount++;
                            $log[] = "   └─ ✅ Berhasil menambahkan " . count($itemList) . " item produk!";
                        } else {
                            $log[] = "   └─ ⚠️ API TikTok tidak mengembalikan item_list.";
                        }
                    }
                } catch (\Exception $e) {
                    $log[] = "   └─ ❌ Error: " . $e->getMessage();
                }
            }
        }

        $log[] = "======================================================================";
        $log[] = "🎉 SELESAI! Berhasil memperbaiki {$fixedCount} pesanan.";
        return implode("\n", $log);
    }

    /**
     * AJAX: Ambil data perbandingan ERP vs API (financial_breakdown) per channel dengan filter tanggal
     */
    public function compareStats(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');

        $applyDateFilter = function ($query) use ($dateFrom, $dateTo) {
            if ($dateFrom) $query->whereDate('order_date', '>=', $dateFrom);
            if ($dateTo)   $query->whereDate('order_date', '<=', $dateTo);
            return $query;
        };

        $tiktokStores = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%tiktok%'))->pluck('id');
        $shopeeStores = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%shopee%'))->pluck('id');
        $notCancelled = ['CANCELLED', 'BATAL', 'CANCELED'];

        // Helper: hitung stats ERP + API dari sekumpulan order (sudah di-filter date & store)
        $calcStats = function ($storeIds) use ($applyDateFilter, $notCancelled) {
            $q = Order::whereIn('store_id', $storeIds);
            $applyDateFilter($q);

            $erpCount = (clone $q)->count();
            $active   = (clone $q)->whereNotIn('order_status', $notCancelled);

            $erpOmset = (clone $active)->sum('total_amount');
            $erpFee   = (clone $active)->sum('marketplace_fee');
            $erpNet   = (clone $active)->sum('net_amount');

            // ── Data API dari financial_breakdown (JSON field di DB) ──
            // Gunakan PHP aggregation agar kompatibel MySQL 5.7 & 8.0
            $apiOmset = $apiFee = $apiNet = $apiCount = 0;

            (clone $active)->whereNotNull('financial_breakdown')
                ->select(['financial_breakdown', 'total_amount', 'marketplace_fee', 'net_amount'])
                ->chunk(500, function ($orders) use (&$apiOmset, &$apiFee, &$apiNet, &$apiCount) {
                    foreach ($orders as $ord) {
                        $fb = $ord->financial_breakdown;
                        if (is_string($fb)) $fb = json_decode($fb, true);
                        if (!is_array($fb)) continue;

                        // Omset API: subtotal_after_seller_discounts → original_price → total_amount kolom ERP
                        $o = (float)($fb['subtotal_after_seller_discounts']
                            ?? $fb['original_price']
                            ?? $fb['buyer_paid_total']
                            ?? $ord->total_amount
                            ?? 0);

                        // Biaya Admin API: service_fee → net_platform_commission → marketplace_fee kolom ERP
                        $f = (float)($fb['service_fee']
                            ?? $fb['net_platform_commission']
                            ?? $fb['platform_commission']
                            ?? $ord->marketplace_fee
                            ?? 0);

                        // Dana Cair API: escrow_amount → settlement_amount → net_amount kolom ERP
                        $n = (float)($fb['escrow_amount']
                            ?? $fb['settlement_amount']
                            ?? $fb['seller_settlement_amount']
                            ?? $ord->net_amount
                            ?? 0);

                        $apiOmset += $o;
                        $apiFee   += $f;
                        $apiNet   += $n;
                        $apiCount++;
                    }
                });

            return [
                'erp_count'  => $erpCount,
                'erp_omset'  => (float) $erpOmset,
                'erp_fee'    => (float) $erpFee,
                'erp_net'    => (float) $erpNet,
                'api_count'  => $apiCount,   // jumlah order yg punya financial_breakdown
                'api_omset'  => $apiOmset,
                'api_fee'    => $apiFee,
                'api_net'    => $apiNet,
                'diff_omset' => (float)$erpOmset - $apiOmset,
                'diff_fee'   => (float)$erpFee   - $apiFee,
                'diff_net'   => (float)$erpNet    - $apiNet,
            ];
        };

        // ── Per Store Breakdown ───────────────────────────────────────────────
        $allStores = Store::with('channel')
            ->whereIn('id', $tiktokStores->merge($shopeeStores))
            ->get();

        $storeRows = [];
        foreach ($allStores as $st) {
            $sq = Order::where('store_id', $st->id);
            $applyDateFilter($sq);

            $count  = (clone $sq)->count();
            $cancel = (clone $sq)->whereIn('order_status', $notCancelled)->count();
            $active = (clone $sq)->whereNotIn('order_status', $notCancelled);
            $omset  = (clone $active)->sum('total_amount');
            $fee    = (clone $active)->sum('marketplace_fee');
            $net    = (clone $active)->sum('net_amount');

            $apiO = $apiF = $apiN = 0;
            (clone $active)->whereNotNull('financial_breakdown')
                ->select(['financial_breakdown', 'total_amount', 'marketplace_fee', 'net_amount'])
                ->chunk(300, function ($orders) use (&$apiO, &$apiF, &$apiN) {
                    foreach ($orders as $ord) {
                        $fb = $ord->financial_breakdown;
                        if (is_string($fb)) $fb = json_decode($fb, true);
                        if (!is_array($fb)) continue;
                        $apiO += (float)($fb['subtotal_after_seller_discounts'] ?? $fb['original_price'] ?? $fb['buyer_paid_total'] ?? $ord->total_amount ?? 0);
                        $apiF += (float)($fb['service_fee'] ?? $fb['net_platform_commission'] ?? $fb['platform_commission'] ?? $ord->marketplace_fee ?? 0);
                        $apiN += (float)($fb['escrow_amount'] ?? $fb['settlement_amount'] ?? $fb['seller_settlement_amount'] ?? $ord->net_amount ?? 0);
                    }
                });

            $storeRows[] = [
                'store_name'    => $st->store_name,
                'channel'       => strtolower($st->channel->code ?? ''),
                'erp_count'     => $count,
                'erp_cancelled' => $cancel,
                'erp_omset'     => (float) $omset,
                'erp_fee'       => (float) $fee,
                'erp_net'       => (float) $net,
                'api_omset'     => $apiO,
                'api_fee'       => $apiF,
                'api_net'       => $apiN,
                'diff_omset'    => (float)$omset - $apiO,
                'diff_fee'      => (float)$fee - $apiF,
                'diff_net'      => (float)$net - $apiN,
            ];
        }

        $tiktok = $calcStats($tiktokStores);
        $shopee = $calcStats($shopeeStores);

        $allStoreIds = $tiktokStores->merge($shopeeStores);
        $total = $calcStats($allStoreIds);

        return response()->json([
            'date_from' => $dateFrom ?: 'Semua waktu',
            'date_to'   => $dateTo   ?: 'Semua waktu',
            'tiktok'    => $tiktok,
            'shopee'    => $shopee,
            'total'     => $total,
            'stores'    => $storeRows,
        ]);
    }

    /**
     * Halaman detail: daftar order per channel dengan perbandingan ERP vs API
     */
    public function compareDetail(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $channel  = $request->input('channel', 'tiktok'); // tiktok | shopee
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $storeId  = $request->input('store_id');          // optional: filter per toko
        $filter   = $request->input('filter', 'all');     // all | mismatch

        // Resolve store IDs
        if ($storeId) {
            $storeIds = collect([$storeId]);
        } elseif ($channel === 'shopee') {
            $storeIds = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%shopee%'))->pluck('id');
        } else {
            $storeIds = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%tiktok%'))->pluck('id');
        }

        $notCancelled = ['CANCELLED', 'BATAL', 'CANCELED'];

        $query = Order::with(['store', 'items'])
            ->whereIn('store_id', $storeIds)
            ->whereNotIn('order_status', $notCancelled);

        if ($dateFrom) $query->whereDate('order_date', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('order_date', '<=', $dateTo);

        $query->orderBy('order_date', 'desc');

        $allOrders = $query->get(['id', 'order_marketplace_id', 'order_date', 'order_status',
            'total_amount', 'marketplace_fee', 'net_amount', 'financial_breakdown',
            'store_id', 'customer_name', 'shipping_fee']);

        $rows = [];
        foreach ($allOrders as $ord) {
            $fb = $ord->financial_breakdown;
            if (is_string($fb)) $fb = json_decode($fb, true);
            $hasFb = is_array($fb) && !empty($fb);

            $apiOmset = $hasFb ? (float)($fb['subtotal_after_seller_discounts']
                ?? $fb['original_price'] ?? $fb['buyer_paid_total'] ?? $ord->total_amount ?? 0) : null;
            $apiFee   = $hasFb ? (float)($fb['service_fee']
                ?? $fb['net_platform_commission'] ?? $fb['platform_commission'] ?? $ord->marketplace_fee ?? 0) : null;
            $apiNet   = $hasFb ? (float)($fb['escrow_amount']
                ?? $fb['settlement_amount'] ?? $fb['seller_settlement_amount'] ?? $ord->net_amount ?? 0) : null;

            $diffOmset = $hasFb ? (float)$ord->total_amount  - $apiOmset : null;
            $diffFee   = $hasFb ? (float)$ord->marketplace_fee - $apiFee : null;
            $diffNet   = $hasFb ? (float)$ord->net_amount    - $apiNet  : null;

            $isMismatch = $hasFb && (abs($diffNet) > 100 || abs($diffOmset) > 100);

            if ($filter === 'mismatch' && !$isMismatch) continue;

            $rows[] = [
                'id'               => $ord->id,
                'marketplace_id'   => $ord->order_marketplace_id,
                'order_date'       => $ord->order_date,
                'order_status'     => $ord->order_status,
                'store_name'       => $ord->store->store_name ?? '-',
                'customer_name'    => $ord->customer_name,
                'erp_omset'        => (float) $ord->total_amount,
                'erp_fee'          => (float) $ord->marketplace_fee,
                'erp_net'          => (float) $ord->net_amount,
                'api_omset'        => $apiOmset,
                'api_fee'          => $apiFee,
                'api_net'          => $apiNet,
                'diff_omset'       => $diffOmset,
                'diff_fee'         => $diffFee,
                'diff_net'         => $diffNet,
                'has_fb'           => $hasFb,
                'is_mismatch'      => $isMismatch,
            ];
        }

        $stores = Store::whereIn('id', $storeIds)->get(['id', 'store_name']);
        $totalRows    = count($rows);
        $mismatchRows = count(array_filter($rows, fn($r) => $r['is_mismatch']));
        $noFbRows     = count(array_filter($rows, fn($r) => !$r['has_fb']));

        return view('secret_repair_compare_detail', compact(
            'rows', 'channel', 'dateFrom', 'dateTo',
            'filter', 'storeId', 'stores',
            'totalRows', 'mismatchRows', 'noFbRows'
        ));
    }
}


