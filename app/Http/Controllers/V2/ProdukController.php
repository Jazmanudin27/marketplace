<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\Category;
use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = MasterProduct::with(['category', 'brand'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('status') && $request->status !== '') {
            $query->where('is_active', $request->status == '1');
        }

        $products = $query->orderBy('name')->paginate(15)->withQueryString();
        $categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands = Brand::where('tenant_id', $tenantId)->orderBy('name')->get();

        $totalProducts = MasterProduct::where('tenant_id', $tenantId)->count();
        $activeProducts = MasterProduct::where('tenant_id', $tenantId)->where('is_active', true)->count();
        $lowStockProducts = MasterProduct::where('tenant_id', $tenantId)->whereColumn('stock', '<=', 'min_stock')->count();
        $preorderProducts = MasterProduct::where('tenant_id', $tenantId)->where('is_preorder', true)->count();

        $stats = [
            'total' => $totalProducts,
            'active' => $activeProducts,
            'low_stock' => $lowStockProducts,
            'preorder' => $preorderProducts,
        ];

        return view('v2.produk.index', compact('products', 'categories', 'brands', 'stats'));
    }
}

