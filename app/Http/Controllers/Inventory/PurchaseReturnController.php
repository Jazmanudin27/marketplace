<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\PurchaseReturn;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseReturnController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = PurchaseReturn::with(['supplier', 'purchaseOrder'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('return_date');

        if ($request->filled('search')) {
            $query->where('return_number', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $returns   = $query->paginate(20)->withQueryString();
        $suppliers = \App\Models\Supplier::where('tenant_id', $tenantId)->where('is_active', true)->get();

        return view('inventory.purchase_returns.index', compact('returns', 'suppliers'));
    }

    public function create(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Harus memilih PO terlebih dahulu
        $selectedPo = null;
        if ($request->filled('po_id')) {
            $selectedPo = PurchaseOrder::with(['supplier', 'items.inventoryItem', 'items.masterProduct'])
                ->where('tenant_id', $tenantId)
                ->whereIn('status', ['partially_received', 'received', 'ordered'])
                ->find($request->po_id);
        }

        // Daftar PO yang bisa diretur (ordered, partially_received, received)
        $purchaseOrders = PurchaseOrder::with('supplier')
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['ordered', 'partially_received', 'received'])
            ->orderByDesc('po_date')
            ->get();

        return view('inventory.purchase_returns.create', compact('purchaseOrders', 'selectedPo'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'return_date'        => 'required|date',
            'reason'             => 'required|string|max:255',
            'notes'              => 'nullable|string|max:1000',
            'items'              => 'required|array|min:1',
            'items.*.item_id'   => 'required|integer',
            'items.*.item_type' => 'required|in:inventory,product',
            'items.*.quantity'  => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $po = PurchaseOrder::where('tenant_id', $tenantId)->findOrFail($request->purchase_order_id);

        DB::transaction(function () use ($request, $po, $tenantId) {
            $userId       = Auth::id();
            $returnNumber = PurchaseReturn::generateReturnNumber();
            $totalAmount  = 0;

            $purchaseReturn = PurchaseReturn::create([
                'tenant_id'         => $tenantId,
                'purchase_order_id' => $po->id,
                'supplier_id'       => $po->supplier_id,
                'return_number'     => $returnNumber,
                'return_date'       => $request->return_date,
                'reason'            => $request->reason,
                'status'            => 'draft',
                'notes'             => $request->notes,
                'created_by'        => $userId,
            ]);

            foreach ($request->items as $row) {
                $qty      = (int) $row['quantity'];
                $price    = (float) $row['unit_price'];
                $subtotal = $qty * $price;
                $totalAmount += $subtotal;

                $itemData = [
                    'quantity'   => $qty,
                    'unit_price' => $price,
                    'notes'      => $row['notes'] ?? null,
                ];

                if ($row['item_type'] === 'inventory') {
                    $itemData['inventory_item_id'] = $row['item_id'];
                } else {
                    $itemData['master_product_id'] = $row['item_id'];
                }

                $purchaseReturn->items()->create($itemData);

                // Kurangi stok
                if ($row['item_type'] === 'inventory') {
                    $invItem = \App\Models\InventoryItem::where('tenant_id', $tenantId)->find($row['item_id']);
                    if ($invItem) {
                        $invItem->decrement('stock', $qty);
                        $newStock = $invItem->fresh()->stock;

                        StockMovement::create([
                            'tenant_id'          => $tenantId,
                            'inventory_item_id'  => $invItem->id,
                            'purchase_return_id' => $purchaseReturn->id,
                            'user_id'            => $userId,
                            'type'               => 'out',
                            'quantity'           => -$qty,
                            'reference'          => 'Retur ke Supplier — ' . $purchaseReturn->return_number,
                            'balance_after'      => $newStock,
                        ]);
                    }
                } else {
                    $product = \App\Models\MasterProduct::where('tenant_id', $tenantId)->find($row['item_id']);
                    if ($product) {
                        $product->decrement('stock', $qty);
                        $newStock = $product->fresh()->stock;

                        StockMovement::create([
                            'tenant_id'          => $tenantId,
                            'master_product_id'  => $product->id,
                            'purchase_return_id' => $purchaseReturn->id,
                            'user_id'            => $userId,
                            'type'               => 'out',
                            'quantity'           => -$qty,
                            'reference'          => 'Retur ke Supplier — ' . $purchaseReturn->return_number,
                            'balance_after'      => $newStock,
                        ]);
                    }
                }
            }

            $purchaseReturn->update(['total_amount' => $totalAmount]);
        });

        return redirect()->route('purchase_returns.index')
            ->with('success', 'Retur pembelian berhasil dibuat. Stok telah dikurangi.');
    }

    public function show(PurchaseReturn $purchaseReturn)
    {
        abort_unless($purchaseReturn->tenant_id === Auth::user()->tenant_id, 403);
        $purchaseReturn->load(['supplier', 'purchaseOrder', 'items.inventoryItem', 'items.masterProduct', 'createdBy']);

        return view('inventory.purchase_returns.show', compact('purchaseReturn'));
    }

    public function destroy(PurchaseReturn $purchaseReturn)
    {
        abort_unless($purchaseReturn->tenant_id === Auth::user()->tenant_id, 403);

        if ($purchaseReturn->status !== 'draft') {
            return back()->with('error', 'Hanya retur berstatus Draft yang dapat dihapus.');
        }

        DB::transaction(function () use ($purchaseReturn) {
            $tenantId = Auth::user()->tenant_id;

            // Kembalikan stok
            foreach ($purchaseReturn->items()->with(['inventoryItem', 'masterProduct'])->get() as $item) {
                if ($item->inventory_item_id && $item->inventoryItem) {
                    $item->inventoryItem->increment('stock', $item->quantity);
                    $newStock = $item->inventoryItem->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'inventory_item_id' => $item->inventory_item_id,
                        'user_id'           => Auth::id(),
                        'type'              => 'adjustment',
                        'quantity'          => $item->quantity,
                        'reference'         => 'Batal Retur — ' . $purchaseReturn->return_number,
                        'balance_after'     => $newStock,
                    ]);
                }
                if ($item->master_product_id && $item->masterProduct) {
                    $item->masterProduct->increment('stock', $item->quantity);
                    $newStock = $item->masterProduct->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'        => $tenantId,
                        'master_product_id' => $item->master_product_id,
                        'user_id'          => Auth::id(),
                        'type'             => 'adjustment',
                        'quantity'         => $item->quantity,
                        'reference'        => 'Batal Retur — ' . $purchaseReturn->return_number,
                        'balance_after'    => $newStock,
                    ]);
                }
            }

            $purchaseReturn->delete();
        });

        return redirect()->route('purchase_returns.index')
            ->with('success', 'Retur pembelian berhasil dihapus. Stok dikembalikan.');
    }

    public function updateStatus(Request $request, PurchaseReturn $purchaseReturn)
    {
        abort_unless($purchaseReturn->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'status' => 'required|in:approved,sent',
        ]);

        if ($purchaseReturn->status === 'sent') {
            return back()->with('error', 'Retur sudah final dan tidak bisa diubah.');
        }

        $purchaseReturn->update(['status' => $request->status]);

        return redirect()->route('purchase_returns.show', $purchaseReturn)
            ->with('success', 'Status retur berhasil diperbarui menjadi: ' . $purchaseReturn->fresh()->status_label);
    }
}
