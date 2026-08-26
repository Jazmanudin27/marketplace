<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Store;
use App\Models\OrderItem;
use App\Models\MasterProduct;
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
        $notCancelled = ['CANCELLED', 'BATAL', 'CANCELED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'TO_RETURN'];
        $ordersCount = Order::whereNotIn('order_status', $notCancelled)->count();
        $apiOrdersCount = Order::whereNotNull('financial_breakdown')->whereNotIn('order_status', $notCancelled)->count();
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
        $tiktokStores = Store::whereHas('channel', fn($q) => $q->whereIn('code', ['tiktok', 'tokopedia']))->pluck('id');
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

        // Duplicate Orders Stats
        $duplicateOrdersCount = \DB::table('orders')
            ->select('tenant_id', \DB::raw('TRIM(order_marketplace_id) as mp_id'))
            ->whereNotNull('order_marketplace_id')
            ->where('order_marketplace_id', '!=', '')
            ->groupBy('tenant_id', \DB::raw('TRIM(order_marketplace_id)'))
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        return view('secret_repair_dashboard', compact(
            'ordersCount',
            'apiOrdersCount',
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
            'manualTotalOrders',
            'duplicateOrdersCount'
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
                    $stores = Store::whereHas('channel', fn($q) => $q->whereIn('code', ['tiktok', 'tokopedia']))->get();
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

                case 'run_migrations':
                    Artisan::call('migrate', ['--force' => true]);
                    $migOut = Artisan::output();
                    Artisan::call('route:clear');
                    Artisan::call('config:clear');
                    $output = "🚀 Executed php artisan migrate --force:\n" . ($migOut ?: 'Database migration completed with no pending changes.');
                    $output .= "\n⚡ Route & Config cache berhasil dibersihkan! Route baru 'marketing.teams.index' kini aktif.";
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

                case 'clean_duplicate_orders':
                    $output = $this->executeCleanDuplicateOrders();
                    break;

                case 'sync_product_stock':
                    $output = $this->executeSyncProductStock();
                    break;

                case 'resync_shopee_status':
                    $output = $this->executeResyncShopeeStatus();
                    break;

                case 'resync_tiktok_status':
                    $output = $this->executeResyncTiktokStatus();
                    break;

                case 'recalculate_bundle_stocks':
                    $output = $this->executeRecalculateBundleStocks();
                    break;

                case 'sync_order_dates':
                    $dateFrom = $request->input('date_from');
                    $dateTo   = $request->input('date_to');
                    $output = $this->executeSyncOrderDates($dateFrom, $dateTo);
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

        } catch (\Throwable $e) {
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
                                $unitPrice = max(0.0, $origPrice - $sDisc);
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

    private function executeCleanDuplicateOrders()
    {
        $tenantId = auth()->user()->tenant_id;
        
        $duplicates = Order::select('tenant_id', \DB::raw('TRIM(order_marketplace_id) as mp_id'), \DB::raw('COUNT(*) as total_count'))
            ->where('tenant_id', $tenantId)
            ->whereNotNull('order_marketplace_id')
            ->where('order_marketplace_id', '!=', '')
            ->groupBy('tenant_id', \DB::raw('TRIM(order_marketplace_id)'))
            ->having('total_count', '>', 1)
            ->get();

        $log = [];
        $log[] = "======================================================================";
        $log[] = "🧹 PEMBERSIHAN & PENGHAPUSAN MASSAL PESANAN GANDA (DUPLICATE ORDERS)";
        $log[] = "======================================================================";

        if ($duplicates->isEmpty()) {
            $log[] = "✅ TIDAK DITEMUKAN PESANAN GANDA (DUPLICATE) DI DATABASE ERP!";
            $log[] = "Database Anda 100% bersih dari pesanan ganda.";
            return implode("\n", $log);
        }

        $log[] = "⚠️ Menemukan " . $duplicates->count() . " grup nomor pesanan yang memiliki data ganda (duplicate).\n";

        $deletedCount = 0;
        $mergedCount  = 0;

        foreach ($duplicates as $dup) {
            $mpId = $dup->mp_id;

            $orders = Order::where('tenant_id', $tenantId)
                ->where(\DB::raw('TRIM(order_marketplace_id)'), $mpId)
                ->orderByDesc('updated_at')
                ->orderByDesc('id')
                ->get();

            if ($orders->count() <= 1) {
                continue;
            }

            // Pilih 1 Utama (Utamakan yang COMPLETED/CANCELLED/SELESAI atau yang punya item)
            $primaryOrder = $orders->first(function ($o) {
                return in_array(strtoupper($o->order_status), ['COMPLETED', 'CANCELLED', 'SELESAI', 'FINISHED', 'BATAL']);
            });

            if (!$primaryOrder) {
                $primaryOrder = $orders->first();
            }

            $log[] = "📌 Order Marketplace ID: {$mpId} ({$orders->count()} pesanan ganda)";
            $log[] = "   -> Menyimpan Order Utama ID: {$primaryOrder->id} (Status: {$primaryOrder->order_status})";

            foreach ($orders as $order) {
                if ($order->id == $primaryOrder->id) {
                    continue;
                }

                // Pindahkan items jika order utama belum punya item
                if ($primaryOrder->items->isEmpty() && $order->items->isNotEmpty()) {
                    OrderItem::where('order_id', $order->id)->update(['order_id' => $primaryOrder->id]);
                    $log[] = "   -> Item dipindahkan dari ID {$order->id} ke Utama ID {$primaryOrder->id}";
                } else {
                    OrderItem::where('order_id', $order->id)->delete();
                }

                $order->delete();
                $deletedCount++;
                $log[] = "   -> Hapus Order Duplikat ID: {$order->id}";
            }

            $mergedCount++;
        }

        $log[] = "\n======================================================================";
        $log[] = "✨ PEMBERSIHAN SELESAI!";
        $log[] = "• Total grup pesanan ganda yang digabung  : {$mergedCount}";
        $log[] = "• Total baris pesanan duplikat yang dihapus: {$deletedCount}";
        $log[] = "======================================================================";

        return implode("\n", $log);
    }

    private function executeSyncProductStock()
    {
        $tenantId = auth()->user()->tenant_id;

        $log = [];
        $log[] = "======================================================================";
        $log[] = "📦 SINKRONISASI STOK PRODUK ERP KE MARKETPLACE (SHOPEE, TIKTOK, LAZADA)";
        $log[] = "======================================================================";

        try {
            // 1. Rekalkulasi stok seluruh Produk Set / Bundle berdasarkan komponennya (di DB secara Instant)
            $updatedBundles = MasterProduct::recalculateAllBundleStocks($tenantId);
            $log[] = "🎁 Berhasil menghitung ulang stok {$updatedBundles} produk Set/Bundle di ERP.";

            // 2. Ambil produk master dan kirim job via dispatchAfterResponse agar HTTP response langsung kembali tanpa menahan koneksi web
            $masterProducts = MasterProduct::where('tenant_id', $tenantId)->get(['id', 'sku', 'stock', 'is_bundle']);
            $count = 0;

            foreach ($masterProducts as $mp) {
                if (method_exists(\App\Jobs\PushStockToMarketplaces::class, 'dispatchAfterResponse')) {
                    \App\Jobs\PushStockToMarketplaces::dispatchAfterResponse($mp->id, $mp->stock);
                } else {
                    \App\Jobs\PushStockToMarketplaces::dispatch($mp->id, $mp->stock);
                }
                $count++;
            }

            // 3. Jalankan sync stok di background CLI (Non-Blocking)
            $artisanCmd = "php artisan stock:sync --filter=all --tenant_id={$tenantId}";
            if (str_starts_with(strtoupper(PHP_OS), 'WIN')) {
                @pclose(@popen("start /B " . $artisanCmd, "r"));
            } else {
                @exec($artisanCmd . " > /dev/null 2>&1 &");
            }

            $log[] = "🚀 Berhasil memperbarui stok ERP & mengirimkan {$count} produk ke antrean push marketplace.";
            $log[] = "⚡ Penyesuaian stok di toko Shopee/TikTok/Lazada berjalan di background secara kilat!";

        } catch (\Throwable $e) {
            $log[] = "❌ Terjadi kesalahan: " . $e->getMessage();
        }

        $log[] = "======================================================================";
        return implode("\n", $log);
    }

    private function executeResyncShopeeStatus()
    {
        $shopeeService = app(ShopeeService::class);
        $tenantId = auth()->user()->tenant_id;

        $shopeeStores = Store::where('tenant_id', $tenantId)
            ->whereHas('channel', fn($q) => $q->where('code', 'shopee'))
            ->get();

        $log = [];
        $log[] = "======================================================================";
        $log[] = "⚡ RE-SINKRONISASI KOREKSI STATUS PESANAN SHOPEE RESMI DARI SHOPEE API";
        $log[] = "======================================================================";

        if ($shopeeStores->isEmpty()) {
            $log[] = "⚠️ Tidak ditemukan toko Shopee yang terhubung di tenant ini.";
            return implode("\n", $log);
        }

        $totalRestored = 0;

        foreach ($shopeeStores as $store) {
            $activeOrders = Order::where('store_id', $store->id)
                ->whereNotNull('order_marketplace_id')
                ->whereNotIn('order_status', ['COMPLETED', 'CANCELLED', 'BATAL', 'CANCELED'])
                ->get();

            if ($activeOrders->isEmpty()) {
                $log[] = "📌 Toko {$store->store_name}: Tidak ada pesanan Shopee aktif di database ERP.";
                continue;
            }

            try {
                $accessToken = $store->getValidAccessToken();
                $orderSns = $activeOrders->pluck('order_marketplace_id')->toArray();
                $chunks = array_chunk($orderSns, 50);

                foreach ($chunks as $chunk) {
                    $detailRes = $shopeeService->getOrderDetail(
                        $accessToken,
                        (int) $store->marketplace_store_id,
                        $chunk
                    );

                    $ordersList = $detailRes['order_list'] ?? [];
                    foreach ($ordersList as $shopeeOrder) {
                        $orderSn = $shopeeOrder['order_sn'] ?? null;
                        if (!$orderSn) continue;

                        $dbOrder = $activeOrders->firstWhere('order_marketplace_id', $orderSn);
                        if (!$dbOrder) continue;

                        $statusRaw = strtoupper((string)($shopeeOrder['order_status'] ?? $dbOrder->order_status));
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
                        $correctStatus = $shopeeStatusMap[$statusRaw] ?? $statusRaw;

                        $isChanged = false;
                        if ($dbOrder->order_status !== $correctStatus) {
                            $oldSt = $dbOrder->order_status;
                            $dbOrder->order_status = $correctStatus;
                            if (in_array($correctStatus, ['CANCELLED', 'BATAL'])) {
                                $dbOrder->cancel_reason = $shopeeOrder['cancel_reason'] ?? 'Cancelled on Shopee';
                                $dbOrder->cancelled_by = 'Shopee / System';
                            }
                            if (!empty($shopeeOrder['update_time'])) {
                                $dbOrder->completed_at = date('Y-m-d H:i:s', $shopeeOrder['update_time']);
                            }
                            $isChanged = true;
                            $log[] = "   -> Order #{$orderSn}: Status dikoreksi dari {$oldSt} => {$correctStatus}";
                        }

                        // Sinkronisasi nomor resi asli Shopee (hapus resi palsu jika belum dibooking)
                        $apiTrackingNumber = (!empty($shopeeOrder['package_list']) && !empty(current($shopeeOrder['package_list'])['tracking_number'])) ? current($shopeeOrder['package_list'])['tracking_number'] : null;
                        if ($apiTrackingNumber && (str_starts_with($apiTrackingNumber, 'PSG') || str_starts_with($apiTrackingNumber, 'psg'))) {
                            $apiTrackingNumber = null;
                        }

                        if ($dbOrder->tracking_number !== $apiTrackingNumber) {
                            if (empty($apiTrackingNumber) && !empty($dbOrder->tracking_number)) {
                                $dbOrder->tracking_number = null;
                                $isChanged = true;
                                $log[] = "   -> Order #{$orderSn}: Resi kosong di API, resi di ERP dikosongkan agar kembali ke tab Perlu Diproses";
                            } elseif (!empty($apiTrackingNumber)) {
                                $dbOrder->tracking_number = $apiTrackingNumber;
                                $isChanged = true;
                                $log[] = "   -> Order #{$orderSn}: Resi diperbarui menjadi {$apiTrackingNumber}";
                            }
                        }

                        if ($isChanged) {
                            $dbOrder->save();
                            $totalRestored++;
                        }
                    }
                }
            } catch (\Throwable $e) {
                $log[] = "❌ Toko {$store->store_name} Error: " . $e->getMessage();
            }
        }

        $log[] = "======================================================================";
        $log[] = "✨ SELESAI! Berhasil mengoreksi {$totalRestored} status pesanan Shopee di ERP.";
        $log[] = "======================================================================";

        return implode("\n", $log);
    }

    private function executeResyncTiktokStatus()
    {
        $tiktokService = app(TiktokService::class);
        $tenantId = auth()->user()->tenant_id;

        $tiktokStores = Store::where('tenant_id', $tenantId)
            ->whereHas('channel', fn($q) => $q->whereIn('code', ['tiktok', 'tokopedia']))
            ->get();

        $log = [];
        $log[] = "======================================================================";
        $log[] = "⚡ RE-SINKRONISASI KOREKSI STATUS PESANAN TIKTOK RESMI DARI TIKTOK API";
        $log[] = "======================================================================";

        if ($tiktokStores->isEmpty()) {
            $log[] = "⚠️ Tidak ditemukan toko TikTok/Tokopedia yang terhubung di tenant ini.";
            return implode("\n", $log);
        }

        $totalRestored = 0;
        $statusMap = [
            'UNPAID' => 'UNPAID',
            '100' => 'UNPAID',
            'AWAITING_SHIPMENT' => 'READY_TO_SHIP',
            '111' => 'READY_TO_SHIP',
            'AWAITING_COLLECTION' => 'READY_TO_SHIP',
            '112' => 'READY_TO_SHIP',
            'IN_TRANSIT' => 'SHIPPED',
            '121' => 'SHIPPED',
            'DELIVERED' => 'DELIVERED',
            '122' => 'DELIVERED',
            'COMPLETED' => 'COMPLETED',
            '130' => 'COMPLETED',
            'CANCELLED' => 'CANCELLED',
            '140' => 'CANCELLED',
        ];

        foreach ($tiktokStores as $store) {
            $activeOrders = Order::where('store_id', $store->id)
                ->whereNotNull('order_marketplace_id')
                ->whereNotIn('order_status', ['COMPLETED', 'CANCELLED', 'BATAL', 'CANCELED'])
                ->get();

            if ($activeOrders->isEmpty()) {
                $log[] = "📌 Toko {$store->store_name}: Tidak ada pesanan TikTok 60 hari terakhir.";
                continue;
            }

            try {
                $accessToken = $store->getValidAccessToken();
                $shopCipher = $store->shop_cipher;

                if (empty($shopCipher)) {
                    $log[] = "📌 Toko {$store->store_name}: Skip (shop_cipher kosong)";
                    continue;
                }

                $orderSns = $activeOrders->pluck('order_marketplace_id')
                    ->map(fn($id) => trim($id))
                    ->filter(fn($id) => is_numeric($id) && strlen($id) >= 15)
                    ->toArray();

                if (empty($orderSns)) {
                    $log[] = "📌 Toko {$store->store_name}: Tidak ada pesanan TikTok numerik aktif yang perlu disinkronkan.";
                    continue;
                }

                $chunks = array_chunk($orderSns, 50);

                foreach ($chunks as $chunk) {
                    $detailRes = $tiktokService->getOrderDetail(
                        $accessToken,
                        $shopCipher,
                        $chunk
                    );

                    $ordersList = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];
                    foreach ($ordersList as $tiktokOrder) {
                        $orderSn = $tiktokOrder['id'] ?? $tiktokOrder['order_id'] ?? null;
                        if (!$orderSn) continue;

                        $dbOrder = $activeOrders->firstWhere('order_marketplace_id', $orderSn);
                        if (!$dbOrder) continue;

                        $statusRaw = strtoupper((string)($tiktokOrder['status'] ?? $tiktokOrder['order_status'] ?? ''));
                        $correctStatus = $statusMap[$statusRaw] ?? $statusRaw;

                        if ($dbOrder->order_status !== $correctStatus) {
                            $oldSt = $dbOrder->order_status;
                            $dbOrder->order_status = $correctStatus;
                            
                            if (in_array($correctStatus, ['COMPLETED', 'DELIVERED', 'CANCELLED'])) {
                                $ts = $tiktokOrder['finish_time'] ?? $tiktokOrder['delivered_time'] ?? $tiktokOrder['complete_time'] ?? $tiktokOrder['update_time'] ?? time();
                                $compTsSec = (is_numeric($ts) && strlen((string)$ts) >= 13) ? (int)($ts / 1000) : (int)$ts;
                                $dbOrder->completed_at = date('Y-m-d H:i:s', $compTsSec);
                            }
                            
                            $dbOrder->save();
                            $totalRestored++;
                            $log[] = "   -> Order #{$orderSn}: Status dikoreksi dari {$oldSt} => {$correctStatus}";
                        }
                    }
                }
            } catch (\Throwable $e) {
                $log[] = "❌ Toko {$store->store_name} Error: " . $e->getMessage();
            }
        }

        $log[] = "======================================================================";
        $log[] = "✨ SELESAI! Berhasil mengoreksi {$totalRestored} status pesanan TikTok di ERP.";
        $log[] = "======================================================================";

        return implode("\n", $log);
    }

    private function executeRecalculateBundleStocks()
    {
        $tenantId = auth()->user()->tenant_id;

        $log = [];
        $log[] = "======================================================================";
        $log[] = "🎁 AUTO-BUNDLE MATCHING & REKALKULASI STOK PRODUK SET / BUNDLE ERP";
        $log[] = "======================================================================";

        try {
            // 1. Jalankan Auto-Bundle Matching berdasarkan pencocokan SKU produk single
            $singleProducts = MasterProduct::where('tenant_id', $tenantId)
                ->where(function ($q) {
                    $q->where('is_bundle', false)->orWhereNull('is_bundle');
                })
                ->get(['id', 'sku']);

            $targetProducts = MasterProduct::where('tenant_id', $tenantId)
                ->where(function ($q) {
                    $q->where('is_bundle', true)
                      ->orWhere('sku', 'like', 'SET-%')
                      ->orWhere('sku', 'like', 'PAKET-%')
                      ->orWhere('sku', 'like', 'BUNDLE-%');
                })
                ->get();

            $autoMatchedCount = 0;
            $totalComponentsLinked = 0;

            foreach ($targetProducts as $product) {
                $sku = $product->sku;
                if (empty($sku)) continue;

                $matchedChildIds = [];
                foreach ($singleProducts as $single) {
                    if ($single->id === $product->id) continue;
                    if (!empty($single->sku) && str_contains($sku, $single->sku)) {
                        $matchedChildIds[] = $single->id;
                    }
                }

                if (!empty($matchedChildIds)) {
                    $product->update(['is_bundle' => true]);

                    $syncData = [];
                    foreach ($matchedChildIds as $childId) {
                        $syncData[$childId] = ['quantity' => 1];
                    }
                    $product->components()->syncWithoutDetaching($syncData);

                    $autoMatchedCount++;
                    $totalComponentsLinked += count($syncData);
                }
            }

            $log[] = "🔗 Auto-Bundle Matching: Berhasil mencocokkan {$autoMatchedCount} produk Set dengan total {$totalComponentsLinked} komponen single terhubung.";

            // 2. Rekalkulasi dan update stok fisik di master_products ERP
            $sampleLogs = [];
            $updatedCount = MasterProduct::recalculateAllBundleStocks($tenantId, $sampleLogs);

            $log[] = "✅ SELESAI! Berhasil menghitung ulang & memperbarui stok untuk {$updatedCount} produk Set/Bundle di ERP.";
            if (!empty($sampleLogs)) {
                $log[] = "\n📋 Sampel Hasil Update Stok Bundle ERP:";
                foreach ($sampleLogs as $sLog) {
                    $log[] = $sLog;
                }
                $log[] = "";
            }
            $log[] = "📌 Stok produk Set/Bundle di ERP kini 100% selaras dengan ketersediaan stok komponen single-nya.";
            $log[] = "💡 Catatan: Perubahan stok ini khusus internal ERP. Pendorongan ke toko marketplace berjalan otomatis via Scheduler.";
        } catch (\Throwable $e) {
            $log[] = "❌ Terjadi kesalahan: " . $e->getMessage();
        }

        $log[] = "======================================================================";
        return implode("\n", $log);
    }

    private function executeSyncOrderDates($dateFrom, $dateTo)
    {
        $tenantId = auth()->user()->tenant_id;

        // Get Shopee and TikTok stores
        $tiktokStores = Store::where('tenant_id', $tenantId)
            ->whereHas('channel', fn($q) => $q->whereIn('code', ['tiktok', 'tokopedia']))
            ->get();
        $shopeeStores = Store::where('tenant_id', $tenantId)
            ->whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%shopee%'))
            ->get();

        $log = [];
        $log[] = "======================================================================";
        $log[] = "🛠️ RE-SINKRONISASI TANGGAL ORDER & TANGGAL CAIR DARI API MARKETPLACE";
        $log[] = "======================================================================";
        $log[] = "Filter Tanggal: " . ($dateFrom ?: 'Semua') . " s/d " . ($dateTo ?: 'Semua');

        $totalTiktok = 0;
        $totalShopee = 0;

        // 1. Process TikTok
        foreach ($tiktokStores as $store) {
            $query = Order::where('store_id', $store->id)
                ->whereNotNull('order_marketplace_id');
            if ($dateFrom) $query->whereDate('order_date', '>=', $dateFrom);
            if ($dateTo)   $query->whereDate('order_date', '<=', $dateTo);

            $orders = $query->get();
            if ($orders->isEmpty()) continue;

            $tiktokService = app(TiktokService::class);
            $orderSns = $orders->pluck('order_marketplace_id')->toArray();
            $chunks = array_chunk($orderSns, 50);

            foreach ($chunks as $chunk) {
                try {
                    $accessToken = $store->getValidAccessToken();
                    $shopCipher = $store->shop_cipher;
                    $detailRes = $tiktokService->getOrderDetail($accessToken, $shopCipher, $chunk);
                    $ordersList = $detailRes['orders'] ?? ($detailRes['order_list'] ?? []);

                    foreach ($ordersList as $tiktokOrder) {
                        $orderSn = $tiktokOrder['order_id'] ?? null;
                        if (!$orderSn) continue;

                        $dbOrder = $orders->firstWhere('order_marketplace_id', $orderSn);
                        if (!$dbOrder) continue;

                        $isChanged = false;

                        // Correct order_date (create_time)
                        if (!empty($tiktokOrder['create_time'])) {
                            $createTime = $tiktokOrder['create_time'];
                            $createTsSec = (is_numeric($createTime) && strlen((string)$createTime) >= 13) ? (int)($createTime / 1000) : (int)$createTime;
                            $orderDateTime = date('Y-m-d H:i:s', $createTsSec);
                            if ($dbOrder->order_date != $orderDateTime) {
                                $oldDate = $dbOrder->order_date;
                                $dbOrder->order_date = $orderDateTime;
                                $isChanged = true;
                                $log[] = "   [TikTok] Order #{$orderSn}: Tanggal Order dikoreksi dari {$oldDate} => {$orderDateTime}";
                            }
                        }

                        // Correct completed_at (finish_time, delivered_time, etc.)
                        $erpStatus = $dbOrder->order_status;
                        $isCompleted = in_array($erpStatus, ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED']);
                        if ($isCompleted) {
                            $createTime = $tiktokOrder['create_time'] ?? time();
                            $ts = $tiktokOrder['finish_time'] ?? $tiktokOrder['delivered_time'] ?? $tiktokOrder['complete_time'] ?? $tiktokOrder['delivery_time'] ?? $tiktokOrder['update_time'] ?? $tiktokOrder['paid_time'] ?? $createTime;
                            $tsSec = (is_numeric($ts) && strlen((string)$ts) >= 13) ? (int)($ts / 1000) : (int)$ts;
                            $compTime = date('Y-m-d H:i:s', $tsSec);
                            
                            if ($dbOrder->completed_at != $compTime) {
                                $oldComp = $dbOrder->completed_at;
                                $dbOrder->completed_at = $compTime;
                                $isChanged = true;
                                $log[] = "   [TikTok] Order #{$orderSn}: Tanggal Selesai/Cair dikoreksi dari " . ($oldComp ?: 'NULL') . " => {$compTime}";
                            }
                        }

                        if ($isChanged) {
                            $dbOrder->save();
                            $totalTiktok++;
                        }
                    }
                } catch (\Exception $ex) {
                    $log[] = "⚠️ Error sinkronisasi TikTok Store {$store->store_name}: " . $ex->getMessage();
                }
            }
        }

        // 2. Process Shopee
        foreach ($shopeeStores as $store) {
            $query = Order::where('store_id', $store->id)
                ->whereNotNull('order_marketplace_id');
            if ($dateFrom) $query->whereDate('order_date', '>=', $dateFrom);
            if ($dateTo)   $query->whereDate('order_date', '<=', $dateTo);

            $orders = $query->get();
            if ($orders->isEmpty()) continue;

            $shopeeService = app(ShopeeService::class);
            $accessToken = $store->getValidAccessToken();
            $orderSns = $orders->pluck('order_marketplace_id')->toArray();
            $chunks = array_chunk($orderSns, 50);

            foreach ($chunks as $chunk) {
                try {
                    $detailRes = $shopeeService->getOrderDetail(
                        $accessToken,
                        (int) $store->marketplace_store_id,
                        $chunk
                    );

                    $ordersList = $detailRes['order_list'] ?? [];
                    foreach ($ordersList as $shopeeOrder) {
                        $orderSn = $shopeeOrder['order_sn'] ?? null;
                        if (!$orderSn) continue;

                        $dbOrder = $orders->firstWhere('order_marketplace_id', $orderSn);
                        if (!$dbOrder) continue;

                        $isChanged = false;

                        // Correct order_date (create_time)
                        if (!empty($shopeeOrder['create_time'])) {
                            $createTime = date('Y-m-d H:i:s', $shopeeOrder['create_time']);
                            if ($dbOrder->order_date != $createTime) {
                                $oldDate = $dbOrder->order_date;
                                $dbOrder->order_date = $createTime;
                                $isChanged = true;
                                $log[] = "   [Shopee] Order #{$orderSn}: Tanggal Order dikoreksi dari {$oldDate} => {$createTime}";
                            }
                        }

                        // Correct completed_at (update_time for completed status)
                        $erpStatus = $dbOrder->order_status;
                        $isCompleted = in_array($erpStatus, ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED']);
                        if ($isCompleted) {
                            if (!empty($shopeeOrder['update_time'])) {
                                $compTime = date('Y-m-d H:i:s', $shopeeOrder['update_time']);
                                if ($dbOrder->completed_at != $compTime) {
                                    $oldComp = $dbOrder->completed_at;
                                    $dbOrder->completed_at = $compTime;
                                    $isChanged = true;
                                    $log[] = "   [Shopee] Order #{$orderSn}: Tanggal Selesai/Cair dikoreksi dari " . ($oldComp ?: 'NULL') . " => {$compTime}";
                                }
                            }
                        }

                        if ($isChanged) {
                            $dbOrder->save();
                            $totalShopee++;
                        }
                    }
                } catch (\Exception $ex) {
                    $log[] = "⚠️ Error sinkronisasi Shopee Store {$store->store_name}: " . $ex->getMessage();
                }
            }
        }

        $log[] = "======================================================================";
        $log[] = "✨ SELESAI! Berhasil mengoreksi tanggal untuk:";
        $log[] = "   -> {$totalTiktok} pesanan TikTok Shop";
        $log[] = "   -> {$totalShopee} pesanan Shopee";
        $log[] = "======================================================================";

        return implode("\n", $log);
    }

    /**
     * AJAX: Ambil data perbandingan ERP vs API (financial_breakdown) per channel dengan filter tanggal
     */
    /**
     * Helper: Ekstrak metrik ERP dan API (Omset/Harga Jual, Biaya Admin, Dana Cair) secara presisi
     * Selaras 100% dengan logika perintah `shopee:sync-escrow` dan `tiktok:sync-escrow`
     */
    private function parseOrderFinancials(Order $ord, bool $isShopee): array
    {
        $fb = $ord->financial_breakdown;
        if (is_string($fb)) {
            $fb = json_decode($fb, true);
        }
        $hasFb = is_array($fb) && !empty($fb);

        // ── 1. DATA ERP (Nilai yang tersimpan di ERP) ──
        $erpOmset = (float) $ord->total_amount;
        $erpFee   = (float) $ord->marketplace_fee;
        $erpNet   = (float) $ord->net_amount;

        if (!$hasFb) {
            return [
                'has_fb'     => false,
                'erp_omset'  => $erpOmset,
                'erp_fee'    => $erpFee,
                'erp_net'    => $erpNet,
                'erp_refund' => 0.0,
                'api_omset'  => null,
                'api_fee'    => null,
                'api_net'    => null,
                'api_refund' => null,
            ];
        }

        // Normalisasi order_income Shopee jika dibungkus
        $inc = (isset($fb['order_income']) && is_array($fb['order_income']))
            ? array_merge($fb, $fb['order_income'])
            : $fb;

        // Statement TikTok jika ada
        $stmtList = $inc['statement_transactions'] ?? $inc['statement_transaction_list'] ?? $inc['transactions'] ?? [];
        $st0 = (is_array($stmtList) && !empty($stmtList[0]) && is_array($stmtList[0])) ? $stmtList[0] : [];

        // ── 2. OMSET API (HARGA JUAL SETELAH DISKON PENJUAL / NET SALES) ──
        $apiOmset = 0.0;
        if ($isShopee) {
            $sellerDisc = (float)($inc['voucher_from_seller'] ?? $inc['seller_discount'] ?? $inc['seller_discount_amount'] ?? 0);
            if (isset($inc['original_cost_of_goods_sold']) && (float)$inc['original_cost_of_goods_sold'] > 0) {
                $apiOmset = (float)$inc['original_cost_of_goods_sold'];
            } elseif (isset($inc['order_selling_price']) && (float)$inc['order_selling_price'] > 0) {
                $apiOmset = (float)$inc['order_selling_price'];
            } elseif (isset($inc['cost_of_goods_sold']) && (float)$inc['cost_of_goods_sold'] > 0) {
                $cogs = (float)$inc['cost_of_goods_sold'];
                $apiOmset = ($sellerDisc > 0 && $cogs > $sellerDisc) ? max(0.0, $cogs - $sellerDisc) : $cogs;
            } elseif (isset($inc['order_original_price']) && (float)$inc['order_original_price'] > 0) {
                $orig = (float)$inc['order_original_price'];
                $apiOmset = ($sellerDisc > 0 && $orig > $sellerDisc) ? max(0.0, $orig - $sellerDisc) : $orig;
            } else {
                $apiOmset = $erpOmset;
            }
        } else {
            // TikTok
            $sellerDisc = (float)($inc['seller_discount'] ?? $inc['voucher_from_seller'] ?? $inc['discount_amount'] ?? 0);
            if (isset($inc['subtotal_after_seller_discounts']) && (float)$inc['subtotal_after_seller_discounts'] > 0) {
                $apiOmset = (float)$inc['subtotal_after_seller_discounts'];
            } elseif (isset($st0['net_sales_amount']) && (float)$st0['net_sales_amount'] > 0) {
                $apiOmset = (float)$st0['net_sales_amount'];
            } elseif (isset($st0['revenue_amount']) && (float)$st0['revenue_amount'] > 0) {
                $apiOmset = (float)$st0['revenue_amount'];
            } elseif (isset($inc['original_total_product_price']) && (float)$inc['original_total_product_price'] > 0) {
                $orig = (float)$inc['original_total_product_price'];
                $apiOmset = ($sellerDisc > 0 && $orig > $sellerDisc) ? max(0.0, $orig - $sellerDisc) : $orig;
            } else {
                $apiOmset = $erpOmset;
            }
        }

        if ($apiOmset <= 0) {
            $apiOmset = $erpOmset;
        }

        // ── 3. DANA CAIR API (ESCROW / SETTLEMENT RESMI SEPERTI DI SYNC-ESCROW) ──
        $apiNet = 0.0;
        $hasEscrowApi = false;
        if (isset($inc['escrow_amount_after_adjustment'])) {
            $apiNet = (float) $inc['escrow_amount_after_adjustment'];
            $hasEscrowApi = true;
        } elseif (isset($inc['escrow_amount']) && (float)$inc['escrow_amount'] > 0) {
            $apiNet = (float) $inc['escrow_amount'];
            $hasEscrowApi = true;
        } elseif (isset($st0['settlement_amount']) && (float)$st0['settlement_amount'] > 0) {
            $apiNet = (float) $st0['settlement_amount'];
            $hasEscrowApi = true;
        } elseif (isset($inc['settlement_amount']) && (float)$inc['settlement_amount'] > 0) {
            $apiNet = (float) $inc['settlement_amount'];
            $hasEscrowApi = true;
        } elseif (isset($inc['seller_settlement_amount']) && (float)$inc['seller_settlement_amount'] > 0) {
            $apiNet = (float) $inc['seller_settlement_amount'];
            $hasEscrowApi = true;
        }

        // ── 4. BIAYA ADMIN API (PERSIS SAMA DENGAN SYNC-ESCROW) ──
        $apiFee = 0.0;
        if ($isShopee) {
            $feeDetails = $ord->fee_breakdown_details;
            $totalFeeCalc = abs((float)($feeDetails['total_fee'] ?? 0));

            if ($totalFeeCalc > 0) {
                $apiFee = $totalFeeCalc;
            } elseif ($apiOmset > 0 && $apiNet > 0 && $apiOmset > $apiNet) {
                $apiFee = max(0.0, $apiOmset - $apiNet);
            } else {
                $apiFee = $erpFee;
            }
        } else {
            // TikTok
            if (!empty($st0['fee_amount']) && (float)$st0['fee_amount'] != 0) {
                $apiFee = abs((float)$st0['fee_amount']);
            } else {
                // Sum estimated TikTok fees from financial_breakdown keys (payment_info side)
                $platComm = (float) ($inc['platform_commission'] ?? $inc['commission_before_discount'] ?? 0);
                $platCommDisc = (float) ($inc['platform_commission_discount'] ?? 0);
                $netPlatComm = (float) ($inc['net_platform_commission'] ?? ($platComm > 0 ? max(0.0, $platComm - $platCommDisc) : 0));

                $preorder = (float) ($inc['preorder_service_fee'] ?? 0);
                $dynComm  = (float) ($inc['dynamic_commission'] ?? $inc['affiliate_commission'] ?? 0);
                $growth   = (float) ($inc['growth_xtra_fee'] ?? 0);
                $procFee  = (float) ($inc['order_processing_fee'] ?? $inc['transaction_fee'] ?? $inc['seller_transaction_fee'] ?? 0);
                $protFee  = (float) ($inc['shipping_seller_protection_fee_amount'] ?? $inc['protection_fee'] ?? 0);

                $totalEstFees = $netPlatComm + $preorder + $dynComm + $growth + $procFee + $protFee;

                if ($totalEstFees > 0) {
                    $apiFee = $totalEstFees;
                } elseif ($apiOmset > 0 && $apiNet > 0 && $apiOmset > $apiNet) {
                    $apiFee = max(0.0, $apiOmset - $apiNet);
                } else {
                    $feeDetails = $ord->fee_breakdown_details;
                    $totalFeeCalc = abs((float)($feeDetails['total_fee'] ?? 0));
                    $apiFee = $totalFeeCalc > 0 ? $totalFeeCalc : $erpFee;
                }
            }
        }

        // ── 5. REFUND / PENGEMBALIAN DANA ──
        // ERP Refund: ambil dari financial_breakdown refund keys
        $erpRefund = 0.0;
        $erpRefundKeys = ['customer_refund_amount', 'gross_sales_refund_amount', 'seller_return_refund', 'buyer_return_refund_amount', 'refund_amount', 'return_amount', 'customer_order_refund_amount', 'total_adjustment_amount'];
        foreach ($erpRefundKeys as $rk) {
            if (!empty($inc[$rk]) && (float)$inc[$rk] != 0) {
                $erpRefund = abs((float)$inc[$rk]);
                break;
            }
        }
        // Cek juga sub-array statement_transactions TikTok
        if ($erpRefund <= 0 && is_array($stmtList)) {
            foreach ($stmtList as $stRow) {
                if (!is_array($stRow)) continue;
                foreach ($erpRefundKeys as $rk) {
                    if (!empty($stRow[$rk]) && (float)$stRow[$rk] != 0) {
                        $erpRefund = abs((float)$stRow[$rk]);
                        break 2;
                    }
                }
            }
        }
        // Jika status RETURN, refund = omset penuh
        if ($erpRefund <= 0 && in_array(strtoupper($ord->order_status), ['RETURN','RETURNED','REFUNDED','REFUND'])) {
            $erpRefund = $erpOmset;
        }

        // API Refund: dari field refund di financial_breakdown (API side)
        $apiRefund = 0.0;
        $apiRefundKeys = ['customer_refund_amount', 'buyer_return_refund_amount', 'return_amount', 'refund_amount', 'seller_return_refund', 'total_adjustment_amount'];
        foreach ($apiRefundKeys as $rk) {
            if (isset($inc[$rk]) && (float)$inc[$rk] != 0) {
                $apiRefund = abs((float)$inc[$rk]);
                break;
            }
        }
        if ($apiRefund <= 0 && !empty($st0)) {
            foreach ($apiRefundKeys as $rk) {
                if (!empty($st0[$rk]) && (float)$st0[$rk] != 0) {
                    $apiRefund = abs((float)$st0[$rk]);
                    break;
                }
            }
        }

        $rawEscrow = (float) ($inc['escrow_amount'] ?? 0);
        $originalApiNet = $apiNet;
        if ($apiNet > 0 && $apiRefund > 0) {
            if ($apiNet == $rawEscrow || !isset($inc['escrow_amount_after_adjustment'])) {
                $apiNet = $apiNet - $apiRefund;
            }
        }

        if (!$hasEscrowApi && $originalApiNet <= 0) {
            if ($apiRefund >= $apiOmset && $apiOmset > 0) {
                $apiNet = 0.0;
            } else {
                $apiNet = max(0.0, $apiOmset - $apiFee);
            }
        }


        return [
            'has_fb'     => true,
            'erp_omset'  => $erpOmset,
            'erp_fee'    => $erpFee,
            'erp_net'    => $erpNet,
            'erp_refund' => $erpRefund,
            'api_omset'  => $apiOmset,
            'api_fee'    => $apiFee,
            'api_net'    => $apiNet,
            'api_refund' => $apiRefund,
        ];
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

        $tiktokStores = Store::whereHas('channel', fn($q) => $q->whereIn('code', ['tiktok', 'tokopedia']))->pluck('id');
        $shopeeStores = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%shopee%'))->pluck('id');
        $notCancelled = ['CANCELLED', 'BATAL', 'CANCELED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'TO_RETURN'];

        // Helper: hitung stats ERP + API dari sekumpulan order
        $calcStats = function ($storeIds) use ($applyDateFilter, $notCancelled, $shopeeStores) {
            $q = Order::whereIn('store_id', $storeIds);
            $applyDateFilter($q);

            $erpCount = (clone $q)->whereNotIn('order_status', $notCancelled)->count();
            $active   = (clone $q)->whereNotIn('order_status', $notCancelled);

            $erpOmset = $erpFee = $erpNet = 0.0;
            $apiOmset = $apiFee = $apiNet = 0.0;
            $apiCount = 0;

            (clone $active)->select(['id', 'store_id', 'total_amount', 'discount_amount', 'marketplace_fee', 'net_amount', 'financial_breakdown'])
                ->chunk(500, function ($orders) use (&$erpOmset, &$erpFee, &$erpNet, &$apiOmset, &$apiFee, &$apiNet, &$apiCount, $shopeeStores) {
                    foreach ($orders as $ord) {
                        $isShopee = $shopeeStores->contains($ord->store_id);
                        $fin = $this->parseOrderFinancials($ord, $isShopee);

                        $erpOmset += $fin['erp_omset'];
                        $erpFee   += $fin['erp_fee'];
                        $erpNet   += $fin['erp_net'];

                        if ($fin['has_fb']) {
                            $apiOmset += $fin['api_omset'];
                            $apiFee   += $fin['api_fee'];
                            $apiNet   += $fin['api_net'];
                            $apiCount++;
                        }
                    }
                });

            return [
                'erp_count'  => $erpCount,
                'erp_omset'  => (float) $erpOmset,
                'erp_fee'    => (float) $erpFee,
                'erp_net'    => (float) $erpNet,
                'api_count'  => $apiCount,
                'api_omset'  => (float) $apiOmset,
                'api_fee'    => (float) $apiFee,
                'api_net'    => (float) $apiNet,
                'diff_omset' => (float) $erpOmset - $apiOmset,
                'diff_fee'   => (float) $erpFee   - $apiFee,
                'diff_net'   => (float) $erpNet    - $apiNet,
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

            $isShopee = $shopeeStores->contains($st->id);

            $sErpOmset = $sErpFee = $sErpNet = 0.0;
            $sApiOmset = $sApiFee = $sApiNet = 0.0;

            (clone $active)->select(['id', 'store_id', 'total_amount', 'discount_amount', 'marketplace_fee', 'net_amount', 'financial_breakdown'])
                ->chunk(300, function ($orders) use (&$sErpOmset, &$sErpFee, &$sErpNet, &$sApiOmset, &$sApiFee, &$sApiNet, $isShopee) {
                    foreach ($orders as $ord) {
                        $fin = $this->parseOrderFinancials($ord, $isShopee);
                        $sErpOmset += $fin['erp_omset'];
                        $sErpFee   += $fin['erp_fee'];
                        $sErpNet   += $fin['erp_net'];

                        if ($fin['has_fb']) {
                            $sApiOmset += $fin['api_omset'];
                            $sApiFee   += $fin['api_fee'];
                            $sApiNet   += $fin['api_net'];
                        }
                    }
                });

            $storeRows[] = [
                'store_name'    => $st->store_name,
                'channel'       => strtolower($st->channel->code ?? ''),
                'erp_count'     => $count,
                'erp_cancelled' => $cancel,
                'erp_omset'     => (float) $sErpOmset,
                'erp_fee'       => (float) $sErpFee,
                'erp_net'       => (float) $sErpNet,
                'api_omset'     => (float) $sApiOmset,
                'api_fee'       => (float) $sApiFee,
                'api_net'       => (float) $sApiNet,
                'diff_omset'    => (float) $sErpOmset - $sApiOmset,
                'diff_fee'      => (float) $sErpFee - $sApiFee,
                'diff_net'      => (float) $sErpNet - $sApiNet,
            ];
        }

        $tiktok = $calcStats($tiktokStores);
        $shopee = $calcStats($shopeeStores);

        $allStoreIds = $tiktokStores->merge($shopeeStores);
        $total = $calcStats($allStoreIds);

        // Overall totals with date filter applied
        $qAll = Order::query()->whereNotIn('order_status', $notCancelled);
        $applyDateFilter($qAll);
        $totalErpAll = $qAll->count();

        $qApi = Order::whereNotNull('financial_breakdown')->whereNotIn('order_status', $notCancelled);
        $applyDateFilter($qApi);
        $totalApiAll = $qApi->count();

        $qMissing = Order::whereDoesntHave('items')
            ->whereNotNull('order_marketplace_id')
            ->where('order_marketplace_id', 'NOT LIKE', 'MANUAL-%')
            ->where('order_marketplace_id', 'NOT LIKE', 'SHOPEE-DEMO-%')
            ->where('order_marketplace_id', 'NOT LIKE', 'DS-%')
            ->whereNotIn('order_status', $notCancelled);
        $applyDateFilter($qMissing);
        $missingItemsCountAll = $qMissing->count();

        $qDup = \DB::table('orders')
            ->select(\DB::raw('TRIM(order_marketplace_id) as mp_id'))
            ->whereNotNull('order_marketplace_id')
            ->where('order_marketplace_id', '!=', '')
            ->where('tenant_id', auth()->user()->tenant_id);
        if ($dateFrom) $qDup->whereDate('order_date', '>=', $dateFrom);
        if ($dateTo)   $qDup->whereDate('order_date', '<=', $dateTo);
        $duplicateOrdersCountAll = $qDup->groupBy(\DB::raw('TRIM(order_marketplace_id)'))
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->count();

        return response()->json([
            'date_from' => $dateFrom ?: 'Semua waktu',
            'date_to'   => $dateTo   ?: 'Semua waktu',
            'tiktok'    => $tiktok,
            'shopee'    => $shopee,
            'total'     => $total,
            'stores'    => $storeRows,
            'erp_count_all' => $totalErpAll,
            'api_count_all' => $totalApiAll,
            'missing_items_count_all' => $missingItemsCountAll,
            'duplicate_orders_count_all' => $duplicateOrdersCountAll,
        ]);
    }

    public function compareStatsCompleted(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $notCancelled = ['CANCELLED', 'BATAL', 'CANCELED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'TO_RETURN'];

        $applyDateFilter = function ($query) use ($dateFrom, $dateTo) {
            $query->whereIn('order_status', ['COMPLETED', 'SELESAI', 'FINISHED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR']);
            if ($dateFrom) $query->whereDate('completed_at', '>=', $dateFrom);
            if ($dateTo)   $query->whereDate('completed_at', '<=', $dateTo);
            return $query;
        };

        $tiktokStores = Store::whereHas('channel', fn($q) => $q->whereIn('code', ['tiktok', 'tokopedia']))->pluck('id');
        $shopeeStores = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%shopee%'))->pluck('id');

        // Helper: hitung stats ERP + API dari sekumpulan order
        $calcStats = function ($storeIds) use ($applyDateFilter, $shopeeStores) {
            $q = Order::with('returnOrder')->whereIn('store_id', $storeIds);
            $applyDateFilter($q);

            $erpCount = 0;
            $erpOmset = $erpFee = $erpNet = 0.0;
            $apiOmset = $apiFee = $apiNet = 0.0;
            $apiCount = 0;

            $q->select(['id', 'store_id', 'total_amount', 'discount_amount', 'marketplace_fee', 'net_amount', 'financial_breakdown', 'order_status'])
                ->chunk(500, function ($orders) use (&$erpOmset, &$erpFee, &$erpNet, &$apiOmset, &$apiFee, &$apiNet, &$apiCount, &$erpCount, $shopeeStores) {
                    foreach ($orders as $ord) {
                        $isShopee = $shopeeStores->contains($ord->store_id);
                        $fin = $this->parseOrderFinancials($ord, $isShopee);

                        $erpOmset += $fin['erp_omset'];
                        $erpFee   += $fin['erp_fee'];
                        $erpNet   += $fin['erp_net'];
                        $erpCount++;

                        if ($fin['has_fb']) {
                            $apiOmset += $fin['api_omset'];
                            $apiFee   += $fin['api_fee'];
                            $apiNet   += $fin['api_net'];
                            $apiCount++;
                        }
                    }
                });

            return [
                'erp_count'  => $erpCount,
                'erp_omset'  => (float) $erpOmset,
                'erp_fee'    => (float) $erpFee,
                'erp_net'    => (float) $erpNet,
                'api_count'  => $apiCount,
                'api_omset'  => (float) $apiOmset,
                'api_fee'    => (float) $apiFee,
                'api_net'    => (float) $apiNet,
                'diff_omset' => (float) $erpOmset - $apiOmset,
                'diff_fee'   => (float) $erpFee   - $apiFee,
                'diff_net'   => (float) $erpNet    - $apiNet,
            ];
        };

        // ── Per Store Breakdown ───────────────────────────────────────────────
        $allStores = Store::with('channel')
            ->whereIn('id', $tiktokStores->merge($shopeeStores))
            ->get();

        foreach ($allStores as $st) {
            $sq = Order::with('returnOrder')->where('store_id', $st->id);
            $applyDateFilter($sq);

            $count  = 0;
            $cancel = 0;
            $isShopee = $shopeeStores->contains($st->id);

            $sErpOmset = $sErpFee = $sErpNet = 0.0;
            $sApiOmset = $sApiFee = $sApiNet = 0.0;

            $sq->select(['id', 'store_id', 'total_amount', 'discount_amount', 'marketplace_fee', 'net_amount', 'financial_breakdown', 'order_status'])
                ->chunk(300, function ($orders) use (&$sErpOmset, &$sErpFee, &$sErpNet, &$sApiOmset, &$sApiFee, &$sApiNet, &$count, $isShopee) {
                    foreach ($orders as $ord) {
                        if ($ord->refund_amount > 0) {
                            continue;
                        }
                        $fin = $this->parseOrderFinancials($ord, $isShopee);
                        $sErpOmset += $fin['erp_omset'];
                        $sErpFee   += $fin['erp_fee'];
                        $sErpNet   += $fin['erp_net'];
                        $count++;

                        if ($fin['has_fb']) {
                            $sApiOmset += $fin['api_omset'];
                            $sApiFee   += $fin['api_fee'];
                            $sApiNet   += $fin['api_net'];
                        }
                    }
                });

            $storeRows[] = [
                'store_name'    => $st->store_name,
                'channel'       => strtolower($st->channel->code ?? ''),
                'erp_count'     => $count,
                'erp_cancelled' => $cancel,
                'erp_omset'     => (float) $sErpOmset,
                'erp_fee'       => (float) $sErpFee,
                'erp_net'       => (float) $sErpNet,
                'api_omset'     => (float) $sApiOmset,
                'api_fee'       => (float) $sApiFee,
                'api_net'       => (float) $sApiNet,
                'diff_omset'    => (float) $sErpOmset - $sApiOmset,
                'diff_fee'      => (float) $sErpFee - $sApiFee,
                'diff_net'      => (float) $sErpNet - $sApiNet,
            ];
        }

        $tiktok = $calcStats($tiktokStores);
        $shopee = $calcStats($shopeeStores);

        $allStoreIds = $tiktokStores->merge($shopeeStores);
        $total = $calcStats($allStoreIds);

        // Overall totals with date filter applied
        $qAll = Order::with('returnOrder');
        $applyDateFilter($qAll);
        $totalErpAll = 0;
        $qAll->select(['id', 'order_status', 'financial_breakdown'])->chunk(500, function ($orders) use (&$totalErpAll) {
            foreach ($orders as $ord) {
                if ($ord->refund_amount > 0) continue;
                $totalErpAll++;
            }
        });

        $qApi = Order::with('returnOrder')->whereNotNull('financial_breakdown');
        $applyDateFilter($qApi);
        $totalApiAll = 0;
        $qApi->select(['id', 'order_status', 'financial_breakdown'])->chunk(500, function ($orders) use (&$totalApiAll) {
            foreach ($orders as $ord) {
                if ($ord->refund_amount > 0) continue;
                $totalApiAll++;
            }
        });

        $qMissing = Order::with('returnOrder')->whereDoesntHave('items')
            ->whereNotNull('order_marketplace_id')
            ->where('order_marketplace_id', 'NOT LIKE', 'MANUAL-%')
            ->where('order_marketplace_id', 'NOT LIKE', 'SHOPEE-DEMO-%')
            ->where('order_marketplace_id', 'NOT LIKE', 'DS-%');
        $applyDateFilter($qMissing);
        $missingItemsCountAll = 0;
        $qMissing->select(['id', 'order_status', 'financial_breakdown'])->chunk(500, function ($orders) use (&$missingItemsCountAll) {
            foreach ($orders as $ord) {
                if ($ord->refund_amount > 0) continue;
                $missingItemsCountAll++;
            }
        });

        $qDup = Order::with('returnOrder')
            ->select(['id', 'order_marketplace_id', 'completed_at', 'order_status', 'financial_breakdown'])
            ->whereNotNull('order_marketplace_id')
            ->where('order_marketplace_id', '!=', '')
            ->where('tenant_id', auth()->user()->tenant_id);
        $applyDateFilter($qDup);

        $mpIds = [];
        $qDup->chunk(500, function ($orders) use (&$mpIds) {
            foreach ($orders as $ord) {
                if ($ord->refund_amount > 0) continue;
                $mpId = trim($ord->order_marketplace_id);
                if (!isset($mpIds[$mpId])) {
                    $mpIds[$mpId] = 0;
                }
                $mpIds[$mpId]++;
            }
        });
        $duplicateOrdersCountAll = count(array_filter($mpIds, fn($cnt) => $cnt > 1));

        return response()->json([
            'date_from' => $dateFrom ?: 'Semua waktu',
            'date_to'   => $dateTo   ?: 'Semua waktu',
            'tiktok'    => $tiktok,
            'shopee'    => $shopee,
            'total'     => $total,
            'stores'    => $storeRows,
            'erp_count_all' => $totalErpAll,
            'api_count_all' => $totalApiAll,
            'missing_items_count_all' => $missingItemsCountAll,
            'duplicate_orders_count_all' => $duplicateOrdersCountAll,
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

        $shopeeStores = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%shopee%'))->pluck('id');

        $tiktokStores = Store::whereHas('channel', fn($q) => $q->whereIn('code', ['tiktok', 'tokopedia']))->pluck('id');

        // Resolve store IDs
        if ($storeId) {
            $storeIds = collect([$storeId]);
        } elseif ($channel === 'shopee') {
            $storeIds = $shopeeStores;
        } elseif ($channel === 'tiktok') {
            $storeIds = $tiktokStores;
        } else {
            $storeIds = $tiktokStores->merge($shopeeStores);
        }

        $notCancelled = ['CANCELLED', 'BATAL', 'CANCELED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'TO_RETURN'];
        $status   = $request->input('status', 'all'); // all | READY_TO_SHIP | SHIPPED | DELIVERED | COMPLETED | CANCELLED | etc.

        $query = Order::with(['store', 'items', 'returnOrder'])
            ->whereIn('store_id', $storeIds);

        $filterType = $request->input('filter_type', 'order_date');

        if ($filterType === 'completed_at') {
            $query->whereIn('order_status', ['COMPLETED', 'SELESAI', 'FINISHED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR']);
            if ($dateFrom) $query->whereDate('completed_at', '>=', $dateFrom);
            if ($dateTo)   $query->whereDate('completed_at', '<=', $dateTo);
            $query->orderBy('completed_at', 'desc');
        } else {
            if ($status !== 'all') {
                if ($status === 'CANCELLED') {
                    $query->whereIn('order_status', $notCancelled);
                } else {
                    $query->where('order_status', $status);
                }
            } else {
                $query->whereNotIn('order_status', $notCancelled);
            }
            if ($dateFrom) $query->whereDate('order_date', '>=', $dateFrom);
            if ($dateTo)   $query->whereDate('order_date', '<=', $dateTo);
            $query->orderBy('order_date', 'desc');
        }

        $allOrders = $query->get(['id', 'order_marketplace_id', 'order_date', 'completed_at', 'order_status',
            'total_amount', 'discount_amount', 'marketplace_fee', 'net_amount', 'financial_breakdown',
            'store_id', 'buyer_name', 'shipping_fee']);

        $rowsAll = [];
        foreach ($allOrders as $ord) {
            $isShopee = $shopeeStores->contains($ord->store_id);
            $fin = $this->parseOrderFinancials($ord, $isShopee);

            $diffOmset   = $fin['has_fb'] ? (float)$fin['erp_omset'] - (float)$fin['api_omset'] : null;
            $diffFee     = $fin['has_fb'] ? (float)$fin['erp_fee']   - (float)$fin['api_fee']   : null;
            $diffNet     = $fin['has_fb'] ? (float)$fin['erp_net']   - (float)$fin['api_net']   : null;
            $diffRefund  = $fin['has_fb'] ? (float)$fin['erp_refund']- (float)$fin['api_refund']: null;

            $isMismatch = $fin['has_fb'] && (
                abs($diffNet)  > 100 ||
                abs($diffOmset) > 100 ||
                abs($diffFee)  > 100 ||
                abs($diffRefund) > 100
            );

            $rowsAll[] = [
                'id'               => $ord->id,
                'marketplace_id'   => $ord->order_marketplace_id,
                'order_date'       => $ord->order_date,
                'completed_at'     => $ord->completed_at ? $ord->completed_at->format('Y-m-d H:i:s') : null,
                'order_status'     => $ord->order_status,
                'store_name'       => $ord->store->store_name ?? '-',
                'buyer_name'       => $ord->buyer_name,
                'erp_omset'        => (float)$fin['erp_omset'],
                'erp_fee'          => (float)$fin['erp_fee'],
                'erp_net'          => (float)$fin['erp_net'],
                'erp_refund'       => (float)$fin['erp_refund'],
                'api_omset'        => $fin['api_omset'],
                'api_fee'          => $fin['api_fee'],
                'api_net'          => $fin['api_net'],
                'api_refund'       => $fin['api_refund'],
                'diff_omset'       => $diffOmset,
                'diff_fee'         => $diffFee,
                'diff_net'         => $diffNet,
                'diff_refund'      => $diffRefund,
                'has_fb'           => $fin['has_fb'],
                'is_mismatch'      => $isMismatch,
            ];
        }

        // Hitung total data global untuk stat cards sebelum difilter
        $totalRowsGlobal    = count($rowsAll);
        $mismatchRowsGlobal = count(array_filter($rowsAll, fn($r) => $r['is_mismatch']));
        $noFbRowsGlobal     = count(array_filter($rowsAll, fn($r) => !$r['has_fb']));
        $matchRowsGlobal    = $totalRowsGlobal - $mismatchRowsGlobal - $noFbRowsGlobal;

        // Terapkan filter list ke rows yang ditampilkan
        $rows = [];
        foreach ($rowsAll as $row) {
            if ($filter === 'mismatch' && !$row['is_mismatch']) continue;
            if ($filter === 'no_api' && $row['has_fb']) continue;
            if ($filter === 'match' && (!$row['has_fb'] || $row['is_mismatch'])) continue;
            $rows[] = $row;
        }

        $stores = Store::whereIn('id', $storeIds)->get(['id', 'store_name']);

        return view('secret_repair_compare_detail', compact(
            'rows', 'channel', 'dateFrom', 'dateTo',
            'filter', 'storeId', 'stores', 'status',
            'totalRowsGlobal', 'mismatchRowsGlobal', 'noFbRowsGlobal', 'matchRowsGlobal',
            'filterType'
        ));
    }

    /**
     * AJAX: Menjalankan pull order & sync escrow resmi dari API Marketplace untuk 1 ID Order tertentu
     */
    public function syncSingleOrder(Order $order, Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $orderSn = trim($order->order_marketplace_id);
        if (!$orderSn) {
            return response()->json(['error' => 'Pesanan ini tidak memiliki ID Marketplace.'], 422);
        }

        // Cek jika pesanan adalah pesanan manual/offline
        $isManual = str_starts_with($orderSn, 'MANUAL-') || 
                    str_starts_with($orderSn, 'DS-') || 
                    str_starts_with($orderSn, 'SHOPEE-DEMO-');
        if ($isManual) {
            return response()->json([
                'error' => "Pesanan ini adalah Pesanan Manual (Offline/Dropship) yang diinput langsung di ERP. Pesanan manual tidak memiliki data settlement keuangan di API Shopee maupun TikTok Shop.",
            ], 422);
        }

        $store = $order->store;
        if (!$store) {
            return response()->json(['error' => 'Toko tidak ditemukan.'], 404);
        }

        $chCode = strtolower($store->channel->code ?? '');
        $isShopee = str_contains($chCode, 'shopee');
        $isTiktok = str_contains($chCode, 'tiktok') || str_contains($chCode, 'tokopedia');

        // Validasi kecocokan format ID Order dengan Channel Toko
        $isOrderNumeric = ctype_digit($orderSn);
        if ($isTiktok && !$isOrderNumeric) {
            return response()->json([
                'error' => "Format ID Pesanan ({$orderSn}) adalah format Shopee (alphanumeric), sedangkan toko '{$store->store_name}' terdaftar sebagai Toko TikTok/Tokopedia. Harap periksa apakah data store_id pesanan ini tertukar di database.",
            ], 422);
        }
        if ($isShopee && $isOrderNumeric && strlen($orderSn) >= 18) {
            return response()->json([
                'error' => "Format ID Pesanan ({$orderSn}) adalah format TikTok/Tokopedia (pure numeric), sedangkan toko '{$store->store_name}' terdaftar sebagai Toko Shopee. Harap periksa apakah data store_id pesanan ini tertukar di database.",
            ], 422);
        }

        // 1. Coba tarik data live dari API Marketplace (Graceful - tidak gagalkan proses jika token expired)
        $tokenError = null;
        try {
            if ($isTiktok && !empty($store->shop_cipher)) {
                $accessToken = $store->getValidAccessToken();
                $tiktokService = app(\App\Services\TiktokService::class);
                $detailRes = $tiktokService->getOrderDetail($accessToken, $store->shop_cipher, [$orderSn]);
                $tiktokOrders = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];

                if (!empty($tiktokOrders[0])) {
                    $job = new \App\Jobs\PullOrdersFromTiktok($store, time() - 86400, time());
                    $job->store = $store;
                    $reflection = new \ReflectionClass($job);
                    $method = $reflection->getMethod('processOrder');
                    $method->setAccessible(true);
                    $method->invoke($job, $tiktokOrders[0]);
                }

                try {
                    \Artisan::call('tiktok:sync-escrow', [
                        '--order_id' => $orderSn,
                        '--store_id' => $store->id,
                    ]);
                } catch (\Throwable $e) {}
            } elseif ($isShopee && !empty($store->marketplace_store_id)) {
                $accessToken = $store->getValidAccessToken();
                $shopeeService = app(\App\Services\ShopeeService::class);
                $shopId = (int) $store->marketplace_store_id;
                $detailRes = $shopeeService->getOrderDetail($accessToken, $shopId, [$orderSn]);
                $shopeeOrders = $detailRes['order_list'] ?? [];

                if (!empty($shopeeOrders[0])) {
                    $job = new \App\Jobs\PullOrdersFromShopee($store, time() - 86400, time());
                    $job->store = $store;
                    $reflection = new \ReflectionClass($job);
                    $method = $reflection->getMethod('saveOrder');
                    $method->setAccessible(true);
                    $method->invoke($job, $shopeeOrders[0]);
                }

                try {
                    \Artisan::call('shopee:sync-escrow', [
                        '--order_sn' => $orderSn,
                        '--store_id' => $store->id,
                    ]);
                } catch (\Throwable $e) {}
            }
        } catch (\Throwable $liveEx) {
            Log::warning("Live API call notice for {$orderSn}: " . $liveEx->getMessage());
            $tokenError = $liveEx->getMessage();
        }

        // 2. Ekstrak data resmi API dari breakdown (baik dari live API maupun yang tersimpan di DB)
        $order->refresh();
        $fin = $this->parseOrderFinancials($order, $isShopee);

        if ($fin['has_fb']) {
            $targetOmset = ($fin['api_omset'] > 0) ? (float)$fin['api_omset'] : (float)$order->total_amount;
            $targetFee   = ($fin['api_fee'] !== null) ? (float)$fin['api_fee'] : (float)$order->marketplace_fee;
            $targetNet   = ($fin['api_net'] !== null) ? (float)$fin['api_net'] : (float)$order->net_amount;

            // Simpan langsung ke database ERP agar data ERP dan API sama persis (Match)
            \DB::table('orders')->where('id', $order->id)->update([
                'total_amount'        => $targetOmset,
                'marketplace_fee'     => $targetFee,
                'net_amount'          => $targetNet,
                'recon_status'        => 'RECONCILED',
                'financial_breakdown' => is_array($order->financial_breakdown) ? json_encode($order->financial_breakdown) : $order->financial_breakdown,
                'updated_at'          => now(),
            ]);

            // Update items jika single item
            $items = $order->items;
            if ($items->count() === 1 && $targetOmset > 0) {
                $it = $items->first();
                $qty = $it->quantity ?: 1;
                \DB::table('order_items')->where('id', $it->id)->update([
                    'price'       => round($targetOmset / $qty, 2),
                    'total_price' => $targetOmset,
                    'updated_at'  => now(),
                ]);
            }

            Cache::flush();
            $order->refresh();
            $finAfter = $this->parseOrderFinancials($order, $isShopee);

            return response()->json([
                'success'   => true,
                'message'   => "Order {$orderSn} BERHASIL DISINKRONKAN!\nOmset: Rp " . number_format($order->total_amount, 0, ',', '.') . " | Fee: Rp " . number_format($order->marketplace_fee, 0, ',', '.') . " | Dana Cair: Rp " . number_format($order->net_amount, 0, ',', '.'),
                'erp_omset' => (float) $order->total_amount,
                'erp_fee'   => (float) $order->marketplace_fee,
                'erp_net'   => (float) $order->net_amount,
                'api_omset' => $finAfter['api_omset'],
                'api_fee'   => $finAfter['api_fee'],
                'api_net'   => $finAfter['api_net'],
            ]);
        } else {
            if ($tokenError) {
                return response()->json([
                    'error' => "Gagal terhubung dengan API Marketplace: {$tokenError}. Silakan periksa koneksi atau sambungkan ulang toko '{$store->store_name}' di menu Integrasi Toko.",
                ], 422);
            } else {
                return response()->json([
                    'error' => "Toko '{$store->store_name}' terhubung dengan baik, namun Shopee/TikTok belum mencairkan dana / belum menerbitkan rincian settlement keuangan (escrow) untuk pesanan ini di sistem API mereka. Silakan tunggu beberapa saat hingga dana pesanan dilepas ke Saldo Penjual Anda.",
                ], 422);
            }
        }
    }

    /**
     * AJAX: Menjalankan pull order & sync escrow untuk seluruh pesanan Mismatch
     */
    public function syncMismatches(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $channel  = $request->input('channel', 'tiktok');
        $dateFrom = $request->input('date_from');
        $dateTo   = $request->input('date_to');
        $storeId  = $request->input('store_id');

        $shopeeStores = Store::whereHas('channel', fn($q) => $q->where('code', 'LIKE', '%shopee%'))->pluck('id');
        $tiktokStores = Store::whereHas('channel', fn($q) => $q->whereIn('code', ['tiktok', 'tokopedia']))->pluck('id');
        if ($storeId) {
            $storeIds = collect([$storeId]);
        } elseif ($channel === 'shopee') {
            $storeIds = $shopeeStores;
        } elseif ($channel === 'tiktok') {
            $storeIds = $tiktokStores;
        } else {
            $storeIds = $tiktokStores->merge($shopeeStores);
        }

        $notCancelled = ['CANCELLED', 'BATAL', 'CANCELED'];

        $query = Order::with('store')
            ->whereIn('store_id', $storeIds)
            ->whereNotIn('order_status', $notCancelled)
            ->whereNotNull('order_marketplace_id');

        if ($dateFrom) $query->whereDate('order_date', '>=', $dateFrom);
        if ($dateTo)   $query->whereDate('order_date', '<=', $dateTo);

        $orders = $query->get();
        $syncedCount = 0;

        foreach ($orders as $ord) {
            $isShopee = $shopeeStores->contains($ord->store_id);
            $fin = $this->parseOrderFinancials($ord, $isShopee);

            $diffOmset = $fin['has_fb'] ? abs((float)$fin['erp_omset'] - (float)$fin['api_omset']) : 999999;
            $diffFee   = $fin['has_fb'] ? abs((float)$fin['erp_fee'] - (float)$fin['api_fee']) : 999999;
            $diffNet   = $fin['has_fb'] ? abs((float)$fin['erp_net'] - (float)$fin['api_net']) : 999999;

            if (!$fin['has_fb'] || $diffOmset > 100 || $diffFee > 100 || $diffNet > 100) {
                try {
                    $orderSn = trim($ord->order_marketplace_id);
                    $store = $ord->store;
                    if (!$store) continue;

                    // Coba live call jika memungkinkan (graceful)
                    try {
                        if ($isShopee && !empty($store->marketplace_store_id)) {
                            $accessToken = $store->getValidAccessToken();
                            $shopeeService = app(\App\Services\ShopeeService::class);
                            $shopId = (int) $store->marketplace_store_id;
                            $detailRes = $shopeeService->getOrderDetail($accessToken, $shopId, [$orderSn]);
                            $shopeeOrders = $detailRes['order_list'] ?? [];
                            if (!empty($shopeeOrders[0])) {
                                $job = new \App\Jobs\PullOrdersFromShopee($store, time() - 86400, time());
                                $reflection = new \ReflectionClass($job);
                                $method = $reflection->getMethod('saveOrder');
                                $method->setAccessible(true);
                                $method->invoke($job, $shopeeOrders[0]);
                            }
                            try {
                                \Artisan::call('shopee:sync-escrow', ['--order_sn' => $orderSn, '--store_id' => $store->id]);
                            } catch (\Throwable $e) {}
                        } elseif ($isTiktok && !empty($store->shop_cipher)) {
                            $accessToken = $store->getValidAccessToken();
                            $tiktokService = app(\App\Services\TiktokService::class);
                            $detailRes = $tiktokService->getOrderDetail($accessToken, $store->shop_cipher, [$orderSn]);
                            $tiktokOrders = $detailRes['order_list'] ?? $detailRes['orders'] ?? [];
                            if (!empty($tiktokOrders[0])) {
                                $job = new \App\Jobs\PullOrdersFromTiktok($store, time() - 86400, time());
                                $reflection = new \ReflectionClass($job);
                                $method = $reflection->getMethod('processOrder');
                                $method->setAccessible(true);
                                $method->invoke($job, $tiktokOrders[0]);
                            }
                            try {
                                \Artisan::call('tiktok:sync-escrow', ['--order_id' => $orderSn, '--store_id' => $store->id]);
                            } catch (\Throwable $e) {}
                        }
                    } catch (\Throwable $liveEx) {}

                    $ord->refresh();
                    $finUpdated = $this->parseOrderFinancials($ord, $isShopee);

                    if ($finUpdated['has_fb']) {
                        $targetOmset = ($finUpdated['api_omset'] > 0) ? (float)$finUpdated['api_omset'] : (float)$ord->total_amount;
                        $targetFee   = ($finUpdated['api_fee'] !== null) ? (float)$finUpdated['api_fee'] : (float)$ord->marketplace_fee;
                        $targetNet   = ($finUpdated['api_net'] !== null) ? (float)$finUpdated['api_net'] : (float)$ord->net_amount;

                        \DB::table('orders')->where('id', $ord->id)->update([
                            'total_amount'        => $targetOmset,
                            'marketplace_fee'     => $targetFee,
                            'net_amount'          => $targetNet,
                            'recon_status'        => 'RECONCILED',
                            'financial_breakdown' => is_array($ord->financial_breakdown) ? json_encode($ord->financial_breakdown) : $ord->financial_breakdown,
                            'updated_at'          => now(),
                        ]);

                        $items = $ord->items;
                        if ($items->count() === 1 && $targetOmset > 0) {
                            $it = $items->first();
                            $qty = $it->quantity ?: 1;
                            \DB::table('order_items')->where('id', $it->id)->update([
                                'price'       => round($targetOmset / $qty, 2),
                                'total_price' => $targetOmset,
                                'updated_at'  => now(),
                            ]);
                        }

                        $syncedCount++;
                    }
                } catch (\Throwable $e) {
                    Log::warning("Gagal sync mismatch order {$ord->order_marketplace_id}: " . $e->getMessage());
                }
            }
        }

        Cache::flush();

        return response()->json([
            'success'      => true,
            'synced_count' => $syncedCount,
            'message'      => "Berhasil menyinkronkan dan menyimpan {$syncedCount} pesanan mismatch ke database ERP!",
        ]);
    }
}


