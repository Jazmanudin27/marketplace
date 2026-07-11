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
     * ==========================================
     * HELPER & OTORISASI PRODUKSI
     * ==========================================
     */

    private function authorizeProduksi()
    {
        $user = Auth::user();
        if ($user->role === 'super-admin' || $user->role === 'owner' || $user->hasRole('owner')) {
            return;
        }
        if ($user->role === 'admin' || $user->hasRole('admin')) {
            return;
        }
        if ($user->hasAnyPermission(['manage-inventory', 'production-orders.index'])) {
            return;
        }
        abort(403, 'Akses Ditolak: Anda tidak memiliki akses ke Modul Produksi.');
    }

    private function getProduksiDepartmentId()
    {
        $tenantId = Auth::user()->tenant_id;
        $dept = Department::where('tenant_id', $tenantId)
            ->where(function($q) {
                $q->where('name', 'like', '%produksi%')
                  ->orWhere('code', 'like', '%produksi%');
            })
            ->first();
            
        if (!$dept) {
            $dept = Department::create([
                'tenant_id' => $tenantId,
                'name'      => 'Produksi',
                'code'      => 'PRODUKSI',
                'is_active' => true,
            ]);
        }
        return $dept->id;
    }

    /**
     * ==========================================
     * MODUL MUTASI PRODUKSI
     * ==========================================
     */

    /**
     * Daftar pending approval dari Gudang ke Produksi.
     */
    public function pendingApprovalsProduksi(Request $request)
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        $produksiDeptId = $this->getProduksiDepartmentId();

        $query = WarehouseMutation::with(['fromDepartment', 'toDepartment', 'createdBy', 'items.inventoryItem'])
            ->where('tenant_id', $tenantId)
            ->where('type', 'out')
            ->where('to_department_id', $produksiDeptId)
            ->where('status', 'pending')
            ->orderByDesc('mutation_date');

        if ($request->filled('search')) {
            $query->where('mutation_number', 'like', '%' . $request->search . '%');
        }

        $mutations = $query->paginate(20)->withQueryString();

        return view('inventory.produksi_mutations.pending_approvals', compact('mutations'));
    }

    /**
     * Proses approval pengiriman barang ke Produksi.
     */
    public function approveProduksi(Request $request, WarehouseMutation $warehouseMutation)
    {
        $this->authorizeProduksi();
        abort_unless($warehouseMutation->tenant_id === Auth::user()->tenant_id, 403);
        
        $produksiDeptId = $this->getProduksiDepartmentId();
        abort_unless($warehouseMutation->to_department_id == $produksiDeptId, 403);
        abort_unless($warehouseMutation->status === 'pending', 400, 'Transaksi ini tidak berstatus pending.');

        $newMutation = DB::transaction(function() use ($warehouseMutation, $produksiDeptId) {
            $userId = Auth::id();
            $tenantId = Auth::user()->tenant_id;

            // 1. Update status mutasi keluar asal (WMO) menjadi approved
            $warehouseMutation->update([
                'status' => 'approved'
            ]);

            // 2. Buat mutasi masuk (WMI) untuk Produksi
            $wmiNumber = WarehouseMutation::generateMutationNumber('in');
            $wmi = WarehouseMutation::create([
                'tenant_id'          => $tenantId,
                'mutation_number'    => $wmiNumber,
                'type'               => 'in',
                'from_department_id' => $warehouseMutation->from_department_id,
                'to_department_id'   => $produksiDeptId,
                'mutation_date'      => now(),
                'status'             => 'approved',
                'notes'              => 'Penerimaan otomatis dari pengeluaran ' . $warehouseMutation->mutation_number,
                'created_by'         => $userId,
            ]);

            // 3. Salin item dan pulihkan stok global (karena stok global sempat berkurang saat WMO)
            foreach ($warehouseMutation->items as $wmoItem) {
                $item = $wmoItem->inventoryItem;
                if (!$item) continue;

                $qty = $wmoItem->quantity;

                $wmi->items()->create([
                    'inventory_item_id' => $item->id,
                    'quantity'          => $qty,
                    'unit_price'        => $wmoItem->unit_price,
                    'notes'             => $wmoItem->notes,
                ]);

                // Pulihkan stok global
                $item->increment('stock', $qty);
                $newStock = $item->fresh()->stock;

                // Catat mutasi detail masuk di Produksi
                StockMovement::create([
                    'tenant_id'             => $tenantId,
                    'inventory_item_id'     => $item->id,
                    'department_id'         => $produksiDeptId,
                    'warehouse_mutation_id' => $wmi->id,
                    'user_id'               => $userId,
                    'type'                  => 'in',
                    'quantity'              => $qty,
                    'reference'             => 'Barang Masuk Produksi (' . $wmiNumber . ')',
                    'balance_after'         => $newStock,
                ]);
            }

            return $wmi;
        });

        return redirect()->route('produksi_mutations.show', $newMutation)
            ->with('success', 'Pengiriman barang telah disetujui dan masuk ke mutasi produksi.');
    }

    /**
     * Daftar Barang Masuk Produksi.
     */
    public function indexInProduksi(Request $request)
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        $produksiDeptId = $this->getProduksiDepartmentId();

        $query = WarehouseMutation::with(['fromDepartment', 'toDepartment', 'goodsReceipt'])
            ->where('tenant_id', $tenantId)
            ->where('type', 'in')
            ->where('to_department_id', $produksiDeptId)
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

        return view('inventory.produksi_mutations.index_in', compact('mutations', 'departments'));
    }

    /**
     * Daftar Barang Keluar / Konsumsi Produksi.
     */
    public function indexOutProduksi(Request $request)
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        $produksiDeptId = $this->getProduksiDepartmentId();

        $query = WarehouseMutation::with(['fromDepartment', 'toDepartment'])
            ->where('tenant_id', $tenantId)
            ->where('type', 'out')
            ->where('from_department_id', $produksiDeptId)
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

        return view('inventory.produksi_mutations.index_out', compact('mutations', 'departments'));
    }

    /**
     * Form manual Barang Masuk Produksi.
     */
    public function createInProduksi()
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        $departments = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        
        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('inventory.produksi_mutations.create_in', compact('departments', 'inventoryItems'));
    }

    /**
     * Form manual Barang Keluar Produksi.
     */
    public function createOutProduksi()
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        $departments = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        
        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('inventory.produksi_mutations.create_out', compact('departments', 'inventoryItems'));
    }

    /**
     * Simpan manual Barang Masuk Produksi.
     */
    public function storeInProduksi(Request $request)
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        $produksiDeptId = $this->getProduksiDepartmentId();

        $request->validate([
            'from_department_id' => 'nullable|exists:departments,id',
            'mutation_date'      => 'required|date',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'required|exists:inventory_items,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        $mutation = DB::transaction(function () use ($request, $tenantId, $produksiDeptId) {
            $userId         = Auth::id();
            $mutationNumber = WarehouseMutation::generateMutationNumber('in');

            $mutation = WarehouseMutation::create([
                'tenant_id'          => $tenantId,
                'mutation_number'    => $mutationNumber,
                'type'               => 'in',
                'from_department_id' => $request->from_department_id,
                'to_department_id'   => $produksiDeptId,
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
                    'unit_price'        => 0,
                    'notes'             => $row['notes'] ?? null,
                ]);

                $item->increment('stock', $qty);
                $newStock = $item->fresh()->stock;

                StockMovement::create([
                    'tenant_id'             => $tenantId,
                    'inventory_item_id'     => $item->id,
                    'department_id'         => $produksiDeptId,
                    'warehouse_mutation_id' => $mutation->id,
                    'user_id'               => $userId,
                    'type'                  => 'in',
                    'quantity'              => $qty,
                    'reference'             => 'Barang Masuk Produksi manual (' . $mutationNumber . ')',
                    'balance_after'         => $newStock,
                ]);
            }

            return $mutation;
        });

        return redirect()->route('produksi_mutations.show', $mutation)
            ->with('success', 'Barang Masuk Produksi berhasil disimpan. Stok telah bertambah.');
    }

    /**
     * Simpan manual Barang Keluar Produksi.
     */
    public function storeOutProduksi(Request $request)
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        $produksiDeptId = $this->getProduksiDepartmentId();

        $request->validate([
            'to_department_id'   => 'nullable|exists:departments,id',
            'mutation_date'      => 'required|date',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.item_id'    => 'required|exists:inventory_items,id',
            'items.*.quantity'   => 'required|integer|min:1',
        ]);

        foreach ($request->items as $row) {
            $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($row['item_id']);
            if ($item->stock < (int)$row['quantity']) {
                return back()->withInput()->withErrors(['items' => 'Stok ' . $item->name . ' tidak mencukupi. Sisa: ' . $item->stock]);
            }
        }

        $mutation = DB::transaction(function () use ($request, $tenantId, $produksiDeptId) {
            $userId         = Auth::id();
            $mutationNumber = WarehouseMutation::generateMutationNumber('out');

            $mutation = WarehouseMutation::create([
                'tenant_id'          => $tenantId,
                'mutation_number'    => $mutationNumber,
                'type'               => 'out',
                'from_department_id' => $produksiDeptId,
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
                    'unit_price'        => 0,
                    'notes'             => $row['notes'] ?? null,
                ]);

                $item->decrement('stock', $qty);
                $newStock = $item->fresh()->stock;

                StockMovement::create([
                    'tenant_id'             => $tenantId,
                    'inventory_item_id'     => $item->id,
                    'department_id'         => $produksiDeptId,
                    'warehouse_mutation_id' => $mutation->id,
                    'user_id'               => $userId,
                    'type'                  => 'out',
                    'quantity'              => -$qty,
                    'reference'             => 'Barang Keluar Produksi (' . $mutationNumber . ')',
                    'balance_after'         => $newStock,
                ]);
            }

            return $mutation;
        });

        return redirect()->route('produksi_mutations.show', $mutation)
            ->with('success', 'Barang Keluar Produksi berhasil disimpan. Stok telah dikurangi.');
    }

    /**
     * Detail mutasi Produksi.
     */
    public function showProduksi(WarehouseMutation $warehouseMutation)
    {
        $this->authorizeProduksi();
        abort_unless($warehouseMutation->tenant_id === Auth::user()->tenant_id, 403);
        $warehouseMutation->load(['fromDepartment', 'toDepartment', 'goodsReceipt', 'items.inventoryItem', 'createdBy']);

        return view('inventory.produksi_mutations.show', compact('warehouseMutation'));
    }

    /**
     * Laporan Stok Produksi.
     */
    public function stockReportProduksi(Request $request)
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $items = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('inventory.produksi_mutations.stock_report', compact('items'));
    }

    /**
     * Cetak Laporan Stok Produksi.
     */
    public function printStockReportProduksi(Request $request)
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris']);

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        $items = $query->orderBy('name')->get();

        return view('inventory.produksi_mutations.print_stock_report', compact('items'));
    }

    /**
     * Laporan Barang Masuk & Keluar Produksi.
     */
    public function reportMutationProduksi(Request $request)
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        $produksiDeptId = $this->getProduksiDepartmentId();
        $type     = $request->input('type', 'all');
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));

        $query = WarehouseMutationItem::whereHas('warehouseMutation', function ($q) use ($tenantId, $type, $dateFrom, $dateTo, $produksiDeptId) {
            $q->where('tenant_id', $tenantId)
              ->whereBetween('mutation_date', [$dateFrom, $dateTo])
              ->where(function($sub) use ($produksiDeptId) {
                  $sub->where('from_department_id', $produksiDeptId)
                      ->orWhere('to_department_id', $produksiDeptId);
              });
            if ($type !== 'all') {
                $q->where('type', $type);
            }
        })->with(['warehouseMutation.fromDepartment', 'warehouseMutation.toDepartment', 'inventoryItem']);

        $items = $query->orderByDesc('id')->get();

        return view('inventory.produksi_mutations.report_mutation', compact('items', 'type', 'dateFrom', 'dateTo'));
    }

    /**
     * Cetak Laporan Barang Masuk & Keluar Produksi.
     */
    public function printReportMutationProduksi(Request $request)
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        $produksiDeptId = $this->getProduksiDepartmentId();
        $type     = $request->input('type', 'all');
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));

        $query = WarehouseMutationItem::whereHas('warehouseMutation', function ($q) use ($tenantId, $type, $dateFrom, $dateTo, $produksiDeptId) {
            $q->where('tenant_id', $tenantId)
              ->whereBetween('mutation_date', [$dateFrom, $dateTo])
              ->where(function($sub) use ($produksiDeptId) {
                  $sub->where('from_department_id', $produksiDeptId)
                      ->orWhere('to_department_id', $produksiDeptId);
              });
            if ($type !== 'all') {
                $q->where('type', $type);
            }
        })->with(['warehouseMutation.fromDepartment', 'warehouseMutation.toDepartment', 'inventoryItem']);

        $items = $query->orderByDesc('id')->get();

        return view('inventory.produksi_mutations.print_report_mutation', compact('items', 'type', 'dateFrom', 'dateTo'));
    }

    /**
     * Rekap Persediaan Produksi.
     */
    public function reportSummaryProduksi(Request $request)
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        $produksiDeptId = $this->getProduksiDepartmentId();
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));

        $items = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris'])
            ->orderBy('name')
            ->get();

        $rekap = [];
        foreach ($items as $item) {
            $inQty = StockMovement::where('inventory_item_id', $item->id)
                ->where('department_id', $produksiDeptId)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('quantity', '>', 0)
                ->sum('quantity');

            $outQty = abs(StockMovement::where('inventory_item_id', $item->id)
                ->where('department_id', $produksiDeptId)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('quantity', '<', 0)
                ->sum('quantity'));

            $totalInAllTime = StockMovement::where('inventory_item_id', $item->id)
                ->where('department_id', $produksiDeptId)
                ->where('quantity', '>', 0)
                ->sum('quantity');

            $totalOutAllTime = abs(StockMovement::where('inventory_item_id', $item->id)
                ->where('department_id', $produksiDeptId)
                ->where('quantity', '<', 0)
                ->sum('quantity'));

            $stokAkhir = $totalInAllTime - $totalOutAllTime;
            $stokAwal  = $stokAkhir - $inQty + $outQty;

            $rekap[] = [
                'sku'        => $item->sku,
                'name'       => $item->name,
                'unit'       => $item->unit,
                'type'       => $item->type,
                'stok_awal'  => $stokAwal,
                'qty_masuk'  => $inQty,
                'qty_keluar' => $outQty,
                'stok_akhir' => $stokAkhir,
            ];
        }

        return view('inventory.produksi_mutations.report_summary', compact('rekap', 'dateFrom', 'dateTo'));
    }

    /**
     * Cetak Rekap Persediaan Produksi.
     */
    public function printReportSummaryProduksi(Request $request)
    {
        $this->authorizeProduksi();
        $tenantId = Auth::user()->tenant_id;
        $produksiDeptId = $this->getProduksiDepartmentId();
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));

        $items = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris'])
            ->orderBy('name')
            ->get();

        $rekap = [];
        foreach ($items as $item) {
            $inQty = StockMovement::where('inventory_item_id', $item->id)
                ->where('department_id', $produksiDeptId)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('quantity', '>', 0)
                ->sum('quantity');

            $outQty = abs(StockMovement::where('inventory_item_id', $item->id)
                ->where('department_id', $produksiDeptId)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('quantity', '<', 0)
                ->sum('quantity'));

            $totalInAllTime = StockMovement::where('inventory_item_id', $item->id)
                ->where('department_id', $produksiDeptId)
                ->where('quantity', '>', 0)
                ->sum('quantity');

            $totalOutAllTime = abs(StockMovement::where('inventory_item_id', $item->id)
                ->where('department_id', $produksiDeptId)
                ->where('quantity', '<', 0)
                ->sum('quantity'));

            $stokAkhir = $totalInAllTime - $totalOutAllTime;
            $stokAwal  = $stokAkhir - $inQty + $outQty;

            $rekap[] = [
                'sku'        => $item->sku,
                'name'       => $item->name,
                'unit'       => $item->unit,
                'type'       => $item->type,
                'stok_awal'  => $stokAwal,
                'qty_masuk'  => $inQty,
                'qty_keluar' => $outQty,
                'stok_akhir' => $stokAkhir,
            ];
        }

        return view('inventory.produksi_mutations.print_report_summary', compact('rekap', 'dateFrom', 'dateTo'));
    }

    // ==========================================
    // LAPORAN-LAPORAN PEMBELIAN (semua tipe barang)
    // ==========================================

    public function stockReportPembelian(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris']);
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        $items = $query->orderBy('type')->orderBy('name')->paginate(25)->withQueryString();
        return view('inventory.pembelian.stock_report', compact('items'));
    }

    public function printStockReportPembelian(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris']);
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }
        $items = $query->orderBy('type')->orderBy('name')->get();
        return view('inventory.pembelian.print_stock_report', compact('items'));
    }

    public function reportMutationPembelian(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $type     = $request->input('type', 'all');
        $itemType = $request->input('item_type', 'all');
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));
        $query = WarehouseMutationItem::whereHas('warehouseMutation', function ($q) use ($tenantId, $type, $dateFrom, $dateTo) {
            $q->where('tenant_id', $tenantId)->whereBetween('mutation_date', [$dateFrom, $dateTo]);
            if ($type !== 'all') $q->where('type', $type);
        })->whereHas('inventoryItem', function ($q) use ($itemType) {
            $q->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris']);
            if ($itemType !== 'all') $q->where('type', $itemType);
        })->with(['warehouseMutation.fromDepartment', 'warehouseMutation.toDepartment', 'inventoryItem']);
        $mutations = $query->orderByDesc('id')->paginate(30)->withQueryString();
        return view('inventory.pembelian.report_mutation', compact('mutations', 'type', 'itemType', 'dateFrom', 'dateTo'));
    }

    public function printReportMutationPembelian(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $type     = $request->input('type', 'all');
        $itemType = $request->input('item_type', 'all');
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));
        $query = WarehouseMutationItem::whereHas('warehouseMutation', function ($q) use ($tenantId, $type, $dateFrom, $dateTo) {
            $q->where('tenant_id', $tenantId)->whereBetween('mutation_date', [$dateFrom, $dateTo]);
            if ($type !== 'all') $q->where('type', $type);
        })->whereHas('inventoryItem', function ($q) use ($itemType) {
            $q->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris']);
            if ($itemType !== 'all') $q->where('type', $itemType);
        })->with(['warehouseMutation.fromDepartment', 'warehouseMutation.toDepartment', 'inventoryItem']);
        $mutations = $query->orderByDesc('id')->get();
        return view('inventory.pembelian.print_report_mutation', compact('mutations', 'type', 'itemType', 'dateFrom', 'dateTo'));
    }

    public function reportSummaryPembelian(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $itemType = $request->input('item_type', 'all');
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris'])
            ->orderBy('type')->orderBy('name');
        if ($itemType !== 'all') $query->where('type', $itemType);
        $rekap = [];
        foreach ($query->get() as $item) {
            $inQty  = StockMovement::where('inventory_item_id', $item->id)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('quantity', '>', 0)->sum('quantity');
            $outQty = abs(StockMovement::where('inventory_item_id', $item->id)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('quantity', '<', 0)->sum('quantity'));
            $stokAkhir = $item->stock;
            $stokAwal  = max(0, $stokAkhir - $inQty + $outQty);
            $rekap[] = ['sku' => $item->sku, 'name' => $item->name, 'unit' => $item->unit,
                'type' => $item->type, 'stok_awal' => $stokAwal,
                'qty_masuk' => $inQty, 'qty_keluar' => $outQty, 'stok_akhir' => $stokAkhir,
                'cost_price' => $item->cost_price ?? 0,
                'total_value' => $stokAkhir * ($item->cost_price ?? 0)];
        }
        return view('inventory.pembelian.report_summary', compact('rekap', 'itemType', 'dateFrom', 'dateTo'));
    }

    public function printReportSummaryPembelian(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $itemType = $request->input('item_type', 'all');
        $dateFrom = $request->input('date_from', date('Y-m-01'));
        $dateTo   = $request->input('date_to', date('Y-m-d'));
        $query = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris'])
            ->orderBy('type')->orderBy('name');
        if ($itemType !== 'all') $query->where('type', $itemType);
        $rekap = [];
        foreach ($query->get() as $item) {
            $inQty  = StockMovement::where('inventory_item_id', $item->id)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('quantity', '>', 0)->sum('quantity');
            $outQty = abs(StockMovement::where('inventory_item_id', $item->id)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->where('quantity', '<', 0)->sum('quantity'));
            $stokAkhir = $item->stock;
            $stokAwal  = max(0, $stokAkhir - $inQty + $outQty);
            $rekap[] = ['sku' => $item->sku, 'name' => $item->name, 'unit' => $item->unit,
                'type' => $item->type, 'stok_awal' => $stokAwal,
                'qty_masuk' => $inQty, 'qty_keluar' => $outQty, 'stok_akhir' => $stokAkhir,
                'cost_price' => $item->cost_price ?? 0,
                'total_value' => $stokAkhir * ($item->cost_price ?? 0)];
        }
        return view('inventory.pembelian.print_report_summary', compact('rekap', 'itemType', 'dateFrom', 'dateTo'));
    }

    public function stockCardPembelian(Request $request)
    {
        $tenantId       = Auth::user()->tenant_id;
        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris'])
            ->orderBy('type')->orderBy('name')->get();
        $movements    = collect();
        $selectedItem = null;
        $dateFrom     = $request->input('date_from', date('Y-m-01'));
        $dateTo       = $request->input('date_to', date('Y-m-d'));
        if ($request->filled('item_id')) {
            $selectedItem = InventoryItem::where('tenant_id', $tenantId)->findOrFail($request->item_id);
            $movements = StockMovement::where('inventory_item_id', $selectedItem->id)
                ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->with('department')->orderBy('created_at')->get();
        }
        return view('inventory.pembelian.stock_card', compact('inventoryItems', 'selectedItem', 'movements', 'dateFrom', 'dateTo'));
    }

    public function printStockCardPembelian(Request $request)
    {
        $tenantId     = Auth::user()->tenant_id;
        $dateFrom     = $request->input('date_from', date('Y-m-01'));
        $dateTo       = $request->input('date_to', date('Y-m-d'));
        $selectedItem = InventoryItem::where('tenant_id', $tenantId)->findOrFail($request->item_id);
        $movements    = StockMovement::where('inventory_item_id', $selectedItem->id)
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->with('department')->orderBy('created_at')->get();
        return view('inventory.pembelian.print_stock_card', compact('selectedItem', 'movements', 'dateFrom', 'dateTo'));
    }

    // ==========================================
    // PENGELUARAN BARANG (Goods Issue) PEMBELIAN
    // ==========================================

    public function goodsIssueIndex(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = WarehouseMutation::with(['items.inventoryItem', 'toDepartment'])
            ->where('tenant_id', $tenantId)
            ->where('type', 'out')
            ->orderByDesc('mutation_date');

        if ($request->filled('search')) {
            $query->where('mutation_number', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('mutation_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('mutation_date', '<=', $request->date_to);
        }

        $mutations = $query->paginate(20)->withQueryString();
        return view('inventory.pembelian.goods_issue.index', compact('mutations'));
    }

    public function goodsIssueCreate()
    {
        $tenantId = Auth::user()->tenant_id;
        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris'])
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        return view('inventory.pembelian.goods_issue.create', compact('inventoryItems'));
    }

    private function getDepartmentIdByName($name)
    {
        $tenantId = Auth::user()->tenant_id;
        $dept = Department::where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();
            
        if (!$dept) {
            $dept = Department::create([
                'tenant_id' => $tenantId,
                'name'      => $name,
                'code'      => strtoupper(str_replace(' ', '_', $name)),
                'is_active' => true,
            ]);
        }
        return $dept->id;
    }

    public function goodsIssueStore(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'mutation_date'    => 'required|date',
            'tujuan'           => 'required|in:produksi,percetakan,lain_lain',
            'notes'            => 'nullable|string|max:1000',
            'items'            => 'required|array|min:1',
            'items.*.item_id'  => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        foreach ($request->items as $row) {
            $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($row['item_id']);
            if ($item->stock < (int)$row['quantity']) {
                return back()->withInput()->withErrors(['items' => 'Stok barang ' . $item->name . ' tidak mencukupi. Sisa stok: ' . $item->stock]);
            }
        }

        $mutation = DB::transaction(function () use ($request, $tenantId) {
            $userId         = Auth::id();
            $mutationNumber = WarehouseMutation::generateMutationNumber('out');

            $toDeptId = null;
            if ($request->tujuan === 'produksi') {
                $toDeptId = $this->getDepartmentIdByName('Produksi');
            } elseif ($request->tujuan === 'percetakan') {
                $toDeptId = $this->getDepartmentIdByName('Percetakan');
            } elseif ($request->tujuan === 'lain_lain') {
                $toDeptId = $this->getDepartmentIdByName('Lain-lain');
            }

            $mutation = WarehouseMutation::create([
                'tenant_id'       => $tenantId,
                'mutation_number' => $mutationNumber,
                'type'            => 'out',
                'to_department_id'=> $toDeptId,
                'mutation_date'   => $request->mutation_date,
                'status'          => 'approved',
                'notes'           => $request->notes,
                'created_by'      => $userId,
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

                $item->decrement('stock', $qty);
                $newStock = $item->fresh()->stock;

                StockMovement::create([
                    'tenant_id'             => $tenantId,
                    'inventory_item_id'     => $item->id,
                    'warehouse_mutation_id' => $mutation->id,
                    'user_id'               => $userId,
                    'type'                  => 'out',
                    'quantity'              => -$qty,
                    'reference'             => 'Pengeluaran Barang (' . $mutationNumber . ')',
                    'balance_after'         => $newStock,
                ]);
            }

            return $mutation;
        });

        return redirect()->route('pembelian.goods_issue.show', $mutation)
            ->with('success', 'Transaksi Pengeluaran Barang berhasil disimpan.');
    }

    public function goodsIssueShow(WarehouseMutation $warehouseMutation)
    {
        abort_unless($warehouseMutation->tenant_id === Auth::user()->tenant_id, 403);
        $warehouseMutation->load(['items.inventoryItem', 'createdBy', 'toDepartment']);

        return view('inventory.pembelian.goods_issue.show', compact('warehouseMutation'));
    }

    public function goodsIssueDestroy(WarehouseMutation $warehouseMutation)
    {
        abort_unless($warehouseMutation->tenant_id === Auth::user()->tenant_id, 403);

        DB::transaction(function () use ($warehouseMutation) {
            $tenantId = Auth::user()->tenant_id;
            $userId   = Auth::id();

            foreach ($warehouseMutation->items as $item) {
                $invItem = $item->inventoryItem;
                if (!$invItem) continue;

                $invItem->increment('stock', $item->quantity);
                $newStock = $invItem->fresh()->stock;

                StockMovement::create([
                    'tenant_id'         => $tenantId,
                    'inventory_item_id' => $invItem->id,
                    'user_id'           => $userId,
                    'type'              => 'adjustment',
                    'quantity'          => $item->quantity,
                    'reference'         => 'Batal Pengeluaran Barang (' . $warehouseMutation->mutation_number . ')',
                    'balance_after'     => $newStock,
                ]);
            }

            $warehouseMutation->delete();
        });

        return redirect()->route('pembelian.goods_issue.index')
            ->with('success', 'Transaksi pengeluaran barang berhasil dibatalkan.');
    }
}