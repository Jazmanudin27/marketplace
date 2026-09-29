<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Brand;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KategoriBrandController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $activeTab = $request->get('tab', 'category');

        // Query Categories
        $categoryQuery = Category::withCount('products')->where('tenant_id', $tenantId);
        if ($request->filled('cat_name')) {
            $categoryQuery->where('name', 'like', '%' . $request->cat_name . '%');
        }
        $categories = $categoryQuery->orderBy('name')->paginate(15, ['*'], 'cat_page')->withQueryString();

        // Query Brands
        $brandQuery = Brand::withCount('products')->where('tenant_id', $tenantId);
        if ($request->filled('brand_name')) {
            $brandQuery->where('name', 'like', '%' . $request->brand_name . '%');
        }
        $brands = $brandQuery->orderBy('name')->paginate(15, ['*'], 'brand_page')->withQueryString();

        // Counts
        $counts = [
            'total_categories' => Category::where('tenant_id', $tenantId)->count(),
            'total_brands' => Brand::where('tenant_id', $tenantId)->count(),
            'categorized_products' => MasterProduct::where('tenant_id', $tenantId)->whereNotNull('category_id')->count(),
            'branded_products' => MasterProduct::where('tenant_id', $tenantId)->whereNotNull('brand_id')->count(),
        ];

        return view('v2.kategori.index', compact('categories', 'brands', 'counts', 'activeTab'));
    }

    public function storeCategory(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $data['tenant_id'] = Auth::user()->tenant_id;

        Category::create($data);

        return redirect()->to(url('/v2/kategori-brand') . '?tab=category')->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category->update($data);

        return redirect()->to(url('/v2/kategori-brand') . '?tab=category')->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroyCategory($id)
    {
        $category = Category::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        $category->delete();

        return redirect()->to(url('/v2/kategori-brand') . '?tab=category')->with('success', 'Kategori berhasil dihapus.');
    }

    public function storeBrand(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);
        $data['tenant_id'] = Auth::user()->tenant_id;

        Brand::create($data);

        return redirect()->to(url('/v2/kategori-brand') . '?tab=brand')->with('success', 'Brand/Merek berhasil ditambahkan.');
    }

    public function updateBrand(Request $request, $id)
    {
        $brand = Brand::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $brand->update($data);

        return redirect()->to(url('/v2/kategori-brand') . '?tab=brand')->with('success', 'Brand/Merek berhasil diperbarui.');
    }

    public function destroyBrand($id)
    {
        $brand = Brand::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);
        $brand->delete();

        return redirect()->to(url('/v2/kategori-brand') . '?tab=brand')->with('success', 'Brand/Merek berhasil dihapus.');
    }
}
