<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IncomingGoodController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        // Group incoming stock movements by reference and date
        $query = \App\Models\StockMovement::with(['user'])
            ->select('reference', 'created_at', 'user_id', DB::raw('COUNT(*) as total_items'), DB::raw('SUM(quantity) as total_qty'))
            ->where('tenant_id', $tenantId)
            ->where('type', 'in')
            ->groupBy('reference', 'created_at', 'user_id')
            ->orderBy('created_at', 'desc');
            
        if ($request->filled('search')) {
            $query->where('reference', 'like', '%' . $request->search . '%');
        }
        
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }
        
        $incomings = $query->paginate(20)->withQueryString();
        
        return view('incoming_goods.index', compact('incomings'));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $products = MasterProduct::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $suppliers = \App\Models\Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('incoming_goods.create', compact('products', 'suppliers'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $request->validate([
            'source_type' => 'required|string|in:supplier,return,other',
            'order_id' => 'nullable|string|max:255',
            'incoming_date' => 'required|date',
            'reference' => 'required|string|max:255',
            'products' => 'required|array',
            'products.*' => 'required|exists:master_products,id',
            'quantities' => 'required|array',
            'quantities.*' => 'required|numeric|min:0.01',
            'cost_prices' => 'array',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        $sourceType = $request->source_type;
        $reference = "";

        if ($sourceType === 'supplier') {
            $supplierLabel = '';
            if ($request->filled('supplier_id')) {
                $supplier = \App\Models\Supplier::find($request->supplier_id);
                if ($supplier) {
                    $supplierLabel = " (" . $supplier->name . ")";
                }
            }
            $reference = "Pembelian - " . $request->reference . $supplierLabel;
        } elseif ($sourceType === 'return') {
            $reference = "Retur Penjualan - Order ID: " . ($request->order_id ?? $request->reference);
        } else {
            $reference = "Penerimaan Lainnya - " . $request->reference;
        }

        $date = \Carbon\Carbon::parse($request->incoming_date)->format('Y-m-d H:i:s');
        
        $productIds = $request->products;
        $quantities = $request->quantities;
        $costPrices = $request->cost_prices ?? [];
        
        $products = MasterProduct::where('tenant_id', $tenantId)
            ->whereIn('id', $productIds)
            ->get()->keyBy('id');
            
        $itemsCount = 0;
        $totalQty = 0;
        
        foreach ($productIds as $index => $productId) {
            if (!isset($products[$productId])) {
                continue;
            }
            
            $product = $products[$productId];
            $qty = $quantities[$index];
            $costPrice = $costPrices[$index] ?? null;
            
            if ($qty > 0) {
                // Update cost price ONLY if it's a purchase from a supplier
                if ($sourceType === 'supplier' && $costPrice !== null && $costPrice !== '') {
                    $product->update(['cost_price' => $costPrice]);
                }
                
                // Record incoming stock
                $product->recordStockMovement(
                    $qty,
                    'in',
                    $reference,
                    Auth::id(),
                    $date
                );
                
                $itemsCount++;
                $totalQty += $qty;
            }
        }
        
        return redirect()->route('incoming_goods.index')->with('success', "Penerimaan barang berhasil disimpan. Total {$itemsCount} jenis produk dengan {$totalQty} Qty masuk.");
    }
}
