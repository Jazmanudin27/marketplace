<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Channel;
use App\Models\Tenant;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

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

        $tab = $request->query('tab', '');
        if (!$tab) {
            if ($request->is_bundle === '0') {
                $tab = 'single';
            } elseif ($request->is_bundle === '1') {
                $tab = 'bundle';
            } elseif ($request->is_preorder === '0') {
                $tab = 'ready';
            } elseif ($request->is_preorder === '1') {
                $tab = 'po';
            } elseif ($request->link_status === 'unlinked') {
                $tab = 'unlinked';
            }
        }

        if ($tab === 'single') {
            $query->where(function($q) {
                $q->where('is_bundle', false)->orWhereNull('is_bundle');
            });
        } elseif ($tab === 'bundle') {
            $query->where('is_bundle', true);
        } elseif ($tab === 'ready') {
            $query->where(function($q) {
                $q->where('is_preorder', false)->orWhereNull('is_preorder');
            });
        } elseif ($tab === 'po') {
            $query->where('is_preorder', true);
        } elseif ($tab === 'unlinked') {
            $query->where(function($q) {
                $q->whereDoesntHave('marketplaceProducts')
                  ->orWhereDoesntHave('marketplaceProducts', function($mq) {
                      $mq->whereRaw('LOWER(TRIM(marketplace_sku)) = LOWER(TRIM(master_products.sku))');
                  });
            });
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

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
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

        $tenant = Tenant::find($tenantId);
        $lastImportAt = $tenant?->last_product_import_at;
        $canImport = true;
        $nextImportAt = null;

        if ($lastImportAt) {
            $nextImportAt = $lastImportAt->copy()->addDays(7);
            if (now()->lt($nextImportAt) && Auth::user()->role !== 'super-admin') {
                $canImport = false;
            }
        }

        return view('v2.produk.index', compact(
            'products',
            'stores',
            'channels',
            'categories',
            'brands',
            'counts',
            'tenant',
            'lastImportAt',
            'canImport',
            'nextImportAt'
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
        @set_time_limit(0);
        @ini_set('memory_limit', '1024M');

        $tenantId = Auth::user()->tenant_id;
        $tenant = Tenant::find($tenantId);

        // Validasi pembatasan import: Maksimal 1 kali dalam 1 minggu (7 hari)
        if ($tenant && $tenant->last_product_import_at) {
            $nextAllowed = $tenant->last_product_import_at->copy()->addDays(7);
            if (now()->lt($nextAllowed) && Auth::user()->role !== 'super-admin') {
                $formattedNext = $nextAllowed->translatedFormat('d F Y H:i');
                $diff = now()->diffForHumans($nextAllowed, ['parts' => 2, 'syntax' => CarbonInterface::DIFF_RELATIVE_TO_NOW]);
                return redirect()->back()->with('error', "Import master produk dibatasi maksimal 1 kali dalam 1 minggu. Terakhir diimport: {$tenant->last_product_import_at->translatedFormat('d F Y H:i')}. Anda baru dapat melakukan import kembali pada {$formattedNext} ({$diff}).");
            }
        }

        $request->validate([
            'file' => 'required|file|max:20480',
        ]);

        $file = $request->file('file');
        $filePath = $file->getRealPath();

        $insertedCount = 0;
        $updatedCount = 0;
        $skippedCount = 0;
        $hasBundleChanges = false;

        // ⚡ Cache schema check 1 kali di awal (mencegah ribuan query SHOW FULL COLUMNS di loop)
        $hasBarcodeCol = Schema::hasColumn('master_products', 'barcode');
        $hasShopeeCol  = Schema::hasColumn('master_products', 'shopee_price');
        $hasTiktokCol  = Schema::hasColumn('master_products', 'tiktok_price');
        $hasLazadaCol  = Schema::hasColumn('master_products', 'lazada_price');

        // ⚡ Preload semua Master Product tenant ke Memory Map dalam 1 Query (Mencegah Full Table Scan per baris)
        $masterProducts = MasterProduct::where('tenant_id', $tenantId)->get();
        $productMap = [];
        foreach ($masterProducts as $mp) {
            $skuUpper = strtoupper(trim((string)$mp->sku));
            if ($skuUpper !== '') {
                $productMap[$skuUpper] = $mp;
            }
            if ($hasBarcodeCol && !empty($mp->barcode)) {
                $bcUpper = strtoupper(trim((string)$mp->barcode));
                if ($bcUpper !== '') {
                    $productMap[$bcUpper] = $mp;
                }
            }
        }

        // ⚡ Preload Kategori & Brand ke memory cache
        $categoriesCache = Category::where('tenant_id', $tenantId)->get()->keyBy(function($c) {
            return strtolower(trim((string)$c->name));
        });
        $brandsCache = Brand::where('tenant_id', $tenantId)->get()->keyBy(function($b) {
            return strtolower(trim((string)$b->name));
        });

        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                rewind($handle);
            }

            // Detect delimiter (; or , or \t) from first line
            $firstLine = fgets($handle);
            if ($firstLine === false) {
                fclose($handle);
                return redirect()->back()->with('error', 'File CSV kosong.');
            }

            $counts = [
                ';' => substr_count($firstLine, ';'),
                ',' => substr_count($firstLine, ','),
                "\t" => substr_count($firstLine, "\t"),
            ];
            arsort($counts);
            $delimiter = key($counts) ?: ';';

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
                $colNameClean = preg_replace('/^\xEF\xBB\xBF/', '', (string)$colName);
                $clean = strtolower(trim(str_replace([' ', '_', '-', "\t", "\r", "\n"], '', $colNameClean)));
                $headerMap[$clean] = $idx;
            }

            // Helper to check non-empty string (abaikan kosong, 0, null, dash)
            $hasValue = function($val) {
                if ($val === null) return false;
                $trimmed = trim((string)$val);
                if ($trimmed === '' || $trimmed === '-' || $trimmed === '0') return false;
                $lower = strtolower($trimmed);
                return !in_array($lower, ['null', 'none', 'nan', '0', '-']);
            };

            // Helper to clean and parse numeric values
            $parseNumber = function($val) {
                if ($val === null || $val === '') return null;
                $trimmed = trim((string)$val);
                if ($trimmed === '' || $trimmed === '-') return null;
                if ($trimmed === '0' || $trimmed === '0.00' || $trimmed === '0,00') return 0.0;

                // Strip currency formatting, spaces, etc.
                $cleaned = preg_replace('/[^\d.,]/', '', $trimmed);
                if ($cleaned === '' || $cleaned === '0') return 0.0;

                // Handle both dot and comma
                if (strpos($cleaned, '.') !== false && strpos($cleaned, ',') !== false) {
                    if (strrpos($cleaned, ',') > strrpos($cleaned, '.')) {
                        $cleaned = str_replace('.', '', $cleaned);
                        $cleaned = str_replace(',', '.', $cleaned);
                    } else {
                        $cleaned = str_replace(',', '', $cleaned);
                    }
                } elseif (strpos($cleaned, ',') !== false) {
                    $parts = explode(',', $cleaned);
                    if (count($parts) === 2 && strlen($parts[1]) === 3 && (int)$parts[0] > 0) {
                        $cleaned = str_replace(',', '', $cleaned);
                    } else {
                        $cleaned = str_replace(',', '.', $cleaned);
                    }
                } elseif (strpos($cleaned, '.') !== false) {
                    $parts = explode('.', $cleaned);
                    if (count($parts) > 2) {
                        $cleaned = str_replace('.', '', $cleaned);
                    } elseif (count($parts) === 2 && strlen($parts[1]) === 3 && (int)$parts[0] >= 10) {
                        $cleaned = str_replace('.', '', $cleaned);
                    }
                }

                return is_numeric($cleaned) ? (float)$cleaned : null;
            };

            DB::beginTransaction();
            try {
                while (($row = fgetcsv($handle, 2000, $delimiter)) !== FALSE) {
                    if (count($row) < 1 || (count($row) === 1 && trim((string)$row[0]) === '')) continue;

                    $getValue = function($keyNames) use ($headerMap, $row) {
                        if (!is_array($keyNames)) $keyNames = [$keyNames];
                        foreach ($keyNames as $k) {
                            $cleanKey = strtolower(trim(str_replace([' ', '_', '-'], '', $k)));
                            if (isset($headerMap[$cleanKey]) && isset($row[$headerMap[$cleanKey]])) {
                                return trim((string)$row[$headerMap[$cleanKey]]);
                            }
                        }
                        return '';
                    };

                    $sku = strtoupper($getValue(['sku', 'kodesku', 'kodeproduk', 'productsku', 'itemsku', 'barcode', 'kode', 'skubarang']));
                    if (empty($sku)) {
                        $skippedCount++;
                        continue;
                    }

                    $rawName = $getValue(['namaproduk', 'nama', 'productname', 'namabarang', 'title']);
                    $rawSkuInduk = $getValue(['skuinduk', 'parentsku', 'induksku']);
                    $rawPrice = $getValue(['hargajual', 'harga', 'price', 'sellingprice', 'hargajualproduk']);
                    $rawReseller = $getValue(['hargadropship', 'dropship', 'hargareseller', 'resellerprice', 'reseller']);
                    $rawCost = $getValue(['hpphargabeli', 'hpp', 'costprice', 'hargabeli', 'modal', 'hargamodal']);
                    $rawStock = $getValue(['stok', 'stock', 'qty', 'jumlahstok', 'kuantitas']);
                    $rawMinStock = $getValue(['minstok', 'minstock', 'warningstok', 'safetystock', 'stokminimal']);
                    $rawUnit = $getValue(['satuan', 'unit', 'uom']);
                    $rawEstKain = $getValue(['estkain', 'estimasikain', 'kain', 'pemakaiankain']);
                    $rawEstBiaya = $getValue(['estbiayaproduksi', 'biayaproduksi', 'estproduksi', 'ongkosjahit']);
                    $rawCategory = $getValue(['kategori', 'category', 'namakategori']);
                    $rawBrand = $getValue(['brandmerek', 'brand', 'merek', 'model']);
                    $rawIsBundle = $getValue(['isbundle', 'bundle', 'paket', 'set']);
                    $rawIsPreorder = $getValue(['ispreorder', 'preorder', 'po']);

                    $rawShopeePrice = $getValue(['hargashopee', 'shopeeprice', 'shopee']);
                    $rawTiktokPrice = $getValue(['hargatiktok', 'tiktokprice', 'tiktok']);
                    $rawLazadaPrice = $getValue(['hargalazada', 'lazadaprice', 'lazada']);

                    // ⚡ O(1) Memory Lookup untuk mencari produk eksisting (Tanpa query database)
                    $existingProduct = $productMap[$sku] ?? null;

                    if ($existingProduct) {
                        // MODE UPDATE: Hanya update kolom yang ADA datanya (data kosong diabaikan)
                        $updateData = [];

                        if ($hasValue($rawName)) {
                            $updateData['name'] = $rawName;
                        }

                        if ($hasValue($rawSkuInduk)) {
                            $updateData['sku_induk'] = strtoupper($rawSkuInduk);
                        }

                        $parsedPrice = $parseNumber($rawPrice);
                        if ($parsedPrice !== null && $parsedPrice > 0) {
                            $updateData['price'] = $parsedPrice;
                        }

                        $parsedReseller = $parseNumber($rawReseller);
                        if ($parsedReseller !== null && $parsedReseller > 0) {
                            $updateData['reseller_price'] = $parsedReseller;
                        }

                        $parsedCost = $parseNumber($rawCost);
                        if ($parsedCost !== null && $parsedCost > 0) {
                            $updateData['cost_price'] = $parsedCost;
                        }

                        $parsedStock = $parseNumber($rawStock);
                        if ($parsedStock !== null && $rawStock !== '') {
                            $updateData['stock'] = (int)$parsedStock;
                        }

                        $parsedMinStock = $parseNumber($rawMinStock);
                        if ($parsedMinStock !== null && $rawMinStock !== '') {
                            $updateData['min_stock'] = (int)$parsedMinStock;
                        }

                        if ($hasValue($rawUnit)) {
                            $updateData['unit'] = $rawUnit;
                        }

                        $parsedEstKain = $parseNumber($rawEstKain);
                        if ($parsedEstKain !== null && $parsedEstKain > 0) {
                            $updateData['est_kain'] = $parsedEstKain;
                        }

                        $parsedEstBiaya = $parseNumber($rawEstBiaya);
                        if ($parsedEstBiaya !== null && $parsedEstBiaya > 0) {
                            $updateData['est_biaya_produksi'] = $parsedEstBiaya;
                        }

                        if ($hasValue($rawCategory)) {
                            $catKey = strtolower(trim($rawCategory));
                            if (!$categoriesCache->has($catKey)) {
                                $newCat = Category::create([
                                    'tenant_id' => $tenantId,
                                    'name' => $rawCategory,
                                    'slug' => \Illuminate\Support\Str::slug($rawCategory),
                                ]);
                                $categoriesCache->put($catKey, $newCat);
                            }
                            $updateData['category_id'] = $categoriesCache->get($catKey)->id;
                        }

                        if ($hasValue($rawBrand)) {
                            $brandKey = strtolower(trim($rawBrand));
                            if (!$brandsCache->has($brandKey)) {
                                $newBrand = Brand::create([
                                    'tenant_id' => $tenantId,
                                    'name' => $rawBrand,
                                    'slug' => \Illuminate\Support\Str::slug($rawBrand),
                                ]);
                                $brandsCache->put($brandKey, $newBrand);
                            }
                            $updateData['brand_id'] = $brandsCache->get($brandKey)->id;
                        }

                        if ($rawIsBundle !== '') {
                            $lowerBundle = strtolower(trim($rawIsBundle));
                            if (in_array($lowerBundle, ['1', 'true', 'yes', 'ya', 'paket', 'bundle'])) {
                                $updateData['is_bundle'] = true;
                                $hasBundleChanges = true;
                            } elseif (in_array($lowerBundle, ['0', 'false', 'tidak', 'no', 'bukan bundle', 'single'])) {
                                $updateData['is_bundle'] = false;
                            }
                        }

                        if ($rawIsPreorder !== '') {
                            $lowerPo = strtolower(trim($rawIsPreorder));
                            if (in_array($lowerPo, ['1', 'true', 'yes', 'ya', 'po', 'preorder'])) {
                                $updateData['is_preorder'] = true;
                            } elseif (in_array($lowerPo, ['0', 'false', 'tidak', 'no', 'ready', 'bukan po'])) {
                                $updateData['is_preorder'] = false;
                            }
                        }

                        if ($hasShopeeCol) {
                            $parsedShopee = $parseNumber($rawShopeePrice);
                            if ($parsedShopee !== null && $parsedShopee > 0) {
                                $updateData['shopee_price'] = $parsedShopee;
                            }
                        }
                        if ($hasTiktokCol) {
                            $parsedTiktok = $parseNumber($rawTiktokPrice);
                            if ($parsedTiktok !== null && $parsedTiktok > 0) {
                                $updateData['tiktok_price'] = $parsedTiktok;
                            }
                        }
                        if ($hasLazadaCol) {
                            $parsedLazada = $parseNumber($rawLazadaPrice);
                            if ($parsedLazada !== null && $parsedLazada > 0) {
                                $updateData['lazada_price'] = $parsedLazada;
                            }
                        }

                        if (!empty($updateData)) {
                            $existingProduct->update($updateData);
                            $updatedCount++;
                        } else {
                            $skippedCount++;
                        }
                    } else {
                        // MODE INSERT (Produk Baru / Belum Ada di Master Produk)
                        // Nama produk wajib di database: sesuaikan otomatis dengan SKU jika di file kosong
                        $productName = $hasValue($rawName) ? $rawName : $sku;
                        $skuInduk = $hasValue($rawSkuInduk) ? strtoupper($rawSkuInduk) : $sku;

                        $categoryId = null;
                        if ($hasValue($rawCategory)) {
                            $catKey = strtolower(trim($rawCategory));
                            if (!$categoriesCache->has($catKey)) {
                                $newCat = Category::create([
                                    'tenant_id' => $tenantId,
                                    'name' => $rawCategory,
                                    'slug' => \Illuminate\Support\Str::slug($rawCategory),
                                ]);
                                $categoriesCache->put($catKey, $newCat);
                            }
                            $categoryId = $categoriesCache->get($catKey)->id;
                        }

                        $brandId = null;
                        if ($hasValue($rawBrand)) {
                            $brandKey = strtolower(trim($rawBrand));
                            if (!$brandsCache->has($brandKey)) {
                                $newBrand = Brand::create([
                                    'tenant_id' => $tenantId,
                                    'name' => $rawBrand,
                                    'slug' => \Illuminate\Support\Str::slug($rawBrand),
                                ]);
                                $brandsCache->put($brandKey, $newBrand);
                            }
                            $brandId = $brandsCache->get($brandKey)->id;
                        }

                        $parsedPrice = $parseNumber($rawPrice);
                        $parsedReseller = $parseNumber($rawReseller);
                        $parsedCost = $parseNumber($rawCost);
                        $parsedStock = $parseNumber($rawStock);
                        $parsedMinStock = $parseNumber($rawMinStock);
                        $parsedEstKain = $parseNumber($rawEstKain);
                        $parsedEstBiaya = $parseNumber($rawEstBiaya);

                        $isBundle = false;
                        if ($rawIsBundle !== '') {
                            $lowerBundle = strtolower(trim($rawIsBundle));
                            $isBundle = in_array($lowerBundle, ['1', 'true', 'yes', 'ya', 'paket', 'bundle']);
                            if ($isBundle) $hasBundleChanges = true;
                        }

                        // Otomatis PO untuk produk baru (kecuali eksplisit diset 0 / ready / tidak)
                        $isPreorder = true;
                        if ($rawIsPreorder !== '') {
                            $lowerPo = strtolower(trim($rawIsPreorder));
                            if (in_array($lowerPo, ['0', 'false', 'tidak', 'no', 'ready', 'bukan po'])) {
                                $isPreorder = false;
                            }
                        }

                        $productData = [
                            'tenant_id' => $tenantId,
                            'name' => $productName,
                            'sku' => $sku,
                            'sku_induk' => $skuInduk,
                            'price' => ($parsedPrice !== null && $parsedPrice > 0) ? $parsedPrice : 0,
                            'reseller_price' => ($parsedReseller !== null && $parsedReseller > 0) ? $parsedReseller : 0,
                            'cost_price' => ($parsedCost !== null && $parsedCost > 0) ? $parsedCost : 0,
                            'stock' => ($parsedStock !== null && $parsedStock > 0) ? (int)$parsedStock : 0,
                            'min_stock' => ($parsedMinStock !== null && $parsedMinStock > 0) ? (int)$parsedMinStock : 5,
                            'unit' => $hasValue($rawUnit) ? $rawUnit : 'pcs',
                            'est_kain' => ($parsedEstKain !== null && $parsedEstKain > 0) ? $parsedEstKain : 0,
                            'est_biaya_produksi' => ($parsedEstBiaya !== null && $parsedEstBiaya > 0) ? $parsedEstBiaya : 0,
                            'category_id' => $categoryId,
                            'brand_id' => $brandId,
                            'is_bundle' => $isBundle,
                            'is_preorder' => $isPreorder,
                            'is_active' => true,
                        ];

                        if ($hasShopeeCol) {
                            $parsedShopee = $parseNumber($rawShopeePrice);
                            if ($parsedShopee !== null && $parsedShopee > 0) {
                                $productData['shopee_price'] = $parsedShopee;
                            }
                        }
                        if ($hasTiktokCol) {
                            $parsedTiktok = $parseNumber($rawTiktokPrice);
                            if ($parsedTiktok !== null && $parsedTiktok > 0) {
                                $productData['tiktok_price'] = $parsedTiktok;
                            }
                        }
                        if ($hasLazadaCol) {
                            $parsedLazada = $parseNumber($rawLazadaPrice);
                            if ($parsedLazada !== null && $parsedLazada > 0) {
                                $productData['lazada_price'] = $parsedLazada;
                            }
                        }

                        $created = MasterProduct::create($productData);
                        $productMap[$sku] = $created;
                        $insertedCount++;
                    }
                }
                DB::commit();
            } catch (\Throwable $ex) {
                DB::rollBack();
                fclose($handle);
                return redirect()->back()->with('error', 'Gagal memproses import data: ' . $ex->getMessage());
            }

            fclose($handle);

            // ⚡ Hanya jalankan kalkulasi bundle jika memang ada bundle yang dibuat / diupdate
            if ($hasBundleChanges && method_exists(MasterProduct::class, 'recalculateAllBundleStocks')) {
                try {
                    MasterProduct::recalculateAllBundleStocks($tenantId);
                } catch (\Throwable $e) {}
            }
        }

        if ($tenant) {
            $tenant->update(['last_product_import_at' => now()]);
        }

        return redirect()->route('v2.produk.index')->with('success', "Import produk selesai! {$insertedCount} produk baru ditambahkan (otomatis PO), {$updatedCount} produk diperbarui" . ($skippedCount > 0 ? ", {$skippedCount} baris tidak diupdate/dilewati (karena kosong atau SKU tidak valid)." : "."));
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

    public function bulkDelete(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $ids = $request->input('ids', []);

        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada produk yang dipilih untuk dihapus.',
            ], 422);
        }

        $count = MasterProduct::where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => "{$count} master produk berhasil dihapus secara masal!",
            'deleted_count' => $count,
        ]);
    }

    public function bulkSetPo(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $ids = $request->input('ids', []);
        $isPreorder = $request->boolean('is_preorder', true);

        if (empty($ids) || !is_array($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada produk yang dipilih.',
            ], 422);
        }

        // Update master products
        $updatedRows = MasterProduct::where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->update([
                'is_preorder' => $isPreorder,
            ]);

        // Sinkronkan juga status is_pre_order di produk marketplace yang terhubung
        \App\Models\MarketplaceProduct::whereIn('master_product_id', $ids)
            ->update([
                'is_pre_order' => $isPreorder,
            ]);

        $totalAffected = count($ids);
        $statusText = $isPreorder ? 'Pre-Order (PO)' : 'Ready Stock';

        return response()->json([
            'success' => true,
            'message' => "{$totalAffected} produk berhasil diatur menjadi {$statusText}!",
            'updated_count' => $totalAffected,
        ]);
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
