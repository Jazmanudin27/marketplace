<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductMutationController extends Controller
{
    /**
     * Tampilkan daftar riwayat mutasi stok gudang jadi.
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

        // Filter Produk Specific
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

        // Filter Pencarian Keyword (Keterangan / Nama Produk / SKU)
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
            ->paginate(25)
            ->withQueryString();

        // Summary Stats (Disatukan dalam 1 query agregat cepat)
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
        $totalInbound = $stats->total_inbound ?? 0;
        $totalOutbound = $stats->total_outbound ?? 0;

        return view('inventory.mutations.index', compact(
            'mutations',
            'totalTransactions',
            'totalInbound',
            'totalOutbound'
        ));
    }

    /**
     * Halaman Form Input Mutasi Barang Masuk / Keluar.
     */
    public function create(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'stock', 'unit', 'image_url']);

        $selectedType = $request->get('type', 'in'); // 'in' or 'out'
        $selectedProductId = $request->get('product_id');

        // Ambil data produk yang dipre-select saja (jika ada), tidak load semua
        $preSelectedProduct = null;
        if ($selectedProductId) {
            $preSelectedProduct = MasterProduct::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->where('id', $selectedProductId)
                ->first(['id', 'sku', 'name', 'stock', 'unit']);
        }

        return view('inventory.mutations.create', compact('selectedType', 'selectedProductId', 'preSelectedProduct'));
    }

    /**
     * API Autocomplete: Cari Master Produk berdasarkan keyword (nama / SKU).
     * Hanya mengembalikan max 15 hasil untuk performa.
     */
    public function searchProducts(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $keyword  = trim($request->get('q', ''));

        $query = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('sku',  'like', "%{$keyword}%");
            });
        }

        $products = $query->limit(15)->get(['id', 'sku', 'name', 'stock', 'unit']);

        return response()->json($products);
    }

    /**
     * Simpan data mutasi barang masuk / keluar ke Gudang Jadi.
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
        ], [
            'type.required'            => 'Pilih jenis mutasi (Masuk / Keluar).',
            'category_reason.required' => 'Pilih atau isi kategori / alasan mutasi.',
            'items.required'           => 'Minimal 1 produk harus dipilih.',
            'items.*.quantity.min'     => 'Jumlah barang minimal 1 unit.',
        ]);

        $type = $validated['type'];
        $categoryReason = trim($validated['category_reason']);
        $notes = trim($validated['notes'] ?? '');
        $mutationDate = $validated['date'] ? $validated['date'] . ' ' . date('H:i:s') : null;

        $typeLabel = $type === 'in' ? 'Barang Masuk' : 'Barang Keluar';
        $fullReference = "Mutasi {$typeLabel} Gudang Jadi ({$categoryReason})" . ($notes ? " - {$notes}" : "");

        DB::beginTransaction();
        try {
            $processedCount = 0;

            foreach ($validated['items'] as $item) {
                $product = MasterProduct::where('tenant_id', $tenantId)
                    ->where('id', $item['product_id'])
                    ->firstOrFail();

                $qty = (int) $item['quantity'];

                // Sanitasi pergerakan stok: Method recordStockMovement meng-handle stok lokal,
                // stock_movements ledger, serta otomatis push sync ke marketplace.
                $product->recordStockMovement(
                    $qty,
                    $type,
                    $fullReference,
                    Auth::id(),
                    $mutationDate
                );

                $processedCount++;
            }

            DB::commit();

            return redirect()->route('inventory.mutations.index')
                ->with('success', "Berhasil mencatat Mutasi {$typeLabel} untuk {$processedCount} item produk master.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan mutasi: ' . $e->getMessage());
        }
    }
}
