<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Spk;
use App\Models\SpkItem;
use App\Models\MasterProduct;
use App\Models\Employee;
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
        
        // Load active catalog products
        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        // Load active tailors as tailor choices
        $tailors = \App\Models\Tailor::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

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
            'image'          => 'nullable|image|max:4096', // Max 4MB
            'items'          => 'required|array|min:1',
            'items.*.name'   => 'required|string|max:255',
            'items.*.sku'    => 'nullable|string|max:255',
            'items.*.sku_induk' => 'nullable|string|max:255',
            'items.*.size'   => 'nullable|string|max:100',
            'items.*.qty'    => 'required|integer|min:1',
            'items.*.tailor' => 'nullable|string|max:255',
            'items.*.biaya_bahan' => 'nullable|numeric|min:0',
            'items.*.ongkos_jahit' => 'nullable|numeric|min:0',
            'items.*.ongkos_printing' => 'nullable|numeric|min:0',
            'items.*.alur_proses' => 'nullable|string|max:255',
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
                // Try to find matching master product ID
                $prodId = null;
                if (!empty($row['sku'])) {
                    $prod = MasterProduct::where('tenant_id', $tenantId)
                        ->where('sku', $row['sku'])
                        ->first();
                    if ($prod) {
                        $prodId = $prod->id;
                    }
                }

                $biayaBahan = floatval($row['biaya_bahan'] ?? 0);
                $ongkosJahit = floatval($row['ongkos_jahit'] ?? 0);
                $ongkosPrinting = floatval($row['ongkos_printing'] ?? 0);
                $hpp = $biayaBahan + $ongkosJahit + $ongkosPrinting;

                SpkItem::create([
                    'spk_id'            => $spk->id,
                    'master_product_id' => $prodId,
                    'nama_produk'       => $row['name'],
                    'sku'               => $row['sku'] ?? null,
                    'sku_induk'         => $row['sku_induk'] ?? null,
                    'ukuran'            => $row['size'] ?? 'All Size',
                    'quantity'          => (int) $row['qty'],
                    'penjahit'          => $row['tailor'] ?? null,
                    'biaya_bahan'       => $biayaBahan,
                    'ongkos_jahit'      => $ongkosJahit,
                    'ongkos_printing'   => $ongkosPrinting,
                    'hpp'               => $hpp,
                    'alur_proses'       => $row['alur_proses'] ?? 'Langsung Jahit',
                ]);
            }

            return $spk;
        });

        return redirect()->route('spks.show', $spk)
            ->with('success', 'Perintah Kerja (SPK) #' . $spk->no_spk . ' berhasil disimpan.');
    }

    public function show(Spk $spk)
    {
        abort_unless($spk->tenant_id === Auth::user()->tenant_id, 403);
        $spk->load(['penginput', 'items']);

        // Group items for display
        $grouped = $this->getGroupedItems($spk);

        return view('inventory.spks.show', compact('spk', 'grouped'));
    }

    public function print(Spk $spk)
    {
        abort_unless($spk->tenant_id === Auth::user()->tenant_id, 403);
        $spk->load(['penginput', 'items']);

        // Group items for print layout
        $grouped = $this->getGroupedItems($spk);
        $sizesHeader = ['S', 'M', 'L', 'XL', 'XXL', '3XL'];

        // Collect any custom sizes if present
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
            // Delete image if exists
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
            // Clean up size from name for key
            if ($item->ukuran) {
                $modelKey = trim(str_ireplace($item->ukuran, '', $modelKey));
            }

            if (!isset($grouped[$modelKey])) {
                $grouped[$modelKey] = [
                    'model' => $modelKey,
                    'name' => $item->nama_produk,
                    'sku_induk' => $item->sku_induk ?: '—',
                    'tailors' => [], // tailors involved for this model
                    'sizes' => [
                        'S' => 0,
                        'M' => 0,
                        'L' => 0,
                        'XL' => 0,
                        'XXL' => 0,
                        '3XL' => 0,
                    ],
                    'total' => 0,
                ];
            }

            $sz = strtoupper(trim($item->ukuran));
            if ($sz === 'XXXL' || $sz === '3XL') {
                $sz = '3XL';
            }

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

        // Format tailors string
        foreach ($grouped as &$g) {
            $uniqueTailors = array_unique($g['tailors']);
            $g['tailors_list'] = !empty($uniqueTailors) ? implode(', ', $uniqueTailors) : 'Belum Ditunjuk';
        }

        return $grouped;
    }
}
