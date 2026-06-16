<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = MasterProduct::with('category', 'brand')
            ->where('tenant_id', $tenantId);
            
        if ($request->has('search') && $request->search !== null) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        // Filter status stok
        if ($request->has('status') && !empty($request->status)) {
            if ($request->status === 'low') {
                $query->whereColumn('stock', '<=', 'min_stock')
                      ->where('stock', '>', 0);
            } elseif ($request->status === 'empty') {
                $query->where('stock', '<=', 0);
            } elseif ($request->status === 'safe') {
                $query->whereColumn('stock', '>', 'min_stock');
            }
        }
        
        $products = $query->paginate(20)->withQueryString();
        
        // Hitung statistik keseluruhan gudang untuk tenant ini
        $stats = [
            'total_skus' => MasterProduct::where('tenant_id', $tenantId)->count(),
            'total_stock' => (int) MasterProduct::where('tenant_id', $tenantId)->sum('stock'),
            'low_stock' => MasterProduct::where('tenant_id', $tenantId)
                ->whereColumn('stock', '<=', 'min_stock')
                ->where('stock', '>', 0)
                ->count(),
            'out_of_stock' => MasterProduct::where('tenant_id', $tenantId)
                ->where('stock', '<=', 0)
                ->count(),
        ];
        
        return view('inventory.index', compact('products', 'stats'));
    }

    public function ledger(MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);
        
        $movements = \App\Models\StockMovement::with('user')
            ->where('master_product_id', $product->id)
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(30);
            
        return view('inventory.ledger', compact('product', 'movements'));
    }

    public function adjust(Request $request, MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);
        
        $request->validate([
            'quantity' => 'required|integer|not_in:0',
            'reference' => 'required|string|max:255',
        ]);
        
        $product->recordStockMovement(
            $request->quantity,
            'adj',
            $request->reference,
            Auth::id()
        );
        
        return back()->with('success', 'Stok berhasil disesuaikan.');
    }


}
