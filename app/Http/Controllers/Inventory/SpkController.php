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

            $spk = Spk::create([
                'tenant_id'     => $tenantId,
                'no_produksi'   => $request->no_produksi,
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
        return view('inventory.spks.show', compact('spk', 'grouped'));
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
