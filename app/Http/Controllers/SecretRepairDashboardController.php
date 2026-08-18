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
                    $gitOutput = shell_exec('cd ' . escapeshellarg(base_path()) . ' && git pull 2>&1');
                    $output = "🔄 Git Pull Output:\n" . ($gitOutput ?: '(tidak ada output)');
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
}
