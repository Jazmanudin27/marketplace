<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Store;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Jobs\PushStockToMarketplaces;

class LaporanStokController extends Controller
{
    private function checkAccess()
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $user->role !== 'admin' && $user->role !== 'owner' && !$user->can('reports.stock')) {
            abort(403, 'Anda tidak memiliki hak akses untuk melihat Laporan Stok Barang.');
        }
    }

    /**
     * Build filtered MasterProduct query based on request.
     */
    private function buildQuery(Request $request, $tenantId)
    {
        $query = MasterProduct::with(['category', 'brand', 'marketplaceProducts.store.channel'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('product_id')) {
            $query->where('id', $request->product_id);
        }

        if ($request->filled('is_bundle')) {
            $query->where('is_bundle', (bool)$request->is_bundle);
        }

        if ($request->filled('is_preorder')) {
            if ($request->is_preorder === '1') {
                $query->where('is_preorder', true);
            } elseif ($request->is_preorder === '0') {
                $query->where(function ($q) {
                    $q->where('is_preorder', false)
                      ->orWhereNull('is_preorder');
                });
            }
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->boolean('hide_zero_stock')) {
            $query->where(function ($q) {
                $q->where('stock', '>', 0)
                  ->orWhereHas('marketplaceProducts', function ($mq) {
                      $mq->where('stock', '>', 0);
                  });
            });
        }

        if ($request->boolean('only_different')) {
            $query->whereHas('marketplaceProducts', function ($mq) {
                $mq->where('sync_stock', true)
                   ->whereColumn('stock', '!=', 'master_products.stock');
            });
        }

        return $query;
    }

    /**
     * Display V2 Stock Report Index Page.
     */
    public function index(Request $request)
    {
        $this->checkAccess();
        $tenantId = Auth::user()->tenant_id;

        $categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands     = Brand::where('tenant_id', $tenantId)->orderBy('name')->get();

        $storesQuery = Store::with('channel')
            ->where('tenant_id', $tenantId);

        if ($request->filled('store_id')) {
            $storesQuery->where('id', $request->store_id);
        }

        $allStores = Store::with('channel')->where('tenant_id', $tenantId)->orderBy('store_name')->get();
        $stores    = $storesQuery->orderBy('store_name')->get();

        // Base KPI metrics
        $kpi = [
            'total_products'   => MasterProduct::where('tenant_id', $tenantId)->count(),
            'total_single'     => MasterProduct::where('tenant_id', $tenantId)->where(function($q) {
                $q->where('is_bundle', false)->orWhereNull('is_bundle');
            })->count(),
            'total_bundle'     => MasterProduct::where('tenant_id', $tenantId)->where('is_bundle', true)->count(),
            'total_po'         => MasterProduct::where('tenant_id', $tenantId)->where('is_preorder', true)->count(),
            'total_stock'      => (int) MasterProduct::where('tenant_id', $tenantId)->sum('stock'),
            'total_diff'       => MasterProduct::where('tenant_id', $tenantId)
                ->whereHas('marketplaceProducts', function ($mq) {
                    $mq->where('sync_stock', true)
                       ->whereColumn('stock', '!=', 'master_products.stock');
                })->count(),
        ];

        $query    = $this->buildQuery($request, $tenantId);
        $products = $query->orderBy('name')->paginate(50)->withQueryString();

        return view('v2.laporan_stok.index', compact('categories', 'brands', 'stores', 'allStores', 'products', 'kpi'));
    }

    /**
     * Print View for V2 Stock Report.
     */
    public function print(Request $request)
    {
        $this->checkAccess();
        $tenantId = Auth::user()->tenant_id;

        $storesQuery = Store::with('channel')
            ->where('tenant_id', $tenantId);

        if ($request->filled('store_id')) {
            $storesQuery->where('id', $request->store_id);
        }

        $stores   = $storesQuery->orderBy('store_name')->get();
        $query    = $this->buildQuery($request, $tenantId);
        $products = $query->orderBy('name')->get();

        return view('v2.laporan_stok.print', compact('products', 'stores'));
    }

    /**
     * Synchronize stock for a single product to connected marketplaces.
     */
    public function syncStock(Request $request, MasterProduct $product)
    {
        $this->checkAccess();
        $tenantId = Auth::user()->tenant_id;
        abort_unless($product->tenant_id === $tenantId, 403);

        if ($product->tenant_id === 2) {
            PushStockToMarketplaces::dispatchSync($product->id, $product->stock);
        } else {
            PushStockToMarketplaces::dispatch($product->id, $product->stock);
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Stok produk {$product->sku} ({$product->name}) berhasil disinkronkan ke marketplace."
            ]);
        }

        return back()->with('success', "Stok produk {$product->sku} ({$product->name}) berhasil disinkronkan ke marketplace.");
    }

    /**
     * Batch synchronize multiple or all discrepancy products.
     */
    public function syncBatch(Request $request)
    {
        $this->checkAccess();
        $tenantId = Auth::user()->tenant_id;
        $productIds = $request->input('product_ids', []);

        if (empty($productIds)) {
            $products = MasterProduct::where('tenant_id', $tenantId)
                ->whereHas('marketplaceProducts', function ($mq) {
                    $mq->where('sync_stock', true)
                       ->whereColumn('stock', '!=', 'master_products.stock');
                })
                ->get();
        } else {
            $products = MasterProduct::where('tenant_id', $tenantId)
                ->whereIn('id', $productIds)
                ->get();
        }

        $count = 0;
        foreach ($products as $p) {
            if ($p->tenant_id === 2) {
                PushStockToMarketplaces::dispatchSync($p->id, $p->stock);
            } else {
                PushStockToMarketplaces::dispatch($p->id, $p->stock);
            }
            $count++;
        }

        return response()->json([
            'success' => true,
            'count'   => $count,
            'message' => "Berhasil memicu sinkronisasi untuk {$count} produk ke marketplace."
        ]);
    }
}
