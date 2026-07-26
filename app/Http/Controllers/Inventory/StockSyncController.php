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

        $query = MarketplaceProduct::whereHas('store', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->with(['store.channel', 'masterProduct']);

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
            $query->where(function($q) {
                $q->where('is_pre_order', true)
                  ->orWhereHas('masterProduct', function($mq) {
                      $mq->where('is_preorder', true);
                  })
                  ->orWhere('name', 'like', '%PRE ORDER%')
                  ->orWhere('name', 'like', '%PREORDER%')
                  ->orWhere('name', 'like', '%PRE-ORDER%')
                  ->orWhere('name', 'like', 'PO %');
            });
        }

        // Filter: match = stok sinkron
        if ($request->filter === 'match') {
            $query->whereHas('masterProduct')
                  ->where('is_pre_order', false)
                  ->whereHas('masterProduct', function($mq) { $mq->where('is_preorder', false); })
                  ->where('name', 'not like', '%PRE ORDER%')
                  ->where('name', 'not like', '%PREORDER%')
                  ->where('name', 'not like', '%PRE-ORDER%')
                  ->whereRaw('marketplace_products.stock = GREATEST(0, (
                      SELECT stock FROM master_products WHERE master_products.id = marketplace_products.master_product_id
                  ) - COALESCE(marketplace_products.safety_stock, 0))');
        }

        // Filter: diff = stok marketplace ≠ ekspektasi (bukan PO)
        if ($request->filter === 'diff') {
            $query->whereHas('masterProduct')
                  ->where('is_pre_order', false)
                  ->whereHas('masterProduct', function($mq) { $mq->where('is_preorder', false); })
                  ->where('name', 'not like', '%PRE ORDER%')
                  ->where('name', 'not like', '%PREORDER%')
                  ->where('name', 'not like', '%PRE-ORDER%')
                  ->whereRaw('marketplace_products.stock != GREATEST(0, (
                      SELECT stock FROM master_products WHERE master_products.id = marketplace_products.master_product_id
                  ) - COALESCE(marketplace_products.safety_stock, 0))');
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

        return view('inventory.stock_sync.index', compact('mappedProducts', 'syncLogs', 'stores', 'channels'));
    }

    public function forceSyncAll()
    {
        $tenantId = Auth::user()->tenant_id;
        $mappedProducts = MarketplaceProduct::whereHas('store', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('status', 'connected');
            })
            ->where('sync_stock', true)
            ->with('masterProduct')
            ->get();

        $count = 0;
        foreach ($mappedProducts as $mp) {
            if ($mp->masterProduct) {
                \App\Jobs\PushStockToMarketplaces::dispatch($mp->master_product_id, $mp->masterProduct->stock);
                $count++;
            }
        }

        return back()->with('success', "Instruksi sinkronisasi stok berhasil dikirim ke antrean untuk {$count} produk marketplace.");
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
