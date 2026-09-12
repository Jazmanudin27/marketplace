<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Spk;
use App\Models\SpkItem;
use App\Models\SpkItemExtra;
use App\Models\SpkProses;
use App\Models\SpkItemProgres;
use App\Models\MasterProduct;
use App\Models\InventoryItem;
use App\Models\ProductRecipe;
use App\Models\ProductRecipeItem;
use App\Models\SpkPayment;
use App\Models\Expense;
use App\Models\BankAccount;
use App\Models\WarehouseMutation;
use App\Models\WarehouseMutationItem;
use App\Models\Department;
use App\Models\StockMovement;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SpkController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = Spk::with(['penginput', 'items.masterProduct', 'items.progres', 'proses'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_spk', 'like', '%' . $search . '%')
                  ->orWhere('no_produksi', 'like', '%' . $search . '%')
                  ->orWhere('pemesan', 'like', '%' . $search . '%')
                  ->orWhere('instansi', 'like', '%' . $search . '%')
                  ->orWhereHas('items', function ($i) use ($search) {
                      $i->where('nama_produk', 'like', '%' . $search . '%')
                        ->orWhere('sku', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('urgent') && $request->urgent == '1') {
            $query->where('is_urgent', true);
        }

        if ($request->filled('stage')) {
            $stage = strtolower(trim($request->stage));
            $query->where(function ($q) use ($stage) {
                $q->where(DB::raw('LOWER(tahap_saat_ini)'), 'like', '%' . $stage . '%');
                if (in_array($stage, ['pesanan_baru', 'perencanaan', 'draft'])) {
                    $q->orWhereIn(DB::raw('LOWER(tahap_saat_ini)'), ['draft', 'pesanan baru', 'perencanaan', 'perancangan produksi (spk)', 'tahap desain & mockup'])
                      ->orDoesntHave('proses');
                } else {
                    $q->orWhereHas('proses', function ($p) use ($stage) {
                        $p->where('nama_proses', 'like', '%' . $stage . '%');
                    });
                }
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('tanggal', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('deadline', '<=', $request->date_to);
        }
        if ($request->filled('tipe_spk')) {
            $query->where('tipe_spk', $request->tipe_spk);
        }

        // Group SQL query by Nomor Produksi (or no_pesanan / created_at timestamp if no_produksi is empty)
        $groupExpr = DB::raw("COALESCE(NULLIF(TRIM(no_produksi), ''), NULLIF(TRIM(no_pesanan), ''), DATE_FORMAT(created_at, '%Y-%m-%d %H:%i:%s'))");

        $subQuery = (clone $query)
            ->reorder()
            ->select($groupExpr, DB::raw('MAX(id) as max_id'))
            ->groupBy($groupExpr);

        $groupedMaxIds = $subQuery->pluck('max_id');

        $allGroupedSpks = Spk::with(['penginput', 'items.masterProduct', 'items.progres', 'items.pickups', 'proses'])
            ->whereIn('id', $groupedMaxIds)
            ->orderByDesc('id')
            ->get();

        // Attach sub_spks collection to each production group item
        $noProduksiList = $allGroupedSpks->pluck('no_produksi')->filter()->unique()->toArray();
        $siblingSpksMap = [];
        if (!empty($noProduksiList)) {
            $allSiblings = Spk::with(['items.masterProduct', 'items.progres', 'items.pickups', 'proses'])
                ->where('tenant_id', $tenantId)
                ->whereIn('no_produksi', $noProduksiList)
                ->orderBy('id')
                ->get();
            $siblingSpksMap = $allSiblings->groupBy('no_produksi');
        }

        foreach ($allGroupedSpks as $spkItem) {
            if (!empty($spkItem->no_produksi) && isset($siblingSpksMap[$spkItem->no_produksi])) {
                $spkItem->sub_spks = $siblingSpksMap[$spkItem->no_produksi];
            } else {
                $siblings = Spk::with(['items.masterProduct', 'items.progres', 'items.pickups', 'proses'])
                    ->where('tenant_id', $tenantId)
                    ->where('created_at', $spkItem->created_at)
                    ->orderBy('id')
                    ->get();
                $spkItem->sub_spks = $siblings->isNotEmpty() ? $siblings : collect([$spkItem]);
            }
        }

        // Exact active stage filter matching current_stage_name attribute or urgent status
        if ($request->filled('stage')) {
            $stage = strtolower(trim($request->stage));
            $allGroupedSpks = $allGroupedSpks->filter(function ($row) use ($stage) {
                $spkGroup = $row->sub_spks ?? collect([$row]);
                if ($stage === 'urgent') {
                    return $spkGroup->contains('is_urgent', true);
                }
                return $spkGroup->contains(function ($s) use ($stage) {
                    $currName = strtolower($s->current_stage_name);
                    if ($stage === 'draft') {
                        return str_contains($currName, 'draft') || strtolower($s->tahap_saat_ini ?? '') === 'draft';
                    } elseif ($stage === 'desain' || $stage === 'design' || $stage === 'mockup') {
                        return str_contains($currName, 'desain') || str_contains($currName, 'design') || str_contains($currName, 'mockup') || str_contains(strtolower($s->tahap_saat_ini ?? ''), 'desain');
                    } elseif ($stage === 'pesanan_baru' || $stage === 'perencanaan') {
                        return (str_contains($currName, 'pesanan') || str_contains($currName, 'perencanaan') || str_contains($currName, 'perancangan')) && !str_contains($currName, 'draft') && !str_contains($currName, 'desain');
                    } elseif ($stage === 'sampling') {
                        return str_contains($currName, 'sampling') || str_contains($currName, 'antrian');
                    } elseif ($stage === 'potong') {
                        return str_contains($currName, 'potong') || str_contains($currName, 'pemotongan');
                    } elseif ($stage === 'sablon_bordir' || $stage === 'sablon' || $stage === 'bordir') {
                        return str_contains($currName, 'sablon') || str_contains($currName, 'bordir');
                    } elseif ($stage === 'jahit') {
                        return str_contains($currName, 'jahit');
                    } elseif ($stage === 'lkpk') {
                        return str_contains($currName, 'lkpk') || str_contains($currName, 'kancing');
                    } elseif ($stage === 'qc') {
                        return str_contains($currName, 'qc') || str_contains($currName, 'quality');
                    } elseif ($stage === 'packing') {
                        return str_contains($currName, 'packing') || str_contains($currName, 'finishing');
                    } elseif ($stage === 'selesai') {
                        return str_contains($currName, 'selesai') || str_contains($currName, 'finished');
                    } elseif ($stage === 'dikirim') {
                        return str_contains($currName, 'dikirim') || str_contains($currName, 'shipped');
                    }
                    return str_contains($currName, $stage);
                });
            });
        }

        // Manual Pagination for the filtered collection
        $page = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?: 1;
        $perPage = 12;
        $paginatedItems = $allGroupedSpks->slice(($page - 1) * $perPage, $perPage)->values();

        $spks = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedItems,
            $allGroupedSpks->count(),
            $perPage,
            $page,
            ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(), 'query' => $request->query()]
        );

        // Summary stats for top dashboard cards
        $stats = [
            'total_produksi' => $spks->total(),
            'total_urgent'   => Spk::where('tenant_id', $tenantId)->where('is_urgent', true)->count(),
            'total_pcs'      => (int) DB::table('spk_items')
                                    ->join('spks', 'spk_items.spk_id', '=', 'spks.id')
                                    ->where('spks.tenant_id', $tenantId)
                                    ->sum('spk_items.quantity'),
        ];

        return view('inventory.spks.index', compact('spks', 'stats'));
    }

    public function create(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select(['id', 'tenant_id', 'name', 'sku', 'sku_induk', 'ukuran', 'warna', 'cost_price'])
            ->with([
                'activeRecipe:id,master_product_id,batch_qty',
                'activeRecipe.items:id,product_recipe_id,inventory_item_id,quantity',
                'activeRecipe.items.inventoryItem:id,name,unit,cost_price',
                'activeRecipe.labors:id,product_recipe_id,service_name,default_cost'
            ])
            ->orderBy('name')
            ->get();

        // Optimized query: Only fetch the MAX(id) spk_item per product to avoid scanning entire spk_items table
        $productIds = $products->pluck('id')->toArray();
        $latestItems = collect();
        if (!empty($productIds)) {
            $latestSpkItemIds = DB::table('spk_items')
                ->join('spks', 'spk_items.spk_id', '=', 'spks.id')
                ->where('spks.tenant_id', $tenantId)
                ->whereIn('spk_items.master_product_id', $productIds)
                ->select(DB::raw('MAX(spk_items.id) as max_id'))
                ->groupBy('spk_items.master_product_id')
                ->pluck('max_id')
                ->filter();

            if ($latestSpkItemIds->isNotEmpty()) {
                $latestItems = SpkItem::with('extras')
                    ->whereIn('id', $latestSpkItemIds)
                    ->get()
                    ->keyBy('master_product_id');
            }
        }

        foreach ($products as $product) {
            $latestItem = $latestItems->get($product->id);

            if ($latestItem && $latestItem->extras->count() > 0) {
                $product->latest_costs = $latestItem->extras->map(function ($ex) {
                    return [
                        'keterangan' => $ex->keterangan,
                        'nominal' => (float)$ex->nominal
                    ];
                })->toArray();
            } else {
                $product->latest_costs = null;
            }
        }

        $vendorsData = \App\Models\Tailor::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        $pemotongList = $vendorsData->where('category', 'Pemotong')->pluck('name')->values();
        $penjahitList = $vendorsData->filter(fn($v) => in_array($v->category, ['Penjahit', null, ''], true))->pluck('name')->values();
        $vendorKancingList = $vendorsData->where('category', 'Vendor Kancing')->pluck('name')->values();
        $petugasQcList = $vendorsData->where('category', 'Petugas QC')->pluck('name')->values();
        $tailors = $vendorsData->pluck('name')->values();

        $laborServices = \App\Models\LaborService::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['name', 'default_cost']);

        $order = null;
        if ($request->filled('order_id')) {
            $order = \App\Models\Order::with(['items.masterProduct', 'store'])->where('tenant_id', $tenantId)->find($request->order_id);
        }

        $stores = \App\Models\Store::with('channel')
            ->where('tenant_id', $tenantId)
            ->orderBy('store_name')
            ->get();

        $existingNoProduksi = Spk::where('tenant_id', $tenantId)
            ->whereNotNull('no_produksi')
            ->where('no_produksi', '!=', '')
            ->distinct()
            ->orderByDesc('no_produksi')
            ->pluck('no_produksi');

        $inventoryItemsData = \App\Models\InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select(['id', 'name', 'unit', 'cost_price'])
            ->orderBy('name')
            ->get();

        $inventoryItems = $inventoryItemsData->pluck('name');

        $inventoryItemsMap = [];
        foreach ($inventoryItemsData as $inv) {
            if (!empty($inv->name)) {
                $inventoryItemsMap[strtoupper(trim($inv->name))] = [
                    'name'       => $inv->name,
                    'unit'       => $inv->unit ?? '',
                    'cost_price' => (float) ($inv->cost_price ?? 0),
                ];
            }
        }

        $recipesMap = [];
        foreach ($products as $prod) {
            $rec = $prod->activeRecipe;
            if ($rec) {
                $batchQty = max(1, (int)$rec->batch_qty);
                $itemsList = [];
                foreach ($rec->items as $rItem) {
                    $invItem = $rItem->inventoryItem;
                    if ($invItem && !empty($invItem->name)) {
                        $itemsList[] = [
                            'nama_bahan' => $invItem->name,
                            'unit'       => $invItem->unit ?? '',
                            'qty_unit'   => round((float)$rItem->quantity / $batchQty, 4),
                            'harga'      => (float)($invItem->cost_price ?? 0),
                        ];
                    }
                }

                $recipeData = [
                    'product_id' => $prod->id,
                    'name'       => $prod->name,
                    'sku'        => $prod->sku,
                    'sku_induk'  => $prod->sku_induk,
                    'ukuran'     => $prod->ukuran,
                    'items'      => $itemsList,
                ];

                if (!empty($prod->name)) {
                    $recipesMap[strtoupper(trim($prod->name))] = $recipeData;
                }
                if (!empty($prod->sku)) {
                    $recipesMap[strtoupper(trim($prod->sku))] = $recipeData;
                }
                if (!empty($prod->sku_induk)) {
                    $recipesMap[strtoupper(trim($prod->sku_induk))] = $recipeData;
                }
            }
        }

        $allMasterProductsList = $products->map(function($p) {
            return [
                'sku'       => $p->sku,
                'sku_induk' => $p->sku_induk,
                'name'      => $p->name,
                'ukuran'    => $p->ukuran ?? '',
                'est_kain'  => (float) ($p->est_kain ?? 0),
            ];
        });

        $defaultNoProduksi = Spk::generateNoProduksi();

        return view('inventory.spks.create', compact('products', 'tailors', 'pemotongList', 'penjahitList', 'vendorKancingList', 'petugasQcList', 'laborServices', 'order', 'stores', 'existingNoProduksi', 'defaultNoProduksi', 'recipesMap', 'inventoryItems', 'inventoryItemsMap', 'allMasterProductsList'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'order_id'          => 'nullable|integer|exists:orders,id',
            'no_produksi'       => 'nullable|string|max:255',
            'no_pesanan'        => 'nullable|string|max:255',
            'tanggal'           => 'required|date',
            'deadline'          => 'nullable|date|after_or_equal:tanggal',
            'tipe_spk'          => 'nullable|string|in:pesanan_pelanggan,stok_gudang',
            'tahap_saat_ini'    => 'nullable|string|max:100',
            'pemesan'           => 'nullable|string|max:255',
            'no_hp_pemesan'     => 'nullable|string|max:100',
            'instansi'          => 'nullable|string|max:255',
            'nama_pic'          => 'nullable|string|max:255',
            'tambahan'          => 'nullable|string',
            'sku_kain'          => 'nullable|string|max:100',
            'link_file_mentah'  => 'nullable|string|max:2048',
            'image'             => 'nullable|image|max:4096',
            'referensi_klien'   => 'nullable|image|max:8192',
            'mockup_final'      => 'nullable|image|max:8192',
            'items'             => 'nullable|array',
            'items.*.sku_produk'=> 'nullable|string|max:255',
            'items.*.qty'       => 'nullable|integer|min:1',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = str_replace('/storage/', '', $this->resizeAndStoreUploadedImage($request->file('image'), 'spks', 1200, 80));
        }

        $referensiPath = null;
        if ($request->hasFile('referensi_klien')) {
            $referensiPath = str_replace('/storage/', '', $this->resizeAndStoreUploadedImage($request->file('referensi_klien'), 'spks/referensi', 1200, 80));
        }

        $mockupPath = null;
        if ($request->hasFile('mockup_final')) {
            $mockupPath = str_replace('/storage/', '', $this->resizeAndStoreUploadedImage($request->file('mockup_final'), 'spks/mockup', 1200, 80));
        }

        $spk = DB::transaction(function () use ($request, $tenantId, $imagePath, $referensiPath, $mockupPath) {
            $noProduksi = trim((string) $request->input('no_produksi'));
            if (empty($noProduksi)) {
                $noProduksi = Spk::generateNoProduksi();
            }

            $tahapSaatIni = $request->input('tahap_saat_ini', 'DRAFT');
            if (empty($noProduksi)) {
                $tahapSaatIni = 'DRAFT';
            }

            // Check if multiple rincian blocks exist
            $rincianBlocks = $request->input('rincian', []);
            if (!empty($rincianBlocks) && is_array($rincianBlocks)) {
                $firstSpk = null;
                foreach ($rincianBlocks as $rIdx => $rBlock) {
                    $noSpk = Spk::generateNoSpk();

                    // Uploads for this rincian block
                    $refUrl = null;
                    if ($request->hasFile("rincian.{$rIdx}.referensi_klien")) {
                        $p = $request->file("rincian.{$rIdx}.referensi_klien")->store('spks/referensi', 'public');
                        $refUrl = Storage::url($p);
                    }
                    $mockUrl = null;
                    if ($request->hasFile("rincian.{$rIdx}.mockup_final")) {
                        $p = $request->file("rincian.{$rIdx}.mockup_final")->store('spks/mockup', 'public');
                        $mockUrl = Storage::url($p);
                    }

                    $spkRecord = Spk::create([
                        'tenant_id'           => $tenantId,
                        'order_id'            => $request->order_id,
                        'no_produksi'         => $noProduksi,
                        'no_pesanan'          => $request->no_pesanan,
                        'no_spk'              => $noSpk,
                        'tipe_spk'            => $request->input('tipe_spk', 'pesanan_pelanggan'),
                        'kategori'            => $rBlock['kategori'] ?? $request->kategori,
                        'is_urgent'           => $request->boolean('is_urgent'),
                        'tahap_saat_ini'      => $tahapSaatIni,
                        'tanggal'             => $request->tanggal,
                        'deadline'            => $request->deadline ?: null,
                        'pemesan'             => $request->pemesan,
                        'no_hp_pemesan'       => $request->no_hp_pemesan,
                        'instansi'            => $request->instansi,
                        'nama_pic'            => $request->nama_pic ?: Auth::user()->name,
                        'tambahan'            => $request->tambahan,
                        'sku_kain'            => $rBlock['sku_kain'] ?? $request->sku_kain,
                        'link_file_mentah'    => $rBlock['link_file_mentah'] ?? $request->link_file_mentah,
                        'image_url'           => $imagePath ? Storage::url($imagePath) : null,
                        'referensi_klien_url' => $refUrl,
                        'mockup_url'          => $mockUrl,
                        'penginput_id'        => Auth::id(),
                    ]);

                    if (!$firstSpk) $firstSpk = $spkRecord;

                    $productRows = $rBlock['produk'] ?? [];
                    if (empty($productRows)) {
                        $productRows = [[
                            'nama_produk'  => $rBlock['nama_produk'] ?? 'Produk SPK',
                            'sku_produk'   => $rBlock['sku_produk'] ?? ($rBlock['sku'] ?? null),
                            'ukuran'       => $rBlock['ukuran'] ?? null,
                            'qty_produksi' => (int) ($rBlock['qty_produksi'] ?? ($rBlock['quantity'] ?? 1)),
                        ]];
                    }

                    foreach ($productRows as $pRow) {
                        $namaProduk  = $pRow['nama_produk'] ?? 'Produk SPK';
                        $skuProduk   = $pRow['sku_produk'] ?? ($pRow['sku'] ?? null);
                        $qtyProduksi = max(1, (int) ($pRow['qty_produksi'] ?? ($pRow['qty'] ?? 1)));
                        $ukuran      = $pRow['ukuran'] ?? null;
                        $bahanList   = $pRow['bahan'] ?? ($rBlock['bahan'] ?? []);

                        $prod = null;
                        if (!empty($skuProduk)) {
                            $prod = MasterProduct::where('tenant_id', $tenantId)->where('sku', trim($skuProduk))->first();
                        }
                        if (!$prod && !empty($namaProduk)) {
                            $prod = MasterProduct::where('tenant_id', $tenantId)->where('name', trim($namaProduk))->first();
                        }
                        $estKainVal = !empty($pRow['est_kain']) ? (float)$pRow['est_kain'] : (($prod && $prod->est_kain > 0) ? (float)$prod->est_kain * $qtyProduksi : 0);

                        $spkItem = SpkItem::create([
                            'spk_id'            => $spkRecord->id,
                            'master_product_id' => $prod ? $prod->id : null,
                            'nama_produk'       => $namaProduk,
                            'sku'               => $skuProduk,
                            'sku_kain'          => $rBlock['sku_kain'] ?? ($bahanList[0]['nama_bahan'] ?? null),
                            'ukuran'            => $ukuran,
                            'catatan'           => $rBlock['catatan'] ?? null,
                            'quantity'          => $qtyProduksi,
                            'est_kain'          => $estKainVal,
                            'pemotong'          => $pRow['pemotong'] ?? null,
                            'penjahit'          => $pRow['penjahit'] ?? null,
                            'vendor_kancing'    => $pRow['vendor_kancing'] ?? null,
                            'hpp'               => 0,
                        ]);

                        // Save operational labor details and tariffs into SpkItemExtra for payment/payroll tracking
                        $laborTotal = 0;

                        // 1. Pemotong
                        $pemotong = trim($pRow['pemotong'] ?? '');
                        if ($pemotong !== '') {
                            $this->processAutoSaveVendor($tenantId, $pemotong, 'Pemotong');
                        }
                        $qtyPotong = (int) ($pRow['qty_potong'] ?? 0);
                        $tarifPotong = floatval($pRow['tarif_potong'] ?? 0);
                        if ($pemotong !== '' || $qtyPotong > 0) {
                            $subtotal = $qtyPotong * $tarifPotong;
                            $laborTotal += $subtotal;
                            SpkItemExtra::create([
                                'spk_item_id' => $spkItem->id,
                                'keterangan'  => "Ongkos Potong: {$pemotong} ({$qtyPotong} pcs" . ($tarifPotong > 0 ? " @ Rp " . number_format($tarifPotong) : "") . ")",
                                'nominal'     => $subtotal,
                            ]);
                        }

                        // 2. Penjahit
                        $penjahit = trim($pRow['penjahit'] ?? '');
                        if ($penjahit !== '') {
                            $this->processAutoSaveVendor($tenantId, $penjahit, 'Penjahit');
                        }
                        $qtyJahit = (int) ($pRow['qty_jahit'] ?? 0);
                        $tarifJahit = floatval($pRow['tarif_jahit'] ?? 0);
                        if ($penjahit !== '' || $qtyJahit > 0) {
                            $subtotal = $qtyJahit * $tarifJahit;
                            $laborTotal += $subtotal;
                            SpkItemExtra::create([
                                'spk_item_id' => $spkItem->id,
                                'keterangan'  => "Ongkos Jahit: {$penjahit} ({$qtyJahit} pcs" . ($tarifJahit > 0 ? " @ Rp " . number_format($tarifJahit) : "") . ")",
                                'nominal'     => $subtotal,
                            ]);
                        }

                        // 3. Vendor Kancing
                        $vendorKancing = trim($pRow['vendor_kancing'] ?? '');
                        if ($vendorKancing !== '') {
                            $this->processAutoSaveVendor($tenantId, $vendorKancing, 'Vendor Kancing');
                        }
                        $qtyKancing = (int) ($pRow['qty_kancing'] ?? 0);
                        $tarifKancing = floatval($pRow['tarif_kancing'] ?? 0);
                        if ($vendorKancing !== '' || $qtyKancing > 0) {
                            $subtotal = $qtyKancing * $tarifKancing;
                            $laborTotal += $subtotal;
                            SpkItemExtra::create([
                                'spk_item_id' => $spkItem->id,
                                'keterangan'  => "Ongkos Kancing/LKPK: {$vendorKancing} ({$qtyKancing} pcs" . ($tarifKancing > 0 ? " @ Rp " . number_format($tarifKancing) : "") . ")",
                                'nominal'     => $subtotal,
                            ]);
                        }

                        // 4. Petugas QC
                        $petugasQc = trim($pRow['petugas_qc'] ?? '');
                        if ($petugasQc !== '') {
                            $this->processAutoSaveVendor($tenantId, $petugasQc, 'Petugas QC');
                        }
                        $qcLolos = (int) ($pRow['qc_lolos'] ?? 0);
                        $qcReject = (int) ($pRow['qc_reject'] ?? 0);
                        $tarifQc = floatval($pRow['tarif_qc'] ?? 0);
                        if ($petugasQc !== '' || $qcLolos > 0 || $qcReject > 0) {
                            $subtotal = $qcLolos * $tarifQc;
                            $laborTotal += $subtotal;
                            SpkItemExtra::create([
                                'spk_item_id' => $spkItem->id,
                                'keterangan'  => "Ongkos QC: {$petugasQc} (Lolos: {$qcLolos} pcs, Reject: {$qcReject} pcs" . ($tarifQc > 0 ? " @ Rp " . number_format($tarifQc) : "") . ")",
                                'nominal'     => $subtotal,
                            ]);
                        }

                        // 5. Finishing
                        $petugasFinishing = trim($pRow['petugas_finishing'] ?? '');
                        if ($petugasFinishing !== '') {
                            $this->processAutoSaveVendor($tenantId, $petugasFinishing, 'Finishing');
                        }
                        $qtyFinishing = (int) ($pRow['qty_finishing'] ?? 0);
                        $qtyFgood = (int) ($pRow['qty_fgood'] ?? 0);
                        $tarifFinishing = floatval($pRow['tarif_finishing'] ?? 0);
                        if ($petugasFinishing !== '' || $qtyFinishing > 0 || $qtyFgood > 0) {
                            $subtotal = $qtyFinishing * $tarifFinishing;
                            $laborTotal += $subtotal;
                            SpkItemExtra::create([
                                'spk_item_id' => $spkItem->id,
                                'keterangan'  => "Ongkos Finishing: {$petugasFinishing} ({$qtyFinishing} pcs, F.Good: {$qtyFgood} pcs" . ($tarifFinishing > 0 ? " @ Rp " . number_format($tarifFinishing) : "") . ")",
                                'nominal'     => $subtotal,
                            ]);
                        }

                        if (!empty($bahanList) && is_array($bahanList)) {
                            $totalHpp = $this->processAutoSaveBahanAndRecipe($tenantId, $namaProduk, $skuProduk, $qtyProduksi, $bahanList, $spkItem);
                            if ($totalHpp > 0) {
                                $spkItem->update(['hpp' => $totalHpp]);
                            }
                        }
                    }
                }
                return $firstSpk;
            }

            // Fallback single SPK creation
            $noSpk = Spk::generateNoSpk();
            $spk = Spk::create([
                'tenant_id'           => $tenantId,
                'order_id'            => $request->order_id,
                'no_produksi'         => $noProduksi,
                'no_pesanan'          => $request->no_pesanan,
                'no_spk'              => $noSpk,
                'tipe_spk'            => $request->input('tipe_spk', 'pesanan_pelanggan'),
                'is_urgent'           => $request->boolean('is_urgent'),
                'tahap_saat_ini'      => $tahapSaatIni,
                'tanggal'             => $request->tanggal,
                'deadline'            => $request->deadline ?: null,
                'pemesan'             => $request->pemesan,
                'no_hp_pemesan'       => $request->no_hp_pemesan,
                'instansi'            => $request->instansi,
                'nama_pic'            => $request->nama_pic ?: Auth::user()->name,
                'tambahan'            => $request->tambahan,
                'sku_kain'            => $request->sku_kain,
                'link_file_mentah'    => $request->link_file_mentah,
                'image_url'           => $imagePath ? Storage::url($imagePath) : null,
                'referensi_klien_url' => $referensiPath ? Storage::url($referensiPath) : null,
                'mockup_url'          => $mockupPath ? Storage::url($mockupPath) : null,
                'penginput_id'        => Auth::id(),
            ]);

            if (empty($request->items) || !is_array($request->items)) {
                return $spk;
            }

            // Calculate total SPK Qty across items
            $totalSpkQty = 0;
            foreach ($request->items as $row) {
                $totalSpkQty += max(1, (int) ($row['qty'] ?? 1));
            }

            // Process global Jasa & Bahan from form
            $globalJasa = $request->input('global_jasa', []);
            $globalBahan = $request->input('global_bahan', []);

            $globalJasaItems = [];
            $totalJasaNominal = 0;
            if (is_array($globalJasa)) {
                foreach ($globalJasa as $gj) {
                    $ket = trim($gj['keterangan'] ?? '');
                    $nom = floatval($gj['nominal'] ?? 0);
                    if ($ket !== '' && $nom > 0) {
                        $totalJasaNominal += $nom;
                        $globalJasaItems[] = ['keterangan' => $ket, 'nominal' => $nom];
                    }
                }
            }

            $globalBahanItems = [];
            $totalBahanNominal = 0;
            if (is_array($globalBahan)) {
                foreach ($globalBahan as $gb) {
                    $ket = trim($gb['keterangan'] ?? '');
                    $nom = floatval($gb['nominal'] ?? 0);
                    if ($ket !== '' && $nom > 0) {
                        $totalBahanNominal += $nom;
                        $globalBahanItems[] = ['keterangan' => 'Bahan: ' . $ket, 'nominal' => $nom];
                    }
                }
            }

            $grandTotalGlobal = $totalJasaNominal + $totalBahanNominal;
            $allocatedPerUnit = $totalSpkQty > 0 ? ($grandTotalGlobal / $totalSpkQty) : 0;

            foreach ($request->items as $row) {
                $prodId = null;
                if (!empty($row['sku'])) {
                    $prod = MasterProduct::where('tenant_id', $tenantId)
                        ->where('sku', trim($row['sku']))->first();
                    if ($prod) $prodId = $prod->id;
                }
                if (!$prodId && !empty($row['sku_induk'])) {
                    $prod = MasterProduct::where('tenant_id', $tenantId)
                        ->where('sku_induk', trim($row['sku_induk']))->first();
                    if ($prod) $prodId = $prod->id;
                }
                if (!$prodId && !empty($row['name'])) {
                    $prod = MasterProduct::where('tenant_id', $tenantId)
                        ->where('name', trim($row['name']))->first();
                    if ($prod) $prodId = $prod->id;
                }

                // Sum item-specific extras
                $itemExtrasTotal = 0;
                $itemExtrasList = [];
                if (!empty($row['extras']) && is_array($row['extras'])) {
                    foreach ($row['extras'] as $extra) {
                        if (!empty($extra['keterangan'])) {
                            $nom = floatval($extra['nominal'] ?? 0);
                            $itemExtrasTotal += $nom;
                            $itemExtrasList[] = [
                                'keterangan' => $extra['keterangan'],
                                'nominal' => $nom
                            ];
                        }
                    }
                }

                $hpp = round($allocatedPerUnit + $itemExtrasTotal, 2);

                // For new form: sku_produk maps to sku; if name empty, use sku_produk
                $skuProduk = $row['sku_produk'] ?? ($row['sku'] ?? null);
                $namaProduk = $row['name'] ?? $skuProduk ?? 'Produk SPK';

                $qtyVal = (int) ($row['qty'] ?? 1);
                $estKainVal = (float) ($row['est_kain'] ?? 0);
                if ($estKainVal <= 0 && $prod && $prod->est_kain > 0) {
                    $estKainVal = (float) $prod->est_kain * $qtyVal;
                }

                $item = SpkItem::create([
                    'spk_id'            => $spk->id,
                    'master_product_id' => $prodId,
                    'nama_produk'       => $namaProduk,
                    'sku'               => $skuProduk,
                    'sku_kain'          => $row['sku_kain'] ?? null,
                    'sku_induk'         => $row['sku_induk'] ?? null,
                    'ukuran'            => $row['size'] ?? null,
                    'catatan'           => $row['catatan'] ?? null,
                    'quantity'          => $qtyVal,
                    'est_kain'          => $estKainVal,
                    'kain_pakai'        => (float) ($row['kain_pakai'] ?? 0),
                    'kain_sisa'         => (float) ($row['kain_sisa'] ?? 0),
                    'penjahit'          => $row['penjahit'] ?? ($row['tailor'] ?? null),
                    'vendor_kancing'    => $row['vendor_kancing'] ?? null,
                    'alur_proses'       => $row['alur_proses'] ?? 'Langsung Jahit',
                    'hpp'               => $hpp,
                ]);

                // Save allocated global Jasa entries into spk_item_extras
                foreach ($globalJasaItems as $gj) {
                    $allocatedNominal = $totalSpkQty > 0 ? round($gj['nominal'] / $totalSpkQty, 2) : 0;
                    SpkItemExtra::create([
                        'spk_item_id' => $item->id,
                        'keterangan'  => $gj['keterangan'],
                        'nominal'     => $allocatedNominal,
                    ]);
                }

                // Save allocated global Bahan entries into spk_item_extras
                foreach ($globalBahanItems as $gb) {
                    $allocatedNominal = $totalSpkQty > 0 ? round($gb['nominal'] / $totalSpkQty, 2) : 0;
                    SpkItemExtra::create([
                        'spk_item_id' => $item->id,
                        'keterangan'  => $gb['keterangan'],
                        'nominal'     => $allocatedNominal,
                    ]);
                }

                // Save item specific extras
                foreach ($itemExtrasList as $ex) {
                    SpkItemExtra::create([
                        'spk_item_id' => $item->id,
                        'keterangan'  => $ex['keterangan'],
                        'nominal'     => $ex['nominal'],
                    ]);
                }
            }

            if (!empty($globalBahanItems)) {
                $this->syncSpkWarehouseMutation($spk, $globalBahanItems);
            }

            return $spk;
        });

        return redirect()->route('spks.show', $spk)
            ->with('success', 'SPK #' . $spk->no_spk . ' berhasil disimpan.');
    }

    public function show(Spk $spk)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($spk->tenant_id === $tenantId, 403);
        $spk->load(['penginput', 'items.extras', 'items.progres', 'items.pickups.pemberi', 'proses']);
        
        $this->ensureDefaultProses($spk);
        $grouped = $this->getGroupedItems($spk);

        $sizesHeader = ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'];
        foreach ($spk->items as $item) {
            $sz = $this->normalizeSizeKey($item->ukuran);
            if (!in_array($sz, $sizesHeader)) {
                $sizesHeader[] = $sz;
            }
        }

        $statusOptions = $this->getStatusOptions($spk);

        $progresMap = [];
        foreach ($spk->items as $item) {
            foreach ($item->progres as $pg) {
                $progresMap[$item->id][$pg->spk_proses_id] = $pg;
            }
        }

        // Form autocompletion datasets matching create()
        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->with(['activeRecipe.items.inventoryItem'])
            ->orderBy('name')
            ->get();

        $vendorsData = \App\Models\Tailor::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'category']);

        $pemotongList = $vendorsData->where('category', 'Pemotong')->pluck('name')->values();
        $penjahitList = $vendorsData->filter(fn($v) => in_array($v->category, ['Penjahit', null, ''], true))->pluck('name')->values();
        $vendorKancingList = $vendorsData->where('category', 'Vendor Kancing')->pluck('name')->values();
        $petugasQcList = $vendorsData->where('category', 'Petugas QC')->pluck('name')->values();
        $tailors = $vendorsData->pluck('name')->values();

        $laborServices = \App\Models\LaborService::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['name', 'default_cost']);

        $stores = \App\Models\Store::with('channel')
            ->where('tenant_id', $tenantId)
            ->orderBy('store_name')
            ->get();

        $existingNoProduksi = Spk::where('tenant_id', $tenantId)
            ->whereNotNull('no_produksi')
            ->where('no_produksi', '!=', '')
            ->distinct()
            ->orderByDesc('no_produksi')
            ->pluck('no_produksi');

        $inventoryItemsData = \App\Models\InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->select(['id', 'name', 'unit', 'cost_price'])
            ->orderBy('name')
            ->get();

        $inventoryItems = $inventoryItemsData->pluck('name');

        $inventoryItemsMap = [];
        foreach ($inventoryItemsData as $inv) {
            if (!empty($inv->name)) {
                $inventoryItemsMap[strtoupper(trim($inv->name))] = [
                    'name'       => $inv->name,
                    'unit'       => $inv->unit ?? '',
                    'cost_price' => (float) ($inv->cost_price ?? 0),
                ];
            }
        }

        $recipesMap = [];
        foreach ($products as $prod) {
            $rec = $prod->activeRecipe;
            if ($rec) {
                $batchQty = max(1, (int)$rec->batch_qty);
                $itemsList = [];
                foreach ($rec->items as $rItem) {
                    $invItem = $rItem->inventoryItem;
                    if ($invItem && !empty($invItem->name)) {
                        $itemsList[] = [
                            'nama_bahan' => $invItem->name,
                            'unit'       => $invItem->unit ?? '',
                            'qty_unit'   => round((float)$rItem->quantity / $batchQty, 4),
                            'harga'      => (float)($invItem->cost_price ?? 0),
                        ];
                    }
                }

                $recipeData = [
                    'product_id' => $prod->id,
                    'name'       => $prod->name,
                    'sku'        => $prod->sku,
                    'sku_induk'  => $prod->sku_induk,
                    'ukuran'     => $prod->ukuran,
                    'items'      => $itemsList,
                ];

                if (!empty($prod->name)) {
                    $recipesMap[strtoupper(trim($prod->name))] = $recipeData;
                }
                if (!empty($prod->sku)) {
                    $recipesMap[strtoupper(trim($prod->sku))] = $recipeData;
                }
                if (!empty($prod->sku_induk)) {
                    $recipesMap[strtoupper(trim($prod->sku_induk))] = $recipeData;
                }
            }
        }

        $allMasterProductsList = $products->map(function($p) {
            return [
                'sku'       => $p->sku,
                'sku_induk' => $p->sku_induk,
                'name'      => $p->name,
                'ukuran'    => $p->ukuran ?? '',
                'est_kain'  => (float) ($p->est_kain ?? 0),
            ];
        });

        // Fetch all sibling SPKs under the same no_produksi
        if (!empty($spk->no_produksi)) {
            $siblingSpks = Spk::where('tenant_id', $tenantId)
                ->where('no_produksi', $spk->no_produksi)
                ->orderBy('id')
                ->get(['id', 'no_spk', 'no_produksi', 'kategori']);
        } else {
            $siblingSpks = collect([$spk]);
        }

        $bankAccounts = \App\Models\BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $spkCode = $spk->no_produksi ?: $spk->no_spk;
        $spkExpenses = \App\Models\Expense::where('tenant_id', $tenantId)
            ->where(function ($q) use ($spkCode, $spk) {
                $q->where('description', 'like', "%#{$spkCode}%")
                    ->orWhere('title', 'like', "%#{$spkCode}%");
                if (!empty($spk->no_spk)) {
                    $q->orWhere('description', 'like', "%{$spk->no_spk}%")
                        ->orWhere('title', 'like', "%{$spk->no_spk}%");
                }
            })
            ->orderByDesc('expense_date')
            ->orderByDesc('id')
            ->get();

        $totalSpkLaborCost = 0;
        $totalSpkLaborPaid = 0;
        $laborBreakdown = [];
        foreach ($spk->items as $item) {
            $pName = $item->sku_induk ?: ($item->sku ?: $item->nama_produk);
            foreach ($item->extras as $extra) {
                $nom = (float) $extra->nominal;
                $ket = $extra->keterangan ?? '';
                // Only include labor service items (exclude materials starting with 'Bahan:')
                if ($nom > 0 && !str_starts_with($ket, 'Bahan:') && !str_contains($ket, 'Bahan:')) {
                    $totalSpkLaborCost += $nom;

                    $cleanKet = trim(explode('(', $ket)[0]);
                    $alreadyPaid = $spkExpenses->filter(function ($exp) use ($ket, $cleanKet) {
                        return str_contains($exp->title ?? '', $ket)
                            || str_contains($exp->description ?? '', $ket)
                            || (!empty($cleanKet) && (str_contains($exp->title ?? '', $cleanKet) || str_contains($exp->description ?? '', $cleanKet)));
                    })->sum('amount');

                    $totalSpkLaborPaid += $alreadyPaid;
                    $sisa = max(0, $nom - $alreadyPaid);

                    $laborBreakdown[] = [
                        'id'            => $extra->id,
                        'produk'        => $pName,
                        'keterangan'    => $ket,
                        'nominal'       => $nom,
                        'sudah_dibayar' => $alreadyPaid,
                        'sisa_bayar'    => $sisa,
                        'is_lunas'      => ($sisa <= 0),
                    ];
                }
            }
        }

        $totalSpkLaborUnpaid = max(0, $totalSpkLaborCost - $totalSpkLaborPaid);

        // Ekstrak Bahan SPK (BB-TH), Biaya Produksi, dan Biaya Tambahan untuk level SPK
        $existingBahanList = [];
        $existingBiayaProduksi = 0;
        $existingBiayaTambahan = 0;
        $existingKetTambahan = '';

        foreach ($spk->items as $item) {
            foreach ($item->extras as $extra) {
                $nom = (float) $extra->nominal;
                $ket = $extra->keterangan ?? '';
                if (str_contains($ket, 'Bahan:')) {
                    $bName = trim(str_replace('Bahan:', '', $ket));
                    $bQty = 1;
                    $bSatuan = '';
                    $bHarga = 0;

                    if (preg_match('/^(.*?)\s*\(Qty:\s*([\d\.,]+)\s*(.*?)\)$/i', $bName, $mQ)) {
                        $bName = trim($mQ[1]);
                        $bQtyStr = $mQ[2];
                        $restSatuan = trim($mQ[3]);

                        if (preg_match('/^(.*?)\s*@\s*Rp\s*([\d\.,]+)$/i', $restSatuan, $mH)) {
                            $bSatuan = trim($mH[1]);
                            $bHarga = floatval(str_replace(['.', ','], '', $mH[2]));
                        } else {
                            $bSatuan = $restSatuan;
                        }
                        $bQty = floatval(str_replace(['.', ','], '', $bQtyStr)) ?: 1;
                    }

                    if ($bHarga <= 0 && $nom > 0 && $bQty > 0) {
                        $bHarga = round($nom / $bQty, 2);
                    }

                    $existingBahanList[] = [
                        'nama_bahan' => $bName,
                        'qty_bahan'  => $bQty,
                        'satuan'     => $bSatuan,
                        'harga'      => $bHarga,
                        'subtotal'   => $nom,
                    ];
                } elseif (str_contains($ket, 'Biaya Tambahan:') || str_contains($ket, 'Tambahan:')) {
                    $existingBiayaTambahan += $nom;
                    if (empty($existingKetTambahan)) {
                        $existingKetTambahan = trim(str_replace(['Biaya Tambahan:', 'Tambahan:'], '', $ket));
                        $existingKetTambahan = preg_replace('/\s*\(.*?\)$/', '', $existingKetTambahan);
                    }
                } else {
                    $existingBiayaProduksi += $nom;
                }
            }
        }

        if (!empty($existingBahanList)) {
            $groupedBahan = [];
            foreach ($existingBahanList as $b) {
                $n = $b['nama_bahan'];
                if (!isset($groupedBahan[$n])) {
                    $groupedBahan[$n] = $b;
                } else {
                    $groupedBahan[$n]['qty_bahan'] += $b['qty_bahan'];
                    $groupedBahan[$n]['subtotal'] += $b['subtotal'];
                    if ($b['harga'] > 0) {
                        $groupedBahan[$n]['harga'] = $b['harga'];
                    } elseif ($groupedBahan[$n]['qty_bahan'] > 0) {
                        $groupedBahan[$n]['harga'] = round($groupedBahan[$n]['subtotal'] / $groupedBahan[$n]['qty_bahan'], 2);
                    }
                }
            }
            $spkBahanData = array_values($groupedBahan);
        } else {
            $spkBahanData = [
                [
                    'nama_bahan' => $spk->sku_kain ?: 'BB-TH',
                    'qty_bahan'  => 1,
                    'satuan'     => 'Roll',
                    'harga'      => 0,
                    'subtotal'   => 0,
                ]
            ];
        }

        $spkPayments = $spk->payments()->with(['user', 'bankAccount', 'expense'])->get();

        return view('inventory.spks.show', compact(
            'spk', 'grouped', 'statusOptions', 'sizesHeader', 'progresMap',
            'products', 'tailors', 'vendorsData', 'pemotongList', 'penjahitList', 'vendorKancingList', 'petugasQcList',
            'laborServices', 'stores', 'existingNoProduksi', 'recipesMap',
            'inventoryItems', 'inventoryItemsMap', 'allMasterProductsList',
            'siblingSpks', 'bankAccounts', 'totalSpkLaborCost', 'totalSpkLaborPaid', 'totalSpkLaborUnpaid',
            'laborBreakdown', 'spkExpenses', 'spkPayments',
            'spkBahanData', 'existingBiayaProduksi', 'existingBiayaTambahan', 'existingKetTambahan'
        ));
    }

    /**
     * Halaman Scanner Penerimaan Karung Multi-SPK (Global Fast Scan)
     */
    public function scanKarungPage(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Ambil 50 riwayat scan penerimaan terbaru di tenant ini
        $recentPickups = \App\Models\SpkItemPickup::whereHas('item.spk', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->with(['item.spk', 'item.masterProduct', 'pemberi'])
            ->orderByDesc('created_at')
            ->take(50)
            ->get();

        $activeSpksCount = Spk::where('tenant_id', $tenantId)
            ->where('tahap_saat_ini', '!=', 'Selesai (Finished Good)')
            ->count();

        return view('inventory.spks.scan_karung', compact('recentPickups', 'activeSpksCount'));
    }

    /**
     * Endpoint AJAX: Proses Scan Barcode / QR Code Karung Multi-SPK (Global Scan)
     */
    public function processScanKarung(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'code'           => 'required|string',
            'qty'            => 'nullable|integer|min:1',
            'nama_pengambil' => 'nullable|string|max:255',
            'catatan'        => 'nullable|string|max:500',
        ]);

        $rawCode = trim($request->input('code'));
        // Unescape double-encoded JSON or escaped quotes if scanned from old stickers
        $rawCode = str_replace('\"', '"', $rawCode);
        $rawCode = trim($rawCode, '"\'');
        
        $qtyRequested = max(1, (int) $request->input('qty', 1));
        $namaPengambil = trim($request->input('nama_pengambil')) ?: (Auth::user()->name ?? 'Petugas Gudang');
        $catatan = trim($request->input('catatan')) ?: 'Scan Multi-SPK Karung Penerimaan';

        $matchedItem = null;
        $matchedSpk = null;

        // Extract JSON string if embedded inside rawCode
        if (preg_match('/\{.*?\}/s', $rawCode, $jsonMatches)) {
            $jsonCode = $jsonMatches[0];
            $json = json_decode($jsonCode, true);
            if (is_array($json)) {
                if (!empty($json['item_id'])) {
                    $matchedItem = \App\Models\SpkItem::whereHas('spk', fn($q) => $q->where('tenant_id', $tenantId))
                        ->with(['spk', 'masterProduct', 'pickups'])
                        ->find((int) $json['item_id']);
                    if ($matchedItem) {
                        $matchedSpk = $matchedItem->spk;
                    }
                }
                if (!$matchedItem && (!empty($json['spk_id']) || !empty($json['no_spk']) || !empty($json['no_produksi']))) {
                    $querySpk = Spk::where('tenant_id', $tenantId);
                    if (!empty($json['spk_id'])) {
                        $querySpk->where('id', (int) $json['spk_id']);
                    } elseif (!empty($json['no_spk'])) {
                        $querySpk->where('no_spk', trim($json['no_spk']));
                    } else {
                        $querySpk->where('no_produksi', trim($json['no_produksi']));
                    }
                    $targetSpk = $querySpk->with(['items.masterProduct', 'items.pickups'])->first();
                    if ($targetSpk) {
                        $targetSku = !empty($json['sku']) ? strtoupper(trim($json['sku'])) : null;
                        if ($targetSku) {
                            $matchedItem = $targetSpk->items->first(function ($it) use ($targetSku) {
                                return (!empty($it->sku) && strtoupper(trim($it->sku)) === $targetSku) ||
                                       ($it->masterProduct && strtoupper(trim($it->masterProduct->sku ?? '')) === $targetSku);
                            });
                        }
                        if (!$matchedItem) {
                            $matchedItem = $targetSpk->items->first(fn($it) => $it->sisa_qty > 0) ?: $targetSpk->items->first();
                        }
                        if ($matchedItem) {
                            $matchedSpk = $targetSpk;
                        }
                    }
                }
            }
        }

        $cleanCode = strtoupper($rawCode);

        // A. Format Kombinasi String: SPK-{spk_id}-ITEM-{item_id} atau ITEM-{item_id}
        if (!$matchedItem) {
            if (preg_match('/(?:SPK-(\d+)-)?ITEM-(\d+)/i', $rawCode, $matches)) {
                $spkIdFromCode = !empty($matches[1]) ? (int) $matches[1] : null;
                $itemIdFromCode = (int) $matches[2];

                $matchedItem = \App\Models\SpkItem::whereHas('spk', fn($q) => $q->where('tenant_id', $tenantId))
                    ->with(['spk', 'masterProduct', 'pickups'])
                    ->find($itemIdFromCode);

                if ($matchedItem && $spkIdFromCode && $matchedItem->spk_id != $spkIdFromCode) {
                    $matchedItem = null;
                } elseif ($matchedItem) {
                    $matchedSpk = $matchedItem->spk;
                }
            }
        }

        // B. Format Separator Pipe/Underscore/Slash/Colon: SPK-001|SKU atau SPK-001_SKU atau SPK-001/SKU
        if (!$matchedItem) {
            if (preg_match('/^([A-Z0-9\-\#]+)[\|_:\/](.+)$/i', $rawCode, $matches)) {
                $noSpkCandidate = ltrim(trim($matches[1]), '#');
                $skuCandidate = strtoupper(trim($matches[2]));

                $targetSpk = Spk::where('tenant_id', $tenantId)
                    ->where(function ($q) use ($noSpkCandidate) {
                        $q->where('no_spk', $noSpkCandidate)
                          ->orWhere('no_produksi', $noSpkCandidate);
                    })
                    ->with(['items.masterProduct', 'items.pickups'])
                    ->first();

                if ($targetSpk) {
                    $matchedItem = $targetSpk->items->first(function ($it) use ($skuCandidate) {
                        return (!empty($it->sku) && strtoupper(trim($it->sku)) === $skuCandidate) ||
                               ($it->masterProduct && strtoupper(trim($it->masterProduct->sku ?? '')) === $skuCandidate);
                    });
                    if (!$matchedItem) {
                        $matchedItem = $targetSpk->items->first(fn($it) => $it->sisa_qty > 0) ?: $targetSpk->items->first();
                    }
                    if ($matchedItem) {
                        $matchedSpk = $targetSpk;
                    }
                }
            }
        }

        // C. Direct No SPK / No Produksi Lookup (e.g., SPK-20260912-0001, #SPK-20260912-0001, Label Stiker Kemasan SPK #SPK-20260912-0001)
        if (!$matchedItem) {
            $spkNoSearch = $rawCode;
            if (preg_match('/(SPK-[A-Z0-9\-]+|PROD-[A-Z0-9\-]+)/i', $rawCode, $spkMatches)) {
                $spkNoSearch = strtoupper(trim($spkMatches[1]));
            } else {
                $spkNoSearch = strtoupper(ltrim(trim($rawCode), '#'));
            }

            $targetSpk = Spk::where('tenant_id', $tenantId)
                ->where(function ($q) use ($spkNoSearch, $rawCode) {
                    $q->where('no_spk', $spkNoSearch)
                      ->orWhere('no_spk', ltrim($rawCode, '#'))
                      ->orWhere('no_spk', $rawCode)
                      ->orWhere('no_produksi', $spkNoSearch)
                      ->orWhere('no_produksi', ltrim($rawCode, '#'))
                      ->orWhere('no_produksi', $rawCode);
                })
                ->with(['items.masterProduct', 'items.pickups'])
                ->first();

            if ($targetSpk) {
                $matchedItem = $targetSpk->items->first(fn($it) => $it->sisa_qty > 0) ?: $targetSpk->items->first();
                if ($matchedItem) {
                    $matchedSpk = $targetSpk;
                }
            }
        }

        // D. Fallback: Search across all open/active SPKs by SKU or Barcode
        if (!$matchedItem) {
            $openSpks = Spk::where('tenant_id', $tenantId)
                ->where('tahap_saat_ini', '!=', 'Selesai (Finished Good)')
                ->with(['items.masterProduct', 'items.pickups'])
                ->orderBy('created_at', 'asc')
                ->get();

            foreach ($openSpks as $spkCandidate) {
                $foundInSpk = $spkCandidate->items->first(function ($it) use ($cleanCode, $rawCode) {
                    if ($it->sisa_qty <= 0) return false;
                    if (!empty($it->sku) && strtoupper(trim($it->sku)) === $cleanCode) return true;
                    if ($it->masterProduct) {
                        if (!empty($it->masterProduct->barcode) && strtoupper(trim($it->masterProduct->barcode)) === $cleanCode) return true;
                        if (!empty($it->masterProduct->sku) && strtoupper(trim($it->masterProduct->sku)) === $cleanCode) return true;
                    }
                    return false;
                });

                if ($foundInSpk) {
                    $matchedItem = $foundInSpk;
                    $matchedSpk = $spkCandidate;
                    break;
                }
            }
        }

        // E. Fallback pencocokan ukuran / SKU ending
        if (!$matchedItem) {
            $openSpks = Spk::where('tenant_id', $tenantId)
                ->where('tahap_saat_ini', '!=', 'Selesai (Finished Good)')
                ->with(['items.masterProduct', 'items.pickups'])
                ->orderBy('created_at', 'asc')
                ->get();

            $szClean = preg_replace('/^(SIZE|UKURAN|SZ|VARIAN)[\s\-_:]*/i', '', $cleanCode);
            $szClean = trim($szClean);

            if (!empty($szClean)) {
                foreach ($openSpks as $spkCandidate) {
                    $foundInSpk = $spkCandidate->items->first(function ($it) use ($szClean) {
                        if ($it->sisa_qty <= 0) return false;
                        return !empty($it->ukuran) && strtoupper(trim($it->ukuran)) === $szClean;
                    });

                    if ($foundInSpk) {
                        $matchedItem = $foundInSpk;
                        $matchedSpk = $spkCandidate;
                        break;
                    }
                }
            }
        }

        // 1. VALIDASI: Jika item tidak ditemukan di SPK manapun
        if (!$matchedItem) {
            return response()->json([
                'success'      => false,
                'error_type'   => 'not_found',
                'title'        => 'KODE LABEL TIDAK DIKENAL!',
                'message'      => "Barcode/QR '{$rawCode}' TIDAK DITEMUKAN pada SPK aktif di sistem.",
                'detail'       => "Pastikan label stiker mencantumkan nomor SPK atau SKU yang valid.",
                'scanned_code' => $rawCode,
            ], 422);
        }

        $spk = $matchedSpk ?: $matchedItem->spk;
        if (!$spk || $spk->tenant_id !== $tenantId) {
            return response()->json([
                'success'      => false,
                'error_type'   => 'access_denied',
                'title'        => 'AKSES DITOLAK!',
                'message'      => "SPK terkait tidak ditemukan pada akun toko Anda.",
                'scanned_code' => $rawCode,
            ], 403);
        }

        // 2. VALIDASI: Cek Sisa Kuota Item
        $sisaQty = $matchedItem->sisa_qty;
        if ($sisaQty <= 0) {
            return response()->json([
                'success'      => false,
                'error_type'   => 'quota_exceeded',
                'title'        => 'KUOTA SPK SUDAH LENGKAP!',
                'message'      => "SPK #{$spk->no_spk} — Item '{$matchedItem->nama_produk}' ({$matchedItem->ukuran}) sudah LENGKAP ({$matchedItem->quantity}/{$matchedItem->quantity} pcs).",
                'detail'       => "Tidak dapat menerima penerimaan melebihi kuota SPK ini.",
                'item_id'      => $matchedItem->id,
                'spk_no'       => $spk->no_spk,
                'scanned_code' => $rawCode,
            ], 422);
        }

        // 3. Eksekusi Penerimaan Barang SPK
        $qtyToTake = min($qtyRequested, $sisaQty);

        $pickup = DB::transaction(function () use ($matchedItem, $spk, $qtyToTake, $namaPengambil, $catatan) {
            $p = \App\Models\SpkItemPickup::create([
                'spk_item_id'    => $matchedItem->id,
                'qty_diambil'    => $qtyToTake,
                'tanggal_ambil'  => now(),
                'nama_pengambil' => $namaPengambil,
                'pemberi_id'     => Auth::id(),
                'catatan'        => $catatan,
            ]);

            // Mutasi Stok Master Produk
            $product = null;
            if ($matchedItem->master_product_id) {
                $product = MasterProduct::find($matchedItem->master_product_id);
            } elseif (!empty($matchedItem->sku)) {
                $product = MasterProduct::where('tenant_id', $spk->tenant_id)->where('sku', trim($matchedItem->sku))->first();
            } elseif (!empty($matchedItem->nama_produk)) {
                $product = MasterProduct::where('tenant_id', $spk->tenant_id)->where('name', trim($matchedItem->nama_produk))->first();
            }

            if ($product) {
                $product->recordStockMovement(
                    $qtyToTake,
                    'in',
                    'Scan Karung SPK #' . $spk->no_spk . ' (' . $matchedItem->nama_produk . ' - ' . $matchedItem->ukuran . ')',
                    Auth::id()
                );

                $product->recordStockMovement(
                    $qtyToTake,
                    'out',
                    'Penyerahan Scan Karung SPK #' . $spk->no_spk . ' (Penerima: ' . $namaPengambil . ')',
                    Auth::id()
                );

                $recipe = \App\Models\ProductRecipe::where('master_product_id', $product->id)
                    ->where('tenant_id', $spk->tenant_id)
                    ->where('is_active', true)
                    ->with('items.inventoryItem')
                    ->first();

                if ($recipe) {
                    foreach ($recipe->items as $recipeItem) {
                        $invItem = $recipeItem->inventoryItem;
                        if ($invItem) {
                            $batchQty = max(1, $recipe->batch_qty);
                            $qtyNeeded = ($recipeItem->quantity / $batchQty) * $qtyToTake;

                            $invItem->recordStockMovement(
                                (int) ceil($qtyNeeded),
                                'out',
                                'Konsumsi Bahan Scan Karung SPK #' . $spk->no_spk . ' (' . $qtyToTake . ' pcs)',
                                Auth::id()
                            );
                        }
                    }
                }
            }

            return $p;
        });

        // 4. Update status & refresh data
        $spk->load(['items.pickups']);
        $matchedItem->refresh();

        $itemTotalDiambil = $matchedItem->qty_diambil;
        $itemSisa = $matchedItem->sisa_qty;
        $isItemComplete = ($itemSisa == 0);

        $spkTotalTarget = (int) $spk->items->sum('quantity');
        $spkTotalDiambil = (int) $spk->items->sum('qty_diambil');
        $spkTotalSisa = (int) $spk->items->sum('sisa_qty');
        $allComplete = $spk->items->every(fn($it) => $it->sisa_qty == 0);

        if ($allComplete && $spk->tahap_saat_ini !== 'Selesai (Finished Good)') {
            $spk->update(['tahap_saat_ini' => 'Selesai (Finished Good)']);
        }

        return response()->json([
            'success'            => true,
            'message'            => "BERHASIL! SPK #{$spk->no_spk} — {$matchedItem->nama_produk} ({$matchedItem->ukuran}) +{$qtyToTake} Pcs.",
            'spk'                => [
                'id'          => $spk->id,
                'no_spk'      => $spk->no_spk,
                'no_produksi' => $spk->no_produksi ?: '-',
                'pemesan'     => $spk->pemesan ?: 'GUDANG',
            ],
            'item'               => [
                'id'           => $matchedItem->id,
                'nama_produk'  => $matchedItem->nama_produk,
                'sku'          => $matchedItem->sku ?: ($matchedItem->masterProduct->sku ?? '-'),
                'ukuran'       => $matchedItem->ukuran ?: 'All',
                'quantity'     => $matchedItem->quantity,
                'qty_diambil'  => $itemTotalDiambil,
                'sisa_qty'     => $itemSisa,
                'is_completed' => $isItemComplete,
            ],
            'spk_total_target'   => $spkTotalTarget,
            'spk_total_diambil'  => $spkTotalDiambil,
            'spk_total_sisa'     => $spkTotalSisa,
            'percent_complete'   => $spkTotalTarget > 0 ? min(100, round(($spkTotalDiambil / $spkTotalTarget) * 100)) : 0,
            'all_completed'      => $allComplete,
            'pickup'             => [
                'id'             => $pickup->id,
                'qty'            => $pickup->qty_diambil,
                'tanggal'        => $pickup->tanggal_ambil->format('d/m/Y H:i'),
                'nama_pengambil' => $pickup->nama_pengambil,
            ]
        ]);
    }

    public function update(Request $request, Spk $spk)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($spk->tenant_id === $tenantId, 403);

        $request->validate([
            'no_produksi'       => 'nullable|string|max:255',
            'no_pesanan'        => 'nullable|string|max:255',
            'tanggal'           => 'required|date',
            'deadline'          => 'nullable|date',
            'tipe_spk'          => 'nullable|string|in:pesanan_pelanggan,stok_gudang',
            'tahap_saat_ini'    => 'nullable|string|max:100',
            'pemesan'           => 'nullable|string|max:255',
            'no_hp_pemesan'     => 'nullable|string|max:100',
            'instansi'          => 'nullable|string|max:255',
            'nama_pic'          => 'nullable|string|max:255',
            'tambahan'          => 'nullable|string',
            'image'             => 'nullable|image|max:4096',
            'referensi_klien'   => 'nullable|image|max:8192',
            'mockup_final'      => 'nullable|image|max:8192',
        ]);

        DB::transaction(function () use ($request, $spk, $tenantId) {
            $noProduksi = trim((string) $request->input('no_produksi'));
            if (empty($noProduksi)) {
                $noProduksi = null;
            }

            $linkFileMentah = $request->input('link_file_mentah');
            if (!$linkFileMentah && $request->has('rincian')) {
                foreach ($request->input('rincian') as $rBlock) {
                    if (!empty($rBlock['link_file_mentah'])) {
                        $linkFileMentah = $rBlock['link_file_mentah'];
                        break;
                    }
                }
            }

            $updateData = [
                'no_produksi'      => $noProduksi,
                'no_pesanan'       => $request->no_pesanan,
                'tanggal'          => $request->tanggal,
                'deadline'         => $request->deadline ?: null,
                'tipe_spk'         => $request->input('tipe_spk', $spk->tipe_spk ?: 'pesanan_pelanggan'),
                'kategori'         => $request->kategori,
                'is_urgent'        => $request->boolean('is_urgent'),
                'tahap_saat_ini'   => $request->input('tahap_saat_ini', $spk->tahap_saat_ini ?: 'DRAFT'),
                'pemesan'          => $request->pemesan,
                'no_hp_pemesan'    => $request->no_hp_pemesan,
                'instansi'         => $request->instansi,
                'nama_pic'         => $request->nama_pic,
                'tambahan'         => $request->tambahan,
                'link_file_mentah' => $linkFileMentah,
            ];

            if ($request->hasFile('image')) {
                $updateData['image_url'] = $this->resizeAndStoreUploadedImage($request->file('image'), 'spks', 1200, 80);
            }

            $referensiFile = $request->file('referensi_klien') ?? $request->file('rincian.0.referensi_klien');
            if (!$referensiFile && $request->hasFile('rincian')) {
                foreach ((array)$request->file('rincian') as $rFile) {
                    if (!empty($rFile['referensi_klien'])) {
                        $referensiFile = $rFile['referensi_klien'];
                        break;
                    }
                }
            }
            if ($referensiFile) {
                $updateData['referensi_klien_url'] = $this->resizeAndStoreUploadedImage($referensiFile, 'spks/referensi', 1200, 80);
            }

            $mockupFile = $request->file('mockup_final') ?? $request->file('rincian.0.mockup_final');
            if (!$mockupFile && $request->hasFile('rincian')) {
                foreach ((array)$request->file('rincian') as $rFile) {
                    if (!empty($rFile['mockup_final'])) {
                        $mockupFile = $rFile['mockup_final'];
                        break;
                    }
                }
            }
            if ($mockupFile) {
                $mockupUrl = $this->resizeAndStoreUploadedImage($mockupFile, 'spks/mockup', 1200, 80);
                $updateData['mockup_url'] = $mockupUrl;
                if (empty($spk->image_url) && !isset($updateData['image_url'])) {
                    $updateData['image_url'] = $mockupUrl;
                }
            }

            $spk->update($updateData);

            // Update item details if provided in request
            $rincianBlocks = $request->input('rincian', []);
            if (!empty($rincianBlocks) && is_array($rincianBlocks)) {
                foreach ($rincianBlocks as $rIdx => $rBlock) {
                    $prodList = $rBlock['produk'] ?? [];
                    if (!empty($prodList) && is_array($prodList)) {
                        $itemsOrdered = $spk->items->values();
                        $savedItemIds = [];

                        foreach ($prodList as $pIdx => $pRow) {
                            $namaProduk = trim($pRow['nama_produk'] ?? '') ?: 'PRODUK BARU';
                            $skuProduk  = trim($pRow['sku_produk'] ?? '');
                            $ukuran     = trim($pRow['ukuran'] ?? '') ?: 'ALL SIZE';
                            $qtyProd    = max(1, (int) ($pRow['qty_produksi'] ?? 1));
                            $spkItem    = $itemsOrdered->get((int)$pIdx);
                            $estKain    = isset($pRow['est_kain']) ? (float)$pRow['est_kain'] : 0;
                            if ($estKain <= 0) {
                                $prod = null;
                                if ($skuProduk) {
                                    $prod = MasterProduct::where('tenant_id', $spk->tenant_id)->where('sku', $skuProduk)->first();
                                }
                                if (!$prod && $namaProduk) {
                                    $prod = MasterProduct::where('tenant_id', $spk->tenant_id)->where('name', $namaProduk)->first();
                                }
                                if ($prod && $prod->est_kain > 0) {
                                    $estKain = (float)$prod->est_kain * $qtyProd;
                                } else {
                                    $estKain = $spkItem->est_kain ?? 0;
                                }
                            }

                            if (!$spkItem) {
                                $spkItem = SpkItem::create([
                                    'spk_id'      => $spk->id,
                                    'nama_produk' => $namaProduk,
                                    'sku'         => $skuProduk,
                                    'ukuran'      => $ukuran,
                                    'quantity'    => $qtyProd,
                                    'est_kain'    => $estKain,
                                ]);
                            } else {
                                $spkItem->update([
                                    'nama_produk' => $namaProduk,
                                    'sku'         => $skuProduk,
                                    'ukuran'      => $ukuran,
                                    'quantity'    => $qtyProd,
                                    'est_kain'    => $estKain,
                                ]);
                            }
                            $savedItemIds[] = $spkItem->id;
                        }

                        // Hapus varian yang dihapus dari tabel jika ada
                        if (!empty($savedItemIds)) {
                            SpkItem::where('spk_id', $spk->id)
                                ->whereNotIn('id', $savedItemIds)
                                ->delete();
                        }

                        if ($request->has('total_est_kain')) {
                            $inputTotalEst = max(0, (float) $request->input('total_est_kain'));
                            $firstItem = SpkItem::where('spk_id', $spk->id)->first();
                            if ($firstItem) {
                                $firstItem->update(['est_kain' => $inputTotalEst]);
                            }
                        }
                    }
                }
            }

            // Reload fresh items to get updated quantities
            $spk->load('items');
            $totalSpkQty = (int) $spk->items->sum('quantity');
            if ($totalSpkQty <= 0) $totalSpkQty = 1;

            // 1. Process Bahan SPK (BB-TH)
            $spkBahanInputs = $request->input('spk_bahan', []);
            $cleanBahanList = [];
            $totalBahanNominal = 0;

            if (is_array($spkBahanInputs)) {
                foreach ($spkBahanInputs as $b) {
                    $rawNama = trim($b['nama_bahan'] ?? '');
                    if (empty($rawNama)) continue;

                    $qtyBahan   = floatval(str_replace(['.', ','], ['', '.'], $b['qty_bahan'] ?? 1)) ?: 1;
                    $hargaBahan = floatval(str_replace(['.', ','], '', $b['harga'] ?? 0));
                    $subtotal   = floatval(str_replace(['.', ','], '', $b['subtotal'] ?? ($qtyBahan * $hargaBahan)));

                    if ($subtotal <= 0 && $hargaBahan > 0) {
                        $subtotal = $qtyBahan * $hargaBahan;
                    }

                    $cleanBahanList[] = [
                        'nama_bahan' => $rawNama,
                        'qty_bahan'  => $qtyBahan,
                        'satuan'     => trim($b['satuan'] ?? ''),
                        'harga'      => $hargaBahan,
                        'subtotal'   => $subtotal,
                    ];
                    $totalBahanNominal += $subtotal;
                }
            }

            // 2. Process Biaya Produksi SPK
            $rawBiayaProduksi = $request->input('spk_biaya_produksi', 0);
            if (is_string($rawBiayaProduksi)) {
                $rawBiayaProduksi = str_replace(['.', ','], '', $rawBiayaProduksi);
            }
            $biayaProduksiNominal = max(0, floatval($rawBiayaProduksi));

            // 3. Process Biaya Tambahan SPK
            $rawBiayaTambahan = $request->input('spk_biaya_tambahan', 0);
            if (is_string($rawBiayaTambahan)) {
                $rawBiayaTambahan = str_replace(['.', ','], '', $rawBiayaTambahan);
            }
            $biayaTambahanNominal = max(0, floatval($rawBiayaTambahan));
            $ketTambahan = trim((string)$request->input('spk_ket_tambahan', ''));

            // 4. Hitung Estimasi HPP SPK
            $grandTotalCost = $totalBahanNominal + $biayaProduksiNominal + $biayaTambahanNominal;
            $hppPerUnit = $totalSpkQty > 0 ? round($grandTotalCost / $totalSpkQty, 2) : 0;

            // 5. Update HPP dan distribusikan SpkItemExtra ke masing-masing item
            $firstItem = $spk->items->first();

            // Clear old extras for all items in this SPK
            SpkItemExtra::whereIn('spk_item_id', $spk->items->pluck('id'))->delete();

            foreach ($spk->items as $item) {
                $item->update(['hpp' => $hppPerUnit]);
                $itemQty = max(1, (int)$item->quantity);
                $ratio = $totalSpkQty > 0 ? ($itemQty / $totalSpkQty) : (1 / max(1, count($spk->items)));

                // A. Extras Bahan
                foreach ($cleanBahanList as $mat) {
                    $itemMatNominal = round($mat['subtotal'] * $ratio, 2);
                    $itemQtyBahan   = round($mat['qty_bahan'] * $ratio, 4);
                    $cleanQtyStr    = ($itemQtyBahan == (int)$itemQtyBahan) ? (int)$itemQtyBahan : (float)$itemQtyBahan;
                    $satuanStr      = !empty($mat['satuan']) ? " " . $mat['satuan'] : "";
                    $hargaStr       = $mat['harga'] > 0 ? " @ Rp " . number_format($mat['harga'], 0, ',', '.') : "";
                    SpkItemExtra::create([
                        'spk_item_id' => $item->id,
                        'keterangan'  => "Bahan: {$mat['nama_bahan']} (Qty: {$cleanQtyStr}{$satuanStr}{$hargaStr})",
                        'nominal'     => $itemMatNominal,
                    ]);
                }

                // B. Extras Biaya Produksi
                if ($biayaProduksiNominal > 0) {
                    $itemLaborNominal = round($biayaProduksiNominal * $ratio, 2);
                    $bpUnit = round($biayaProduksiNominal / $totalSpkQty);
                    SpkItemExtra::create([
                        'spk_item_id' => $item->id,
                        'keterangan'  => "Biaya Produksi ({$itemQty} pcs @ Rp " . number_format($bpUnit, 0, ',', '.') . ")",
                        'nominal'     => $itemLaborNominal,
                    ]);
                }

                // C. Extras Biaya Tambahan
                if ($biayaTambahanNominal > 0) {
                    $itemTambahanNominal = round($biayaTambahanNominal * $ratio, 2);
                    $ketDesc = $ketTambahan ? "Biaya Tambahan: {$ketTambahan}" : "Biaya Tambahan";
                    SpkItemExtra::create([
                        'spk_item_id' => $item->id,
                        'keterangan'  => "{$ketDesc} ({$itemQty} pcs)",
                        'nominal'     => $itemTambahanNominal,
                    ]);
                }
            }

            // Auto-save bahan to Master Inventory / Recipe using first item as reference if available
            if (!empty($cleanBahanList) && $firstItem) {
                $this->processAutoSaveBahanAndRecipe(
                    $tenantId, 
                    $firstItem->nama_produk, 
                    $firstItem->sku, 
                    $totalSpkQty, 
                    $cleanBahanList, 
                    null
                );
            }

            // Sync WarehouseMutation (Pengeluaran Barang) and deduct stock
            $this->syncSpkWarehouseMutation($spk, $cleanBahanList);
        });

        return redirect()->route('spks.show', $spk)
            ->with('success', 'Perubahan SPK #' . $spk->no_spk . ' berhasil disimpan.');
    }

    public function destroy(Spk $spk)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($spk->tenant_id === $tenantId, 403);

        $noProduksi = $spk->no_produksi;

        DB::transaction(function () use ($spk, $tenantId, $noProduksi) {
            if (!empty($noProduksi)) {
                $spkList = Spk::where('tenant_id', $tenantId)
                    ->where('no_produksi', $noProduksi)
                    ->get();
            } else {
                $spkList = collect([$spk]);
            }

            $affectedNoPesanans = $spkList->pluck('no_pesanan')->filter()->unique();

            foreach ($spkList as $itemSpk) {
                // Restore stock and delete associated WarehouseMutation
                $this->syncSpkWarehouseMutation($itemSpk, []);

                foreach ($itemSpk->items as $item) {
                    SpkItemExtra::where('spk_item_id', $item->id)->delete();
                    SpkItemProgres::where('spk_item_id', $item->id)->delete();
                    \App\Models\SpkItemPickup::where('spk_item_id', $item->id)->delete();
                    $item->delete();
                }
                SpkProses::where('spk_id', $itemSpk->id)->delete();
                $itemSpk->delete();
            }

            // Sync kembali status Penjualan Offline jika seluruh SPK terkait telah dihapus
            foreach ($affectedNoPesanans as $noPesanan) {
                $remainingSpkCount = Spk::where('tenant_id', $tenantId)
                    ->where('no_pesanan', $noPesanan)
                    ->count();

                if ($remainingSpkCount === 0) {
                    $offlineSale = \App\Models\OfflineSale::where('tenant_id', $tenantId)
                        ->where('sale_number', $noPesanan)
                        ->first();

                    if ($offlineSale && $offlineSale->is_po && in_array($offlineSale->status, [
                        \App\Models\OfflineSale::STATUS_SPK_PROCESSING,
                        \App\Models\OfflineSale::STATUS_PENDING_APPROVAL,
                    ])) {
                        $revertedStatus = ((float) $offlineSale->paid_amount > 0)
                            ? \App\Models\OfflineSale::STATUS_PENDING_SPK
                            : \App\Models\OfflineSale::STATUS_WAITING_DP;
                        $offlineSale->update(['status' => $revertedStatus]);
                    }
                }
            }
        });

        $prodLabel = !empty($noProduksi) ? 'Produksi ' . $noProduksi : 'SPK #' . $spk->no_spk;
        return redirect()->route('spks.index')
            ->with('success', 'Data ' . $prodLabel . ' berhasil dihapus.');
    }

    public function print(Spk $spk)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($spk->tenant_id === $tenantId, 403);

        $spkQuery = Spk::with(['penginput', 'items.extras', 'items.progres', 'proses', 'order'])
            ->where('tenant_id', $tenantId);

        if (!empty($spk->no_produksi)) {
            $spkList = $spkQuery->where('no_produksi', $spk->no_produksi)->orderBy('id')->get();
        } else {
            $spkList = collect([$spk]);
        }

        if ($spkList->isEmpty()) {
            $spk->load(['penginput', 'items.extras', 'items.progres', 'proses', 'order']);
            $spkList = collect([$spk]);
        }

        $sizesHeader = ['S', 'M', 'L', 'XL', 'XXL', '3XL', '4XL'];

        $spkBlocks = [];
        foreach ($spkList as $currentSpk) {
            $variantRows = [];
            $bazaItems = [];

            foreach ($currentSpk->items as $item) {
                $szKey = $this->normalizeSizeKey($item->ukuran);
                if (!in_array($szKey, $sizesHeader)) {
                    $sizesHeader[] = $szKey;
                }

                $skuInduk = $item->sku_induk;
                if (!$skuInduk && !empty($item->sku)) {
                    $skuInduk = preg_replace('/[_\-\s]+(S|M|L|XL|XXL|3XL|4XL|XXXL|XXXXL|2XL|ALLSIZE|ALL SIZE)$/i', '', trim($item->sku));
                }
                $modelName = $skuInduk ?: ($item->sku ?: ($item->nama_produk ?: 'MODEL VARIAN'));

                if (!isset($variantRows[$modelName])) {
                    $variantRows[$modelName] = [
                        'name'        => $modelName,
                        'sku'         => $modelName,
                        'sizes'       => [],
                        'total'       => 0,
                        'fabric_qty'  => 0,
                        'fabric_name' => '',
                    ];
                }
                $variantRows[$modelName]['sizes'][$szKey] = ($variantRows[$modelName]['sizes'][$szKey] ?? 0) + $item->quantity;
                $variantRows[$modelName]['total'] += $item->quantity;

                foreach ($item->extras as $extra) {
                    if (str_contains($extra->keterangan, 'Bahan:')) {
                        $ket = $extra->keterangan;
                        $bName = trim(str_replace('Bahan:', '', $ket));
                        $nVal = 0;
                        if (preg_match('/^(.*?)\s*\(Qty:\s*([\d\.,]+)\)$/i', $bName, $mQty)) {
                            $bName = trim($mQty[1]);
                            $rawVal = str_replace(',', '.', $mQty[2]);
                            $nVal = (float) $rawVal;
                        }
                        if ($nVal > 150) {
                            $nVal = $nVal / 100;
                        }
                        $variantRows[$modelName]['fabric_qty'] += $nVal;
                        if (empty($variantRows[$modelName]['fabric_name'])) {
                            $variantRows[$modelName]['fabric_name'] = $bName;
                        }

                        $bazaItems[] = [
                            'name' => $bName,
                            'qty'  => $nVal,
                        ];
                    }
                }
            }

            $spkBlocks[] = [
                'spk'         => $currentSpk,
                'variantRows' => $variantRows,
                'bazaItems'   => $bazaItems,
            ];
        }

        return view('inventory.spks.print', compact('spk', 'spkList', 'spkBlocks', 'sizesHeader'));
    }

    public function updateItemStatus(Request $request, $itemId)
    {
        $item = SpkItem::findOrFail($itemId);
        $spk = $item->spk;
        abort_unless($spk->tenant_id === Auth::user()->tenant_id, 403);

        $validStatuses = $this->getStatusOptions($spk);

        $request->validate([
            'status' => 'required|string|in:' . implode(',', $validStatuses),
        ]);

        $oldStatus = $item->status;
        $newStatus = $request->status;

        if ($oldStatus === $newStatus) {
            return redirect()->back();
        }

        DB::transaction(function () use ($item, $spk, $oldStatus, $newStatus) {
            $item->update([
                'status' => $newStatus
            ]);

            // Transition: To 'Selesai' (Production Completed)
            if ($newStatus === 'Selesai' && $oldStatus !== 'Selesai') {
                $product = null;
                if ($item->master_product_id) {
                    $product = MasterProduct::find($item->master_product_id);
                }
                if (!$product && !empty($item->sku)) {
                    $product = MasterProduct::where('tenant_id', $spk->tenant_id)
                        ->where('sku', trim($item->sku))->first();
                }
                if (!$product && !empty($item->sku_induk)) {
                    $product = MasterProduct::where('tenant_id', $spk->tenant_id)
                        ->where('sku_induk', trim($item->sku_induk))->first();
                }
                if (!$product && !empty($item->nama_produk)) {
                    $product = MasterProduct::where('tenant_id', $spk->tenant_id)
                        ->where('name', trim($item->nama_produk))->first();
                }

                if ($product) {
                    if ($item->master_product_id !== $product->id) {
                        $item->update(['master_product_id' => $product->id]);
                    }

                    // 1. Add finished goods stock & record movement
                    $product->recordStockMovement(
                        $item->quantity,
                        'in',
                        'Penerimaan SPK Selesai #' . $spk->no_spk . ' (Item: ' . $item->nama_produk . ')',
                        Auth::id()
                    );

                    // 2. Update catalog HPP (cost_price) menggunakan Metode Rata-Rata Bergerak (Weighted Average)
                    $totalStockAfter = (int) $product->stock;
                    $newBatchQty = (int) $item->quantity;
                    $newBatchHpp = (float) ($item->hpp ?? 0);
                    $previousStock = max(0, $totalStockAfter - $newBatchQty);
                    $previousHpp = (float) ($product->cost_price ?? 0);

                    if ($previousStock > 0 && $previousHpp > 0 && $newBatchHpp > 0) {
                        $weightedAvgHpp = (($previousStock * $previousHpp) + ($newBatchQty * $newBatchHpp)) / $totalStockAfter;
                        $product->update([
                            'cost_price' => round($weightedAvgHpp, 2)
                        ]);
                    } elseif ($newBatchHpp > 0) {
                        $product->update([
                            'cost_price' => $newBatchHpp
                        ]);
                    }

                    // 3. Deduct raw materials based on active recipe
                    $recipe = \App\Models\ProductRecipe::where('master_product_id', $product->id)
                        ->where('tenant_id', $spk->tenant_id)
                        ->where('is_active', true)
                        ->with('items.inventoryItem')
                        ->first();

                    if ($recipe) {
                        foreach ($recipe->items as $recipeItem) {
                            $invItem = $recipeItem->inventoryItem;
                            if ($invItem) {
                                $batchQty = max(1, $recipe->batch_qty);
                                $qtyNeeded = ($recipeItem->quantity / $batchQty) * $item->quantity;
                                
                                $invItem->recordStockMovement(
                                    (int)ceil($qtyNeeded),
                                    'out',
                                    'Konsumsi Bahan Baku SPK #' . $spk->no_spk . ' (Item: ' . $item->nama_produk . ')',
                                    Auth::id()
                                );
                            }
                        }
                    }

                    // 4. If SPK is linked to an order, process stock deduction for the order
                    if ($spk->order_id) {
                        $order = \App\Models\Order::find($spk->order_id);
                        if ($order) {
                            $order->processStockDeduction();
                        }
                    }
                }
            }

            // Transition: From 'Selesai' back to something else (Cancellation/Rollback)
            if ($oldStatus === 'Selesai' && $newStatus !== 'Selesai') {
                $product = null;
                if ($item->master_product_id) {
                    $product = MasterProduct::find($item->master_product_id);
                } elseif (!empty($item->sku)) {
                    $product = MasterProduct::where('tenant_id', $spk->tenant_id)
                        ->where('sku', trim($item->sku))->first();
                } elseif (!empty($item->nama_produk)) {
                    $product = MasterProduct::where('tenant_id', $spk->tenant_id)
                        ->where('name', trim($item->nama_produk))->first();
                }

                if ($product) {
                    // 1. Deduct finished goods stock
                    $product->recordStockMovement(
                        $item->quantity,
                        'out',
                        'Pembatalan SPK Selesai #' . $spk->no_spk . ' (Item: ' . $item->nama_produk . ')',
                        Auth::id()
                    );

                    // 2. Restore raw materials based on active recipe
                    $recipe = \App\Models\ProductRecipe::where('master_product_id', $product->id)
                        ->where('tenant_id', $spk->tenant_id)
                        ->where('is_active', true)
                        ->with('items.inventoryItem')
                        ->first();

                    if ($recipe) {
                        foreach ($recipe->items as $recipeItem) {
                            $invItem = $recipeItem->inventoryItem;
                            if ($invItem) {
                                $batchQty = max(1, $recipe->batch_qty);
                                $qtyNeeded = ($recipeItem->quantity / $batchQty) * $item->quantity;

                                $invItem->recordStockMovement(
                                    (int)ceil($qtyNeeded),
                                    'in',
                                    'Pengembalian Bahan Baku SPK #' . $spk->no_spk . ' (Item: ' . $item->nama_produk . ')',
                                    Auth::id()
                                );
                            }
                        }
                    }
                }
            }
        });

        return redirect()->back()->with('success', 'Status item "' . $item->nama_produk . '" berhasil diubah.');
    }

    private function getGroupedItems(Spk $spk)
    {
        $grouped = [];
        foreach ($spk->items as $item) {
            $modelKey = $item->sku_induk ?: $item->nama_produk;
            if ($item->ukuran) {
                $modelKey = trim(str_ireplace($item->ukuran, '', $modelKey));
            }

            if (!isset($grouped[$modelKey])) {
                $grouped[$modelKey] = [
                    'model'     => $modelKey,
                    'name'      => $item->nama_produk,
                    'sku_induk' => $item->sku_induk ?: '—',
                    'tailors'   => [],
                    'sizes'     => ['S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0, 'XXL' => 0, '3XL' => 0, '4XL' => 0],
                    'total'     => 0,
                ];
            }

            $sz = $this->normalizeSizeKey($item->ukuran);

            if (array_key_exists($sz, $grouped[$modelKey]['sizes'])) {
                $grouped[$modelKey]['sizes'][$sz] += $item->quantity;
            } else {
                $grouped[$modelKey]['sizes'][$sz] = $item->quantity;
            }

            if ($item->penjahit) {
                $grouped[$modelKey]['tailors'][] = $item->penjahit;
            }
            $grouped[$modelKey]['total'] += $item->quantity;
        }

        foreach ($grouped as &$g) {
            $uniqueTailors = array_unique($g['tailors']);
            $g['tailors_list'] = !empty($uniqueTailors) ? implode(', ', $uniqueTailors) : 'Belum Ditunjuk';
        }

        return $grouped;
    }

    public function updateItemDetails(Request $request, SpkItem $item)
    {
        $tenantId = Auth::user()->tenant_id;
        if ($item->spk->tenant_id != $tenantId) {
            abort(403);
        }

        $request->validate([
            'penjahit' => 'nullable|string|max:255',
            'pemotong' => 'nullable|string|max:255',
            'catatan'  => 'nullable|string',
        ]);

        $item->update([
            'penjahit' => $request->penjahit,
            'pemotong' => $request->pemotong,
            'catatan'  => $request->catatan,
        ]);

        return back()->with('success', 'Detail item (Tukang Jahit, Tukang Potong & Catatan) berhasil diperbarui.');
    }

    public function updateTambahan(Request $request, Spk $spk)
    {
        $tenantId = Auth::user()->tenant_id;
        if ($spk->tenant_id != $tenantId) {
            abort(403);
        }

        $request->validate([
            'tambahan' => 'nullable|string',
        ]);

        $spk->update([
            'tambahan' => $request->tambahan,
        ]);

        return back()->with('success', 'Catatan Atribut & Aksesoris Tambahan berhasil diperbarui.');
    }

    public function updateGlobalCosts(Request $request, Spk $spk)
    {
        $tenantId = Auth::user()->tenant_id;
        if ($spk->tenant_id != $tenantId) {
            abort(403);
        }

        $request->validate([
            'global_jasa' => 'nullable|array',
            'global_jasa.*.keterangan' => 'nullable|string',
            'global_jasa.*.nominal' => 'nullable',
            'global_bahan' => 'nullable|array',
            'global_bahan.*.keterangan' => 'nullable|string',
            'global_bahan.*.nominal' => 'nullable',
        ]);

        DB::transaction(function () use ($request, $spk) {
            $totalSpkQty = $spk->items->sum('quantity') ?: 1;

            $globalJasa = $request->input('global_jasa', []);
            $globalBahan = $request->input('global_bahan', []);

            $newGlobalItems = [];
            if (is_array($globalJasa)) {
                foreach ($globalJasa as $gj) {
                    $ket = trim($gj['keterangan'] ?? '');
                    $rawNom = str_replace('.', '', str_replace(',', '.', $gj['nominal'] ?? 0));
                    $nom = floatval($rawNom);
                    if ($ket !== '' && $nom > 0) {
                        $newGlobalItems[] = ['keterangan' => $ket, 'total_nominal' => $nom];
                    }
                }
            }
            if (is_array($globalBahan)) {
                foreach ($globalBahan as $gb) {
                    $ket = trim($gb['keterangan'] ?? '');
                    $rawNom = str_replace('.', '', str_replace(',', '.', $gb['nominal'] ?? 0));
                    $nom = floatval($rawNom);
                    if ($ket !== '' && $nom > 0) {
                        $ketFull = str_starts_with(strtolower($ket), 'bahan:') ? $ket : 'Bahan: ' . $ket;
                        $newGlobalItems[] = ['keterangan' => $ketFull, 'total_nominal' => $nom];
                    }
                }
            }

            foreach ($spk->items as $item) {
                $item->extras()->delete();

                $itemTotalExtras = 0;
                foreach ($newGlobalItems as $gItem) {
                    $allocated = round($gItem['total_nominal'] / $totalSpkQty, 2);
                    SpkItemExtra::create([
                        'spk_item_id' => $item->id,
                        'keterangan'  => $gItem['keterangan'],
                        'nominal'     => $allocated,
                    ]);
                    $itemTotalExtras += $allocated;
                }

                $item->update(['hpp' => $itemTotalExtras]);
            }
        });

        return back()->with('success', 'Setting Biaya SPK (Tambahan Jasa & Bahan) berhasil diperbarui dan HPP per unit dihitung ulang.');
    }

    public function storeProsesSteps(Request $request, Spk $spk)
    {
        abort_unless($spk->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'proses'              => 'nullable|array',
            'proses.*.nama_proses' => 'required|string|max:100',
        ]);

        DB::transaction(function () use ($request, $spk) {
            // Delete proses that are not in the new list
            $existing  = SpkProses::where('spk_id', $spk->id)->get()->keyBy('id');
            $submitted = collect($request->input('proses', []));

            // Delete removed proses (and their progres via cascade)
            $submittedIds = $submitted->pluck('id')->filter()->values();
            SpkProses::where('spk_id', $spk->id)
                ->whereNotIn('id', $submittedIds)
                ->delete();

            $seq = 1;
            foreach ($submitted as $row) {
                $nama = trim($row['nama_proses'] ?? '');
                if (!$nama) continue;

                $prosesId = $row['id'] ?? null;
                if ($prosesId && $existing->has($prosesId)) {
                    $existing[$prosesId]->update(['nama_proses' => $nama, 'urutan' => $seq]);
                    $prosesRecord = $existing[$prosesId];
                } else {
                    $prosesRecord = SpkProses::create([
                        'spk_id'      => $spk->id,
                        'nama_proses' => $nama,
                        'urutan'      => $seq,
                    ]);
                }
                $seq++;

                // Ensure a progres row exists for every item x proses combo
                foreach ($spk->items as $item) {
                    SpkItemProgres::firstOrCreate(
                        ['spk_item_id' => $item->id, 'spk_proses_id' => $prosesRecord->id],
                        ['qty_done' => 0]
                    );
                }
            }
        });

        return back()->with('success', 'Tahapan produksi berhasil diperbarui.');
    }

    public function loadMasterProses(Spk $spk)
    {
        abort_unless($spk->tenant_id === Auth::user()->tenant_id, 403);
        $tenantId = Auth::user()->tenant_id;

        \App\Models\MasterProductionStage::seedDefaultsForTenant($tenantId);
        $masterStages = \App\Models\MasterProductionStage::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        if ($masterStages->isEmpty()) {
            return back()->with('error', 'Tidak ada master tahapan produksi yang aktif.');
        }

        DB::transaction(function () use ($spk, $masterStages) {
            $seq = 1;
            foreach ($masterStages as $stage) {
                $prosesRecord = SpkProses::firstOrCreate(
                    ['spk_id' => $spk->id, 'nama_proses' => $stage->name],
                    ['urutan' => $seq]
                );
                $prosesRecord->update(['urutan' => $seq]);
                $seq++;

                foreach ($spk->items as $item) {
                    SpkItemProgres::firstOrCreate(
                        ['spk_item_id' => $item->id, 'spk_proses_id' => $prosesRecord->id],
                        ['qty_done' => 0]
                    );
                }
            }
        });

        return back()->with('success', 'Master tahapan produksi berhasil diimpor ke SPK ini.');
    }

    public function updateItemProgres(Request $request, SpkItemProgres $progres)
    {
        // Verify ownership via item -> spk -> tenant
        $spk = $progres->item->spk;
        abort_unless($spk->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'qty_done' => 'required|integer|min:0',
        ]);

        $progres->update(['qty_done' => (int) $request->qty_done]);

        return response()->json([
            'success'  => true,
            'qty_done' => $progres->qty_done,
        ]);
    }

    private function getStatusOptions(Spk $spk): array
    {
        if ($spk->proses->isNotEmpty()) {
            $stageNames = $spk->proses->pluck('nama_proses')->toArray();
        } else {
            \App\Models\MasterProductionStage::seedDefaultsForTenant($spk->tenant_id);
            $stageNames = \App\Models\MasterProductionStage::where('tenant_id', $spk->tenant_id)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->pluck('name')
                ->toArray();
        }

        $all = array_merge(['Belum Mulai'], $stageNames, ['Selesai']);
        return array_values(array_unique($all));
    }

    private function ensureDefaultProses(Spk $spk): void
    {
        if ($spk->proses->isEmpty()) {
            $tenantId = $spk->tenant_id;
            \App\Models\MasterProductionStage::seedDefaultsForTenant($tenantId);
            $masterStages = \App\Models\MasterProductionStage::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            if ($masterStages->isNotEmpty()) {
                DB::transaction(function () use ($spk, $masterStages) {
                    $seq = 1;
                    foreach ($masterStages as $stage) {
                        $prosesRecord = SpkProses::firstOrCreate(
                            ['spk_id' => $spk->id, 'nama_proses' => $stage->name],
                            ['urutan' => $seq]
                        );
                        $seq++;

                        foreach ($spk->items as $item) {
                            SpkItemProgres::firstOrCreate(
                                ['spk_item_id' => $item->id, 'spk_proses_id' => $prosesRecord->id],
                                ['qty_done' => 0]
                            );
                        }
                    }
                });
                $spk->load(['proses', 'items.progres']);
            }
        }
    }

    public function storePickup(Request $request, SpkItem $item)
    {
        $spk = $item->spk;
        abort_unless($spk->tenant_id === Auth::user()->tenant_id, 403);

        $sisaQty = $item->sisa_qty;
        if ($sisaQty <= 0) {
            return back()->with('error', 'Semua barang untuk item ini sudah diambil.');
        }

        $request->validate([
            'qty_diambil'    => 'required|integer|min:1|max:' . $sisaQty,
            'nama_pengambil' => 'required|string|max:255',
            'tanggal_ambil'  => 'required|date',
            'catatan'        => 'nullable|string',
        ]);

        $qtyTaken = (int) $request->qty_diambil;

        DB::transaction(function () use ($request, $item, $spk, $qtyTaken) {
            // 1. Create pickup record
            \App\Models\SpkItemPickup::create([
                'spk_item_id'    => $item->id,
                'qty_diambil'    => $qtyTaken,
                'tanggal_ambil'  => $request->tanggal_ambil,
                'nama_pengambil' => $request->nama_pengambil,
                'pemberi_id'     => Auth::id(),
                'catatan'        => $request->catatan,
            ]);

            // 2. Handle Stock Movements for Finished Goods & Raw Materials
            $product = null;
            if ($item->master_product_id) {
                $product = MasterProduct::find($item->master_product_id);
            } elseif (!empty($item->sku)) {
                $product = MasterProduct::where('tenant_id', $spk->tenant_id)->where('sku', trim($item->sku))->first();
            } elseif (!empty($item->nama_produk)) {
                $product = MasterProduct::where('tenant_id', $spk->tenant_id)->where('name', trim($item->nama_produk))->first();
            }

            if ($product) {
                // Record production finish (+in)
                $product->recordStockMovement(
                    $qtyTaken,
                    'in',
                    'Penerimaan SPK Partial #' . $spk->no_spk . ' (Item: ' . $item->nama_produk . ')',
                    Auth::id()
                );

                // Record handover to client (-out)
                $product->recordStockMovement(
                    $qtyTaken,
                    'out',
                    'Penyerahan Barang Partial SPK #' . $spk->no_spk . ' (Pengambil: ' . $request->nama_pengambil . ')',
                    Auth::id()
                );

                // Deduct raw materials based on active recipe
                $recipe = \App\Models\ProductRecipe::where('master_product_id', $product->id)
                    ->where('tenant_id', $spk->tenant_id)
                    ->where('is_active', true)
                    ->with('items.inventoryItem')
                    ->first();

                if ($recipe) {
                    foreach ($recipe->items as $recipeItem) {
                        $invItem = $recipeItem->inventoryItem;
                        if ($invItem) {
                            $batchQty = max(1, $recipe->batch_qty);
                            $qtyNeeded = ($recipeItem->quantity / $batchQty) * $qtyTaken;

                            $invItem->recordStockMovement(
                                (int)ceil($qtyNeeded),
                                'out',
                                'Konsumsi Bahan SPK Partial #' . $spk->no_spk . ' (' . $qtyTaken . ' pcs)',
                                Auth::id()
                            );
                        }
                    }
                }
            }
        });

        return back()->with('success', 'Pengambilan barang partial ' . $qtyTaken . ' pcs oleh "' . $request->nama_pengambil . '" berhasil dicatat.');
    }

    public function destroyPickup(\App\Models\SpkItemPickup $pickup)
    {
        $spk = $pickup->item->spk;
        abort_unless($spk->tenant_id === Auth::user()->tenant_id, 403);

        $pickup->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Catatan pengambilan barang berhasil dihapus.'
            ]);
        }

        return back()->with('success', 'Catatan pengambilan barang berhasil dihapus.');
    }

    /**
     * Tampilan Layar Scanner Penerimaan / Pengambilan Barang SPK
     */
    public function scanPickupPage(Spk $spk)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($spk->tenant_id === $tenantId, 403);

        $spk->load(['items.pickups.pemberi', 'items.masterProduct', 'order', 'penginput']);

        $totalTarget = (int) $spk->items->sum('quantity');
        $totalDiambil = (int) $spk->items->sum('qty_diambil');
        $totalSisa = (int) $spk->items->sum('sisa_qty');
        $percentComplete = $totalTarget > 0 ? min(100, round(($totalDiambil / $totalTarget) * 100)) : 0;

        // Ambil semua riwayat pickup untuk SPK ini
        $recentPickups = \App\Models\SpkItemPickup::whereIn('spk_item_id', $spk->items->pluck('id'))
            ->with(['item', 'pemberi'])
            ->orderByDesc('created_at')
            ->take(50)
            ->get();

        return view('inventory.spks.scan_pickup', compact(
            'spk',
            'totalTarget',
            'totalDiambil',
            'totalSisa',
            'percentComplete',
            'recentPickups'
        ));
    }

    /**
     * Endpoint AJAX: Proses Scan Barcode / QR Code untuk Pengambilan / Penerimaan Barang SPK
     */
    public function processScanPickup(Request $request, Spk $spk)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($spk->tenant_id === $tenantId, 403);

        $request->validate([
            'code'           => 'required|string',
            'qty'            => 'nullable|integer|min:1',
            'nama_pengambil' => 'nullable|string|max:255',
            'catatan'        => 'nullable|string|max:500',
        ]);

        $rawCode = trim($request->input('code'));
        $qtyRequested = max(1, (int) $request->input('qty', 1));
        $namaPengambil = trim($request->input('nama_pengambil')) ?: (Auth::user()->name ?? 'Petugas Gudang');
        $catatan = trim($request->input('catatan')) ?: 'Scan QR / Barcode Penerimaan';

        $spk->load(['items.masterProduct', 'items.pickups']);

        // 1. Parsing & Mencocokkan Barcode / QR Code dengan Item SPK
        $matchedItem = null;
        $cleanCode = strtoupper($rawCode);

        // A. Cek apakah format JSON (misal hasil scan QR data lengkap)
        if (str_starts_with($rawCode, '{') && str_ends_with($rawCode, '}')) {
            $json = json_decode($rawCode, true);
            if (is_array($json)) {
                if (!empty($json['item_id'])) {
                    $matchedItem = $spk->items->firstWhere('id', (int) $json['item_id']);
                }
                if (!$matchedItem && !empty($json['sku'])) {
                    $cleanCode = strtoupper(trim($json['sku']));
                }
            }
        }

        // B. Cek format identifier khusus: SPK-ITEM-{id} atau SPK-{spk_id}-ITEM-{id}
        if (!$matchedItem) {
            if (preg_match('/(?:SPK-\d+-)?ITEM-(\d+)/i', $rawCode, $matches)) {
                $matchedItemId = (int) $matches[1];
                $matchedItem = $spk->items->firstWhere('id', $matchedItemId);
            }
        }

        // C. Cek pencocokan langsung berdasarkan SKU Item SPK
        if (!$matchedItem) {
            $matchedItem = $spk->items->first(function ($it) use ($cleanCode) {
                return !empty($it->sku) && strtoupper(trim($it->sku)) === $cleanCode;
            });
        }

        // D. Cek pencocokan berdasarkan Barcode atau SKU MasterProduct terkait
        if (!$matchedItem) {
            $matchedItem = $spk->items->first(function ($it) use ($cleanCode) {
                if ($it->masterProduct) {
                    if (!empty($it->masterProduct->barcode) && strtoupper(trim($it->masterProduct->barcode)) === $cleanCode) {
                        return true;
                    }
                    if (!empty($it->masterProduct->sku) && strtoupper(trim($it->masterProduct->sku)) === $cleanCode) {
                        return true;
                    }
                }
                return false;
            });
        }

        // E. Cek pencarian MasterProduct global di tenant jika item belum ter-link master_product_id
        if (!$matchedItem) {
            $masterProd = MasterProduct::where('tenant_id', $tenantId)
                ->where(function ($q) use ($cleanCode, $rawCode) {
                    $q->where('sku', $rawCode)
                      ->orWhere('barcode', $rawCode)
                      ->orWhere('sku', $cleanCode)
                      ->orWhere('barcode', $cleanCode);
                })->first();

            if ($masterProd) {
                $matchedItem = $spk->items->first(function ($it) use ($masterProd) {
                    return $it->master_product_id == $masterProd->id ||
                           (!empty($it->sku) && strtoupper(trim($it->sku)) === strtoupper(trim($masterProd->sku)));
                });
            }
        }

        // F. Cek pencocokan berdasarkan Ukuran (jika code berupa nama size, misal: "L", "SIZE L", "UKURAN L", "SZ-L")
        if (!$matchedItem) {
            $szClean = preg_replace('/^(SIZE|UKURAN|SZ|VARIAN)[\s\-_:]*/i', '', $cleanCode);
            $szClean = trim($szClean);

            $matchedItem = $spk->items->first(function ($it) use ($szClean) {
                return !empty($it->ukuran) && strtoupper(trim($it->ukuran)) === $szClean;
            });
        }

        // G. Cek jika SKU item diakhiri dengan ukuran (misal input mengandung "-L" atau "_L")
        if (!$matchedItem) {
            foreach ($spk->items as $it) {
                if (!empty($it->ukuran)) {
                    $u = strtoupper(trim($it->ukuran));
                    if (preg_match('/[\-_]' . preg_quote($u, '/') . '$/i', $cleanCode)) {
                        $matchedItem = $it;
                        break;
                    }
                }
            }
        }

        // 2. VALIDASI: Jika tidak ditemukan item yang cocok dalam SPK ini
        if (!$matchedItem) {
            $availableItems = $spk->items->map(function ($it) {
                return $it->nama_produk . ' (Size: ' . ($it->ukuran ?: 'All Size') . ', Target: ' . $it->quantity . ' pcs)';
            })->implode(', ');

            return response()->json([
                'success'      => false,
                'error_type'   => 'not_in_spk',
                'title'        => 'BARANG TIDAK SESUAI!',
                'message'      => "Barcode/SKU '{$rawCode}' TIDAK DITEMUKAN dalam daftar SPK #{$spk->no_spk}.",
                'detail'       => "SPK ini hanya berisi: {$availableItems}.",
                'scanned_code' => $rawCode,
            ], 422);
        }

        // 3. VALIDASI: Cek Sisa Kuota Item
        $sisaQty = $matchedItem->sisa_qty;
        if ($sisaQty <= 0) {
            return response()->json([
                'success'      => false,
                'error_type'   => 'quota_exceeded',
                'title'        => 'KUOTA SUDAH LENGKAP!',
                'message'      => "Item '{$matchedItem->nama_produk}' Ukuran [{$matchedItem->ukuran}] sudah diterima seluruhnya ({$matchedItem->quantity}/{$matchedItem->quantity} pcs).",
                'detail'       => "Tidak dapat menerima barang melebihi kuota target SPK.",
                'item_id'      => $matchedItem->id,
                'scanned_code' => $rawCode,
            ], 422);
        }

        // 4. Eksekusi Pengambilan / Penerimaan Barang
        $qtyToTake = min($qtyRequested, $sisaQty);

        $pickup = DB::transaction(function () use ($matchedItem, $spk, $qtyToTake, $namaPengambil, $catatan) {
            // A. Buat record pickup
            $p = \App\Models\SpkItemPickup::create([
                'spk_item_id'    => $matchedItem->id,
                'qty_diambil'    => $qtyToTake,
                'tanggal_ambil'  => now(),
                'nama_pengambil' => $namaPengambil,
                'pemberi_id'     => Auth::id(),
                'catatan'        => $catatan,
            ]);

            // B. Mutasi Stok Master Produk (+in penerimaan produksi, -out penyerahan pengambil)
            $product = null;
            if ($matchedItem->master_product_id) {
                $product = MasterProduct::find($matchedItem->master_product_id);
            } elseif (!empty($matchedItem->sku)) {
                $product = MasterProduct::where('tenant_id', $spk->tenant_id)->where('sku', trim($matchedItem->sku))->first();
            } elseif (!empty($matchedItem->nama_produk)) {
                $product = MasterProduct::where('tenant_id', $spk->tenant_id)->where('name', trim($matchedItem->nama_produk))->first();
            }

            if ($product) {
                $product->recordStockMovement(
                    $qtyToTake,
                    'in',
                    'Scan Terima SPK #' . $spk->no_spk . ' (' . $matchedItem->nama_produk . ' - ' . $matchedItem->ukuran . ')',
                    Auth::id()
                );

                $product->recordStockMovement(
                    $qtyToTake,
                    'out',
                    'Penyerahan Scan SPK #' . $spk->no_spk . ' (Penerima: ' . $namaPengambil . ')',
                    Auth::id()
                );

                // Deduct raw materials based on active recipe
                $recipe = \App\Models\ProductRecipe::where('master_product_id', $product->id)
                    ->where('tenant_id', $spk->tenant_id)
                    ->where('is_active', true)
                    ->with('items.inventoryItem')
                    ->first();

                if ($recipe) {
                    foreach ($recipe->items as $recipeItem) {
                        $invItem = $recipeItem->inventoryItem;
                        if ($invItem) {
                            $batchQty = max(1, $recipe->batch_qty);
                            $qtyNeeded = ($recipeItem->quantity / $batchQty) * $qtyToTake;

                            $invItem->recordStockMovement(
                                (int) ceil($qtyNeeded),
                                'out',
                                'Konsumsi Bahan Scan SPK #' . $spk->no_spk . ' (' . $qtyToTake . ' pcs)',
                                Auth::id()
                            );
                        }
                    }
                }
            }

            return $p;
        });

        // 5. Hitung ulang total status SPK terkini
        $spk->load(['items.pickups']);
        $matchedItem->refresh();

        $itemTotalDiambil = $matchedItem->qty_diambil;
        $itemSisa = $matchedItem->sisa_qty;
        $isItemComplete = ($itemSisa == 0);

        $spkTotalTarget = (int) $spk->items->sum('quantity');
        $spkTotalDiambil = (int) $spk->items->sum('qty_diambil');
        $spkTotalSisa = (int) $spk->items->sum('sisa_qty');
        $allComplete = $spk->items->every(fn($it) => $it->sisa_qty == 0);

        if ($allComplete && $spk->tahap_saat_ini !== 'Selesai (Finished Good)') {
            $spk->update(['tahap_saat_ini' => 'Selesai (Finished Good)']);
        }

        return response()->json([
            'success'            => true,
            'message'            => "Berhasil menerima {$qtyToTake} pcs {$matchedItem->nama_produk} (Size: {$matchedItem->ukuran}).",
            'item'               => [
                'id'           => $matchedItem->id,
                'nama_produk'  => $matchedItem->nama_produk,
                'sku'          => $matchedItem->sku,
                'ukuran'       => $matchedItem->ukuran,
                'quantity'     => $matchedItem->quantity,
                'qty_diambil'  => $itemTotalDiambil,
                'sisa_qty'     => $itemSisa,
                'is_completed' => $isItemComplete,
            ],
            'spk_total_target'   => $spkTotalTarget,
            'spk_total_diambil'  => $spkTotalDiambil,
            'spk_total_sisa'     => $spkTotalSisa,
            'percent_complete'   => $spkTotalTarget > 0 ? min(100, round(($spkTotalDiambil / $spkTotalTarget) * 100)) : 0,
            'all_completed'      => $allComplete,
            'pickup'             => [
                'id'             => $pickup->id,
                'qty'            => $pickup->qty_diambil,
                'tanggal'        => $pickup->tanggal_ambil->format('d/m/Y H:i'),
                'nama_pengambil' => $pickup->nama_pengambil,
            ]
        ]);
    }

    /**
     * Cetak Label Stiker Barcode / QR Kemasan untuk Setiap Item SPK
     */
    public function printItemLabels(Spk $spk)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($spk->tenant_id === $tenantId, 403);

        $spk->load(['items.masterProduct', 'order']);

        return view('inventory.spks.print_labels', compact('spk'));
    }

    public function toggleUrgent(Spk $spk)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($spk->tenant_id === $tenantId, 403);

        $spk->is_urgent = !$spk->is_urgent;
        $spk->save();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'is_urgent' => $spk->is_urgent,
                'message' => $spk->is_urgent ? 'SPK ditandai sebagai URGENT!' : 'Status URGENT dibatalkan.'
            ]);
        }

        return redirect()->back()->with('success', $spk->is_urgent ? 'SPK ditandai sebagai URGENT!' : 'Status URGENT dibatalkan.');
    }

    /**
     * Auto-save new materials into InventoryItems (Master Barang)
     * and automatically save/update the ProductRecipe (Formula Produk) for future orders.
     */
    private function processAutoSaveBahanAndRecipe(int $tenantId, ?string $namaProduk, ?string $skuProduk, int $qtyProduksi, array $bahanList, ?SpkItem $spkItem = null): float
    {
        if (empty($bahanList)) {
            return 0.0;
        }

        // 1. Find matching MasterProduct
        $masterProd = null;
        if (!empty($skuProduk)) {
            $masterProd = MasterProduct::where('tenant_id', $tenantId)
                ->where(function ($q) use ($skuProduk) {
                    $q->where('sku', $skuProduk)->orWhere('sku_induk', $skuProduk);
                })->first();
        }
        if (!$masterProd && !empty($namaProduk)) {
            $masterProd = MasterProduct::where('tenant_id', $tenantId)
                ->where('name', $namaProduk)
                ->first();
        }

        if ($spkItem && $masterProd) {
            $spkItem->update(['master_product_id' => $masterProd->id]);
        }

        $totalHpp = 0.0;
        $recipeItemsToSave = [];

        foreach ($bahanList as $b) {
            $rawNama = trim($b['nama_bahan'] ?? ($b['keterangan'] ?? ''));
            if (empty($rawNama)) continue;

            $qtyBahan   = floatval($b['qty_bahan'] ?? ($b['qty'] ?? 1));
            $hargaBahan = floatval($b['harga'] ?? ($b['nominal'] ?? 0));
            $subtotal   = floatval($b['subtotal'] ?? ($qtyBahan * $hargaBahan));

            // Extract unit from parenthetical e.g. "Kain Cotton (Meter)" -> name: "Kain Cotton", unit: "Meter"
            $cleanName = $rawNama;
            $extractedUnit = 'pcs';
            if (preg_match('/^(.*?)\s*\((.*?)\)$/', $rawNama, $m)) {
                $cleanName = trim($m[1]);
                $extractedUnit = trim($m[2]);
            }

            // A. Auto-Save to Master Barang (InventoryItem) if not exists
            $invItem = InventoryItem::where('tenant_id', $tenantId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($cleanName))])
                ->first();

            if (!$invItem) {
                $invItem = InventoryItem::where('tenant_id', $tenantId)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($rawNama))])
                    ->first();
            }

            if (!$invItem) {
                $skuCode = 'INV-' . strtoupper(Str::random(6));
                $invItem = InventoryItem::create([
                    'tenant_id'  => $tenantId,
                    'sku'        => $skuCode,
                    'name'       => $cleanName,
                    'type'       => 'raw', // Bahan Baku
                    'unit'       => $extractedUnit,
                    'stock'      => 0,
                    'min_stock'  => 0,
                    'cost_price' => $hargaBahan,
                    'is_active'  => true,
                ]);
            } else {
                if ($invItem->cost_price <= 0 && $hargaBahan > 0) {
                    $invItem->update(['cost_price' => $hargaBahan]);
                }
            }

            if ($spkItem) {
                $cleanQtyStr = ($qtyBahan == (int)$qtyBahan) ? (int)$qtyBahan : (float)$qtyBahan;
                $hargaStr = $hargaBahan > 0 ? " @ Rp " . number_format($hargaBahan, 0, ',', '.') : "";
                SpkItemExtra::create([
                    'spk_item_id' => $spkItem->id,
                    'keterangan'  => "Bahan: {$rawNama} (Qty: {$cleanQtyStr}{$hargaStr})",
                    'nominal'     => $subtotal,
                ]);
            }

            $totalHpp += $subtotal;

            if ($invItem) {
                $recipeItemsToSave[] = [
                    'inventory_item_id' => $invItem->id,
                    'qty_bahan'         => $qtyBahan,
                    'harga'             => $hargaBahan,
                ];
            }
        }

        // B. Auto-Save/Update Formula/Resep Produk (ProductRecipe)
        if ($masterProd && !empty($recipeItemsToSave)) {
            $recipe = ProductRecipe::where('tenant_id', $tenantId)
                ->where('master_product_id', $masterProd->id)
                ->where('is_active', true)
                ->first();

            if (!$recipe) {
                $recipe = ProductRecipe::create([
                    'tenant_id'         => $tenantId,
                    'master_product_id' => $masterProd->id,
                    'name'              => 'Resep Utama - ' . $masterProd->name,
                    'batch_qty'         => 1,
                    'is_active'         => true,
                ]);
            }

            // Sync recipe items: unit qty per 1 pcs produced = qty_bahan / qty_produksi
            $recipe->items()->delete();
            foreach ($recipeItemsToSave as $rData) {
                $unitQtyNeeded = round($rData['qty_bahan'] / max(1, $qtyProduksi), 4);
                $recipe->items()->create([
                    'inventory_item_id' => $rData['inventory_item_id'],
                    'quantity'          => max(0.0001, $unitQtyNeeded),
                ]);
            }
        }

        return $totalHpp;
    }

    /**
     * Synchronize SPK material usage with WarehouseMutation (Pengeluaran Barang)
     * and deduct/adjust inventory stock automatically.
     */
    private function syncSpkWarehouseMutation(Spk $spk, array $cleanBahanList): void
    {
        $tenantId = $spk->tenant_id;
        $userId   = Auth::id() ?: ($spk->penginput_id ?: 1);

        // 1. Get Department ID for "Produksi"
        $toDeptId = null;
        $dept = Department::where('tenant_id', $tenantId)
            ->where('name', 'Produksi')
            ->first();
        if (!$dept) {
            $dept = Department::create([
                'tenant_id' => $tenantId,
                'name'      => 'Produksi',
                'code'      => 'PRODUKSI',
                'is_active' => true,
            ]);
        }
        $toDeptId = $dept->id;

        // 2. Find existing WarehouseMutation for this SPK
        $existingMutation = WarehouseMutation::where('tenant_id', $tenantId)
            ->where('spk_id', $spk->id)
            ->with('items.inventoryItem')
            ->first();

        // If existing mutation found, restore old stocks before applying new items
        if ($existingMutation) {
            foreach ($existingMutation->items as $oldItem) {
                if ($oldItem->inventoryItem && $oldItem->quantity > 0) {
                    $invItem = $oldItem->inventoryItem;
                    $invItem->increment('stock', $oldItem->quantity);
                    $newStock = $invItem->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'             => $tenantId,
                        'inventory_item_id'     => $invItem->id,
                        'warehouse_mutation_id' => $existingMutation->id,
                        'user_id'               => $userId,
                        'type'                  => 'in',
                        'quantity'              => $oldItem->quantity,
                        'reference'             => 'Revisi/Batal Pengeluaran Bahan Baku SPK #' . $spk->no_spk,
                        'balance_after'         => $newStock,
                    ]);
                }
            }
            $existingMutation->items()->delete();
        }

        // Filter valid bahan entries with qty > 0
        $validBahanList = [];
        foreach ($cleanBahanList as $b) {
            $rawNama = trim($b['nama_bahan'] ?? ($b['keterangan'] ?? ''));
            if (empty($rawNama)) continue;

            $qtyBahan = floatval($b['qty_bahan'] ?? ($b['qty'] ?? 0));
            if ($qtyBahan <= 0) continue;

            $validBahanList[] = [
                'nama_bahan' => $rawNama,
                'qty_bahan'  => $qtyBahan,
                'harga'      => floatval($b['harga'] ?? ($b['nominal'] ?? 0)),
                'subtotal'   => floatval($b['subtotal'] ?? 0),
            ];
        }

        // If no materials, delete existing mutation if it exists and return
        if (empty($validBahanList)) {
            if ($existingMutation) {
                $existingMutation->delete();
            }
            return;
        }

        // 3. Create or update WarehouseMutation
        if (!$existingMutation) {
            $mutationNumber = WarehouseMutation::generateMutationNumber('out');
            $mutation = WarehouseMutation::create([
                'tenant_id'       => $tenantId,
                'spk_id'          => $spk->id,
                'mutation_number' => $mutationNumber,
                'type'            => 'out',
                'to_department_id'=> $toDeptId,
                'mutation_date'   => $spk->tanggal ?: date('Y-m-d'),
                'status'          => 'approved',
                'notes'           => 'Pengeluaran Bahan Baku SPK #' . $spk->no_spk,
                'created_by'      => $userId,
            ]);
        } else {
            $mutation = $existingMutation;
            $mutation->update([
                'to_department_id' => $toDeptId,
                'mutation_date'    => $spk->tanggal ?: date('Y-m-d'),
                'notes'            => 'Pengeluaran Bahan Baku SPK #' . $spk->no_spk,
            ]);
        }

        // 4. Create mutation items and deduct stock
        foreach ($validBahanList as $mat) {
            $cleanName = $mat['nama_bahan'];
            $extractedUnit = 'pcs';
            if (preg_match('/^(.*?)\s*\((.*?)\)$/', $mat['nama_bahan'], $m)) {
                $cleanName = trim($m[1]);
                $extractedUnit = trim($m[2]);
            }

            // Find matching InventoryItem
            $invItem = InventoryItem::where('tenant_id', $tenantId)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($cleanName))])
                ->first();

            if (!$invItem) {
                $invItem = InventoryItem::where('tenant_id', $tenantId)
                    ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($mat['nama_bahan']))])
                    ->first();
            }

            if (!$invItem) {
                // If not found, create new raw inventory item
                $skuCode = 'INV-' . strtoupper(Str::random(6));
                $invItem = InventoryItem::create([
                    'tenant_id'  => $tenantId,
                    'sku'        => $skuCode,
                    'name'       => $cleanName,
                    'type'       => 'raw',
                    'unit'       => $extractedUnit,
                    'stock'      => 0,
                    'min_stock'  => 0,
                    'cost_price' => $mat['harga'],
                    'is_active'  => true,
                ]);
            }

            $qtyDeduct = max(1, (int) round($mat['qty_bahan']));

            $mutation->items()->create([
                'inventory_item_id' => $invItem->id,
                'quantity'          => $qtyDeduct,
                'unit_price'        => $mat['harga'] ?: ($invItem->cost_price ?: 0),
                'notes'             => 'Dialokasikan untuk SPK #' . $spk->no_spk,
            ]);

            $invItem->decrement('stock', $qtyDeduct);
            $newStock = $invItem->fresh()->stock;

            StockMovement::create([
                'tenant_id'             => $tenantId,
                'inventory_item_id'     => $invItem->id,
                'warehouse_mutation_id' => $mutation->id,
                'user_id'               => $userId,
                'type'                  => 'out',
                'quantity'              => -$qtyDeduct,
                'reference'             => 'Pengeluaran Bahan Baku SPK #' . $spk->no_spk,
                'balance_after'         => $newStock,
            ]);
        }
    }

    /**
     * Auto-save vendor/mitra operasional baru ke Master Data Tailors jika belum terdaftar.
     */
    private function processAutoSaveVendor(int $tenantId, string $name, string $category): void
    {
        $cleanName = trim($name);
        if ($cleanName === '' || in_array(strtoupper($cleanName), ['—', '-', 'NO', 'NONE', 'N/A'], true)) {
            return;
        }

        $exists = \App\Models\Tailor::where('tenant_id', $tenantId)
            ->whereRaw('LOWER(name) = ?', [strtolower($cleanName)])
            ->exists();

        if (!$exists) {
            \App\Models\Tailor::create([
                'tenant_id' => $tenantId,
                'name'      => $cleanName,
                'category'  => $category,
                'is_active' => true,
            ]);
        }
    }

    /**
     * Catat Pembayaran Ongkos Jasa SPK ke Pengeluaran Kas (Expense) Terpisah per Vendor.
     */
    public function payLabor(Request $request, Spk $spk)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($spk->tenant_id === $tenantId, 403);

        $request->validate([
            'payment_source' => 'required|string',
            'expense_date'   => 'required|date',
            'payments'       => 'required|array|min:1',
            'payments.*.title'  => 'required|string',
            'payments.*.amount' => 'required|numeric|min:1',
        ]);

        $spkCode = $spk->no_produksi ?: $spk->no_spk;
        $paymentSource = $request->payment_source;
        $expenseDate = $request->expense_date;

        $createdCount = 0;
        $grandTotalPaid = 0;

        foreach ($request->payments as $pData) {
            if (!isset($pData['checked_val']) && empty($pData['checked'])) {
                continue;
            }

            $title = trim($pData['title']);
            $amount = floatval($pData['amount']);
            if ($amount <= 0) continue;

            $expense = \App\Models\Expense::create([
                'tenant_id'      => $tenantId,
                'employee_id'    => Auth::user()->employee_id ?? null,
                'title'          => "Ongkos Jasa SPK #{$spkCode}: {$title}",
                'category'       => 'salary',
                'payment_source' => $paymentSource,
                'amount'         => $amount,
                'expense_date'   => $expenseDate,
                'description'    => "Pembayaran Ongkos Jasa Vendor/Pekerja [{$title}] untuk SPK #{$spkCode}",
            ]);

            if (is_numeric($paymentSource)) {
                $bank = \App\Models\BankAccount::where('tenant_id', $tenantId)->find($paymentSource);
                if ($bank) {
                    $bank->decrement('current_balance', $amount);
                }
            }

            $createdCount++;
            $grandTotalPaid += $amount;
        }

        if ($createdCount === 0) {
            return redirect()->back()->with('error', '⚠️ Tidak ada item pembayaran vendor yang dicentang.');
        }

        return redirect()->back()->with('success', "💳 Berhasil mencatat {$createdCount} transaksi pembayaran ongkos jasa vendor (Total: Rp " . number_format($grandTotalPaid, 0, ',', '.') . ") ke Pengeluaran Kas!");
    }

    /**
     * Menu & Dashboard Rekap Pembayaran Produksi SPK.
     */
    public function paymentsIndex(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = Spk::with(['items.extras', 'payments.bankAccount', 'penginput'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('tanggal')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('no_spk', 'like', "%{$search}%")
                  ->orWhere('no_produksi', 'like', "%{$search}%")
                  ->orWhere('pemesan', 'like', "%{$search}%")
                  ->orWhere('instansi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('tanggal', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('tanggal', '<=', $request->date_to);
        }

        $allSpks = $query->get();

        // Calculate KPI summaries across all matching SPKs
        $totalBiayaProduksiAll    = $allSpks->sum('total_biaya_produksi');
        $totalSudahDibayarAll     = $allSpks->sum('total_paid_production');
        $totalSisaBelumDibayarAll = $allSpks->sum('remaining_production_cost');
        $totalSpkCount            = $allSpks->count();

        $countLunas      = $allSpks->where('production_payment_status', 'paid')->count();
        $countDicicil    = $allSpks->where('production_payment_status', 'partial')->count();
        $countBelumBayar = $allSpks->where('production_payment_status', 'unpaid')->count();

        // Filter by payment status if requested
        if ($request->filled('status') && in_array($request->status, ['paid', 'partial', 'unpaid'])) {
            $statusFilter = $request->status;
            $allSpks = $allSpks->filter(function ($s) use ($statusFilter) {
                return $s->production_payment_status === $statusFilter;
            })->values();
        }

        // Paginate collection
        $page = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 15;
        $currentPageItems = $allSpks->slice(($page - 1) * $perPage, $perPage)->values();
        $spks = new LengthAwarePaginator($currentPageItems, $allSpks->count(), $perPage, $page, [
            'path'  => $request->url(),
            'query' => $request->query(),
        ]);

        $bankAccounts = BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $tailors = \App\Models\Tailor::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('inventory.spks.payments', compact(
            'spks',
            'totalBiayaProduksiAll',
            'totalSudahDibayarAll',
            'totalSisaBelumDibayarAll',
            'totalSpkCount',
            'countLunas',
            'countDicicil',
            'countBelumBayar',
            'bankAccounts',
            'tailors'
        ));
    }

    /**
     * Catat Cicilan Pembayaran Produksi SPK.
     */
    public function storePayment(Request $request, Spk $spk)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($spk->tenant_id === $tenantId, 403);

        $remaining = $spk->remaining_production_cost;
        if ($remaining <= 0) {
            return back()->with('error', 'Biaya produksi SPK #' . ($spk->no_produksi ?: $spk->no_spk) . ' sudah lunas!');
        }

        $request->validate([
            'amount'          => 'required|numeric|min:1|max:' . $remaining,
            'payment_date'    => 'required|date',
            'payment_source'  => 'required|string',
            'recipient_name'  => 'nullable|string|max:150',
            'payment_type'    => 'nullable|string|max:50',
            'notes'           => 'nullable|string|max:500',
        ], [
            'amount.required'         => 'Nominal cicilan wajib diisi.',
            'amount.min'              => 'Nominal cicilan minimal Rp 1.',
            'amount.max'              => 'Nominal cicilan tidak boleh melebihi sisa tagihan (Rp ' . number_format($remaining, 0, ',', '.') . ').',
            'payment_date.required'   => 'Tanggal pembayaran wajib diisi.',
            'payment_source.required' => 'Sumber kas/bank wajib dipilih.',
        ]);

        $amount = (float) $request->amount;
        $paymentSource = $request->payment_source;
        $recipient = trim($request->recipient_name ?: 'Tim Produksi / Vendor');
        $spkCode = $spk->no_produksi ?: $spk->no_spk;

        DB::transaction(function () use ($spk, $tenantId, $amount, $paymentSource, $recipient, $spkCode, $request) {
            $bankAccountId = null;

            // 1. Kurangi saldo Bank jika sumber adalah ID BankAccount
            if (is_numeric($paymentSource)) {
                $bank = BankAccount::where('tenant_id', $tenantId)->find($paymentSource);
                if ($bank) {
                    $bankAccountId = $bank->id;
                    $bank->decrement('current_balance', $amount);
                }
            }

            // 2. Catat Jurnal Pengeluaran (Expense) di Keuangan
            $expense = Expense::create([
                'tenant_id'      => $tenantId,
                'employee_id'    => Auth::user()->employee_id ?? null,
                'title'          => "Cicilan Biaya Produksi SPK #{$spkCode} ({$recipient})",
                'category'       => 'salary',
                'payment_source' => $paymentSource,
                'amount'         => $amount,
                'expense_date'   => $request->payment_date,
                'description'    => "Pembayaran cicilan biaya produksi SPK #{$spkCode} kepada {$recipient}" . ($request->notes ? " - {$request->notes}" : ""),
            ]);

            // 3. Simpan record SpkPayment
            $paymentNumber = SpkPayment::generatePaymentNumber($tenantId);
            $spk->payments()->create([
                'tenant_id'       => $tenantId,
                'payment_number'  => $paymentNumber,
                'payment_date'    => $request->payment_date,
                'amount'          => $amount,
                'payment_source'  => $paymentSource,
                'bank_account_id' => $bankAccountId,
                'recipient_name'  => $recipient,
                'payment_type'    => $request->payment_type ?: 'biaya_produksi',
                'notes'           => $request->notes,
                'created_by'      => Auth::id(),
                'expense_id'      => $expense->id,
            ]);
        });

        $spkFresh = $spk->fresh();
        $msg = $spkFresh->remaining_production_cost <= 0
            ? "✅ Pembayaran cicilan sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil dicatat dan biaya produksi SPK #{$spkCode} telah LUNAS!"
            : "✅ Pembayaran cicilan sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil dicatat untuk SPK #{$spkCode}!";

        return back()->with('success', $msg);
    }

    /**
     * Hapus / Rollback Cicilan Pembayaran Produksi SPK.
     */
    public function destroyPayment(Spk $spk, SpkPayment $payment)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($spk->tenant_id === $tenantId, 403);
        abort_unless($payment->spk_id === $spk->id, 404);
        abort_unless(Auth::user()->isAdmin() || Auth::user()->isOwner() || in_array(Auth::user()->role, ['admin', 'owner']), 403);

        $amount = (float) $payment->amount;

        DB::transaction(function () use ($payment, $tenantId, $amount) {
            // Revert bank balance if paid via bank
            if ($payment->bank_account_id) {
                $bank = BankAccount::where('tenant_id', $tenantId)->find($payment->bank_account_id);
                if ($bank) {
                    $bank->increment('current_balance', $amount);
                }
            }

            // Delete associated expense
            if ($payment->expense_id) {
                Expense::where('tenant_id', $tenantId)->where('id', $payment->expense_id)->delete();
            }

            $payment->delete();
        });

        return back()->with('success', "✅ Pembayaran cicilan ({$payment->payment_number}) sebesar Rp " . number_format($amount, 0, ',', '.') . " berhasil dihapus dan saldo telah dikembalikan.");
    }

    /**
     * Tampilan Khusus Mobile HP Scan & Tracking Tahap Produksi SPK dengan Proteksi PIN
     */
    public function mobileScan(Request $request, Spk $spk)
    {
        $spk->load(['penginput', 'items.extras', 'items.progres', 'proses']);
        $this->ensureDefaultProses($spk);

        $statusOptions = $this->getStatusOptions($spk);
        $tailors = \App\Models\Tailor::where('tenant_id', $spk->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $variantRows = [];
        $fabricName = $spk->items->first()?->sku_kain ?: 'BAHAN UMUM';

        foreach ($spk->items as $item) {
            $szKey = strtoupper($this->normalizeSizeKey($item->ukuran));
            $skuInduk = $item->sku_induk;
            if (!$skuInduk && !empty($item->sku)) {
                $skuInduk = preg_replace('/[_\-\s]+(S|M|L|XL|XXL|3XL|4XL|XXXL|XXXXL|2XL|ALLSIZE|ALL SIZE)$/i', '', trim($item->sku));
            }
            $modelName = $skuInduk ?: ($item->sku ?: ($item->nama_produk ?: 'PRODUK VARIAN'));

            if (!isset($variantRows[$modelName])) {
                $variantRows[$modelName] = [
                    'name'  => $modelName,
                    'sku'   => $modelName,
                    'sizes' => [],
                ];
            }
            $variantRows[$modelName]['sizes'][] = [
                'id'       => $item->id,
                'size'     => $szKey,
                'quantity' => $item->quantity,
                'item'     => $item,
            ];
        }

        $correctPin = session('spk_tracking_pin_' . $spk->tenant_id, '1234');

        $savedRincianAntrian = '';
        $savedCatatanAntrian = '';
        $savedPembuatSample  = '';
        $savedStatusAcc      = '';
        $savedCatatanRevisi  = '';
        $savedVendorPrint    = '';
        $savedCatatanPotong  = '';
        $savedCatatanJahit   = '';
        $savedFotoSample     = '';
        $savedFotoPrint      = '';
        $savedFotoPotong     = '';
        $savedRdyKain        = '';

        if (!empty($spk->tambahan)) {
            $parts = explode('||', $spk->tambahan);
            foreach ($parts as $part) {
                $subParts = explode('|', $part);
                foreach ($subParts as $sub) {
                    $sub = trim($sub);
                    if (str_starts_with($sub, 'Rincian Antrian:')) {
                        $savedRincianAntrian = trim(substr($sub, strlen('Rincian Antrian:')));
                    } elseif (str_starts_with($sub, 'Antrian:')) {
                        $savedCatatanAntrian = trim(substr($sub, strlen('Antrian:')));
                    } elseif (str_starts_with($sub, 'Pembuat Sample:')) {
                        $savedPembuatSample = trim(substr($sub, strlen('Pembuat Sample:')));
                    } elseif (str_starts_with($sub, 'Status ACC:')) {
                        $savedStatusAcc = trim(substr($sub, strlen('Status ACC:')));
                    } elseif (str_starts_with($sub, 'Revisi:')) {
                        $savedCatatanRevisi = trim(substr($sub, strlen('Revisi:')));
                    } elseif (str_starts_with($sub, 'Vendor Print:')) {
                        $savedVendorPrint = trim(substr($sub, strlen('Vendor Print:')));
                    } elseif (str_starts_with($sub, 'Potong:')) {
                        $savedCatatanPotong = trim(substr($sub, strlen('Potong:')));
                    } elseif (str_starts_with($sub, 'Jahit:')) {
                        $savedCatatanJahit = trim(substr($sub, strlen('Jahit:')));
                    } elseif (str_starts_with($sub, 'Foto Sample:')) {
                        $savedFotoSample = trim(substr($sub, strlen('Foto Sample:')));
                    } elseif (str_starts_with($sub, 'Foto Print:')) {
                        $savedFotoPrint = trim(substr($sub, strlen('Foto Print:')));
                    } elseif (str_starts_with($sub, 'Foto Potong:')) {
                        $savedFotoPotong = trim(substr($sub, strlen('Foto Potong:')));
                    } elseif (str_starts_with($sub, 'Ready:')) {
                        $savedRdyKain = trim(substr($sub, strlen('Ready:')));
                    }
                }
            }
        }

        $pembuatSampleList = $tailors->filter(fn($v) => in_array($v->category, ['Pembuat Sample', 'Vendor Print'], true))->pluck('name')->values();
        $vendorPrintList   = $tailors->where('category', 'Vendor Print')->pluck('name')->values();
        $pemotongList      = $tailors->where('category', 'Pemotong')->pluck('name')->values();
        $penjahitList      = $tailors->filter(fn($v) => in_array($v->category, ['Penjahit', null, ''], true))->pluck('name')->values();
        $vendorKancingList = $tailors->where('category', 'Vendor Kancing')->pluck('name')->values();

        $totalEstKain = (float) $spk->items->sum('est_kain');
        if ($totalEstKain <= 0) {
            $totalEstKain = (float) ($spk->items->first()?->est_kain ?: 0);
        }

        return view('inventory.spks.mobile_tracking', compact(
            'spk',
            'statusOptions',
            'tailors',
            'pembuatSampleList',
            'vendorPrintList',
            'pemotongList',
            'penjahitList',
            'vendorKancingList',
            'variantRows',
            'fabricName',
            'correctPin',
            'savedRincianAntrian',
            'savedCatatanAntrian',
            'savedPembuatSample',
            'savedStatusAcc',
            'savedCatatanRevisi',
            'savedVendorPrint',
            'savedCatatanPotong',
            'savedCatatanJahit',
            'savedFotoSample',
            'savedFotoPrint',
            'savedFotoPotong',
            'savedRdyKain',
            'totalEstKain'
        ));
    }

    /**
     * Verifikasi PIN secara AJAX untuk Mobile Tracking SPK
     */
    public function verifyMobilePin(Request $request, Spk $spk)
    {
        $correctPin = session('spk_tracking_pin_' . $spk->tenant_id, '1234');
        $inputPin = trim((string) $request->input('pin'));

        if ($inputPin === $correctPin) {
            session(['spk_mobile_unlocked_' . $spk->id => true]);
            return response()->json(['success' => true, 'message' => 'PIN Benar!']);
        }

        return response()->json(['success' => false, 'message' => 'Kode PIN salah. Silakan coba lagi.'], 422);
    }

    /**
     * Simpan pembaruan tracking tahap produksi via Mobile HP (Semua Tahapan)
     */
    public function updateMobileTracking(Request $request, Spk $spk)
    {
        $spk->load(['items', 'proses']);

        // 1. Update Status SPK & Tahap Saat Ini
        if ($request->has('spk_status')) {
            $newStatus = $request->input('spk_status');
            $spk->tahap_saat_ini = $newStatus;
        }

        // 2. Handle Checkbox "Serahkan ke QC" (Khusus Tahap Jahit)
        if ($request->input('serahkan_ke_qc') == '1') {
            $spk->tahap_saat_ini = 'Quality Control';
        }

        // 3. Handle File Uploads (Foto Bukti Kamera per Tahap)
        $photoUrl = null;
        $photoTag = null;
        if ($request->hasFile('sample_photo')) {
            $photoUrl = $this->resizeAndStoreUploadedImage($request->file('sample_photo'), 'spk_tracking', 1200, 80);
            $photoTag = 'Foto Sample';
        } elseif ($request->hasFile('print_photo')) {
            $photoUrl = $this->resizeAndStoreUploadedImage($request->file('print_photo'), 'spk_tracking', 1200, 80);
            $photoTag = 'Foto Print';
        } elseif ($request->hasFile('potong_photo')) {
            $photoUrl = $this->resizeAndStoreUploadedImage($request->file('potong_photo'), 'spk_tracking', 1200, 80);
            $photoTag = 'Foto Potong';
        }

        if ($photoUrl) {
            $spk->image_url = $photoUrl;
        }

        // 4. Update SKU Kain Konversi (Jika Diisi di Tahap Sampling / Print)
        if ($request->filled('sku_kain_suffix')) {
            $spk->sku_kain = $request->input('sku_kain_suffix');
        } elseif ($request->filled('sku_kain_print_suffix')) {
            $spk->sku_kain = $request->input('sku_kain_print_suffix');
        }

        // 5. Update Items (Pemotong, Penjahit per Model/Item, Vendor LKPK, Est/Pakai Meter Kain)
        $pemotong = $request->input('pemotong');
        $penjahitReq = $request->input('penjahit');
        $penjahitGlobal = is_array($penjahitReq) ? (reset($penjahitReq) ?: null) : $penjahitReq;
        $penjahitPerModel = (array) $request->input('penjahit_per_model', []);
        $vendorLkpkReq = $request->input('vendor_lkpk');
        $vendorLkpk = is_array($vendorLkpkReq) ? (reset($vendorLkpkReq) ?: null) : $vendorLkpkReq;
        $estKainPotong = $request->input('est_kain_potong') ?: $request->input('est_hpp_print');
        $pkiKainPotong = $request->input('pki_kain_potong') ?: ($request->input('terpakai_kain') ?: $request->input('kain_terpakai_print'));
        $sisaKainPotong = $request->input('sisa_kain_potong');

        foreach ($spk->items as $item) {
            $updatePayload = [];
            $skuInduk = $item->sku_induk;
            if (!$skuInduk && !empty($item->sku)) {
                $skuInduk = preg_replace('/[_\-\s]+(S|M|L|XL|XXL|3XL|4XL|XXXL|XXXXL|2XL|ALLSIZE|ALL SIZE)$/i', '', trim($item->sku));
            }
            $modelName = $skuInduk ?: ($item->sku ?: ($item->nama_produk ?: 'PRODUK VARIAN'));

            $itemPenjahit = !empty($penjahitPerModel[$modelName]) ? $penjahitPerModel[$modelName] : $penjahitGlobal;

            if ($pemotong) $updatePayload['pemotong'] = $pemotong;
            if ($itemPenjahit) $updatePayload['penjahit'] = $itemPenjahit;
            if ($vendorLkpk) $updatePayload['vendor_kancing'] = $vendorLkpk;
            if ($estKainPotong) $updatePayload['est_kain'] = $estKainPotong;
            if ($pkiKainPotong) $updatePayload['kain_pakai'] = $pkiKainPotong;
            if ($sisaKainPotong) $updatePayload['kain_sisa'] = $sisaKainPotong;

            if ($request->has("items.{$item->id}.status")) {
                $updatePayload['status'] = $request->input("items.{$item->id}.status");
            }

            if (!empty($updatePayload)) {
                $item->update($updatePayload);
            }

            if ($itemPenjahit) {
                $this->processAutoSaveVendor($spk->tenant_id, (string) $itemPenjahit, 'Penjahit');
            }
        }

        // 6. Update Proses Progres (qty_done per item & proses)
        if ($request->has('progres') && is_array($request->input('progres'))) {
            foreach ($request->input('progres') as $progresId => $qtyDone) {
                $pg = \App\Models\SpkItemProgres::find($progresId);
                if ($pg && $pg->item->spk_id === $spk->id) {
                    $pg->update(['qty_done' => max(0, (int) $qtyDone)]);
                }
            }
        }

        // 7. Simpan Catatan Tambahan (Log Histori Catatan per Tahap)
        $catatanNotes = [];
        if ($request->filled('rincian_antrian')) $catatanNotes[] = "Rincian Antrian: " . $request->input('rincian_antrian');
        if ($request->filled('catatan_antrian')) $catatanNotes[] = "Antrian: " . $request->input('catatan_antrian');
        if ($request->filled('pembuat_sample')) $catatanNotes[] = "Pembuat Sample: " . $request->input('pembuat_sample');
        if ($request->filled('status_acc')) $catatanNotes[] = "Status ACC: " . $request->input('status_acc');
        if ($request->filled('catatan_revisi_kain')) $catatanNotes[] = "Revisi: " . $request->input('catatan_revisi_kain');
        if ($request->filled('vendor_print')) $catatanNotes[] = "Vendor Print: " . $request->input('vendor_print');
        if ($request->filled('catatan_pemotongan')) $catatanNotes[] = "Potong: " . $request->input('catatan_pemotongan');
        if ($request->filled('catatan_jahit')) $catatanNotes[] = "Jahit: " . $request->input('catatan_jahit');
        if ($request->filled('rdy_kain_potong')) $catatanNotes[] = "Ready: " . $request->input('rdy_kain_potong');
        if ($photoUrl && $photoTag) $catatanNotes[] = "{$photoTag}: {$photoUrl}";

        if (!empty($catatanNotes)) {
            $catatanSummary = implode(' | ', $catatanNotes);
            $spk->tambahan = $spk->tambahan ? ($spk->tambahan . ' || ' . $catatanSummary) : $catatanSummary;
        }

        $spk->save();

        // 8. Auto-save Vendor / Mitra baru ke Master Data Tailors
        if ($request->filled('pembuat_sample')) {
            $this->processAutoSaveVendor($spk->tenant_id, (string) $request->input('pembuat_sample'), 'Pembuat Sample');
        }
        if ($request->filled('vendor_print')) {
            $this->processAutoSaveVendor($spk->tenant_id, (string) $request->input('vendor_print'), 'Vendor Print');
        }
        if ($pemotong) {
            $this->processAutoSaveVendor($spk->tenant_id, (string) $pemotong, 'Pemotong');
        }
        if (!empty($penjahitReq)) {
            $listP = is_array($penjahitReq) ? $penjahitReq : [$penjahitReq];
            foreach ($listP as $pVal) {
                if ($pVal) {
                    $this->processAutoSaveVendor($spk->tenant_id, (string) $pVal, 'Penjahit');
                }
            }
        }
        if (!empty($vendorLkpkReq)) {
            $listLkpk = is_array($vendorLkpkReq) ? $vendorLkpkReq : [$vendorLkpkReq];
            foreach ($listLkpk as $vLkpk) {
                if ($vLkpk) {
                    $this->processAutoSaveVendor($spk->tenant_id, (string) $vLkpk, 'Vendor Kancing');
                }
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => '✅ Data Tracking SPK #' . ($spk->no_produksi ?: $spk->no_spk) . ' Berhasil Disimpan ke Database!',
                'status'  => $spk->status
            ]);
        }

        return redirect()->back()->with('success', '✅ Tracking SPK berhasil diperbarui via Mobile!');
    }

    private function normalizeSizeKey(?string $size): string
    {
        $sz = strtoupper(trim((string) $size));
        if ($sz === 'XXXL' || $sz === '3 XL' || $sz === '3-XL' || $sz === '3_XL') {
            return '3XL';
        }
        if ($sz === 'XXXXL' || $sz === '4 XL' || $sz === '4-XL' || $sz === '4_XL') {
            return '4XL';
        }
        if ($sz === '2XL' || $sz === '2 XL' || $sz === '2-XL' || $sz === '2_XL') {
            return 'XXL';
        }
        return $sz ?: 'S';
    }

    /**
     * Resize and store uploaded image to disk using PHP GD (Max 1200px & 80% compression).
     */
    private function resizeAndStoreUploadedImage($file, string $folder = 'spks', int $maxDimension = 1200, int $quality = 80): string
    {
        $mime = $file->getMimeType();
        $filePath = $file->getPathname();

        if (function_exists('imagecreatetruecolor') && in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            list($origWidth, $origHeight) = @getimagesize($filePath) ?: [0, 0];

            if ($origWidth > 0 && $origHeight > 0) {
                $srcImg = match($mime) {
                    'image/jpeg' => @imagecreatefromjpeg($filePath),
                    'image/png'  => @imagecreatefrompng($filePath),
                    'image/webp' => @imagecreatefromwebp($filePath),
                    default      => null,
                };

                if ($srcImg) {
                    $newWidth = $origWidth;
                    $newHeight = $origHeight;

                    if ($origWidth > $maxDimension || $origHeight > $maxDimension) {
                        if ($origWidth >= $origHeight) {
                            $newWidth = $maxDimension;
                            $newHeight = (int) round(($origHeight / $origWidth) * $maxDimension);
                        } else {
                            $newHeight = $maxDimension;
                            $newWidth = (int) round(($origWidth / $origHeight) * $maxDimension);
                        }
                    }

                    $dstImg = imagecreatetruecolor($newWidth, $newHeight);

                    if (in_array($mime, ['image/png', 'image/webp'], true)) {
                        imagealphablending($dstImg, false);
                        imagesavealpha($dstImg, true);
                    }

                    imagecopyresampled($dstImg, $srcImg, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);

                    $tmpFilename = tempnam(sys_get_temp_dir(), 'img_resized_');
                    if ($mime === 'image/png') {
                        imagepng($dstImg, $tmpFilename, 6);
                    } elseif ($mime === 'image/webp') {
                        imagewebp($dstImg, $tmpFilename, $quality);
                    } else {
                        imagejpeg($dstImg, $tmpFilename, $quality);
                    }

                    imagedestroy($srcImg);
                    imagedestroy($dstImg);

                    $extension = $file->getClientOriginalExtension() ?: 'jpg';
                    $targetPath = $folder . '/' . \Illuminate\Support\Str::random(40) . '.' . $extension;

                    \Illuminate\Support\Facades\Storage::disk('public')->put($targetPath, file_get_contents($tmpFilename));
                    @unlink($tmpFilename);

                    return \Illuminate\Support\Facades\Storage::url($targetPath);
                }
            }
        }

        $path = $file->store($folder, 'public');
        return \Illuminate\Support\Facades\Storage::url($path);
    }

    /**
     * Tampilan Publik Tracking Produksi SPK untuk Customer (Tanpa Login & Tanpa Data Internal/Vendor)
     */
    public function customerTrack(Request $request, $spkKey)
    {
        $spk = null;

        if ($spkKey instanceof Spk) {
            $spk = $spkKey;
            $spk->load(['items', 'penginput', 'proses']);
        } elseif (is_numeric($spkKey)) {
            $spk = Spk::with(['items', 'penginput', 'proses'])->find($spkKey);
        }

        if (!$spk && is_string($spkKey)) {
            $spk = Spk::with(['items', 'penginput', 'proses'])
                ->where('no_spk', $spkKey)
                ->orWhere('no_produksi', $spkKey)
                ->orWhere('no_pesanan', $spkKey)
                ->first();
        }

        if (!$spk && $request->filled('search')) {
            $search = trim($request->search);
            $spk = Spk::with(['items', 'penginput', 'proses'])
                ->where('no_spk', $search)
                ->orWhere('no_produksi', $search)
                ->orWhere('no_pesanan', $search)
                ->orWhere('pemesan', 'like', '%' . $search . '%')
                ->first();
        }

        if (!$spk) {
            return view('inventory.spks.customer_tracking', [
                'spk' => null,
                'search' => $request->input('search', $spkKey),
            ]);
        }

        $savedRincianAntrian = '';
        $savedCatatanAntrian = '';
        $savedPembuatSample  = '';
        $savedStatusAcc      = '';
        $savedCatatanRevisi  = '';
        $savedVendorPrint    = '';
        $savedCatatanPotong  = '';
        $savedCatatanJahit   = '';
        $savedFotoSample     = '';
        $savedFotoPrint      = '';
        $savedFotoPotong     = '';

        if (!empty($spk->tambahan)) {
            $parts = explode('||', $spk->tambahan);
            foreach ($parts as $part) {
                $subParts = explode('|', $part);
                foreach ($subParts as $sub) {
                    $sub = trim($sub);
                    if (str_starts_with($sub, 'Rincian Antrian:')) {
                        $savedRincianAntrian = trim(substr($sub, strlen('Rincian Antrian:')));
                    } elseif (str_starts_with($sub, 'Antrian:')) {
                        $savedCatatanAntrian = trim(substr($sub, strlen('Antrian:')));
                    } elseif (str_starts_with($sub, 'Pembuat Sample:')) {
                        $savedPembuatSample = trim(substr($sub, strlen('Pembuat Sample:')));
                    } elseif (str_starts_with($sub, 'Status ACC:')) {
                        $savedStatusAcc = trim(substr($sub, strlen('Status ACC:')));
                    } elseif (str_starts_with($sub, 'Revisi:')) {
                        $savedCatatanRevisi = trim(substr($sub, strlen('Revisi:')));
                    } elseif (str_starts_with($sub, 'Vendor Print:')) {
                        $savedVendorPrint = trim(substr($sub, strlen('Vendor Print:')));
                    } elseif (str_starts_with($sub, 'Potong:')) {
                        $savedCatatanPotong = trim(substr($sub, strlen('Potong:')));
                    } elseif (str_starts_with($sub, 'Jahit:')) {
                        $savedCatatanJahit = trim(substr($sub, strlen('Jahit:')));
                    } elseif (str_starts_with($sub, 'Foto Sample:')) {
                        $savedFotoSample = trim(substr($sub, strlen('Foto Sample:')));
                    } elseif (str_starts_with($sub, 'Foto Print:')) {
                        $savedFotoPrint = trim(substr($sub, strlen('Foto Print:')));
                    } elseif (str_starts_with($sub, 'Foto Potong:')) {
                        $savedFotoPotong = trim(substr($sub, strlen('Foto Potong:')));
                    }
                }
            }
        }

        $defaultPhoto = $spk->mockup_url ?: ($spk->image_url ?: $spk->referensi_klien_url);

        $stagesList = [
            'Perencanaan' => [
                'label' => 'Perencanaan Pesanan',
                'icon' => 'fas fa-clipboard-list',
                'pct' => 10,
                'photo' => $spk->referensi_klien_url ?: $spk->mockup_url,
                'photo_tag' => 'Desain / Referensi Klien',
                'note' => $savedCatatanAntrian ?: 'Pesanan telah diterima dan masuk tahap perencanaan produksi.'
            ],
            'Antrian & Sampling' => [
                'label' => 'Antrian & Preparation',
                'icon' => 'fas fa-hourglass-half',
                'pct' => 20,
                'photo' => $savedFotoSample ?: $defaultPhoto,
                'photo_tag' => 'Persiapan Bahan & Antrian',
                'note' => $savedRincianAntrian ?: 'Persiapan bahan dan antrian jadwal pengerjaan.'
            ],
            'Tahap Sampling' => [
                'label' => 'Pembuatan Sample',
                'icon' => 'fas fa-vial',
                'pct' => 30,
                'photo' => $savedFotoSample ?: $defaultPhoto,
                'photo_tag' => 'Foto Sample Baju',
                'note' => $savedStatusAcc ? "Status ACC: {$savedStatusAcc}" : ($savedPembuatSample ? "Pembuat Sample: {$savedPembuatSample}" : 'Proses pembuatan dan pengecekan sample produk.')
            ],
            'Tahap Print Kain' => [
                'label' => 'Proses Print & Motif',
                'icon' => 'fas fa-print',
                'pct' => 40,
                'photo' => $savedFotoPrint ?: $defaultPhoto,
                'photo_tag' => 'Foto Hasil Print Kain',
                'note' => $savedVendorPrint ? "Mitra Print: {$savedVendorPrint}" : 'Pencetakan motif dan desain pada bahan kain.'
            ],
            'Tahap Pemotongan' => [
                'label' => 'Pemotongan Bahan',
                'icon' => 'fas fa-scissors',
                'pct' => 55,
                'photo' => $savedFotoPotong ?: $defaultPhoto,
                'photo_tag' => 'Foto Hasil Pemotongan Kain',
                'note' => $savedCatatanPotong ?: 'Proses pemotongan kain sesuai pola dan varian ukuran.'
            ],
            'Tahap Jahit' => [
                'label' => 'Proses Penjahitan',
                'icon' => 'fas fa-cut',
                'pct' => 70,
                'photo' => $spk->image_url ?: $defaultPhoto,
                'photo_tag' => 'Foto Bukti Penjahitan',
                'note' => $savedCatatanJahit ?: 'Penggabungan pola dan penjahitan seluruh komponen produk.'
            ],
            'Tahap LKPK' => [
                'label' => 'Pemasangan Aksesoris',
                'icon' => 'fas fa-calculator',
                'pct' => 80,
                'photo' => $defaultPhoto,
                'photo_tag' => 'Pemasangan Aksesoris & Kancing',
                'note' => 'Pemasangan kancing, lubang kancing, dan aksesoris pendukung.'
            ],
            'Quality Control' => [
                'label' => 'Quality Control (QC)',
                'icon' => 'fas fa-check-double',
                'pct' => 90,
                'photo' => $spk->image_url ?: $defaultPhoto,
                'photo_tag' => 'Foto QC Produk',
                'note' => 'Pemeriksaan ketelitian, ukuran, dan kualitas hasil akhir.'
            ],
            'Packing / Finishing' => [
                'label' => 'Packing & Finishing',
                'icon' => 'fas fa-box-open',
                'pct' => 95,
                'photo' => $spk->image_url ?: $defaultPhoto,
                'photo_tag' => 'Foto Finishing & Packing',
                'note' => 'Pembersihan benang, penggosokan, pelipatan, dan pembungkusan rapi.'
            ],
            'Selesai (Finished Good)' => [
                'label' => 'Pesanan Selesai (Siap)',
                'icon' => 'fas fa-check-circle',
                'pct' => 100,
                'photo' => $spk->image_url ?: $defaultPhoto,
                'photo_tag' => 'Foto Produk Selesai',
                'note' => 'Seluruh pengerjaan selesai. Pesanan siap dikirim / diambil.'
            ],
        ];

        $currentStageRaw = $spk->status ?: ($spk->tahap_saat_ini ?: 'Perencanaan');
        $currentStageKey = 'Perencanaan';
        $currentStageIdx = 1;
        $progressPct = 10;

        $idx = 1;
        foreach ($stagesList as $key => $meta) {
            $keyFirstWord = strtolower(explode(' ', $key)[0]);
            $rawLower = strtolower($currentStageRaw);
            if (str_contains($rawLower, strtolower($key)) || ($keyFirstWord !== 'tahap' && str_contains($rawLower, $keyFirstWord))) {
                $currentStageKey = $key;
                $currentStageIdx = $idx;
                $progressPct = $meta['pct'];
            }
            $idx++;
        }

        if (str_contains(strtolower($currentStageRaw), 'jahit')) {
            $currentStageKey = 'Tahap Jahit';
            $currentStageIdx = 6;
            $progressPct = 70;
        } elseif (str_contains(strtolower($currentStageRaw), 'potong')) {
            $currentStageKey = 'Tahap Pemotongan';
            $currentStageIdx = 5;
            $progressPct = 55;
        } elseif (str_contains(strtolower($currentStageRaw), 'qc') || str_contains(strtolower($currentStageRaw), 'quality')) {
            $currentStageKey = 'Quality Control';
            $currentStageIdx = 8;
            $progressPct = 90;
        } elseif (str_contains(strtolower($currentStageRaw), 'packing') || str_contains(strtolower($currentStageRaw), 'finishing')) {
            $currentStageKey = 'Packing / Finishing';
            $currentStageIdx = 9;
            $progressPct = 95;
        } elseif (str_contains(strtolower($currentStageRaw), 'selesai') || str_contains(strtolower($currentStageRaw), 'finished')) {
            $currentStageKey = 'Selesai (Finished Good)';
            $currentStageIdx = 10;
            $progressPct = 100;
        }

        $variantRows = [];
        $fabricName = $spk->items->first()?->sku_kain ?: 'BAHAN STANDAR';
        $totalPcs = 0;

        foreach ($spk->items as $item) {
            $szKey = strtoupper($this->normalizeSizeKey($item->ukuran));
            $skuInduk = $item->sku_induk;
            if (!$skuInduk && !empty($item->sku)) {
                $skuInduk = preg_replace('/[_\-\s]+(S|M|L|XL|XXL|3XL|4XL|XXXL|XXXXL|2XL|ALLSIZE|ALL SIZE)$/i', '', trim($item->sku));
            }
            $modelName = $skuInduk ?: ($item->sku ?: ($item->nama_produk ?: 'PRODUK VARIAN'));

            if (!isset($variantRows[$modelName])) {
                $variantRows[$modelName] = [
                    'name'     => $modelName,
                    'sizes'    => [],
                    'subtotal' => 0,
                ];
            }
            $variantRows[$modelName]['sizes'][] = [
                'size'     => $szKey,
                'quantity' => (int) $item->quantity,
            ];
            $variantRows[$modelName]['subtotal'] += (int) $item->quantity;
            $totalPcs += (int) $item->quantity;
        }

        $photos = [];
        if ($spk->mockup_url) $photos[] = ['title' => 'Mockup Desain Final', 'url' => $spk->mockup_url];
        if ($spk->referensi_klien_url) $photos[] = ['title' => 'Referensi Klien', 'url' => $spk->referensi_klien_url];
        if ($spk->image_url) $photos[] = ['title' => 'Foto Progres Produksi', 'url' => $spk->image_url];

        if (!empty($spk->tambahan)) {
            $parts = explode('||', $spk->tambahan);
            foreach ($parts as $part) {
                foreach (explode('|', $part) as $sub) {
                    $sub = trim($sub);
                    if (str_contains($sub, 'Foto ') && str_contains($sub, 'http')) {
                        $pParts = explode(':', $sub, 2);
                        if (count($pParts) == 2) {
                            $tag = trim($pParts[0]);
                            $url = trim($pParts[1]);
                            $photos[] = ['title' => $tag, 'url' => $url];
                        }
                    }
                }
            }
        }

        return view('inventory.spks.customer_tracking', compact(
            'spk',
            'stagesList',
            'currentStageKey',
            'currentStageIdx',
            'progressPct',
            'variantRows',
            'fabricName',
            'totalPcs',
            'photos'
        ));
    }
}
