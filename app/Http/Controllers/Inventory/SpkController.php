<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Spk;
use App\Models\SpkItem;
use App\Models\SpkItemExtra;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class SpkController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = Spk::with(['penginput', 'items'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('no_spk', 'like', '%' . $search . '%')
                  ->orWhere('no_produksi', 'like', '%' . $search . '%')
                  ->orWhere('pemesan', 'like', '%' . $search . '%')
                  ->orWhere('instansi', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('tanggal', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('deadline', '<=', $request->date_to);
        }

        $spks = $query->paginate(15)->withQueryString();

        return view('inventory.spks.index', compact('spks'));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;

        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'sku_induk', 'ukuran', 'cost_price']);

        foreach ($products as $product) {
            $latestItem = SpkItem::whereHas('spk', function($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                })
                ->where('master_product_id', $product->id)
                ->latest()
                ->first();

            if ($latestItem) {
                $product->latest_costs = [
                    'jasa_konveksi' => (float)$latestItem->jasa_konveksi,
                    'jasa_potong' => (float)$latestItem->jasa_potong,
                    'jasa_printing' => (float)$latestItem->jasa_printing,
                    'jasa_jahit' => (float)$latestItem->jasa_jahit,
                    'jasa_labsas' => (float)$latestItem->jasa_labsas,
                    'kebutuhan_kain' => (float)$latestItem->kebutuhan_kain,
                    'biaya_kain' => (float)$latestItem->biaya_kain,
                    'biaya_sbs' => (float)$latestItem->biaya_sbs,
                    'biaya_pitta' => (float)$latestItem->biaya_pitta,
                    'biaya_kancing' => (float)$latestItem->biaya_kancing,
                    'biaya_kancing_kait' => (float)$latestItem->biaya_kancing_kait,
                    'biaya_karet' => (float)$latestItem->biaya_karet,
                    'biaya_plastik' => (float)$latestItem->biaya_plastik,
                    'biaya_string' => (float)$latestItem->biaya_string,
                    'biaya_bordir' => (float)$latestItem->biaya_bordir,
                    'biaya_servis' => (float)$latestItem->biaya_servis,
                    'biaya_finishing' => (float)$latestItem->biaya_finishing,
                    'biaya_pengiriman' => (float)$latestItem->biaya_pengiriman,
                ];
            } else {
                $product->latest_costs = null;
            }
        }

        $tailors = \App\Models\Tailor::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('inventory.spks.create', compact('products', 'tailors'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'no_produksi'    => 'nullable|string|max:255',
            'tanggal'        => 'required|date',
            'deadline'       => 'required|date|after_or_equal:tanggal',
            'pemesan'        => 'nullable|string|max:255',
            'no_hp_pemesan'  => 'nullable|string|max:100',
            'instansi'       => 'nullable|string|max:255',
            'tambahan'       => 'nullable|string',
            'image'          => 'nullable|image|max:4096',
            'items'          => 'required|array|min:1',
            'items.*.name'   => 'required|string|max:255',
            'items.*.qty'    => 'required|integer|min:1',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('spks', 'public');
        }

        $spk = DB::transaction(function () use ($request, $tenantId, $imagePath) {
            $noSpk = Spk::generateNoSpk();
            $noProduksi = Spk::generateNoProduksi();

            $spk = Spk::create([
                'tenant_id'     => $tenantId,
                'no_produksi'   => $noProduksi,
                'no_spk'        => $noSpk,
                'tanggal'       => $request->tanggal,
                'deadline'      => $request->deadline,
                'pemesan'       => $request->pemesan,
                'no_hp_pemesan' => $request->no_hp_pemesan,
                'instansi'      => $request->instansi,
                'tambahan'      => $request->tambahan,
                'image_url'     => $imagePath ? asset('storage/' . $imagePath) : null,
                'penginput_id'  => Auth::id(),
            ]);

            foreach ($request->items as $row) {
                $prodId = null;
                if (!empty($row['sku'])) {
                    $prod = MasterProduct::where('tenant_id', $tenantId)
                        ->where('sku', $row['sku'])->first();
                    if ($prod) $prodId = $prod->id;
                }

                // Collect all standard cost fields
                $fields = [
                    'jasa_konveksi', 'jasa_potong', 'jasa_printing', 'jasa_jahit', 'jasa_labsas',
                    'kebutuhan_kain', 'biaya_kain', 'biaya_sbs', 'biaya_pitta',
                    'biaya_kancing', 'biaya_kancing_kait', 'biaya_karet', 'biaya_plastik', 'biaya_string',
                    'biaya_bordir', 'biaya_servis', 'biaya_finishing', 'biaya_pengiriman',
                ];

                $costData = [];
                foreach ($fields as $f) {
                    $costData[$f] = floatval($row[$f] ?? 0);
                }

                // Sum all standard costs
                $hppStandar = array_sum(array_filter($costData, fn($k) => $k !== 'kebutuhan_kain', ARRAY_FILTER_USE_KEY));

                // Sum extra costs
                $extrasTotal = 0;
                if (!empty($row['extras']) && is_array($row['extras'])) {
                    foreach ($row['extras'] as $extra) {
                        if (!empty($extra['keterangan'])) {
                            $extrasTotal += floatval($extra['nominal'] ?? 0);
                        }
                    }
                }

                $hpp = $hppStandar + $extrasTotal;

                $item = SpkItem::create(array_merge([
                    'spk_id'            => $spk->id,
                    'master_product_id' => $prodId,
                    'nama_produk'       => $row['name'],
                    'sku'               => $row['sku'] ?? null,
                    'sku_induk'         => $row['sku_induk'] ?? null,
                    'ukuran'            => $row['size'] ?? null,
                    'quantity'          => (int) $row['qty'],
                    'penjahit'          => $row['tailor'] ?? null,
                    'alur_proses'       => $row['alur_proses'] ?? 'Langsung Jahit',
                    'hpp'               => $hpp,
                ], $costData));

                // Save dynamic extras
                if (!empty($row['extras']) && is_array($row['extras'])) {
                    foreach ($row['extras'] as $extra) {
                        if (!empty($extra['keterangan'])) {
                            SpkItemExtra::create([
                                'spk_item_id' => $item->id,
                                'keterangan'  => $extra['keterangan'],
                                'nominal'     => floatval($extra['nominal'] ?? 0),
                            ]);
                        }
                    }
                }
            }

            return $spk;
        });

        return redirect()->route('spks.show', $spk)
            ->with('success', 'SPK #' . $spk->no_spk . ' berhasil disimpan.');
    }

    public function show(Spk $spk)
    {
        abort_unless($spk->tenant_id === Auth::user()->tenant_id, 403);
        $spk->load(['penginput', 'items.extras']);
        $grouped = $this->getGroupedItems($spk);

        // Fetch dynamic production statuses (with seed fallback)
        \App\Models\ProductionStatus::seedDefaultsForTenant($spk->tenant_id);
        $productionStatuses = \App\Models\ProductionStatus::where('tenant_id', $spk->tenant_id)
            ->orderBy('sort_order')
            ->get();

        return view('inventory.spks.show', compact('spk', 'grouped', 'productionStatuses'));
    }

    public function print(Spk $spk)
    {
        abort_unless($spk->tenant_id === Auth::user()->tenant_id, 403);
        $spk->load(['penginput', 'items.extras']);
        $grouped = $this->getGroupedItems($spk);
        $sizesHeader = ['S', 'M', 'L', 'XL', 'XXL', '3XL'];

        foreach ($spk->items as $item) {
            $sz = strtoupper(trim($item->ukuran));
            if ($sz && !in_array($sz, ['S', 'M', 'L', 'XL', 'XXL', '3XL', 'XXXL']) && !in_array($sz, $sizesHeader)) {
                $sizesHeader[] = $sz;
            }
        }

        return view('inventory.spks.print', compact('spk', 'grouped', 'sizesHeader'));
    }

    public function updateItemStatus(Request $request, $itemId)
    {
        $item = SpkItem::findOrFail($itemId);
        $spk = $item->spk;
        abort_unless($spk->tenant_id === Auth::user()->tenant_id, 403);

        $validStatuses = \App\Models\ProductionStatus::where('tenant_id', $spk->tenant_id)
            ->pluck('name')
            ->toArray();

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
                if ($item->master_product_id) {
                    $product = MasterProduct::find($item->master_product_id);
                    if ($product) {
                        // 1. Add finished goods stock & record movement
                        $product->recordStockMovement(
                            $item->quantity,
                            'in',
                            'Penerimaan SPK Selesai #' . $spk->no_spk . ' (Item: ' . $item->nama_produk . ')',
                            Auth::id()
                        );

                        // 2. Update catalog HPP (cost_price)
                        $product->update([
                            'cost_price' => $item->hpp
                        ]);

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
                    }
                }
            }

            // Transition: From 'Selesai' back to something else (Cancellation/Rollback)
            if ($oldStatus === 'Selesai' && $newStatus !== 'Selesai') {
                if ($item->master_product_id) {
                    $product = MasterProduct::find($item->master_product_id);
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
            }
        });

        return redirect()->back()->with('success', 'Status item "' . $item->nama_produk . '" berhasil diubah.');
    }

    public function destroy(Spk $spk)
    {
        abort_unless($spk->tenant_id === Auth::user()->tenant_id, 403);

        DB::transaction(function () use ($spk) {
            if ($spk->image_url) {
                $relative = str_replace(asset('storage/'), '', $spk->image_url);
                Storage::disk('public')->delete($relative);
            }
            $spk->delete();
        });

        return redirect()->route('spks.index')
            ->with('success', 'Data SPK berhasil dihapus.');
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
}
