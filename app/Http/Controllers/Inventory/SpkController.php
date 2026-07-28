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
            if (in_array($stage, ['pesanan_baru', 'perencanaan', 'draft'])) {
                $query->where(function ($q) {
                    $q->doesntHave('proses')
                      ->orWhereIn(DB::raw('LOWER(tahap_saat_ini)'), ['draft', 'pesanan baru', 'perencanaan', 'perancangan produksi (spk)', 'tahap desain & mockup'])
                      ->orWhereDoesntHave('items.progres', function ($pg) {
                          $pg->where('qty_done', '>', 0);
                      });
                });
            } else {
                $query->whereHas('proses', function ($p) use ($stage) {
                    $p->where('nama_proses', 'like', '%' . $stage . '%');
                });
            }
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

        $allGroupedSpks = Spk::with(['penginput', 'items.masterProduct', 'items.progres', 'proses'])
            ->whereIn('id', $groupedMaxIds)
            ->orderByDesc('id')
            ->get();

        // Attach sub_spks collection to each production group item
        $noProduksiList = $allGroupedSpks->pluck('no_produksi')->filter()->unique()->toArray();
        $siblingSpksMap = [];
        if (!empty($noProduksiList)) {
            $allSiblings = Spk::with(['items.masterProduct', 'items.progres', 'proses'])
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
                $siblings = Spk::with(['items.masterProduct', 'items.progres', 'proses'])
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
                    } elseif ($stage === 'pesanan_baru' || $stage === 'perencanaan') {
                        return (str_contains($currName, 'pesanan') || str_contains($currName, 'perencanaan') || str_contains($currName, 'perancangan') || str_contains($currName, 'desain')) && !str_contains($currName, 'draft');
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
            $imagePath = $request->file('image')->store('spks', 'public');
        }

        $referensiPath = null;
        if ($request->hasFile('referensi_klien')) {
            $referensiPath = $request->file('referensi_klien')->store('spks/referensi', 'public');
        }

        $mockupPath = null;
        if ($request->hasFile('mockup_final')) {
            $mockupPath = $request->file('mockup_final')->store('spks/mockup', 'public');
        }

        $spk = DB::transaction(function () use ($request, $tenantId, $imagePath) {
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

                        $spkItem = SpkItem::create([
                            'spk_id'            => $spkRecord->id,
                            'nama_produk'       => $namaProduk,
                            'sku'               => $skuProduk,
                            'sku_kain'          => $rBlock['sku_kain'] ?? ($bahanList[0]['nama_bahan'] ?? null),
                            'ukuran'            => $ukuran,
                            'catatan'           => $rBlock['catatan'] ?? null,
                            'quantity'          => $qtyProduksi,
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

                $item = SpkItem::create([
                    'spk_id'            => $spk->id,
                    'master_product_id' => $prodId,
                    'nama_produk'       => $namaProduk,
                    'sku'               => $skuProduk,
                    'sku_kain'          => $row['sku_kain'] ?? null,
                    'sku_induk'         => $row['sku_induk'] ?? null,
                    'ukuran'            => $row['size'] ?? null,
                    'catatan'           => $row['catatan'] ?? null,
                    'quantity'          => (int) ($row['qty'] ?? 1),
                    'est_kain'          => (float) ($row['est_kain'] ?? 0),
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

        $sizesHeader = ['S', 'M', 'L', 'XL', 'XXL', '3XL'];
        foreach ($spk->items as $item) {
            $sz = strtoupper(trim($item->ukuran));
            if ($sz && !in_array($sz, ['S', 'M', 'L', 'XL', 'XXL', '3XL', 'XXXL']) && !in_array($sz, $sizesHeader)) {
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

        return view('inventory.spks.show', compact(
            'spk', 'grouped', 'statusOptions', 'sizesHeader', 'progresMap',
            'products', 'tailors', 'pemotongList', 'penjahitList', 'vendorKancingList', 'petugasQcList',
            'laborServices', 'stores', 'existingNoProduksi', 'recipesMap',
            'inventoryItems', 'inventoryItemsMap', 'allMasterProductsList',
            'siblingSpks', 'bankAccounts', 'totalSpkLaborCost', 'totalSpkLaborPaid', 'totalSpkLaborUnpaid',
            'laborBreakdown', 'spkExpenses'
        ));
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
                $p = $request->file('image')->store('spks', 'public');
                $updateData['image_url'] = Storage::url($p);
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
                $p = $referensiFile->store('spks/referensi', 'public');
                $updateData['referensi_klien_url'] = Storage::url($p);
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
                $p = $mockupFile->store('spks/mockup', 'public');
                $updateData['mockup_url'] = Storage::url($p);
                if (empty($spk->image_url) && !isset($updateData['image_url'])) {
                    $updateData['image_url'] = Storage::url($p);
                }
            }

            $spk->update($updateData);

            // Update item details if provided in request
            $rincianBlocks = $request->input('rincian', []);
            if (!empty($rincianBlocks) && is_array($rincianBlocks)) {
                foreach ($rincianBlocks as $rIdx => $rBlock) {
                    $prodList = $rBlock['produk'] ?? [];
                    if (!empty($prodList) && is_array($prodList)) {
                        // Build a flat array of item IDs in order (to map by pIdx)
                        $itemsOrdered = $spk->items->values();

                        foreach ($prodList as $pIdx => $pRow) {
                            // Match item by position index
                            $spkItem = $itemsOrdered->get((int)$pIdx);
                            if (!$spkItem) {
                                // If no existing item at this index, use first available item (fallback)
                                $spkItem = $itemsOrdered->first();
                            }
                            if (!$spkItem) continue;

                                $namaProduk = trim($pRow['nama_produk'] ?? '') ?: $spkItem->nama_produk;
                                $skuProduk  = trim($pRow['sku_produk'] ?? '') ?: $spkItem->sku;
                                $ukuran     = trim($pRow['ukuran'] ?? '') ?: $spkItem->ukuran;
                                $qtyProd    = max(1, (int) ($pRow['qty_produksi'] ?? $spkItem->quantity));

                                $spkItem->update([
                                    'nama_produk' => $namaProduk,
                                    'sku'         => $skuProduk,
                                    'ukuran'      => $ukuran,
                                    'quantity'    => $qtyProd,
                                    'pemotong'    => $pRow['pemotong'] ?? $spkItem->pemotong,
                                    'penjahit'    => $pRow['penjahit'] ?? $spkItem->penjahit,
                                    'vendor_kancing' => $pRow['vendor_kancing'] ?? $spkItem->vendor_kancing,
                                ]);

                                // Clean old extras for this item and rebuild
                                SpkItemExtra::where('spk_item_id', $spkItem->id)->delete();

                                // Pemotong
                                $pemotong = trim($pRow['pemotong'] ?? '');
                                if ($pemotong !== '') {
                                    $this->processAutoSaveVendor($tenantId, $pemotong, 'Pemotong');
                                }
                                $qtyPotong = (int) ($pRow['qty_potong'] ?? 0);
                                $tarifPotong = floatval($pRow['tarif_potong'] ?? 0);
                                if ($pemotong !== '' || $qtyPotong > 0) {
                                    $sub = $qtyPotong * $tarifPotong;
                                    SpkItemExtra::create([
                                        'spk_item_id' => $spkItem->id,
                                        'keterangan'  => "Ongkos Potong: {$pemotong} ({$qtyPotong} pcs" . ($tarifPotong > 0 ? " @ Rp " . number_format($tarifPotong) : "") . ")",
                                        'nominal'     => $sub,
                                    ]);
                                }

                                // Penjahit
                                $penjahit = trim($pRow['penjahit'] ?? '');
                                if ($penjahit !== '') {
                                    $this->processAutoSaveVendor($tenantId, $penjahit, 'Penjahit');
                                }
                                $qtyJahit = (int) ($pRow['qty_jahit'] ?? 0);
                                $tarifJahit = floatval($pRow['tarif_jahit'] ?? 0);
                                if ($penjahit !== '' || $qtyJahit > 0) {
                                    $sub = $qtyJahit * $tarifJahit;
                                    SpkItemExtra::create([
                                        'spk_item_id' => $spkItem->id,
                                        'keterangan'  => "Ongkos Jahit: {$penjahit} ({$qtyJahit} pcs" . ($tarifJahit > 0 ? " @ Rp " . number_format($tarifJahit) : "") . ")",
                                        'nominal'     => $sub,
                                    ]);
                                }

                                // Vendor Kancing
                                $vendorKancing = trim($pRow['vendor_kancing'] ?? '');
                                if ($vendorKancing !== '') {
                                    $this->processAutoSaveVendor($tenantId, $vendorKancing, 'Vendor Kancing');
                                }
                                $qtyKancing = (int) ($pRow['qty_kancing'] ?? 0);
                                $tarifKancing = floatval($pRow['tarif_kancing'] ?? 0);
                                if ($vendorKancing !== '' || $qtyKancing > 0) {
                                    $sub = $qtyKancing * $tarifKancing;
                                    SpkItemExtra::create([
                                        'spk_item_id' => $spkItem->id,
                                        'keterangan'  => "Ongkos Kancing/LKPK: {$vendorKancing} ({$qtyKancing} pcs" . ($tarifKancing > 0 ? " @ Rp " . number_format($tarifKancing) : "") . ")",
                                        'nominal'     => $sub,
                                    ]);
                                }

                                // Petugas QC
                                $petugasQc = trim($pRow['petugas_qc'] ?? '');
                                if ($petugasQc !== '') {
                                    $this->processAutoSaveVendor($tenantId, $petugasQc, 'Petugas QC');
                                }
                                $qcLolos = (int) ($pRow['qc_lolos'] ?? 0);
                                $qcReject = (int) ($pRow['qc_reject'] ?? 0);
                                $tarifQc = floatval($pRow['tarif_qc'] ?? 0);
                                if ($petugasQc !== '' || $qcLolos > 0 || $qcReject > 0) {
                                    $sub = $qcLolos * $tarifQc;
                                    SpkItemExtra::create([
                                        'spk_item_id' => $spkItem->id,
                                        'keterangan'  => "Ongkos QC: {$petugasQc} (Lolos: {$qcLolos} pcs, Reject: {$qcReject} pcs" . ($tarifQc > 0 ? " @ Rp " . number_format($tarifQc) : "") . ")",
                                        'nominal'     => $sub,
                                    ]);
                                }

                                // Finishing
                                $petugasFinishing = trim($pRow['petugas_finishing'] ?? '');
                                if ($petugasFinishing !== '') {
                                    $this->processAutoSaveVendor($tenantId, $petugasFinishing, 'Finishing');
                                }
                                $qtyFinishing = (int) ($pRow['qty_finishing'] ?? 0);
                                $qtyFgood = (int) ($pRow['qty_fgood'] ?? 0);
                                $tarifFinishing = floatval($pRow['tarif_finishing'] ?? 0);
                                if ($petugasFinishing !== '' || $qtyFinishing > 0 || $qtyFgood > 0) {
                                    $sub = $qtyFinishing * $tarifFinishing;
                                    SpkItemExtra::create([
                                        'spk_item_id' => $spkItem->id,
                                        'keterangan'  => "Ongkos Finishing: {$petugasFinishing} ({$qtyFinishing} pcs, F.Good: {$qtyFgood} pcs" . ($tarifFinishing > 0 ? " @ Rp " . number_format($tarifFinishing) : "") . ")",
                                        'nominal'     => $sub,
                                    ]);
                                }

                                // Bahan List
                                $bahanList = $pRow['bahan'] ?? [];
                                if (!empty($bahanList) && is_array($bahanList)) {
                                    $totalHpp = $this->processAutoSaveBahanAndRecipe($tenantId, $namaProduk, $skuProduk, $qtyProd, $bahanList, $spkItem);
                                    if ($totalHpp > 0) {
                                        $spkItem->update(['hpp' => $totalHpp]);
                                    }
                                }
                        } // end foreach prodList
                    }
                }
            }
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

            foreach ($spkList as $itemSpk) {
                foreach ($itemSpk->items as $item) {
                    SpkItemExtra::where('spk_item_id', $item->id)->delete();
                    SpkItemProgres::where('spk_item_id', $item->id)->delete();
                    \App\Models\SpkItemPickup::where('spk_item_id', $item->id)->delete();
                    $item->delete();
                }
                SpkProses::where('spk_id', $itemSpk->id)->delete();
                $itemSpk->delete();
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

        $sizesHeader = ['S', 'M', 'L', 'XL', 'XXL', '3XL'];

        $spkBlocks = [];
        foreach ($spkList as $currentSpk) {
            $variantRows = [];
            $bazaItems = [];

            foreach ($currentSpk->items as $item) {
                $sz = strtoupper(trim($item->ukuran));
                if ($sz && !in_array($sz, ['S', 'M', 'L', 'XL', 'XXL', '3XL', 'XXXL']) && !in_array($sz, $sizesHeader)) {
                    $sizesHeader[] = $sz;
                }

                $skuInduk = $item->sku_induk;
                if (!$skuInduk && !empty($item->sku)) {
                    $skuInduk = preg_replace('/[_\-\s]+(S|M|L|XL|XXL|3XL|XXXL|ALLSIZE|ALL SIZE)$/i', '', trim($item->sku));
                }
                $modelName = $skuInduk ?: ($item->sku ?: ($item->nama_produk ?: 'MODEL VARIAN'));
                $szKey = strtoupper(trim($item->ukuran)) ?: 'S';

                if (!isset($variantRows[$modelName])) {
                    $variantRows[$modelName] = [
                        'name'  => $modelName,
                        'sku'   => $modelName,
                        'sizes' => [],
                        'total' => 0,
                    ];
                }
                $variantRows[$modelName]['sizes'][$szKey] = ($variantRows[$modelName]['sizes'][$szKey] ?? 0) + $item->quantity;
                $variantRows[$modelName]['total'] += $item->quantity;

                foreach ($item->extras as $extra) {
                    if (str_contains($extra->keterangan, 'Bahan:')) {
                        $ket = $extra->keterangan;
                        $bName = trim(str_replace('Bahan:', '', $ket));
                        $bQty = '—';
                        if (preg_match('/^(.*?)\s*\(Qty:\s*([\d\.]+)\)$/i', $bName, $mQty)) {
                            $bName = trim($mQty[1]);
                            $nVal = (float) $mQty[2];
                            $bQty = ($nVal == (int)$nVal) ? (int)$nVal : (float)$nVal;
                        }
                        $bazaItems[] = [
                            'name' => $bName,
                            'qty'  => $bQty,
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
                    'sizes'     => ['S' => 0, 'M' => 0, 'L' => 0, 'XL' => 0, 'XXL' => 0, '3XL' => 0],
                    'total'     => 0,
                ];
            }

            $sz = strtoupper(trim($item->ukuran));
            if ($sz === 'XXXL') $sz = '3XL';

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

        return back()->with('success', 'Catatan pengambilan barang berhasil dihapus.');
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
                SpkItemExtra::create([
                    'spk_item_id' => $spkItem->id,
                    'keterangan'  => "Bahan: {$rawNama} (Qty: {$cleanQtyStr})",
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
}
