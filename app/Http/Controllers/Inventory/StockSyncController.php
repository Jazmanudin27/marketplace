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

        // Filter: diff = stok marketplace ≠ (stok lokal - safety_stock)
        // Ini dilakukan setelah load karena perlu data dari master product
        // Kita gunakan whereHas + kolom langsung untuk perbandingan di DB
        if ($request->filter === 'diff') {
            $query->whereHas('masterProduct')
                  ->whereRaw('marketplace_products.stock != GREATEST(0, (
                      SELECT stock FROM master_products WHERE master_products.id = marketplace_products.master_product_id
                  ) - COALESCE(marketplace_products.safety_stock, 0))');
        }

        $mappedProducts = $query->orderByDesc('last_synced_at')->paginate(25)->withQueryString();

        $syncLogs = MarketplaceSyncLog::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get();

        return view('inventory.stock_sync.index', compact('mappedProducts', 'syncLogs'));
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
