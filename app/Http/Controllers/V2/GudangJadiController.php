<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GudangJadiController extends Controller
{
    /**
     * Tampilkan Halaman Gudang Jadi V2 (Mutasi & Stok Barang Jadi).
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = StockMovement::with(['masterProduct', 'user'])
            ->where('tenant_id', $tenantId)
            ->whereNotNull('master_product_id');

        // Filter Jenis Mutasi (in, out, adj)
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter Produk Spesifik
        if ($request->filled('product_id')) {
            $query->where('master_product_id', $request->product_id);
        }

        // Filter Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        // Filter Keyword (Referensi / Nama Produk / SKU)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'like', "%{$search}%")
                  ->orWhereHas('masterProduct', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

        $mutations = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate(20)
            ->withQueryString();

        // Summary Stats KPI (Mutasi Gudang Jadi)
        $statsQuery = StockMovement::where('tenant_id', $tenantId)
            ->whereNotNull('master_product_id');

        if ($request->filled('start_date')) {
            $statsQuery->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $statsQuery->whereDate('created_at', '<=', $request->end_date);
        }

        $stats = $statsQuery->selectRaw("
            COUNT(*) as total_transactions,
            COALESCE(SUM(CASE WHEN type = 'in' THEN quantity ELSE 0 END), 0) as total_inbound,
            COALESCE(SUM(CASE WHEN type = 'out' THEN ABS(quantity) ELSE 0 END), 0) as total_outbound
        ")->first();

        $totalTransactions = $stats->total_transactions ?? 0;
        $totalInbound      = $stats->total_inbound ?? 0;
        $totalOutbound     = $stats->total_outbound ?? 0;

        // Ambil daftar produk master untuk pilihan filter / modal input
        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'stock', 'unit']);

        return view('v2.gudang_jadi.index', compact(
            'mutations',
            'totalTransactions',
            'totalInbound',
            'totalOutbound',
            'products'
        ));
    }

    /**
     * Form Input Mutasi Stok Gudang Jadi V2.
     */
    public function create(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $selectedType = $request->get('type', 'in');
        $selectedProductId = $request->get('product_id');

        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'sku', 'name', 'stock', 'unit']);

        if ($selectedProductId && !$products->contains('id', $selectedProductId)) {
            $selectedProduct = MasterProduct::find($selectedProductId);
            if ($selectedProduct) {
                $products->prepend($selectedProduct);
            }
        }

        return view('v2.gudang_jadi.create', compact('selectedType', 'selectedProductId', 'products'));
    }

    /**
     * API Search Master Product untuk Select2 (Dibatasi Maksimal 100 Produk).
     */
    public function searchProducts(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search   = trim($request->input('q', ''));

        $query = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%')
                  ->orWhere('sku_induk', 'like', '%' . $search . '%');
            });
        }

        $products = $query->select(['id', 'sku', 'name', 'stock', 'unit'])
            ->orderBy('name')
            ->limit(100)
            ->get();

        $results = $products->map(function ($p) {
            return [
                'id'    => $p->id,
                'text'  => '[' . $p->sku . '] ' . $p->name . ' (Stok: ' . number_format($p->stock) . ')',
                'sku'   => $p->sku,
                'name'  => $p->name,
                'stock' => (int) $p->stock,
                'unit'  => $p->unit ?: 'PCS',
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * Simpan Data Mutasi Stok Gudang Jadi V2.
     */
    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'type'            => 'required|in:in,out',
            'date'            => 'nullable|date',
            'category_reason' => 'required|string|max:100',
            'notes'           => 'nullable|string|max:500',
            'items'           => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:master_products,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.notes'      => 'nullable|string|max:255',
        ], [
            'type.required'            => 'Pilih jenis mutasi (Masuk / Keluar).',
            'category_reason.required' => 'Pilih atau isi kategori / alasan mutasi.',
            'items.required'           => 'Minimal 1 produk harus dipilih.',
            'items.*.quantity.min'     => 'Jumlah barang minimal 1 unit.',
        ]);

        $type           = $validated['type'];
        $categoryReason = trim($validated['category_reason']);
        $notes          = trim($validated['notes'] ?? '');
        $mutationDate   = $validated['date'] ? $validated['date'] . ' ' . date('H:i:s') : null;

        $typeLabel     = $type === 'in' ? 'Barang Masuk' : 'Barang Keluar';
        $fullReference = "Mutasi {$typeLabel} Gudang Jadi ({$categoryReason})" . ($notes ? " - {$notes}" : "");

        DB::beginTransaction();
        try {
            $processedCount = 0;

            foreach ($validated['items'] as $item) {
                $product = MasterProduct::where('tenant_id', $tenantId)
                    ->where('id', $item['product_id'])
                    ->firstOrFail();

                $qty = (int) $item['quantity'];
                $itemNote = !empty($item['notes']) ? " [Note: {$item['notes']}]" : '';

                $product->recordStockMovement(
                    $qty,
                    $type,
                    $fullReference . $itemNote,
                    Auth::id(),
                    $mutationDate
                );

                $processedCount++;
            }

            DB::commit();

            $redirectRoute = $type === 'in' ? 'v2.gudang_jadi.masuk' : 'v2.gudang_jadi.keluar';

            return redirect()->route($redirectRoute)
                ->with('success', "Berhasil mencatat Mutasi {$typeLabel} Gudang Jadi untuk {$processedCount} item produk.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan mutasi Gudang Jadi: ' . $e->getMessage());
        }
    }

    /**
     * Redirect Halaman Mutasi Barang Masuk Gudang Jadi V2 ke Index Tab Masuk.
     */
    public function masuk(Request $request)
    {
        $params = array_merge(['type' => 'in'], $request->all());
        return redirect()->route('v2.gudang_jadi.index', $params);
    }

    /**
     * Redirect Halaman Mutasi Barang Keluar Gudang Jadi V2 ke Index Tab Keluar.
     */
    public function keluar(Request $request)
    {
        $params = array_merge(['type' => 'out'], $request->all());
        return redirect()->route('v2.gudang_jadi.index', $params);
    }
}
