<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\WarehouseMutation;
use App\Models\WarehouseMutationItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BarangKeluarController extends Controller
{
    private function checkAccess($permission = 'goods-issues.index')
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin() && $user->role !== 'admin' && !$user->can($permission)) {
            abort(403, 'Anda tidak memiliki hak akses untuk mengelola Pengeluaran Barang Keluar.');
        }
    }

    /**
     * Display V2 Barang Keluar (Goods Issue) Index page.
     */
    public function index(Request $request)
    {
        $this->checkAccess('goods-issues.index');
        $tenantId = Auth::user()->tenant_id;

        $query = WarehouseMutation::with(['items.inventoryItem', 'toDepartment', 'spk', 'createdBy'])
            ->where('tenant_id', $tenantId)
            ->where('type', 'out')
            ->orderByDesc('mutation_date')
            ->orderByDesc('id');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('mutation_number', 'like', '%' . $search . '%')
                  ->orWhere('notes', 'like', '%' . $search . '%')
                  ->orWhereHas('spk', function ($sq) use ($search) {
                      $sq->where('no_spk', 'like', '%' . $search . '%')
                         ->orWhere('no_produksi', 'like', '%' . $search . '%');
                  });
            });
        }

        if ($request->filled('to_department_id') && $request->to_department_id !== 'all') {
            $query->where('to_department_id', $request->to_department_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('mutation_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('mutation_date', '<=', $request->date_to);
        }

        // Statistics
        $statsQuery = WarehouseMutation::where('tenant_id', $tenantId)->where('type', 'out');
        if ($request->filled('date_from')) {
            $statsQuery->whereDate('mutation_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $statsQuery->whereDate('mutation_date', '<=', $request->date_to);
        }

        $totalTransactions = (clone $statsQuery)->count();
        $mutationIds       = (clone $statsQuery)->pluck('id');
        $totalItemsCount   = WarehouseMutationItem::whereIn('warehouse_mutation_id', $mutationIds)->sum('quantity');
        $totalValue        = WarehouseMutationItem::whereIn('warehouse_mutation_id', $mutationIds)->sum(DB::raw('quantity * unit_price'));

        $mutations   = $query->paginate(15)->withQueryString();
        $departments = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('v2.barang_keluar.index', compact(
            'mutations',
            'departments',
            'totalTransactions',
            'totalItemsCount',
            'totalValue'
        ));
    }

    /**
     * Show form to create Barang Keluar.
     */
    public function create()
    {
        $this->checkAccess('goods-issues.index');
        $tenantId = Auth::user()->tenant_id;

        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereIn('type', ['bahan', 'kemasan', 'atk', 'inventaris'])
            ->where('stock', '>', 0)
            ->orderBy('name')
            ->get();

        $departments = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('v2.barang_keluar.create', compact('inventoryItems', 'departments'));
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

    /**
     * Store Barang Keluar.
     */
    public function store(Request $request)
    {
        $this->checkAccess('goods-issues.index');
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'mutation_date'    => 'required|date',
            'tujuan'           => 'required|string',
            'notes'            => 'nullable|string|max:1000',
            'items'            => 'required|array|min:1',
            'items.*.item_id'  => 'required|exists:inventory_items,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ], [
            'items.required'   => 'Minimal masukkan 1 item barang yang dikeluarkan.',
        ]);

        // Cek kecukupan stok
        foreach ($request->items as $row) {
            $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($row['item_id']);
            if ($item->stock < (float)$row['quantity']) {
                return back()->withInput()->with('error', "Stok barang '{$item->name}' tidak mencukupi (Sisa stok: {$item->stock} {$item->unit}).");
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
            } elseif (numeric($request->tujuan)) {
                $toDeptId = (int) $request->tujuan;
            } else {
                $toDeptId = $this->getDepartmentIdByName('Lain-lain');
            }

            $mutation = WarehouseMutation::create([
                'tenant_id'        => $tenantId,
                'mutation_number'  => $mutationNumber,
                'type'             => 'out',
                'to_department_id' => $toDeptId,
                'mutation_date'    => $request->mutation_date,
                'status'           => 'approved',
                'notes'            => $request->notes,
                'created_by'       => $userId,
            ]);

            foreach ($request->items as $row) {
                $item = InventoryItem::where('tenant_id', $tenantId)->findOrFail($row['item_id']);
                $qty  = (float) $row['quantity'];

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
                    'reference'             => 'Pengeluaran Barang V2 (' . $mutationNumber . ')',
                    'balance_after'         => $newStock,
                ]);
            }

            return $mutation;
        });

        return redirect()->route('v2.barang_keluar.show', $mutation)
            ->with('success', "✅ Transaksi Pengeluaran Barang ({$mutation->mutation_number}) berhasil disimpan.");
    }

    /**
     * Show detail of Barang Keluar.
     */
    public function show(WarehouseMutation $warehouseMutation)
    {
        $this->checkAccess('goods-issues.index');
        abort_unless($warehouseMutation->tenant_id === Auth::user()->tenant_id, 403);

        $warehouseMutation->load(['items.inventoryItem', 'createdBy', 'toDepartment', 'spk']);

        return view('v2.barang_keluar.show', compact('warehouseMutation'));
    }

    /**
     * Delete / Cancel Barang Keluar.
     */
    public function destroy(WarehouseMutation $warehouseMutation)
    {
        $this->checkAccess('goods-issues.index');
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

        return redirect()->route('v2.barang_keluar.index')
            ->with('success', '✅ Transaksi pengeluaran barang berhasil dibatalkan dan stok dikembalikan.');
    }
}
