<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MarketplaceProductController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = MarketplaceProduct::with(['store.channel', 'masterProduct'])
            ->whereHas('store', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });

        if ($request->has('status')) {
            if ($request->status === 'unmapped') {
                $query->whereNull('master_product_id');
            } elseif ($request->status === 'mapped') {
                $query->whereNotNull('master_product_id');
            }
        }

        $marketplaceProducts = $query->latest('updated_at')->paginate(20);

        // Ambil data master product untuk dropdown 'Tautkan'
        $masterProducts = MasterProduct::where('tenant_id', $tenantId)->orderBy('name')->get();

        return view('marketplace_products.index', compact('marketplaceProducts', 'masterProducts'));
    }

    public function promote(MarketplaceProduct $product)
    {
        abort_unless($product->store->tenant_id === Auth::user()->tenant_id, 403);
        
        if ($product->master_product_id) {
            return back()->with('error', 'Produk ini sudah ditautkan ke Master Product.');
        }

        if ($product->marketplace_sku) {
            $existingMaster = MasterProduct::where('tenant_id', Auth::user()->tenant_id)
                                ->where('sku', $product->marketplace_sku)
                                ->first();
            
            if ($existingMaster) {
                return back()->with('error', "Gagal! SKU '{$product->marketplace_sku}' sudah terdaftar di Master Produk ('{$existingMaster->name}'). Silakan gunakan tombol 'Tautkan' ke produk tersebut agar tidak terjadi duplikat.");
            }
        }

        try {
            DB::transaction(function () use ($product) {
                // Buat Master Product baru berdasarkan data dari MarketplaceProduct
                $master = MasterProduct::create([
                    'tenant_id' => Auth::user()->tenant_id,
                    'sku' => $product->marketplace_sku ?: ('SKU-' . time() . '-' . rand(100, 999)),
                    'name' => $product->name,
                    'price' => $product->price,
                    'stock' => $product->stock,
                    'image_url' => $product->image_url,
                    'is_active' => true,
                ]);

                // Update marketplace product agar tertaut ke master yang baru
                $product->update([
                    'master_product_id' => $master->id,
                ]);
            });

            return back()->with('success', "Berhasil! Produk '{$product->name}' telah dijadikan Master Product.");
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menjadikan Master Product: ' . $e->getMessage());
        }
    }

    public function link(Request $request, MarketplaceProduct $product)
    {
        abort_unless($product->store->tenant_id === Auth::user()->tenant_id, 403);
        
        $request->validate([
            'master_product_id' => 'required|exists:master_products,id'
        ]);

        $master = MasterProduct::findOrFail($request->master_product_id);
        abort_unless($master->tenant_id === Auth::user()->tenant_id, 403);

        $product->update([
            'master_product_id' => $master->id,
        ]);

        if (empty($master->image_url) && !empty($product->image_url)) {
            $master->update(['image_url' => $product->image_url]);
        }

        return back()->with('success', "Produk marketplace '{$product->name}' berhasil ditautkan ke Master '{$master->name}'.");
    }
}
