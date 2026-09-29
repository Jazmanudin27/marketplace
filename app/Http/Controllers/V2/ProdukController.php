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

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;

        $categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands = Brand::where('tenant_id', $tenantId)->orderBy('name')->get();

        return view('v2.produk.create', compact('categories', 'brands'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'reseller_price' => 'nullable|numeric|min:0',
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
            'reseller_price' => $request->reseller_price ?? 0,
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

    public function storeAutoBundle(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:100',
            'price' => 'nullable|numeric|min:0',
            'reseller_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable',
            'brand_id' => 'nullable',
            'components' => 'required|array|min:1',
            'components.*.id' => 'required|exists:master_products,id',
            'components.*.quantity' => 'required|integer|min:1',
        ]);

        $sku = strtoupper(trim($request->sku));
        if (MasterProduct::where('tenant_id', $tenantId)->where('sku', $sku)->exists()) {
            return redirect()->back()->withErrors(['sku' => 'SKU Bundle "' . $sku . '" sudah digunakan. Silakan gunakan SKU lain.'])->withInput();
        }

        $bundle = MasterProduct::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'sku' => $sku,
            'sku_induk' => $request->sku_induk ? strtoupper(trim($request->sku_induk)) : $sku,
            'price' => $request->price ?? 0,
            'selling_price' => $request->price ?? 0,
            'reseller_price' => $request->reseller_price ?? 0,
            'cost_price' => $request->cost_price ?? 0,
            'stock' => 0,
            'min_stock' => $request->min_stock ?? 5,
            'unit' => $request->unit ?? 'set',
            'category_id' => $request->category_id ?: null,
            'brand_id' => $request->brand_id ?: null,
            'is_bundle' => true,
            'is_preorder' => $request->filled('is_preorder') ? (bool)$request->is_preorder : false,
            'is_active' => true,
        ]);

        foreach ($request->components as $comp) {
            $childId = $comp['id'] ?? null;
            $qty = (int) ($comp['quantity'] ?? 1);
            if ($childId && $qty > 0) {
                $bundle->components()->attach($childId, ['quantity' => $qty]);
            }
        }

        MasterProduct::recalculateAllBundleStocks($tenantId);

        return redirect()->route('v2.produk.index')->with('success', "Set Bundle Paket \"{$bundle->name}\" berhasil dibuat dari " . count($request->components) . " produk komponen!");
    }

    public function downloadImportTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="Template_Import_Master_Produk.csv"',
        ];

        $columns = [
            'sku',
            'nama_produk',
            'sku_induk',
            'kategori',
            'brand_merek',
            'harga_jual',
            'harga_dropship',
            'hpp_harga_beli',
            'stok',
            'min_stok',
            'satuan',
            'est_kain',
            'est_biaya_produksi',
            'is_bundle',
            'is_preorder'
        ];

        $sampleRow1 = [
            'SMP-PJG-L',
            'Seragam SMP Lengan Panjang Size L',
            'SMP-PJG',
            'Seragam Sekolah',
            'Lengan Panjang',
            '109000',
            '95000',
            '75000',
            '50',
            '5',
            'pcs',
            '1.50',
            '25000',
            '0',
            '0'
        ];

        $sampleRow2 = [
            'SMA-PDK-M',
            'Seragam SMA Lengan Pendek Size M',
            'SMA-PDK',
            'Seragam Sekolah',
            'Lengan Pendek',
            '98000',
            '85000',
            '68000',
            '35',
            '5',
            'pcs',
            '1.20',
            '22000',
            '0',
            '0'
        ];

        $callback = function () use ($columns, $sampleRow1, $sampleRow2) {
            $file = fopen('php://output', 'w');
            // UTF-8 BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($file, $columns, ';');
            fputcsv($file, $sampleRow1, ';');
            fputcsv($file, $sampleRow2, ';');
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importProduct(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'file' => 'required|file|max:10240',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Detect delimiter (; or ,) from first line
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                fclose($handle);
                return redirect()->back()->with('error', 'File CSV kosong.');
            }

            $delimiter = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';

            // Rewind & skip BOM again
            rewind($handle);
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            $header = fgetcsv($handle, 2000, $delimiter);
            if (!$header) {
                fclose($handle);
                return redirect()->back()->with('error', 'File CSV kosong atau format tidak valid.');
            }

            $headerMap = [];
            foreach ($header as $idx => $colName) {
                $clean = strtolower(trim(str_replace([' ', '_', '-'], '', $colName)));
                $headerMap[$clean] = $idx;
            }

            while (($row = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
                if (count($row) < 2) continue;

                $getValue = function($keyNames) use ($headerMap, $row) {
                    if (!is_array($keyNames)) $keyNames = [$keyNames];
                    foreach ($keyNames as $k) {
                        $cleanKey = strtolower(trim(str_replace([' ', '_', '-'], '', $k)));
                        if (isset($headerMap[$cleanKey]) && isset($row[$headerMap[$cleanKey]])) {
                            return trim($row[$headerMap[$cleanKey]]);
                        }
                    }
                    return '';
                };

                $sku = strtoupper($getValue(['sku', 'kodesku']));
                $name = $getValue(['namaproduk', 'nama', 'productname']);

                if (empty($sku) || empty($name)) {
                    $skippedCount++;
                    continue;
                }

                $skuInduk = $getValue(['skuinduk', 'parentsku']);
                $skuInduk = !empty($skuInduk) ? strtoupper($skuInduk) : $sku;

                $price = floatval($getValue(['hargajual', 'harga', 'price', 'sellingprice']));
                $resellerPrice = floatval($getValue(['hargadropship', 'dropship', 'hargareseller', 'resellerprice']));
                $costPrice = floatval($getValue(['hpphargabeli', 'hpp', 'costprice', 'hargabeli']));
                $stock = intval($getValue(['stok', 'stock', 'qty']));
                $minStock = intval($getValue(['minstok', 'minstock', 'warningstok']));
                if ($minStock <= 0) $minStock = 5;

                $unit = $getValue(['satuan', 'unit']) ?: 'pcs';
                $estKain = floatval($getValue(['estkain', 'estimasikain']));
                $estBiayaProduksi = floatval($getValue(['estbiayaproduksi', 'biayaproduksi', 'estproduksi']));
                
                $isBundleVal = strtolower($getValue(['isbundle', 'bundle', 'paket']));
                $isBundle = in_array($isBundleVal, ['1', 'true', 'yes', 'ya', 'paket', 'bundle']) ? 1 : 0;

                $isPreorderVal = strtolower($getValue(['ispreorder', 'preorder', 'po']));
                $isPreorder = in_array($isPreorderVal, ['1', 'true', 'yes', 'ya', 'po']) ? 1 : 0;

                // Process Category
                $categoryId = null;
                $catName = $getValue(['kategori', 'category']);
                if (!empty($catName)) {
                    $category = Category::firstOrCreate(
                        ['tenant_id' => $tenantId, 'name' => $catName],
                        ['slug' => \Illuminate\Support\Str::slug($catName)]
                    );
                    $categoryId = $category->id;
                }

                // Process Brand / Model
                $brandId = null;
                $brandName = $getValue(['brandmerek', 'brand', 'merek', 'model']);
                if (!empty($brandName)) {
                    $brand = Brand::firstOrCreate(
                        ['tenant_id' => $tenantId, 'name' => $brandName],
                        ['slug' => \Illuminate\Support\Str::slug($brandName)]
                    );
                    $brandId = $brand->id;
                }

                $existingProduct = MasterProduct::where('tenant_id', $tenantId)->where('sku', $sku)->first();

                $productData = [
                    'tenant_id' => $tenantId,
                    'name' => $name,
                    'sku' => $sku,
                    'sku_induk' => $skuInduk,
                    'price' => $price,
                    'selling_price' => $price,
                    'reseller_price' => $resellerPrice,
                    'cost_price' => $costPrice,
                    'stock' => $stock,
                    'min_stock' => $minStock,
                    'unit' => $unit,
                    'est_kain' => $estKain,
                    'est_biaya_produksi' => $estBiayaProduksi,
                    'category_id' => $categoryId,
                    'brand_id' => $brandId,
                    'is_bundle' => (bool)$isBundle,
                    'is_preorder' => (bool)$isPreorder,
                    'is_active' => true,
                ];

                if ($existingProduct) {
                    $existingProduct->update($productData);
                    $updatedCount++;
                } else {
                    MasterProduct::create($productData);
                    $insertedCount++;
                }
            }
            fclose($handle);
        }

        return redirect()->route('v2.produk.index')->with('success', "Import produk berhasil! {$insertedCount} produk baru ditambahkan, {$updatedCount} produk diperbarui" . ($skippedCount > 0 ? ", {$skippedCount} baris dilewati (SKU/Nama kosong)." : "."));
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
            'reseller_price' => 'nullable|numeric|min:0',
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
            'reseller_price' => $request->reseller_price ?? 0,
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
