<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceSyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockSyncController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $baseQuery = MarketplaceProduct::whereHas('store', function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        });

        // Closure untuk deteksi produk Pre-Order di SQL
        $poCondition = function($q) {
            $q->where('marketplace_products.is_pre_order', true)
              ->orWhereHas('masterProduct', function($mq) {
                  $mq->where('is_preorder', true);
              })
              ->orWhere('marketplace_products.name', 'like', '%PRE ORDER%')
              ->orWhere('marketplace_products.name', 'like', '%PREORDER%')
              ->orWhere('marketplace_products.name', 'like', '%PRE-ORDER%')
              ->orWhere('marketplace_products.name', 'like', 'PO %')
              ->orWhere('marketplace_products.name', 'like', '% PO %');
        };

        // Hitung statistik aktual untuk stat cards (seluruh data tenant)
        $totalAll = (clone $baseQuery)->count();
        $totalPo = (clone $baseQuery)->where($poCondition)->count();
        $totalTidakMap = (clone $baseQuery)->whereNull('master_product_id')->count();
        $totalSyncOff = (clone $baseQuery)->where('sync_stock', false)->count();

        $totalSinkron = (clone $baseQuery)->whereHas('masterProduct')
            ->where('is_pre_order', false)
            ->whereHas('masterProduct', function($mq) { $mq->where('is_preorder', false); })
            ->where('name', 'not like', '%PRE ORDER%')
            ->where('name', 'not like', '%PREORDER%')
            ->where('name', 'not like', '%PRE-ORDER%')
            ->where('name', 'not like', 'PO %')
            ->where('name', 'not like', '% PO %')
            ->whereNotNull('last_synced_at')
            ->whereRaw('marketplace_products.stock = GREATEST(0, (
                SELECT stock FROM master_products WHERE master_products.id = marketplace_products.master_product_id
            ) - COALESCE(marketplace_products.safety_stock, 0))')
            ->count();

        $totalBeda = (clone $baseQuery)->whereHas('masterProduct')
            ->where('is_pre_order', false)
            ->whereHas('masterProduct', function($mq) { $mq->where('is_preorder', false); })
            ->where('name', 'not like', '%PRE ORDER%')
            ->where('name', 'not like', '%PREORDER%')
            ->where('name', 'not like', '%PRE-ORDER%')
            ->where('name', 'not like', 'PO %')
            ->where('name', 'not like', '% PO %')
            ->where(function($q) {
                $q->whereNull('last_synced_at')
                  ->orWhereRaw('marketplace_products.stock != GREATEST(0, (
                      SELECT stock FROM master_products WHERE master_products.id = marketplace_products.master_product_id
                  ) - COALESCE(marketplace_products.safety_stock, 0))');
            })
            ->count();

        $query = (clone $baseQuery)->with(['store.channel', 'masterProduct']);

        // Filter: search
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('marketplace_sku', 'like', '%' . $request->search . '%')
                  ->orWhere('marketplace_product_id', 'like', '%' . $request->search . '%');
            });
        }

        // Filter: nomap = belum terhubung ke produk master
        if ($request->filter === 'nomap') {
            $query->whereNull('master_product_id');
        }

        // Filter: po = Pre-Order
        if ($request->filter === 'po') {
            $query->where($poCondition);
        }

        // Filter: match = stok sinkron (bukan PO, sudah pernah sync, dan stok cocok)
        if ($request->filter === 'match') {
            $query->whereHas('masterProduct')
                  ->where('is_pre_order', false)
                  ->whereHas('masterProduct', function($mq) { $mq->where('is_preorder', false); })
                  ->where('name', 'not like', '%PRE ORDER%')
                  ->where('name', 'not like', '%PREORDER%')
                  ->where('name', 'not like', '%PRE-ORDER%')
                  ->where('name', 'not like', 'PO %')
                  ->where('name', 'not like', '% PO %')
                  ->whereNotNull('last_synced_at')
                  ->whereRaw('marketplace_products.stock = GREATEST(0, (
                      SELECT stock FROM master_products WHERE master_products.id = marketplace_products.master_product_id
                  ) - COALESCE(marketplace_products.safety_stock, 0))');
        }

        // Filter: diff = stok marketplace ≠ ekspektasi ATAU belum pernah sync (bukan PO)
        if ($request->filter === 'diff') {
            $query->whereHas('masterProduct')
                  ->where('is_pre_order', false)
                  ->whereHas('masterProduct', function($mq) { $mq->where('is_preorder', false); })
                  ->where('name', 'not like', '%PRE ORDER%')
                  ->where('name', 'not like', '%PREORDER%')
                  ->where('name', 'not like', '%PRE-ORDER%')
                  ->where('name', 'not like', 'PO %')
                  ->where('name', 'not like', '% PO %')
                  ->where(function($q) {
                      $q->whereNull('last_synced_at')
                        ->orWhereRaw('marketplace_products.stock != GREATEST(0, (
                            SELECT stock FROM master_products WHERE master_products.id = marketplace_products.master_product_id
                        ) - COALESCE(marketplace_products.safety_stock, 0))');
                  });
        }

        // Filter: channel (shopee, tiktok, tokopedia, lazada)
        if ($request->filled('channel')) {
            $query->whereHas('store.channel', function($q) use ($request) {
                $q->where('code', $request->channel);
            });
        }

        // Filter: store_id
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter: sync_status (on/off)
        if ($request->filled('sync_status')) {
            $query->where('sync_stock', $request->sync_status === 'on' ? true : false);
        }

        $mappedProducts = $query->orderByDesc('last_synced_at')->paginate(30)->withQueryString();

        $syncLogs = MarketplaceSyncLog::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        // Data untuk dropdown filter
        $stores = \App\Models\Store::where('tenant_id', $tenantId)
            ->with('channel')
            ->orderBy('store_name')
            ->get(['id', 'store_name', 'channel_id']);

        $channels = \App\Models\Channel::orderBy('name')->get(['id', 'name', 'code']);

        $summaryStats = [
            'totalAll'      => $totalAll,
            'totalSinkron'  => $totalSinkron,
            'totalBeda'     => $totalBeda,
            'totalTidakMap' => $totalTidakMap,
            'totalSyncOff'  => $totalSyncOff,
            'totalPo'       => $totalPo,
        ];

        return view('inventory.stock_sync.index', compact('mappedProducts', 'syncLogs', 'stores', 'channels', 'summaryStats'));
    }

    public function forceSyncAll(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $poCondition = function($q) {
            $q->where('marketplace_products.is_pre_order', true)
              ->orWhereHas('masterProduct', function($mq) {
                  $mq->where('is_preorder', true);
              })
              ->orWhere('marketplace_products.name', 'like', '%PRE ORDER%')
              ->orWhere('marketplace_products.name', 'like', '%PREORDER%')
              ->orWhere('marketplace_products.name', 'like', '%PRE-ORDER%')
              ->orWhere('marketplace_products.name', 'like', 'PO %')
              ->orWhere('marketplace_products.name', 'like', '% PO %');
        };

        // Base query: toko connected, sync_stock aktif, ter-map ke masterProduct
        $query = MarketplaceProduct::whereHas('store', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('status', 'connected');
            })
            ->where('sync_stock', true)
            ->whereHas('masterProduct')
            ->with('masterProduct');

        // Terapkan filter pencarian & dropdown yang sedang aktif di layar (jika ada)
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('marketplace_sku', 'like', '%' . $request->search . '%')
                  ->orWhere('marketplace_product_id', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filter === 'nomap') {
            $query->whereNull('master_product_id');
        }

        if ($request->filter === 'po') {
            $query->where($poCondition);
        }

        if ($request->filter === 'match') {
            $query->whereHas('masterProduct')
                  ->where('is_pre_order', false)
                  ->whereHas('masterProduct', function($mq) { $mq->where('is_preorder', false); })
                  ->where('name', 'not like', '%PRE ORDER%')
                  ->where('name', 'not like', '%PREORDER%')
                  ->where('name', 'not like', '%PRE-ORDER%')
                  ->where('name', 'not like', 'PO %')
                  ->where('name', 'not like', '% PO %')
                  ->whereNotNull('last_synced_at')
                  ->whereRaw('marketplace_products.stock = GREATEST(0, (
                      SELECT stock FROM master_products WHERE master_products.id = marketplace_products.master_product_id
                  ) - COALESCE(marketplace_products.safety_stock, 0))');
        }

        if ($request->filter === 'diff') {
            $query->whereHas('masterProduct')
                  ->where('is_pre_order', false)
                  ->whereHas('masterProduct', function($mq) { $mq->where('is_preorder', false); })
                  ->where('name', 'not like', '%PRE ORDER%')
                  ->where('name', 'not like', '%PREORDER%')
                  ->where('name', 'not like', '%PRE-ORDER%')
                  ->where('name', 'not like', 'PO %')
                  ->where('name', 'not like', '% PO %')
                  ->where(function($q) {
                      $q->whereNull('last_synced_at')
                        ->orWhereRaw('marketplace_products.stock != GREATEST(0, (
                            SELECT stock FROM master_products WHERE master_products.id = marketplace_products.master_product_id
                        ) - COALESCE(marketplace_products.safety_stock, 0))');
                  });
        }

        if ($request->filled('channel')) {
            $query->whereHas('store.channel', function($q) use ($request) {
                $q->where('code', $request->channel);
            });
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->filled('sync_status')) {
            $query->where('sync_stock', $request->sync_status === 'on' ? true : false);
        }

        $mappedProducts = $query->get();

        $count = 0;
        $dispatchedMasterIds = [];

        foreach ($mappedProducts as $mp) {
            // Abaikan produk Pre-Order
            if ($mp->isPreOrder()) {
                continue;
            }

            // Hitung ekspektasi stok
            $expected = max(0, (int)$mp->masterProduct->stock - (int)($mp->safety_stock ?? 0));

            // HANYA sync produk yang BELUM pernah sync ATAU stoknya BEDA (belum sinkron)
            if ($mp->last_synced_at === null || (int)$mp->stock !== $expected) {
                if (!in_array($mp->master_product_id, $dispatchedMasterIds)) {
                    \App\Jobs\PushStockToMarketplaces::dispatch($mp->master_product_id, $mp->masterProduct->stock);
                    $dispatchedMasterIds[] = $mp->master_product_id;
                }
                $count++;
            }
        }

        if ($count === 0) {
            return back()->with('info', 'Tidak ada produk (sesuai filter) yang perlu di-sync.');
        }

        return back()->with('success', "Instruksi sinkronisasi stok berhasil dikirim untuk {$count} produk marketplace (sesuai filter).");
    }

    public function forceSyncProduct(MarketplaceProduct $product)
    {
        abort_unless($product->store->tenant_id === Auth::user()->tenant_id, 403);
        
        if (!$product->masterProduct) {
            return back()->with('error', 'Produk marketplace belum ter-map ke produk master lokal.');
        }

        \App\Jobs\PushStockToMarketplaces::dispatch($product->master_product_id, $product->masterProduct->stock);

        return back()->with('success', "Instruksi sinkronisasi stok untuk {$product->name} berhasil dikirim.");
    }
}

