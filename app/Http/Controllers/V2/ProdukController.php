<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = MasterProduct::with(['category', 'brand', 'marketplaceProducts.store.channel'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('sku')) {
            $sku = $request->sku;
            $query->where(function($q) use ($sku) {
                $q->where('sku', 'like', '%' . $sku . '%')
                  ->orWhere('sku_induk', 'like', '%' . $sku . '%');
            });
        }

        if ($request->filled('is_bundle')) {
            if ($request->is_bundle === '1') {
                $query->where('is_bundle', true);
            } elseif ($request->is_bundle === '0') {
                $query->where(function($q) {
                    $q->where('is_bundle', false)->orWhereNull('is_bundle');
                });
            }
        }

        if ($request->filled('is_preorder')) {
            if ($request->is_preorder === '1') {
                $query->where('is_preorder', true);
            } elseif ($request->is_preorder === '0') {
                $query->where(function($q) {
                    $q->where('is_preorder', false)->orWhereNull('is_preorder');
                });
            }
        }

        if ($request->filled('channel_id')) {
            $query->whereHas('marketplaceProducts.store', function($q) use ($request) {
                $q->where('channel_id', $request->channel_id);
            });
        }

        if ($request->filled('store_id')) {
            $query->whereHas('marketplaceProducts', function($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        if ($request->filled('link_status')) {
            if ($request->link_status === 'unlinked') {
                $query->where(function($q) {
                    $q->whereDoesntHave('marketplaceProducts')
                      ->orWhereDoesntHave('marketplaceProducts', function($mq) {
                          $mq->whereRaw('LOWER(TRIM(marketplace_sku)) = LOWER(TRIM(master_products.sku))');
                      });
                });
            }
        }

        $products = $query->orderBy('name')->paginate(25)->withQueryString();

        $stores = Store::with('channel')->where('tenant_id', $tenantId)->where('status', 'connected')->get();
        $channels = Channel::all();
        $categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands = Brand::where('tenant_id', $tenantId)->orderBy('name')->get();

        $poCount = MasterProduct::where('tenant_id', $tenantId)->where('is_preorder', true)->count();
        $readyCount = MasterProduct::where('tenant_id', $tenantId)->where(function($q) {
            $q->where('is_preorder', false)->orWhereNull('is_preorder');
        })->count();
        $bundleCount = MasterProduct::where('tenant_id', $tenantId)->where('is_bundle', true)->count();
        $singleCount = MasterProduct::where('tenant_id', $tenantId)->where(function($q) {
            $q->where('is_bundle', false)->orWhereNull('is_bundle');
        })->count();
        $unlinkedCount = MasterProduct::where('tenant_id', $tenantId)
            ->where(function($q) {
                $q->whereDoesntHave('marketplaceProducts')
                  ->orWhereDoesntHave('marketplaceProducts', function($mq) {
                      $mq->whereRaw('LOWER(TRIM(marketplace_sku)) = LOWER(TRIM(master_products.sku))');
                  });
            })->count();

        $counts = [
            'total' => MasterProduct::where('tenant_id', $tenantId)->count(),
            'single' => $singleCount,
            'bundle' => $bundleCount,
            'ready' => $readyCount,
            'po' => $poCount,
            'unlinked' => $unlinkedCount,
        ];

        return view('v2.produk.index', compact(
            'products',
            'stores',
            'channels',
            'categories',
            'brands',
            'counts'
        ));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'est_kain' => 'nullable|numeric|min:0',
            'est_biaya_produksi' => 'nullable|numeric|min:0',
            'category_id' => 'nullable',
            'brand_id' => 'nullable',
        ]);

        $product = MasterProduct::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'sku' => strtoupper(trim($request->sku)),
            'sku_induk' => $request->sku_induk ? strtoupper(trim($request->sku_induk)) : strtoupper(trim($request->sku)),
            'price' => $request->price ?? 0,
            'selling_price' => $request->price ?? 0,
            'cost_price' => $request->cost_price ?? 0,
            'stock' => $request->stock ?? 0,
            'min_stock' => $request->min_stock ?? 5,
            'unit' => $request->unit ?? 'pcs',
            'est_kain' => $request->est_kain ?? 0,
            'est_biaya_produksi' => $request->est_biaya_produksi ?? 0,
            'category_id' => $request->category_id ?: null,
            'brand_id' => $request->brand_id ?: null,
            'is_bundle' => $request->filled('is_bundle') ? (bool)$request->is_bundle : false,
            'is_preorder' => $request->filled('is_preorder') ? (bool)$request->is_preorder : false,
            'is_active' => true,
        ]);

        return redirect()->route('v2.produk.index')->with('success', 'Master produk baru berhasil ditambahkan!');
    }

    public function show($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $product = MasterProduct::with(['category', 'brand', 'marketplaceProducts.store.channel'])
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        return response()->json($product);
    }

    public function edit($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $product = MasterProduct::where('tenant_id', $tenantId)->findOrFail($id);

        $categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands = Brand::where('tenant_id', $tenantId)->orderBy('name')->get();

        return view('v2.produk.edit', compact('product', 'categories', 'brands'));
    }

    public function update(Request $request, $id)
    {
        $tenantId = Auth::user()->tenant_id;
        $product = MasterProduct::where('tenant_id', $tenantId)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'stock' => 'nullable|integer|min:0',
            'min_stock' => 'nullable|integer|min:0',
            'est_kain' => 'nullable|numeric|min:0',
            'est_biaya_produksi' => 'nullable|numeric|min:0',
            'category_id' => 'nullable',
            'brand_id' => 'nullable',
        ]);

        $product->update([
            'name' => $request->name,
            'sku' => $request->sku,
            'sku_induk' => $request->sku_induk ?? $request->sku,
            'price' => $request->price ?? 0,
            'selling_price' => $request->price ?? 0,
            'cost_price' => $request->cost_price ?? 0,
            'stock' => $request->stock ?? 0,
            'min_stock' => $request->min_stock ?? 5,
            'unit' => $request->unit ?? 'pcs',
            'est_kain' => $request->est_kain ?? 0,
            'est_biaya_produksi' => $request->est_biaya_produksi ?? 0,
            'category_id' => $request->category_id ?: null,
            'brand_id' => $request->brand_id ?: null,
            'is_bundle' => $request->filled('is_bundle') ? (bool)$request->is_bundle : false,
            'is_preorder' => $request->filled('is_preorder') ? (bool)$request->is_preorder : false,
        ]);

        return redirect()->route('v2.produk.index')->with('success', 'Master produk berhasil diperbarui!');
    }

    public function destroy($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $product = MasterProduct::where('tenant_id', $tenantId)->findOrFail($id);

        $productName = $product->name;
        $product->delete();

        return redirect()->route('v2.produk.index')->with('success', "Master produk \"{$productName}\" berhasil dihapus!");
    }

    public function print(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;
        $tenant = $user->tenant;

        $query = MasterProduct::with(['category', 'brand', 'marketplaceProducts.store.channel'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('sku')) {
            $sku = $request->sku;
            $query->where(function($q) use ($sku) {
                $q->where('sku', 'like', '%' . $sku . '%')
                  ->orWhere('sku_induk', 'like', '%' . $sku . '%');
            });
        }

        if ($request->filled('is_bundle')) {
            if ($request->is_bundle === '1') {
                $query->where('is_bundle', true);
            } elseif ($request->is_bundle === '0') {
                $query->where(function($q) {
                    $q->where('is_bundle', false)->orWhereNull('is_bundle');
                });
            }
        }

        if ($request->filled('is_preorder')) {
            if ($request->is_preorder === '1') {
                $query->where('is_preorder', true);
            } elseif ($request->is_preorder === '0') {
                $query->where(function($q) {
                    $q->where('is_preorder', false)->orWhereNull('is_preorder');
                });
            }
        }

        if ($request->filled('store_id')) {
            $query->whereHas('marketplaceProducts', function($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        if ($request->filled('link_status')) {
            if ($request->link_status === 'unlinked') {
                $query->where(function($q) {
                    $q->whereDoesntHave('marketplaceProducts')
                      ->orWhereDoesntHave('marketplaceProducts', function($mq) {
                          $mq->whereRaw('LOWER(TRIM(marketplace_sku)) = LOWER(TRIM(master_products.sku))');
                      });
                });
            }
        }

        $products = $query->orderBy('name')->get();

        return view('v2.produk.print', compact('products', 'tenant'));
    }
}
