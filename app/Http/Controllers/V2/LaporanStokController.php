<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Channel;
use App\Models\MasterProduct;
use App\Models\SupplierConsignmentItem;
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
        $tab    = $request->get('tab');
        $format = $request->get('report_format', 'standard');

        if ($tab === 'single' || $request->get('is_bundle') === '0') {
            $query->where(function($q) {
                $q->where('is_bundle', false)->orWhereNull('is_bundle');
            });
        } elseif ($tab === 'bundle' || $request->get('is_bundle') === '1') {
            $query->where('is_bundle', true);
        }

        // Tipe Pre-Order (PO vs Reguler)
        $poStatus = $request->get('po_status', $request->get('is_preorder'));
        if ($tab === 'ready' || $poStatus === '0') {
            $query->where(function ($q) {
                $q->where('is_preorder', false)->orWhereNull('is_preorder');
            });
        } elseif ($tab === 'po' || $poStatus === '1') {
            $query->where('is_preorder', true);
        }

        // Filter Discrepancy (Selisih)
        if ($tab === 'diff' || $format === 'discrepancy' || $request->boolean('only_different') || $request->boolean('only_diff')) {
            $query->whereHas('marketplaceProducts', function ($mq) {
                $mq->where('sync_stock', true)
                   ->whereColumn('stock', '!=', 'master_products.stock');
            });
        }

        // Filter Low Stock (< 10 PCS)
        if ($tab === 'low' || $format === 'low_stock' || $request->boolean('low_stock') || $request->boolean('only_low')) {
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

        // Sort By
        $sortBy = $request->get('sort_by', 'name_asc');
        switch ($sortBy) {
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'stock_desc':
                $query->orderBy('stock', 'desc');
                break;
            case 'stock_asc':
                $query->orderBy('stock', 'asc');
                break;
            case 'sku_asc':
                $query->orderBy('sku', 'asc');
                break;
            case 'sku_desc':
                $query->orderBy('sku', 'desc');
                break;
            case 'name_asc':
            default:
                $query->orderBy('name', 'asc');
                break;
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
        $products = $query->paginate(50)->withQueryString();

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
        $products = $query->get();
        $format   = $request->get('report_format', 'standard');

        // Hitung stok titipan konsinyasi (approved) per master product
        $titipanItems = SupplierConsignmentItem::whereHas('consignment', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)->where('status', 'approved');
        })->get()->groupBy('master_product_id');

        foreach ($products as $p) {
            $consGroup = $titipanItems->get($p->id);
            $stokTitipan = 0;
            if ($consGroup) {
                $qtyRec = $consGroup->sum('qty_received');
                $qtySold = (int) $consGroup->sum('qty_sold');
                $stokTitipan = max(0, $qtyRec - $qtySold);
            }
            $p->stok_gudang  = (int) $p->stock;
            $p->stok_titipan = $stokTitipan;
            $p->jumlah_stok  = $p->stok_gudang + $stokTitipan;
        }

        return view('v2.laporan_stok.print', compact('products', 'stores', 'format'));
    }

    /**
     * Export Stock Report to CSV/Excel.
     */
    public function export(Request $request)
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
        $products = $query->get();
        $format   = $request->get('report_format', 'standard');

        // Hitung stok titipan konsinyasi (approved) per master product
        $titipanItems = SupplierConsignmentItem::whereHas('consignment', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)->where('status', 'approved');
        })->get()->groupBy('master_product_id');

        foreach ($products as $p) {
            $consGroup = $titipanItems->get($p->id);
            $stokTitipan = 0;
            if ($consGroup) {
                $qtyRec = $consGroup->sum('qty_received');
                $qtySold = (int) $consGroup->sum('qty_sold');
                $stokTitipan = max(0, $qtyRec - $qtySold);
            }
            $p->stok_gudang  = (int) $p->stock;
            $p->stok_titipan = $stokTitipan;
            $p->jumlah_stok  = $p->stok_gudang + $stokTitipan;
        }

        $filename = 'Laporan_Stok_' . ucfirst($format) . '_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($products, $stores, $format) {
            $output = fopen('php://output', 'w');
            // Write UTF-8 BOM so Excel opens with proper encoding
            fputs($output, "\xEF\xBB\xBF");

            if ($format === 'warehouse') {
                // Format Ringkas Gudang / Stock Opname
                fputcsv($output, [
                    'NO',
                    'SKU',
                    'SKU INDUK',
                    'NAMA PRODUK',
                    'KATEGORI',
                    'BRAND',
                    'TIPE',
                    'STATUS PO',
                    'MIN STOK',
                    'STOK FISIK GUDANG (PCS)',
                    'HASIL CEK FISIK / OPNAME',
                    'SELISIH FISIK',
                    'CATATAN / KONDISI'
                ], ';');

                foreach ($products as $i => $p) {
                    fputcsv($output, [
                        $i + 1,
                        $p->sku ?: '-',
                        $p->sku_induk ?: '-',
                        $p->name,
                        $p->category->name ?? '-',
                        $p->brand->name ?? '-',
                        $p->is_bundle ? 'Bundle' : 'Single',
                        $p->is_preorder ? 'PO' : 'Ready',
                        $p->min_stock ?? 5,
                        (int)$p->stock,
                        '',
                        '',
                        ''
                    ], ';');
                }
            } elseif ($format === 'discrepancy') {
                // Format Selisih Stok
                fputcsv($output, [
                    'NO',
                    'SKU',
                    'NAMA PRODUK',
                    'STOK GUDANG',
                    'NAMA TOKO MARKETPLACE',
                    'CHANNEL',
                    'KUOTA MARKETPLACE',
                    'SELISIH (MP - GUDANG)',
                    'STATUS SINKRONISASI'
                ], ';');

                $rowNum = 1;
                foreach ($products as $p) {
                    $gudangStock = (int)$p->stock;
                    foreach ($p->marketplaceProducts as $mp) {
                        if (!$mp->store) continue;
                        $mpStock = (int)$mp->stock;
                        $diff = $mpStock - $gudangStock;
                        if ($diff !== 0) {
                            fputcsv($output, [
                                $rowNum++,
                                $p->sku ?: '-',
                                $p->name,
                                $gudangStock,
                                $mp->store->store_name,
                                $mp->store->channel->name ?? 'MP',
                                $mpStock,
                                ($diff > 0 ? "+{$diff}" : $diff),
                                $mp->sync_stock ? 'Perlu Sinkronisasi Ulang' : 'Sync Nonaktif'
                            ], ';');
                        }
                    }
                }
            } elseif ($format === 'low_stock') {
                // Format Stok Kritis / Menipis
                fputcsv($output, [
                    'NO',
                    'SKU',
                    'SKU INDUK',
                    'NAMA PRODUK',
                    'KATEGORI',
                    'BRAND',
                    'STATUS',
                    'MIN STOK',
                    'SISA STOK SAAT INI',
                    'DEFISIT / KEKURANGAN',
                    'STATUS REKOMENDASI'
                ], ';');

                foreach ($products as $i => $p) {
                    $currStock = (int)$p->stock;
                    $minStock = (int)($p->min_stock ?? 10);
                    $deficit = max(0, $minStock - $currStock);
                    fputcsv($output, [
                        $i + 1,
                        $p->sku ?: '-',
                        $p->sku_induk ?: '-',
                        $p->name,
                        $p->category->name ?? '-',
                        $p->brand->name ?? '-',
                        $p->is_preorder ? 'PO' : 'Ready',
                        $minStock,
                        $currStock,
                        $deficit,
                        $currStock <= 0 ? 'HABIS TOTAL (URGENT RESTOCK)' : 'MENIPIS (SEGERA PRODUKSI/BELANJA)'
                    ], ';');
                }
            } else {
                // Format Standar / Lengkap: SKU, Nama Produk, Stok Gudang, Stok Titipan, Jumlah Stok
                $headers = [
                    'NO',
                    'SKU',
                    'SKU INDUK',
                    'NAMA PRODUK',
                    'KATEGORI',
                    'BRAND',
                    'JENIS',
                    'STATUS PO',
                    'STOK GUDANG',
                    'STOK TITIPAN',
                    'JUMLAH STOK'
                ];
                foreach ($stores as $store) {
                    $ch = $store->channel->name ?? 'MP';
                    $headers[] = "{$store->store_name} ({$ch})";
                }
                $headers[] = 'STATUS SINKRON';

                fputcsv($output, $headers, ';');

                foreach ($products as $i => $p) {
                    $gudangStock  = (int)($p->stok_gudang ?? $p->stock);
                    $titipanStock = (int)($p->stok_titipan ?? 0);
                    $totalStock   = (int)($p->jumlah_stok ?? ($gudangStock + $titipanStock));

                    $row = [
                        $i + 1,
                        $p->sku ?: '-',
                        $p->sku_induk ?: '-',
                        $p->name,
                        $p->category->name ?? '-',
                        $p->brand->name ?? '-',
                        $p->is_bundle ? 'Bundle' : 'Single',
                        $p->is_preorder ? 'PO' : 'Ready',
                        $gudangStock,
                        $titipanStock,
                        $totalStock
                    ];

                    $hasDiff = false;
                    foreach ($stores as $store) {
                        $mp = $p->marketplaceProducts->firstWhere('store_id', $store->id);
                        if ($mp) {
                            $stk = (int)$mp->stock;
                            if ($mp->sync_stock && $stk !== $gudangStock) {
                                $hasDiff = true;
                            }
                            $row[] = $stk;
                        } else {
                            $row[] = '-';
                        }
                    }

                    $row[] = $hasDiff ? 'Ada Selisih' : 'Sinkron';
                    fputcsv($output, $row, ';');
                }
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
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
        @set_time_limit(300);
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
                PushStockToMarketplaces::dispatch($p->id, (int)$p->stock, true);
                $count++;
            } catch (\Throwable $e) {
                $errors[] = $p->sku . ': ' . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'count'   => $count,
            'errors'  => $errors,
            'message' => "Berhasil memasukkan {$count} produk ke antrean sinkronisasi marketplace di background."
        ]);
    }
}
