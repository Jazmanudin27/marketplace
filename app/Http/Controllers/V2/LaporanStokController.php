<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Channel;
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

        // Tab filter (matches Master Product)
        $tab = $request->get('tab');
        if ($tab === 'single') {
            $query->where(function($q) {
                $q->where('is_bundle', false)->orWhereNull('is_bundle');
            });
        } elseif ($tab === 'bundle') {
            $query->where('is_bundle', true);
        } elseif ($tab === 'ready') {
            $query->where(function ($q) {
                $q->where('is_preorder', false)->orWhereNull('is_preorder');
            });
        } elseif ($tab === 'po') {
            $query->where('is_preorder', true);
        } elseif ($tab === 'diff') {
            $query->whereHas('marketplaceProducts', function ($mq) {
                $mq->where('sync_stock', true)
                   ->whereColumn('stock', '!=', 'master_products.stock');
            });
        } elseif ($tab === 'low') {
            $query->where('stock', '<', 10);
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('product_id')) {
            $query->where('id', $request->product_id);
        }

        if (!$tab && $request->filled('is_bundle')) {
            $query->where('is_bundle', (bool)$request->is_bundle);
        }

        if (!$tab && $request->filled('is_preorder')) {
            if ($request->is_preorder === '1') {
                $query->where('is_preorder', true);
            } elseif ($request->is_preorder === '0') {
                $query->where(function ($q) {
                    $q->where('is_preorder', false)
                      ->orWhereNull('is_preorder');
                });
            }
        }

        if ($request->filled('name')) {
            $name = trim($request->name);
            $query->where('name', 'like', "%{$name}%");
        }

        if ($request->filled('sku')) {
            $sku = trim($request->sku);
            $query->where(function ($q) use ($sku) {
                $q->where('sku', 'like', "%{$sku}%")
                  ->orWhere('sku_induk', 'like', "%{$sku}%");
            });
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('sku_induk', 'like', "%{$search}%");
            });
        }

        if ($request->filled('channel_id')) {
            $channelId = $request->channel_id;
            $query->whereHas('marketplaceProducts.store', function ($sq) use ($channelId) {
                $sq->where('channel_id', $channelId);
            });
        }

        if ($request->filled('store_id')) {
            $storeId = $request->store_id;
            $query->whereHas('marketplaceProducts', function ($mq) use ($storeId) {
                $mq->where('store_id', $storeId);
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

        if ($request->boolean('only_different') || $request->boolean('only_diff')) {
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
        $channels   = Channel::orderBy('name')->get();

        $storesQuery = Store::with('channel')
            ->where('tenant_id', $tenantId);

        if ($request->filled('store_id')) {
            $storesQuery->where('id', $request->store_id);
        }

        $allStores = Store::with('channel')->where('tenant_id', $tenantId)->orderBy('store_name')->get();
        $stores    = $storesQuery->orderBy('store_name')->get();

        // Base KPI metrics matching Master Product layout (PO dikecualikan dari diff)
        $counts = [
            'total'       => MasterProduct::where('tenant_id', $tenantId)->count(),
            'single'      => MasterProduct::where('tenant_id', $tenantId)->where(function($q) {
                $q->where('is_bundle', false)->orWhereNull('is_bundle');
            })->count(),
            'bundle'      => MasterProduct::where('tenant_id', $tenantId)->where('is_bundle', true)->count(),
            'ready'       => MasterProduct::where('tenant_id', $tenantId)->where(function($q) {
                $q->where('is_preorder', false)->orWhereNull('is_preorder');
            })->count(),
            'po'          => MasterProduct::where('tenant_id', $tenantId)->where('is_preorder', true)->count(),
            'diff'        => MasterProduct::where('tenant_id', $tenantId)
                ->whereHas('marketplaceProducts', function ($mq) {
                    $mq->where('sync_stock', true)
                       ->whereColumn('stock', '!=', 'master_products.stock');
                })->count(),
            'low'         => MasterProduct::where('tenant_id', $tenantId)->where('stock', '<', 10)->count(),
            'total_stock' => (int) MasterProduct::where('tenant_id', $tenantId)->sum('stock'),
        ];

        $kpi = [
            'total_products' => $counts['total'],
            'total_single'   => $counts['single'],
            'total_bundle'   => $counts['bundle'],
            'total_ready'    => $counts['ready'],
            'total_po'       => $counts['po'],
            'total_diff'     => $counts['diff'],
            'total_low'      => $counts['low'],
            'total_stock'    => $counts['total_stock'],
        ];

        $query    = $this->buildQuery($request, $tenantId);
        $products = $query->orderBy('name')->paginate(50)->withQueryString();

        return view('v2.laporan_stok.index', compact('categories', 'brands', 'stores', 'allStores', 'channels', 'products', 'counts', 'kpi'));
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

        try {
            // Jalankan push stok secara langsung (synchronous) ke marketplace.
            // Khusus menu Laporan Stok V2, forcePushPreOrder = true agar produk PO tetap dapat disinkronkan.
            PushStockToMarketplaces::dispatchSync($product->id, (int)$product->stock, true);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Gagal sync manual stok produk ID {$product->id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => "Gagal sinkronisasi ke marketplace: " . $e->getMessage()
            ], 500);
        }

        // Ambil stok marketplace terbaru setelah sync
        $product->load(['marketplaceProducts.store.channel']);
        $storeStocks = [];
        foreach ($product->marketplaceProducts as $mp) {
            if ($mp->store) {
                $storeStocks[] = [
                    'store_id'   => $mp->store_id,
                    'store_name' => $mp->store->store_name,
                    'stock'      => (int) $mp->stock,
                ];
            }
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success'      => true,
                'master_stock' => (int) $product->stock,
                'store_stocks' => $storeStocks,
                'message'      => "Stok produk {$product->sku} ({$product->name}) berhasil disinkronkan langsung ke marketplace."
            ]);
        }

        return back()->with('success', "Stok produk {$product->sku} ({$product->name}) berhasil disinkronkan langsung ke marketplace.");
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
        $errors = [];
        foreach ($products as $p) {
            try {
                // Khusus menu V2, forcePushPreOrder = true
                PushStockToMarketplaces::dispatchSync($p->id, (int)$p->stock, true);
                $count++;
            } catch (\Throwable $e) {
                $errors[] = $p->sku . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'count'   => $count,
            'errors'  => $errors,
            'message' => "Berhasil menyinkronkan {$count} produk ke marketplace."
        ]);
    }
}
