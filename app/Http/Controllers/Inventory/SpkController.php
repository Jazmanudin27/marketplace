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

    public function create(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->with(['activeRecipe.items.inventoryItem', 'activeRecipe.labors'])
            ->get();

        // Fetch latest SpkItem extras in ONE single query to eliminate N+1 loop slowness
        $productIds = $products->pluck('id')->toArray();
        $latestItems = [];
        if (!empty($productIds)) {
            $latestItems = SpkItem::with('extras')
                ->whereHas('spk', function ($q) use ($tenantId) {
                    $q->where('tenant_id', $tenantId);
                })
                ->whereIn('master_product_id', $productIds)
                ->orderBy('id', 'desc')
                ->get()
                ->unique('master_product_id')
                ->keyBy('master_product_id');
        }

        foreach ($products as $product) {
            $latestItem = $latestItems[$product->id] ?? null;

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

        $tailors = \App\Models\Tailor::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $laborServices = \App\Models\LaborService::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['name', 'default_cost']);

        $order = null;
        if ($request->filled('order_id')) {
            $order = \App\Models\Order::with('items.masterProduct')->where('tenant_id', $tenantId)->find($request->order_id);
        }

        return view('inventory.spks.create', compact('products', 'tailors', 'laborServices', 'order'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'order_id'       => 'nullable|integer|exists:orders,id',
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
                'order_id'      => $request->order_id,
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

                $item = SpkItem::create([
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

        // Fetch dynamic production statuses (with seed fallback)
        \App\Models\ProductionStatus::seedDefaultsForTenant($spk->tenant_id);
        $productionStatuses = \App\Models\ProductionStatus::where('tenant_id', $spk->tenant_id)
            ->orderBy('sort_order')
            ->get();

        return view('inventory.spks.show', compact('spk', 'grouped', 'productionStatuses', 'sizesHeader'));
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
