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
        return view('products.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku'        => 'required|string|max:100',
            'name'       => 'required|string|max:255',
            'price'      => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock'      => 'required|integer|min:0',
            'min_stock'  => 'nullable|integer|min:0',
            'unit'       => 'nullable|string|max:50',
            'category'   => 'nullable|string|max:100',
            'brand'      => 'nullable|string|max:100',
        ]);

        $data['tenant_id'] = Auth::user()->tenant_id;

        MasterProduct::create($data);

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'price'      => 'required|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock'      => 'required|integer|min:0',
            'min_stock'  => 'nullable|integer|min:0',
            'unit'       => 'nullable|string|max:50',
            'category'   => 'nullable|string|max:100',
            'brand'      => 'nullable|string|max:100',
        ]);
        $product->update($data);
        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }
}
