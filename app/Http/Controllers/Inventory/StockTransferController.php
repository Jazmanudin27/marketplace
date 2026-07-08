<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = StockTransfer::with(['fromDepartment', 'toDepartment'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('transfer_date');

        if ($request->filled('search')) {
            $query->where('transfer_number', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from_department_id')) {
            $query->where('from_department_id', $request->from_department_id);
        }
        if ($request->filled('to_department_id')) {
            $query->where('to_department_id', $request->to_department_id);
        }

        $transfers   = $query->paginate(20)->withQueryString();
        $departments = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('inventory.stock_transfers.index', compact('transfers', 'departments'));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;

        $departments    = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        // Semua inventory items aktif (bahan, kemasan, atk, inventaris)
        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('stock', '>', 0)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        return view('inventory.stock_transfers.create', compact('departments', 'inventoryItems'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'from_department_id' => 'nullable|exists:departments,id',
            'to_department_id'   => 'required|exists:departments,id',
            'transfer_date'      => 'required|date',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.item_id'   => 'required|exists:inventory_items,id',
            'items.*.quantity'  => 'required|integer|min:1',
        ]);

        if ($request->from_department_id && $request->from_department_id === $request->to_department_id) {
            return back()->withInput()->withErrors(['to_department_id' => 'Departemen tujuan harus berbeda dari departemen asal.']);
        }

        DB::transaction(function () use ($request, $tenantId) {
            $userId          = Auth::id();
            $transferNumber  = StockTransfer::generateTransferNumber();

            $transfer = StockTransfer::create([
                'tenant_id'          => $tenantId,
                'transfer_number'    => $transferNumber,
                'from_department_id' => $request->from_department_id,
                'to_department_id'   => $request->to_department_id,
                'transfer_date'      => $request->transfer_date,
                'status'             => 'confirmed', // langsung confirmed
                'notes'              => $request->notes,
                'created_by'         => $userId,
                'confirmed_by'       => $userId,
                'confirmed_at'       => now(),
            ]);

            foreach ($request->items as $row) {
                $itemId = $row['item_id'];
                $qty    = (int) $row['quantity'];

                $invItem = InventoryItem::where('tenant_id', $tenantId)->findOrFail($itemId);

                $transfer->items()->create([
                    'inventory_item_id' => $invItem->id,
                    'quantity'          => $qty,
                    'notes'             => $row['notes'] ?? null,
                ]);

                // Stock movement OUT dari dept asal (stok global item tetap, hanya movement dept)
                // Catatan: stok global inventory_item tidak berubah saat transfer dept
                // Transfer hanya mencatat perpindahan tanggung jawab antar dept via movement
                StockMovement::create([
                    'tenant_id'          => $tenantId,
                    'inventory_item_id'  => $invItem->id,
                    'department_id'      => $request->from_department_id,
                    'stock_transfer_id'  => $transfer->id,
                    'user_id'            => $userId,
                    'type'               => 'transfer_out',
                    'quantity'           => -$qty,
                    'reference'          => 'Transfer Stok — ' . $transferNumber . ' (keluar)',
                    'balance_after'      => $invItem->stock,
                ]);

                // Stock movement IN ke dept tujuan
                StockMovement::create([
                    'tenant_id'          => $tenantId,
                    'inventory_item_id'  => $invItem->id,
                    'department_id'      => $request->to_department_id,
                    'stock_transfer_id'  => $transfer->id,
                    'user_id'            => $userId,
                    'type'               => 'transfer_in',
                    'quantity'           => $qty,
                    'reference'          => 'Transfer Stok — ' . $transferNumber . ' (masuk)',
                    'balance_after'      => $invItem->stock,
                ]);
            }
        });

        return redirect()->route('stock_transfers.index')
            ->with('success', 'Transfer stok berhasil dicatat.');
    }

    public function show(StockTransfer $stockTransfer)
    {
        abort_unless($stockTransfer->tenant_id === Auth::user()->tenant_id, 403);
        $stockTransfer->load(['fromDepartment', 'toDepartment', 'items.inventoryItem', 'createdBy', 'confirmedBy']);

        return view('inventory.stock_transfers.show', compact('stockTransfer'));
    }

    public function destroy(StockTransfer $stockTransfer)
    {
        abort_unless($stockTransfer->tenant_id === Auth::user()->tenant_id, 403);

        if ($stockTransfer->status !== 'draft') {
            return back()->with('error', 'Hanya transfer stok berstatus Draft yang dapat dihapus.');
        }

        $stockTransfer->delete();

        return redirect()->route('stock_transfers.index')
            ->with('success', 'Transfer stok berhasil dihapus.');
    }
}
