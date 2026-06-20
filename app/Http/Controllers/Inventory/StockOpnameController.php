<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StockOpnameController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = \App\Models\StockMovement::with(['masterProduct', 'user'])
            ->where('tenant_id', $tenantId)
            ->where('reference', 'like', 'Stock Opname Massal%')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc');

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->whereHas('masterProduct', function($q2) use ($request) {
                    $q2->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('sku', 'like', '%' . $request->search . '%');
                })->orWhere('reference', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $histories = $query->paginate(30)->withQueryString();

        return view('inventory.stock_opnames.index', compact('histories'));
    }

    public function create(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = MasterProduct::with('category', 'brand')->where('tenant_id', $tenantId)->where('is_active', true);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        $products = $query->paginate(50)->withQueryString();

        $categories = \App\Models\Category::orderBy('name')->get();
        $brands = \App\Models\Brand::orderBy('name')->get();

        return view('inventory.stock_opnames.create', compact('products', 'categories', 'brands'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'actual_stocks' => 'required|array',
            'actual_stocks.*' => 'nullable|integer|min:0',
            'opname_date' => 'required|date',
            'pic' => 'required|string|max:255',
        ]);

        $actualStocks = $request->actual_stocks;
        $productIds = array_keys($actualStocks);

        $products = MasterProduct::where('tenant_id', $tenantId)
            ->whereIn('id', $productIds)
            ->get();

        $changesCount = 0;

        $date = \Carbon\Carbon::parse($request->opname_date)->format('Y-m-d H:i:s');
        $reference = 'Stock Opname Massal - ' . $request->pic;

        foreach ($products as $product) {
            $actualStock = $actualStocks[$product->id];

            if ($actualStock === null || $actualStock === '') {
                continue;
            }

            $difference = $actualStock - $product->stock;

            if ($difference != 0) {
                $product->recordStockMovement(
                    $difference,
                    'adj',
                    $reference,
                    Auth::id(),
                    $date
                );
                $changesCount++;
            }
        }

        return redirect()->route('stock_opnames.index')->with('success', "Stock Opname berhasil disimpan. Terdapat {$changesCount} produk yang disesuaikan pada tanggal {$request->opname_date} oleh {$request->pic}.");
    }
}
