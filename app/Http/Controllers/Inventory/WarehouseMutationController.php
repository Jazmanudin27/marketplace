<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\WarehouseMutation;
use App\Models\WarehouseMutationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WarehouseMutationController extends Controller
{
    /**
     * Tampilkan daftar barang masuk (WMI).
     */
    public function indexIn(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = WarehouseMutation::with(['fromDepartment', 'toDepartment', 'goodsReceipt'])
            ->where('tenant_id', $tenantId)
            ->where('type', 'in')
            ->orderByDesc('mutation_date');

        if ($request->filled('search')) {
            $query->where('mutation_number', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('department_id')) {
            $query->where('from_department_id', $request->department_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('mutation_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('mutation_date', '<=', $request->date_to);
        }

        $mutations   = $query->paginate(20)->withQueryString();
        $departments = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('inventory.warehouse_mutations.index_in', compact('mutations', 'departments'));
    }

    /**
     * Tampilkan daftar barang keluar (WMO).
     */
    public function indexOut(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = WarehouseMutation::with(['fromDepartment', 'toDepartment'])
            ->where('tenant_id', $tenantId)
            ->where('type', 'out')
            ->orderByDesc('mutation_date');

        if ($request->filled('search')) {
            $query->where('mutation_number', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('department_id')) {
            $query->where('to_department_id', $request->department_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('mutation_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('mutation_date', '<=', $request->date_to);
        }

        $mutations   = $query->paginate(20)->withQueryString();
        $departments = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('inventory.warehouse_mutations.index_out', compact('mutations', 'departments'));
    }

    /**
     * Form buat Barang Masuk baru.
     */
    public function createIn()
    {
        $tenantId = Auth::user()->tenant_id;
        $departments = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        // Hanya barang bertipe bahan & kemasan
        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('type', ['bahan', 'kemasan'])
            ->orderBy('name')
            ->get();

        return view('inventory.warehouse_mutations.create_in', compact('departments', 'inventoryItems'));
    }

    /**
     * Form buat Barang Keluar baru.
     */
    public function createOut()
    {
        $tenantId = Auth::user()->tenant_id;
        $departments = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        // Hanya barang bertipe bahan & kemasan dengan stok > 0
        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('type', ['bahan', 'kemasan'])
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('inventory.warehouse_mutations.create_out', compact('departments', 'inventoryItems'));
    }

    /**
     * Simpan transaksi Barang Masuk (Stok bertambah).
     */
    public function storeIn(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'from_department_id' => 'nullable|exists:departments,id',
            'to_department_id'   => 'nullable|exists:departments,id',
            'mutation_date'      => 'required|date',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'required|exists:inventory_items,id',
            'items.*.quantity'   => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $mutation = DB::transaction(function () use ($request, $tenantId) {
            $userId         = Auth::id();
            $mutationNumber = WarehouseMutation::generateMutationNumber('in');

            $mutation = WarehouseMutation::create([
                'tenant_id'          => $tenantId,
                'mutation_number'    => $mutationNumber,
                'type'               => 'in',
                'from_department_id' => $request->from_department_id,
                'to_department_id'   => $request->to_department_id,
                'mutation_date'      => $request->mutation_date,
                'status'             => 'approved', // langsung disetujui untuk mutasi manual
                'notes'              => $request->notes,
                'created_by'         => $userId,
            ]);

            foreach ($request->items as $row) {
                $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($row['item_id']);
                $qty  = (int) $row['quantity'];

                $mutation->items()->create([
                    'inventory_item_id' => $item->id,
                    'quantity'          => $qty,
                    'unit_price'        => $row['unit_price'],
                    'notes'             => $row['notes'] ?? null,
                ]);

                // Tambah stok item
                $item->increment('stock', $qty);
                $newStock = $item->fresh()->stock;

                // Catat mutasi detail
                StockMovement::create([
                    'tenant_id'             => $tenantId,
                    'inventory_item_id'     => $item->id,
                    'department_id'         => $request->to_department_id,
                    'warehouse_mutation_id' => $mutation->id,
                    'user_id'               => $userId,
                    'type'                  => 'in',
                    'quantity'              => $qty,
                    'reference'             => 'Barang Masuk Gudang (' . $mutationNumber . ')',
                    'balance_after'         => $newStock,
                ]);
            }

            return $mutation;
        });

        return redirect()->route('warehouse_mutations.show', $mutation)
            ->with('success', 'Transaksi Barang Masuk berhasil disimpan. Stok telah bertambah.');
    }

    /**
     * Simpan transaksi Barang Keluar (Stok berkurang).
     */
    public function storeOut(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'from_department_id' => 'nullable|exists:departments,id',
            'to_department_id'   => 'nullable|exists:departments,id',
            'mutation_date'      => 'required|date',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'required|exists:inventory_items,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        // Cek kecukupan stok sebelum menyimpan
        foreach ($request->items as $row) {
            $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($row['item_id']);
            if ($item->stock < (int)$row['quantity']) {
                return back()->withInput()->withErrors(['items' => 'Stok barang ' . $item->name . ' tidak mencukupi. Sisa stok: ' . $item->stock]);
            }
        }

        $mutation = DB::transaction(function () use ($request, $tenantId) {
            $userId         = Auth::id();
            $mutationNumber = WarehouseMutation::generateMutationNumber('out');

            $mutation = WarehouseMutation::create([
                'tenant_id'          => $tenantId,
                'mutation_number'    => $mutationNumber,
                'type'               => 'out',
                'from_department_id' => $request->from_department_id,
                'to_department_id'   => $request->to_department_id,
                'mutation_date'      => $request->mutation_date,
                'status'             => 'approved',
                'notes'              => $request->notes,
                'created_by'         => $userId,
            ]);

            foreach ($request->items as $row) {
                $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($row['item_id']);
                $qty  = (int) $row['quantity'];

                $mutation->items()->create([
                    'inventory_item_id' => $item->id,
                    'quantity'          => $qty,
                    'unit_price'        => $item->cost_price ?: 0,
                    'notes'             => $row['notes'] ?? null,
                ]);

                // Kurangi stok item
                $item->decrement('stock', $qty);
                $newStock = $item->fresh()->stock;

                // Catat mutasi detail
                StockMovement::create([
                    'tenant_id'             => $tenantId,
                    'inventory_item_id'     => $item->id,
                    'department_id'         => $request->from_department_id,
                    'warehouse_mutation_id' => $mutation->id,
                    'user_id'               => $userId,
                    'type'                  => 'out',
                    'quantity'              => -$qty,
                    'reference'             => 'Barang Keluar Gudang (' . $mutationNumber . ')',
                    'balance_after'         => $newStock,
                ]);
            }

            return $mutation;
        });

        return redirect()->route('warehouse_mutations.show', $mutation)
            ->with('success', 'Transaksi Barang Keluar berhasil disimpan. Stok telah dikurangi.');
    }

    /**
     * Tampilkan detail transaksi mutasi.
     */
    public function show(WarehouseMutation $warehouseMutation)
    {
        abort_unless($warehouseMutation->tenant_id === Auth::user()->tenant_id, 403);
        $warehouseMutation->load(['fromDepartment', 'toDepartment', 'goodsReceipt', 'items.inventoryItem', 'createdBy']);

        return view('inventory.warehouse_mutations.show', compact('warehouseMutation'));
    }

    /**
     * Hapus / batalkan mutasi (Mengembalikan efek stok).
     */
    public function destroy(WarehouseMutation $warehouseMutation)
    {
        abort_unless($warehouseMutation->tenant_id === Auth::user()->tenant_id, 403);

        DB::transaction(function () use ($warehouseMutation) {
            $tenantId = Auth::user()->tenant_id;
            $userId   = Auth::id();

            // Reverse stok
            foreach ($warehouseMutation->items as $item) {
                $invItem = $item->inventoryItem;
                if (!$invItem) continue;

                if ($warehouseMutation->type === 'in') {
                    // Masuk dibatalkan -> kurangi stok
                    $invItem->decrement('stock', $item->quantity);
                    $newStock = $invItem->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'inventory_item_id' => $invItem->id,
                        'user_id'           => $userId,
                        'type'              => 'adjustment',
                        'quantity'          => -$item->quantity,
                        'reference'         => 'Batal Barang Masuk (' . $warehouseMutation->mutation_number . ')',
                        'balance_after'     => $newStock,
                    ]);
                } else {
                    // Keluar dibatalkan -> tambah stok kembali
                    $invItem->increment('stock', $item->quantity);
                    $newStock = $invItem->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'inventory_item_id' => $invItem->id,
                        'user_id'           => $userId,
                        'type'              => 'adjustment',
                        'quantity'          => $item->quantity,
                        'reference'         => 'Batal Barang Keluar (' . $warehouseMutation->mutation_number . ')',
                        'balance_after'     => $newStock,
                    ]);
                }
            }

            $warehouseMutation->delete();
        });

        $redirectRoute = $warehouseMutation->type === 'in' ? 'warehouse_mutations.index_in' : 'warehouse_mutations.index_out';
        return redirect()->route($redirectRoute)
            ->with('success', 'Transaksi mutasi berhasil dibatalkan dan stok telah disesuaikan.');
    }

    /**
     * ==========================================
     * LAPORAN-LAPORAN GUDANG BAHAN KEMASAN
     * ==========================================
     */

    /**
     * Halaman & Pencarian Laporan Barang Masuk & Keluar.
     */
    public function reportMutation(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $type = $request->input('type', 'all'); // all, in, out
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));

        $query = WarehouseMutationItem::whereHas('warehouseMutation', function ($q) use ($tenantId, $type, $dateFrom, $dateTo) {
            $q->where('tenant_id', $tenantId)
              ->whereBetween('mutation_date', [$dateFrom, $dateTo]);

            if ($type !== 'all') {
                $q->where('type', $type);
            }
        })->with(['warehouseMutation.fromDepartment', 'warehouseMutation.toDepartment', 'inventoryItem']);

        $items = $query->orderByDesc('id')->get();

        return view('inventory.warehouse_mutations.report_mutation', compact('items', 'type', 'dateFrom', 'dateTo'));
    }

    /**
     * Cetak Laporan Barang Masuk & Keluar.
     */
    public function printReportMutation(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $type = $request->input('type', 'all');
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));

        $query = WarehouseMutationItem::whereHas('warehouseMutation', function ($q) use ($tenantId, $type, $dateFrom, $dateTo) {
            $q->where('tenant_id', $tenantId)
              ->whereBetween('mutation_date', [$dateFrom, $dateTo]);

            if ($type !== 'all') {
                $q->where('type', $type);
            }
        })->with(['warehouseMutation.fromDepartment', 'warehouseMutation.toDepartment', 'inventoryItem']);

        $items = $query->orderByDesc('id')->get();

        return view('inventory.warehouse_mutations.print_report_mutation', compact('items', 'type', 'dateFrom', 'dateTo'));
    }

    /**
     * Laporan Rekap Persediaan Gudang Bahan & Kemasan.
     */
    public function reportSummary(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Ambil stok awal dan mutasi dalam rentang tanggal
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));

        $items = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan'])
            ->orderBy('name')
            ->get();

        $rekap = [];

        foreach ($items as $item) {
            // Qty Masuk dalam periode
            $inQty = StockMovement::where('inventory_item_id', $item->id)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('quantity', '>', 0)
                ->sum('quantity');

            // Qty Keluar dalam periode (disimpan negatif, kita abs)
            $outQty = abs(StockMovement::where('inventory_item_id', $item->id)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('quantity', '<', 0)
                ->sum('quantity'));

            // Hitung Stok Awal: Stok saat ini - Qty Masuk + Qty Keluar (periode ini sampai sekarang)
            // Namun untuk kesederhanaan rekap: Stok Awal = Stok Akhir - Masuk + Keluar
            $stokAkhir = $item->stock;
            $stokAwal  = $stokAkhir - $inQty + $outQty;

            $rekap[] = [
                'sku'         => $item->sku,
                'name'        => $item->name,
                'unit'        => $item->unit,
                'type'        => $item->type,
                'stok_awal'   => $stokAwal,
                'qty_masuk'   => $inQty,
                'qty_keluar'  => $outQty,
                'stok_akhir'  => $stokAkhir,
                'cost_price'  => $item->cost_price,
                'total_value' => $stokAkhir * ($item->cost_price ?: 0),
            ];
        }

        return view('inventory.warehouse_mutations.report_summary', compact('rekap', 'dateFrom', 'dateTo'));
    }

    /**
     * Cetak Laporan Rekap Persediaan.
     */
    public function printReportSummary(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo = $request->input('date_to', date('Y-m-d'));

        $items = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan'])
            ->orderBy('name')
            ->get();

        $rekap = [];
        foreach ($items as $item) {
            $inQty = StockMovement::where('inventory_item_id', $item->id)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('quantity', '>', 0)
                ->sum('quantity');

            $outQty = abs(StockMovement::where('inventory_item_id', $item->id)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('quantity', '<', 0)
                ->sum('quantity'));

            $stokAkhir = $item->stock;
            $stokAwal  = $stokAkhir - $inQty + $outQty;

            $rekap[] = [
                'sku'         => $item->sku,
                'name'        => $item->name,
                'unit'        => $item->unit,
                'type'        => $item->type,
                'stok_awal'   => $stokAwal,
                'qty_masuk'   => $inQty,
                'qty_keluar'  => $outQty,
                'stok_akhir'  => $stokAkhir,
                'cost_price'  => $item->cost_price,
                'total_value' => $stokAkhir * ($item->cost_price ?: 0),
            ];
        }

        return view('inventory.warehouse_mutations.print_report_summary', compact('rekap', 'dateFrom', 'dateTo'));
    }

    /**
     * Laporan Stok Gudang Bahan & Kemasan (Read-Only).
     */
    public function stockReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan']);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $items = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('inventory.warehouse_mutations.stock_report', compact('items'));
    }

    /**
     * Cetak Laporan Stok Gudang Bahan & Kemasan.
     */
    public function printStockReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan']);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $items = $query->orderBy('name')->get();

        return view('inventory.warehouse_mutations.print_stock_report', compact('items'));
    }
}
