<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceProduct;
use App\Models\MasterProduct;
use App\Models\Channel;
use App\Models\Store;
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

        if ($request->filled('status')) {
            if ($request->status === 'unmapped') {
                $query->whereNull('master_product_id');
            } elseif ($request->status === 'mapped') {
                $query->whereNotNull('master_product_id');
            }
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('sku')) {
            $query->where('marketplace_sku', 'like', '%' . $request->sku . '%');
        }

        if ($request->filled('channel_id')) {
            $query->whereHas('store', function($q) use ($request) {
                $q->where('channel_id', $request->channel_id);
            });
        }

        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        $marketplaceProducts = $query->latest('updated_at')->paginate(20)->withQueryString();

        // Ambil data master product untuk dropdown 'Tautkan'
        $masterProducts = MasterProduct::where('tenant_id', $tenantId)->orderBy('name')->get();

        // Ambil data channel dan store untuk filter
        $channels = Channel::orderBy('name')->get();
        $stores = Store::where('tenant_id', $tenantId)->orderBy('store_name')->get();

        return view('marketplace_products.index', compact('marketplaceProducts', 'masterProducts', 'channels', 'stores'));
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

    public function updateSettings(Request $request, MarketplaceProduct $product)
    {
        abort_unless($product->store->tenant_id === Auth::user()->tenant_id, 403);

        $data = $request->validate([
            'safety_stock' => 'required|integer|min:0',
        ]);

        $syncStock = $request->boolean('sync_stock');

        $product->update([
            'sync_stock' => $syncStock,
            'safety_stock' => $data['safety_stock'],
        ]);

        if ($syncStock && $product->master_product_id) {
            \App\Jobs\PushStockToMarketplaces::dispatch($product->master_product_id, $product->masterProduct->stock);
        }

        return back()->with('success', "Pengaturan sinkronisasi untuk produk '{$product->name}' berhasil diperbarui.");
    }

    public function cloneAndPublish(MarketplaceProduct $product)
    {
        abort_unless($product->store->tenant_id === Auth::user()->tenant_id, 403);

        // Jika sudah tertaut ke master product, langsung arahkan ke halaman publish
        if ($product->master_product_id) {
            return redirect()->route('products.publish', $product->master_product_id);
        }

        // Cek jika SKU sudah ada di master
        if ($product->marketplace_sku) {
            $existingMaster = MasterProduct::where('tenant_id', Auth::user()->tenant_id)
                                ->where('sku', $product->marketplace_sku)
                                ->first();
            
            if ($existingMaster) {
                // Tautkan otomatis ke master yang sudah ada
                $product->update(['master_product_id' => $existingMaster->id]);
                return redirect()->route('products.publish', $existingMaster->id)
                    ->with('success', "Produk marketplace otomatis ditautkan ke Master Produk '{$existingMaster->name}' yang memiliki SKU yang sama.");
            }
        }

        try {
            $master = DB::transaction(function () use ($product) {
                // Buat Master Product baru berdasarkan data dari MarketplaceProduct
                $newMaster = MasterProduct::create([
                    'tenant_id'   => Auth::user()->tenant_id,
                    'sku'         => $product->marketplace_sku ?: ('SKU-' . time() . '-' . rand(100, 999)),
                    'name'        => $product->name,
                    'price'       => $product->price,
                    'stock'       => $product->stock,
                    'image_url'   => $product->image_url,
                    'is_active'   => true,
                    // Default values for standard dimensions/weight to prevent publish errors
                    'weight'      => 0.1, 
                    'length'      => 10,
                    'width'       => 10,
                    'height'      => 10,
                ]);

                // Update marketplace product agar tertaut ke master yang baru
                $product->update([
                    'master_product_id' => $newMaster->id,
                ]);

                return $newMaster;
            });

            return redirect()->route('products.publish', $master->id)
                ->with('success', "Master produk baru '{$master->name}' berhasil dibuat dari produk marketplace. Sekarang pilih toko tujuan untuk menduplikat.");

        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses kloning produk: ' . $e->getMessage());
        }
    }
}
