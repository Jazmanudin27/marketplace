<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MasterProductController extends Controller
{
    public function index()
    {
        $products = MasterProduct::where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('name')
            ->paginate(25);

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $product = new MasterProduct();
        $categories = \App\Models\Category::where('tenant_id', Auth::user()->tenant_id)->get();
        $brands = \App\Models\Brand::where('tenant_id', Auth::user()->tenant_id)->get();
        return view('products.form', compact('product', 'categories', 'brands'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku'        => 'required|string|max:100|unique:master_products,sku',
            'name'       => 'required|string|max:255',
            'price'      => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock'      => 'required|integer|min:0',
            'min_stock'  => 'nullable|integer|min:0',
            'unit'       => 'nullable|string|max:50',
            'category_id'=> 'nullable|exists:categories,id',
            'brand_id'   => 'nullable|exists:brands,id',
        ]);

        $data['tenant_id'] = Auth::user()->tenant_id;

        MasterProduct::create($data);

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);
        $categories = \App\Models\Category::where('tenant_id', Auth::user()->tenant_id)->get();
        $brands = \App\Models\Brand::where('tenant_id', Auth::user()->tenant_id)->get();
        return view('products.form', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);
        $data = $request->validate([
            'sku'        => 'nullable|string|max:100|unique:master_products,sku,' . $product->id,
            'name'       => 'required|string|max:255',
            'price'      => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock'      => 'required|integer|min:0',
            'min_stock'  => 'nullable|integer|min:0',
            'unit'       => 'nullable|string|max:50',
            'category_id'=> 'nullable|exists:categories,id',
            'brand_id'   => 'nullable|exists:brands,id',
        ]);
        $oldStock = $product->stock;
        $oldPrice = $product->price;
        $oldName = $product->name;
        
        $product->update($data);
        
        if ($oldStock !== (int)$data['stock']) {
            \App\Jobs\PushStockToMarketplaces::dispatch($product->id, (int)$data['stock']);
        }

        if ($oldPrice != $data['price']) {
            \App\Jobs\PushPriceToMarketplaces::dispatch($product->id, (float)$data['price']);
        }

        if ($oldName !== $data['name']) {
            \App\Jobs\PushItemInfoToMarketplaces::dispatch($product->id, [
                'name' => $data['name']
            ]);
        }
        
        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
