<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use App\Models\CategoryMapping;
use App\Models\BrandMapping;
use App\Models\PublicationLog;
use App\Jobs\PublishProductToMarketplace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\ShopeeService;
use App\Services\TiktokService;

class MasterProductController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Auto-recovery: reset job yang stuck > 3 menit ke status failed
        PublicationLog::where('tenant_id', $tenantId)
            ->where('status', 'processing')
            ->where('updated_at', '<', now()->subMinutes(3))
            ->update([
                'status'        => 'failed',
                'error_message' => 'Job timeout: Queue worker berhenti tak terduga. Klik Retry untuk coba ulang.',
            ]);

        $connectedStoresCount = \App\Models\Store::where('tenant_id', $tenantId)
            ->where('status', 'connected')
            ->count();

        $query = MasterProduct::with(['marketplaceProducts.store.channel', 'category', 'brand'])
            ->where('tenant_id', $tenantId);

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
                $query->whereDoesntHave('marketplaceProducts');
            } elseif ($request->link_status === 'partial') {
                $query->whereHas('marketplaceProducts')
                    ->whereRaw('(SELECT COUNT(DISTINCT store_id) FROM marketplace_products WHERE marketplace_products.master_product_id = master_products.id) < ?', [$connectedStoresCount]);
            } elseif ($request->link_status === 'all') {
                $query->whereRaw('(SELECT COUNT(DISTINCT store_id) FROM marketplace_products WHERE marketplace_products.master_product_id = master_products.id) >= ?', [$connectedStoresCount]);
            }
        }

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

        $products = $query->orderBy('name')->paginate(25)->withQueryString();

        $publicationLogs = PublicationLog::with(['masterProduct', 'store.channel'])
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get();

        $categoryMappings = CategoryMapping::with(['category', 'store.channel'])
            ->where('tenant_id', $tenantId)
            ->get();

        $brandMappings = collect();

        $brands = \App\Models\Brand::where('tenant_id', $tenantId)->orderBy('name')->get();
        $stores = \App\Models\Store::with('channel')->where('tenant_id', $tenantId)->where('status', 'connected')->get();
        $channels = \App\Models\Channel::all();

        return view('products.index', compact(
            'products',
            'connectedStoresCount',
            'publicationLogs',
            'categoryMappings',
            'brandMappings',
            'brands',
            'stores',
            'channels'
        ));
    }

    public function create()
    {
        $product = new MasterProduct();
        $categories = \App\Models\Category::where('tenant_id', Auth::user()->tenant_id)->get();
        $brands = \App\Models\Brand::where('tenant_id', Auth::user()->tenant_id)->get();
        
        $allProducts = MasterProduct::where('tenant_id', Auth::user()->tenant_id)
            ->where(function($q) {
                $q->where('is_bundle', false)->orWhereNull('is_bundle');
            })
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'stock']);

        $laborServices = \App\Models\LaborService::where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('name')
            ->get(['name', 'default_cost']);

        return view('products.form', compact('product', 'categories', 'brands', 'allProducts', 'laborServices'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $data = $request->validate([
            'sku'          => 'nullable|string|max:100|unique:master_products,sku',
            'sku_induk'    => 'nullable|string|max:100',
            'name'         => 'required|string|min:30|max:255',
            'price'        => 'required|numeric|min:0',
            'cost_price'   => 'nullable|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'min_stock'    => 'nullable|integer|min:0',
            'unit'         => 'nullable|string|max:50',
            'category_id'  => 'nullable|exists:categories,id',
            'sub_kategori' => 'nullable|string|max:100',
            'brand_id'     => 'nullable|exists:brands,id',
            'description'  => 'nullable|string',
            'weight'       => 'nullable|numeric|min:0',
            'length'       => 'nullable|numeric|min:0',
            'width'        => 'nullable|numeric|min:0',
            'height'       => 'nullable|numeric|min:0',
            'image_url'    => 'nullable|string|max:1000',
            'ukuran'       => 'nullable|string|max:100',
            'warna'        => 'nullable|string|max:100',
            'is_active'    => 'nullable|boolean',
            'is_preorder'  => 'nullable|boolean',
            'preorder_days'=> 'nullable|integer|min:0',
            'is_bundle'    => 'nullable|boolean',
            'components'   => 'nullable|array',
            'components.*.child_id' => 'required_with:components|exists:master_products,id',
            'components.*.quantity' => 'required_with:components|integer|min:1',
        ]);

        $data['is_bundle'] = $request->has('is_bundle') ? true : false;
        $data['is_preorder'] = $request->has('is_preorder') ? true : false;
        if (!$data['is_preorder']) {
            $data['preorder_days'] = null;
        }

        if ($data['is_bundle']) {
            $data['stock'] = 0;
        }

        // Handle file upload — takes priority over URL
        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            $path = $request->file('image_file')->store('products', 'public');
            $data['image_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        if (empty($data['sku'])) {
            $dateStr = date('Ymd');
            $prefix = 'SKU-' . $dateStr . '-';
            
            $lastProduct = MasterProduct::where('sku', 'like', $prefix . '%')
                ->orderBy('sku', 'desc')
                ->first();
                
            if ($lastProduct) {
                $lastNumber = (int) substr($lastProduct->sku, strlen($prefix));
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }
            
            $skuCandidate = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            
            while (MasterProduct::where('sku', $skuCandidate)->exists()) {
                $newNumber++;
                $skuCandidate = $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
            }
            
            $data['sku'] = $skuCandidate;
        }

        $data['tenant_id'] = Auth::user()->tenant_id;

        $product = MasterProduct::create($data);

        // Save bundle components
        if ($product->is_bundle && $request->has('components')) {
            foreach ($request->components as $comp) {
                if (!empty($comp['child_id'])) {
                    $product->components()->attach($comp['child_id'], ['quantity' => $comp['quantity'] ?? 1]);
                }
            }
        }

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function edit(MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);
        $categories = \App\Models\Category::where('tenant_id', Auth::user()->tenant_id)->get();
        $brands = \App\Models\Brand::where('tenant_id', Auth::user()->tenant_id)->get();
        
        $recipe = \App\Models\ProductRecipe::with(['items.inventoryItem', 'labors'])
            ->where('master_product_id', $product->id)
            ->where('is_active', true)
            ->first();
            
        $inventoryItems = \App\Models\InventoryItem::where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('type', ['bahan', 'kemasan'])
            ->orderBy('name')
            ->get();
            
        $allProducts = MasterProduct::where('tenant_id', Auth::user()->tenant_id)
            ->where('id', '!=', $product->id)
            ->where(function($q) {
                $q->where('is_bundle', false)->orWhereNull('is_bundle');
            })
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'stock']);

        $laborServices = \App\Models\LaborService::where('tenant_id', Auth::user()->tenant_id)
            ->orderBy('name')
            ->get(['name', 'default_cost']);

        if (request()->has('debug')) {
            return response()->json([
                'tenant_id' => Auth::user()->tenant_id,
                'product_id' => $product->id,
                'count' => $allProducts->count(),
                'products' => $allProducts
            ]);
        }

        return view('products.form', compact('product', 'categories', 'brands', 'recipe', 'inventoryItems', 'allProducts', 'laborServices'));
    }

    public function saveRecipe(Request $request, MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);
        
        $request->validate([
            'batch_qty' => 'required|integer|min:1',
            'items' => 'nullable|array',
            'items.*.inventory_item_id' => 'required_with:items|exists:inventory_items,id',
            'items.*.quantity' => 'required_with:items|numeric|min:0.0001',
            'labors' => 'nullable|array',
            'labors.*.service_name' => 'required_with:labors|string|max:255',
            'labors.*.qty' => 'required_with:labors|integer|min:1',
            'labors.*.unit_cost' => 'required_with:labors|numeric|min:0',
        ]);

        \DB::transaction(function() use ($request, $product) {
            \App\Models\ProductRecipe::where('master_product_id', $product->id)
                ->update(['is_active' => false]);
                
            $recipe = \App\Models\ProductRecipe::create([
                'tenant_id' => Auth::user()->tenant_id,
                'master_product_id' => $product->id,
                'name' => 'Resep Utama ' . date('d/m/Y H:i'),
                'batch_qty' => $request->batch_qty,
                'is_active' => true,
            ]);
            
            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $recipe->items()->create([
                        'inventory_item_id' => $item['inventory_item_id'],
                        'quantity' => $item['quantity'],
                    ]);
                }
            }
            
            if ($request->has('labors')) {
                foreach ($request->labors as $labor) {
                    $recipe->labors()->create([
                        'service_name' => $labor['service_name'],
                        'qty' => $labor['qty'],
                        'unit_cost' => $labor['unit_cost'],
                        'default_cost' => $labor['qty'] * $labor['unit_cost'],
                    ]);
                }
            }
        });

        return back()->with('success', 'Resep (BOM) & Jasa Ahli berhasil disimpan.');
    }

    public function update(Request $request, MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'image_file' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $data = $request->validate([
            'sku'          => 'nullable|string|max:100|unique:master_products,sku,' . $product->id,
            'sku_induk'    => 'nullable|string|max:100',
            'name'         => 'required|string|min:30|max:255',
            'price'        => 'required|numeric|min:0',
            'cost_price'   => 'nullable|numeric|min:0',
            'stock'        => 'required|integer|min:0',
            'min_stock'    => 'nullable|integer|min:0',
            'unit'         => 'nullable|string|max:50',
            'category_id'  => 'nullable|exists:categories,id',
            'sub_kategori' => 'nullable|string|max:100',
            'brand_id'     => 'nullable|exists:brands,id',
            'description'  => 'nullable|string',
            'weight'       => 'nullable|numeric|min:0',
            'length'       => 'nullable|numeric|min:0',
            'width'        => 'nullable|numeric|min:0',
            'height'       => 'nullable|numeric|min:0',
            'image_url'    => 'nullable|string|max:1000',
            'ukuran'       => 'nullable|string|max:100',
            'warna'        => 'nullable|string|max:100',
            'is_active'    => 'nullable|boolean',
            'is_preorder'  => 'nullable|boolean',
            'preorder_days'=> 'nullable|integer|min:0',
            'is_bundle'    => 'nullable|boolean',
            'components'   => 'nullable|array',
            'components.*.child_id' => 'required_with:components|exists:master_products,id',
            'components.*.quantity' => 'required_with:components|integer|min:1',
        ]);

        $data['is_bundle'] = $request->has('is_bundle') ? true : false;
        $data['is_preorder'] = $request->has('is_preorder') ? true : false;
        if (!$data['is_preorder']) {
            $data['preorder_days'] = null;
        }

        if ($data['is_bundle']) {
            $data['stock'] = 0;
        }

        // Handle file upload — takes priority over URL
        if ($request->hasFile('image_file') && $request->file('image_file')->isValid()) {
            // Delete old uploaded file if it was locally stored
            if ($product->image_url && str_starts_with($product->image_url, '/storage/products/')) {
                $oldPath = 'products/' . basename($product->image_url);
                \Illuminate\Support\Facades\Storage::disk('public')->delete($oldPath);
            }
            $path = $request->file('image_file')->store('products', 'public');
            $data['image_url'] = \Illuminate\Support\Facades\Storage::url($path);
        }

        // Prevent direct stock updates if mapped to marketplace or is bundle
        if ($product->marketplaceProducts()->exists() || $data['is_bundle']) {
            unset($data['stock']);
        }

        $oldStock = $product->stock;
        $oldPrice = $product->price;
        $oldName = $product->name;
        
        $product->update($data);

        // Sync components
        if ($product->is_bundle) {
            $syncData = [];
            if ($request->has('components')) {
                foreach ($request->components as $comp) {
                    if (!empty($comp['child_id'])) {
                        $syncData[$comp['child_id']] = ['quantity' => $comp['quantity'] ?? 1];
                    }
                }
            }
            $product->components()->sync($syncData);
        } else {
            $product->components()->detach();
        }
        
        if (isset($data['stock']) && $oldStock !== (int)$data['stock']) {
            \App\Jobs\PushStockToMarketplaces::dispatch($product->id, (int)$data['stock']);
        }

        if ($oldPrice != $data['price']) {
            \App\Jobs\PushPriceToMarketplaces::dispatch($product->id, (float)$data['price']);
        }
        
        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);
        $product->delete();
        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }

    public function publish(MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);

        $stores = \App\Models\Store::with('channel')
            ->where('tenant_id', Auth::user()->tenant_id)
            ->where('status', 'connected')
            ->get();

        $mappedStoreIds = $product->marketplaceProducts->pluck('store_id')->toArray();

        // Get store IDs that are currently being published (pending/processing)
        $processingStoreIds = PublicationLog::where('master_product_id', $product->id)
            ->where('tenant_id', Auth::user()->tenant_id)
            ->whereIn('status', ['pending', 'processing'])
            ->pluck('store_id')
            ->toArray();

        $fallbackImageUrl = $product->image_url;
        if (empty($fallbackImageUrl)) {
            $linkedWithImage = \App\Models\MarketplaceProduct::where(function($q) use ($product) {
                    $q->where('master_product_id', $product->id);
                    if ($product->sku) {
                        $q->orWhere('marketplace_sku', $product->sku);
                    }
                })
                ->whereNotNull('image_url')
                ->where('image_url', '!=', '')
                ->first();
            if ($linkedWithImage) {
                $fallbackImageUrl = $linkedWithImage->image_url;
            }
        }

        // Fetch pre-existing category mappings for this product's category
        $categoryMappings = [];
        if ($product->category_id) {
            $categoryMappings = CategoryMapping::where('category_id', $product->category_id)
                ->where('tenant_id', Auth::user()->tenant_id)
                ->get()
                ->keyBy('store_id')
                ->toArray();
        }

        return view('products.publish', compact('product', 'stores', 'mappedStoreIds', 'processingStoreIds', 'fallbackImageUrl', 'categoryMappings'));
    }

    public function storePublish(Request $request, MasterProduct $product)
    {
        abort_unless($product->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'stores' => 'required|array',
            'stores.*' => 'exists:stores,id',
            'categories' => 'required|array',
            'category_names' => 'required|array',
            'save_mapping' => 'nullable|array',
        ]);

        $selectedStoreIds = $request->input('stores');
        $categories = $request->input('categories');
        $categoryNames = $request->input('category_names');
        $saveMapping = $request->input('save_mapping', []);
        $sizeChartIds = $request->input('size_chart_ids', []);

        // Check if weight is filled
        if (empty($product->weight) || $product->weight <= 0) {
            return back()->withErrors(['error' => 'Berat produk harus lebih besar dari 0 kg untuk di-upload ke marketplace. Silakan edit produk terlebih dahulu.']);
        }

        $queuedCount = 0;
        foreach ($selectedStoreIds as $storeId) {
            $store = \App\Models\Store::find($storeId);
            if (!$store || $store->tenant_id !== Auth::user()->tenant_id) {
                continue;
            }

            // Check if already mapped
            $exists = \App\Models\MarketplaceProduct::where('store_id', $store->id)
                ->where(function($q) use ($product) {
                    $q->where('master_product_id', $product->id);
                    if ($product->sku) {
                        $q->orWhere('marketplace_sku', $product->sku);
                    }
                })
                ->exists();
            if ($exists) {
                continue;
            }

            // Check if currently being published (pending or processing)
            $isProcessing = PublicationLog::where('store_id', $store->id)
                ->where('master_product_id', $product->id)
                ->whereIn('status', ['pending', 'processing'])
                ->exists();
            if ($isProcessing) {
                continue;
            }

            $catId = $categories[$storeId] ?? null;
            $catName = $categoryNames[$storeId] ?? 'Unknown Category';
            if (empty($catId)) {
                continue;
            }

            // Save mapping if requested and product has category
            if ($product->category_id && !empty($saveMapping[$storeId])) {
                CategoryMapping::updateOrCreate([
                    'tenant_id' => Auth::user()->tenant_id,
                    'category_id' => $product->category_id,
                    'store_id' => $storeId,
                ], [
                    'marketplace_category_id' => $catId,
                    'marketplace_category_name' => $catName,
                ]);
            }

            // Append size_chart_id to category_id in logs if provided
            $finalCatId = $catId;
            if (!empty($sizeChartIds[$storeId])) {
                $finalCatId = $catId . '|' . $sizeChartIds[$storeId];
            }

            // Create or update background job log entry
            $log = PublicationLog::updateOrCreate([
                'tenant_id' => Auth::user()->tenant_id,
                'master_product_id' => $product->id,
                'store_id' => $storeId,
            ], [
                'status' => 'pending',
                'category_id' => $finalCatId,
                'category_name' => $catName,
                'error_message' => null,
                'marketplace_product_id' => null,
            ]);

            // Dispatch job
            PublishProductToMarketplace::dispatch($log->id);
            $queuedCount++;
        }

        if ($queuedCount > 0) {
            return redirect()->route('products.index')->with('success', "$queuedCount produk berhasil dikirim ke antrean publikasi latar belakang. Silakan pantau perkembangannya pada tab \"Riwayat Publikasi\".");
        }

        return back()->withErrors(['error' => 'Tidak ada toko baru yang dipilih untuk dipublikasikan.']);
    }

    public function retryPublish($logId)
    {
        $log = PublicationLog::findOrFail($logId);
        abort_unless($log->tenant_id === Auth::user()->tenant_id, 403);

        $log->update([
            'status' => 'pending',
            'error_message' => null
        ]);

        PublishProductToMarketplace::dispatch($log->id);

        return back()->with('success', 'Percobaan ulang publikasi telah dikirim ke antrean.');
    }

    public function storeBrandMapping(Request $request)
    {
        $request->validate([
            'brand_id' => 'required|exists:brands,id',
            'store_id' => 'required|exists:stores,id',
            'marketplace_brand_id' => 'required|string|max:100',
            'marketplace_brand_name' => 'nullable|string|max:255',
        ]);

        $store = \App\Models\Store::findOrFail($request->store_id);
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);

        BrandMapping::updateOrCreate([
            'tenant_id' => Auth::user()->tenant_id,
            'brand_id' => $request->brand_id,
            'store_id' => $request->store_id,
        ], [
            'marketplace_brand_id' => $request->marketplace_brand_id,
            'marketplace_brand_name' => $request->marketplace_brand_name ?: 'Custom Brand',
        ]);

        return back()->with('success', 'Pemetaan merk berhasil disimpan.');
    }

    public function destroyCategoryMapping($id)
    {
        $mapping = CategoryMapping::findOrFail($id);
        abort_unless($mapping->tenant_id === Auth::user()->tenant_id, 403);

        $mapping->delete();
        return back()->with('success', 'Pemetaan kategori berhasil dihapus.');
    }

    public function destroyBrandMapping($id)
    {
        $mapping = BrandMapping::findOrFail($id);
        abort_unless($mapping->tenant_id === Auth::user()->tenant_id, 403);

        $mapping->delete();
        return back()->with('success', 'Pemetaan merk berhasil dihapus.');
    }

    /**
     * AJAX: Ambil daftar kategori Shopee dan kembalikan sebagai JSON.
     * Akan mencoba semua toko Shopee aktif milik tenant hingga berhasil.
     */
    public function shopeeCategories(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $tenantId = \Illuminate\Support\Facades\Auth::user()->tenant_id;

            \App\Models\Channel::ensureChannelsExist();
            $shopeeChannel = \App\Models\Channel::where('code', 'shopee')->first();
            if (!$shopeeChannel) {
                return response()->json(['success' => false, 'message' => 'Channel Shopee tidak ditemukan.'], 404);
            }

            // Ambil semua toko Shopee milik tenant — urutkan: token belum expired duluan
            $stores = \App\Models\Store::where('tenant_id', $tenantId)
                ->where('channel_id', $shopeeChannel->id)
                ->whereNotNull('access_token')
                ->orderByRaw('CASE WHEN token_expires_at IS NULL OR token_expires_at > NOW() THEN 0 ELSE 1 END')
                ->get();

            if ($stores->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'Tidak ada toko Shopee aktif. Silakan hubungkan toko Shopee terlebih dahulu.'], 404);
            }

            $shopee     = app(\App\Services\ShopeeService::class);
            $lastError  = 'Semua toko Shopee gagal memuat kategori.';
            $allCategories = null;
            $usedStore  = null;

            foreach ($stores as $store) {
                $shopId      = (int) $store->marketplace_store_id;

                try {
                    $accessToken = $store->getValidAccessToken();
                } catch (\Throwable $refreshErr) {
                    \Illuminate\Support\Facades\Log::warning("[shopeeCategories] Refresh token gagal untuk store {$store->id}: " . $refreshErr->getMessage());
                    $lastError = 'Token expired dan gagal refresh: ' . $refreshErr->getMessage();
                    continue; // Coba toko berikutnya
                }

                // Hapus cache lama
                \Illuminate\Support\Facades\Cache::forget("shopee_categories_{$shopId}_id");

                try {
                    $allCategories = $shopee->getCategoryTree($accessToken, $shopId, 'id');
                    $usedStore     = $store;
                    break; // Berhasil — keluar dari loop
                } catch (\Throwable $apiErr) {
                    \Illuminate\Support\Facades\Log::warning("[shopeeCategories] API error untuk store {$store->id}: " . $apiErr->getMessage());
                    $lastError = $apiErr->getMessage();
                    continue; // Coba toko berikutnya
                }
            }

            if ($allCategories === null) {
                return response()->json(['success' => false, 'message' => $lastError], 500);
            }

            // Bangun path lengkap untuk setiap leaf category
            $leafCategories = array_filter($allCategories, fn($cat) => !($cat['has_children'] ?? false));

            $categoryMap = [];
            $parentMap   = [];
            foreach ($allCategories as $cat) {
                $id = $cat['category_id'];
                $categoryMap[$id] = $cat['display_category_name'] ?? '';
                $parentMap[$id]   = $cat['parent_category_id'] ?? 0;
            }

            $result = [];
            foreach ($leafCategories as $cat) {
                $path     = [];
                $parentId = $cat['parent_category_id'] ?? 0;

                $visited = [];
                while ($parentId && !in_array($parentId, $visited) && isset($categoryMap[$parentId])) {
                    $visited[]  = $parentId;
                    array_unshift($path, $categoryMap[$parentId]);
                    $parentId = $parentMap[$parentId] ?? 0;
                }

                $path[]   = $cat['display_category_name'] ?? '';
                $result[] = [
                    'id'   => $cat['category_id'],
                    'name' => implode(' > ', $path),
                ];
            }

            usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));

            return response()->json([
                'success' => true,
                'data'    => $result,
                'total'   => count($result),
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
              ->header('Pragma', 'no-cache')
              ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('shopeeCategories error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function tiktokCategories()
    {
        try {
            $store = \App\Models\Store::whereHas('channel', function ($q) {
                $q->where('code', 'tiktok');
            })->where('tenant_id', \Illuminate\Support\Facades\Auth::user()->tenant_id)
              ->whereNotNull('access_token')
              ->first();

            if (!$store) {
                return response()->json(['success' => false, 'message' => 'Toko TikTok tidak ditemukan.'], 404);
            }

            $tiktokService = app(\App\Services\TiktokService::class);
            $rawCategories = $tiktokService->getCategories($store->access_token, $store->shop_cipher);
            
            $officialCategories = array_map(function($c) {
                return [
                    'category_id' => (int) $c['id'],
                    'parent_category_id' => (int) $c['parent_id'],
                    'display_category_name' => $c['local_name'],
                    'has_children' => !$c['is_leaf'],
                    'permission_statuses' => $c['permission_statuses'] ?? []
                ];
            }, $rawCategories);

            $categoryMap = [];
            $parentMap   = [];
            foreach ($officialCategories as $cat) {
                $id = $cat['category_id'];
                $categoryMap[$id] = $cat['display_category_name'] ?? '';
                $parentMap[$id]   = $cat['parent_category_id'] ?? 0;
            }

            $leafCategories = array_filter($officialCategories, function($cat) {
                if ($cat['has_children'] ?? false) {
                    return false;
                }
                return in_array('AVAILABLE', $cat['permission_statuses'] ?? []);
            });

            $result = [];
            foreach ($leafCategories as $cat) {
                $path     = [];
                $parentId = $cat['parent_category_id'] ?? 0;

                $visited = [];
                while ($parentId && !in_array($parentId, $visited) && isset($categoryMap[$parentId])) {
                    $visited[]  = $parentId;
                    array_unshift($path, $categoryMap[$parentId]);
                    $parentId = $parentMap[$parentId] ?? 0;
                }

                $leafName = $cat['display_category_name'] ?? '';
                $path[]   = $leafName;
                $fullPath = implode(' > ', $path);

                $result[] = [
                    'id'   => $cat['category_id'],
                    'name' => $fullPath,
                ];
            }

            usort($result, fn($a, $b) => strcmp($a['name'], $b['name']));

            return response()->json([
                'success' => true,
                'data' => $result,
                'total' => count($result)
            ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
              ->header('Pragma', 'no-cache')
              ->header('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('tiktokCategories error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function bulkPublish(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $ids = $request->input('ids', []);
        
        if (empty($ids) || !is_array($ids)) {
            return redirect()->route('products.index')->with('error', 'Silakan pilih setidaknya satu produk untuk dipublikasikan.');
        }

        $products = MasterProduct::whereIn('id', $ids)
            ->where('tenant_id', $tenantId)
            ->get();

        if ($products->isEmpty()) {
            return redirect()->route('products.index')->with('error', 'Produk tidak ditemukan.');
        }

        $stores = \App\Models\Store::with('channel')
            ->where('tenant_id', $tenantId)
            ->where('status', 'connected')
            ->get();

        $categoryIds = $products->pluck('category_id')->filter()->unique();
        $categoryMappings = [];
        if ($categoryIds->isNotEmpty()) {
            $categoryMappings = CategoryMapping::whereIn('category_id', $categoryIds)
                ->where('tenant_id', $tenantId)
                ->get()
                ->groupBy('store_id')
                ->map(function ($items) {
                    return $items->keyBy('category_id')->toArray();
                })
                ->toArray();
        }

        return view('products.bulk_publish', compact('products', 'stores', 'categoryMappings'));
    }

    public function storeBulkPublish(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:master_products,id',
            'stores' => 'required|array',
            'stores.*' => 'exists:stores,id',
            'categories' => 'required|array',
            'category_names' => 'required|array',
        ]);

        $productIds = $request->input('product_ids');
        $selectedStoreIds = $request->input('stores');
        $defaultCategories = $request->input('categories');
        $defaultCategoryNames = $request->input('category_names');
        $saveMapping = $request->input('save_mapping', []);
        $sizeChartIds = $request->input('size_chart_ids', []);

        $queuedCount = 0;
        $skippedCount = 0;
        $weightErrors = 0;

        foreach ($productIds as $productId) {
            $product = MasterProduct::where('id', $productId)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$product) {
                continue;
            }

            // Check if weight is filled
            if (empty($product->weight) || $product->weight <= 0) {
                $weightErrors++;
                continue;
            }

            foreach ($selectedStoreIds as $storeId) {
                $store = \App\Models\Store::find($storeId);
                if (!$store || $store->tenant_id !== $tenantId) {
                    continue;
                }

                // Check if already mapped
                $exists = \App\Models\MarketplaceProduct::where('store_id', $store->id)
                    ->where(function($q) use ($product) {
                        $q->where('master_product_id', $product->id);
                        if ($product->sku) {
                            $q->orWhere('marketplace_sku', $product->sku);
                        }
                    })
                    ->exists();
                if ($exists) {
                    $skippedCount++;
                    continue;
                }

                // Check if currently being published (pending or processing)
                $isProcessing = PublicationLog::where('store_id', $store->id)
                    ->where('master_product_id', $product->id)
                    ->whereIn('status', ['pending', 'processing'])
                    ->exists();
                if ($isProcessing) {
                    $skippedCount++;
                    continue;
                }

                // Resolve category:
                // 1. Check if specific category mapping exists for this product's category
                $catId = null;
                $catName = null;

                if ($product->category_id) {
                    $mapping = CategoryMapping::where('category_id', $product->category_id)
                        ->where('store_id', $storeId)
                        ->first();
                    if ($mapping) {
                        $catId = $mapping->marketplace_category_id;
                        $catName = $mapping->marketplace_category_name;
                    }
                }

                // 2. If no specific mapping, fallback to default category selected for this store
                if (empty($catId)) {
                    $catId = $defaultCategories[$storeId] ?? null;
                    $catName = $defaultCategoryNames[$storeId] ?? 'Unknown Category';
                }

                if (empty($catId)) {
                    continue;
                }

                // Save mapping if requested and product has category
                if ($product->category_id && !empty($saveMapping[$storeId]) && empty($mapping)) {
                    CategoryMapping::updateOrCreate([
                        'tenant_id' => $tenantId,
                        'category_id' => $product->category_id,
                        'store_id' => $storeId,
                    ], [
                        'marketplace_category_id' => $catId,
                        'marketplace_category_name' => $catName,
                    ]);
                }

                // Append size_chart_id to category_id in logs if provided
                $finalCatId = $catId;
                if (!empty($sizeChartIds[$storeId])) {
                    $finalCatId = $catId . '|' . $sizeChartIds[$storeId];
                }

                // Create Publication Log entry
                $log = PublicationLog::updateOrCreate([
                    'tenant_id' => $tenantId,
                    'master_product_id' => $product->id,
                    'store_id' => $storeId,
                ], [
                    'status' => 'pending',
                    'category_id' => $finalCatId,
                    'category_name' => $catName,
                    'error_message' => null,
                    'marketplace_product_id' => null,
                ]);

                // Dispatch job
                PublishProductToMarketplace::dispatch($log->id);
                $queuedCount++;
            }
        }

        $msg = "Berhasil mengirim $queuedCount publikasi produk massal ke latar belakang.";
        if ($skippedCount > 0) {
            $msg .= " ($skippedCount dilewati karena sudah terhubung/sedang diproses).";
        }
        if ($weightErrors > 0) {
            $msg .= " ($weightErrors dilewati karena berat produk kosong/0).";
        }

        return redirect()->route('products.index')->with('success', $msg);
    }
}
