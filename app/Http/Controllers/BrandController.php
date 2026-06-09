<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BrandController extends Controller
{
    public function index()
    {
        $brands = Brand::where('tenant_id', Auth::user()->tenant_id)->get();
        return view('brands.index', compact('brands'));
    }

    public function create()
    {
        $brand = new Brand();
        return view('brands.form', compact('brand'));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255']);
        $data['tenant_id'] = Auth::user()->tenant_id;
        Brand::create($data);
        return redirect()->route('brands.index')->with('success', 'Merk berhasil ditambahkan.');
    }

    public function edit(Brand $brand)
    {
        abort_unless($brand->tenant_id === Auth::user()->tenant_id, 403);
        return view('brands.form', compact('brand'));
    }

    public function update(Request $request, Brand $brand)
    {
        abort_unless($brand->tenant_id === Auth::user()->tenant_id, 403);
        $data = $request->validate(['name' => 'required|string|max:255']);
        $brand->update($data);
        return redirect()->route('brands.index')->with('success', 'Merk berhasil diupdate.');
    }

    public function destroy(Brand $brand)
    {
        abort_unless($brand->tenant_id === Auth::user()->tenant_id, 403);
        $brand->delete();
        return redirect()->route('brands.index')->with('success', 'Merk berhasil dihapus.');
    }
}
