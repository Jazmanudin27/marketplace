<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\InventoryItem;
use App\Models\MasterProduct;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class GoodsReceiptController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = GoodsReceipt::with(['supplier', 'department', 'purchaseOrder'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('receipt_date');

        if ($request->filled('search')) {
            $query->where('receipt_number', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('receipt_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('receipt_date', '<=', $request->date_to);
        }

        $receipts  = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('inventory.goods_receipts.index', compact('receipts', 'suppliers'));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;

        $suppliers      = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $departments    = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        return view('inventory.goods_receipts.create', compact('suppliers', 'departments', 'inventoryItems'));
    }

    /**
     * Store Goods Receipt dengan status 'pending' (Belum menambah stok).
     */
    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'supplier_id'   => 'nullable|exists:suppliers,id',
            'department_id' => 'nullable|exists:departments,id',
            'receipt_date'  => 'required|date',
            'source'        => 'required|in:direct,emergency,walk_in',
            'notes'         => 'nullable|string|max:1000',
            'items'         => 'required|array|min:1',
            'items.*.item_type' => 'required|in:inventory,product',
            'items.*.item_id'   => 'required|integer',
            'items.*.quantity'  => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $receipt = DB::transaction(function () use ($request, $tenantId) {
            $userId        = Auth::id();
            $receiptNumber = GoodsReceipt::generateReceiptNumber();
            $totalAmount   = 0;

            $receipt = GoodsReceipt::create([
                'tenant_id'     => $tenantId,
                'supplier_id'   => $request->supplier_id,
                'department_id' => $request->department_id,
                'receipt_number' => $receiptNumber,
                'receipt_date'  => $request->receipt_date,
                'source'        => $request->source,
                'status'        => 'pending', // menunggu approval
                'notes'         => $request->notes,
                'total_amount'  => 0,
                'created_by'    => $userId,
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

                $receipt->items()->create($itemData);
            }

            $receipt->update(['total_amount' => $totalAmount]);

            return $receipt;
        });

        return redirect()->route('goods_receipts.show', $receipt)
            ->with('success', 'Penerimaan langsung berhasil dicatat. Silakan lakukan Approval agar stok bertambah.');
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        abort_unless($goodsReceipt->tenant_id === Auth::user()->tenant_id, 403);
        $goodsReceipt->load(['supplier', 'department', 'purchaseOrder', 'items.inventoryItem', 'items.masterProduct', 'createdBy', 'approvedBy']);

        return view('inventory.goods_receipts.show', compact('goodsReceipt'));
    }

    public function edit(GoodsReceipt $goodsReceipt)
    {
        abort_unless($goodsReceipt->tenant_id === Auth::user()->tenant_id, 403);
        
        if ($goodsReceipt->status !== 'pending') {
            return redirect()->route('goods_receipts.show', $goodsReceipt)
                ->with('error', 'Hanya penerimaan berstatus Pending yang dapat diubah.');
        }

        $goodsReceipt->load('items');

        $tenantId = Auth::user()->tenant_id;
        $suppliers      = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $departments    = Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $inventoryItems = InventoryItem::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('type')
            ->orderBy('name')
            ->get()
            ->groupBy('type');

        return view('inventory.goods_receipts.edit', compact('goodsReceipt', 'suppliers', 'departments', 'inventoryItems'));
    }

    public function update(Request $request, GoodsReceipt $goodsReceipt)
    {
        abort_unless($goodsReceipt->tenant_id === Auth::user()->tenant_id, 403);

        if ($goodsReceipt->status !== 'pending') {
            return redirect()->route('goods_receipts.show', $goodsReceipt)
                ->with('error', 'Hanya penerimaan berstatus Pending yang dapat diubah.');
        }

        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'supplier_id'   => 'nullable|exists:suppliers,id',
            'department_id' => 'nullable|exists:departments,id',
            'receipt_date'  => 'required|date',
            'source'        => 'required|in:direct,emergency,walk_in,po',
            'notes'         => 'nullable|string|max:1000',
            'items'         => 'required|array|min:1',
            'items.*.item_type' => 'required|in:inventory,product',
            'items.*.item_id'   => 'required|integer',
            'items.*.quantity'  => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $goodsReceipt, $tenantId) {
            // Hapus item lama (Karena status pending, stok belum bertambah jadi cukup hapus item lama)
            $goodsReceipt->items()->delete();

            // Masukkan item baru
            $totalAmount = 0;
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

                $goodsReceipt->items()->create($itemData);
            }

            // Update Header
            $goodsReceipt->update([
                'supplier_id'   => $request->supplier_id,
                'department_id' => $request->department_id,
                'receipt_date'  => $request->receipt_date,
                'source'        => $request->source,
                'notes'         => $request->notes,
                'total_amount'  => $totalAmount,
            ]);
        });

        return redirect()->route('goods_receipts.show', $goodsReceipt)
            ->with('success', 'Penerimaan barang langsung berhasil diperbarui.');
    }

    /**
     * Approve Penerimaan Barang: Tambahkan stok dan catat stock movement.
     */
    public function approve(GoodsReceipt $goodsReceipt)
    {
        abort_unless($goodsReceipt->tenant_id === Auth::user()->tenant_id, 403);

        if ($goodsReceipt->status !== 'pending') {
            return back()->with('error', 'Hanya penerimaan berstatus Pending yang dapat disetujui.');
        }

        $tenantId = Auth::user()->tenant_id;
        $userId   = Auth::id();

        DB::transaction(function () use ($goodsReceipt, $tenantId, $userId) {
            $goodsReceipt->load(['items.inventoryItem', 'items.masterProduct', 'purchaseOrder.items']);

            $poItemsIndexed = null;
            if ($goodsReceipt->purchase_order_id && $goodsReceipt->purchaseOrder) {
                $poItemsIndexed = $goodsReceipt->purchaseOrder->items->keyBy(function ($item) {
                    return $item->inventory_item_id ? 'inventory_' . $item->inventory_item_id : 'product_' . $item->master_product_id;
                });
            }

            foreach ($goodsReceipt->items as $item) {
                $qty = $item->quantity;

                // 1. Tambah stok & stock movement
                if ($item->inventory_item_id && $item->inventoryItem) {
                    $invItem = $item->inventoryItem;
                    $invItem->increment('stock', $qty);
                    $newStock = $invItem->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'          => $tenantId,
                        'inventory_item_id'  => $invItem->id,
                        'department_id'      => $goodsReceipt->department_id,
                        'goods_receipt_id'   => $goodsReceipt->id,
                        'user_id'            => $userId,
                        'type'               => 'in',
                        'quantity'           => $qty,
                        'reference'          => 'Terima Barang ' . $goodsReceipt->source_label . ' — ' . $goodsReceipt->receipt_number,
                        'balance_after'      => $newStock,
                    ]);
                } elseif ($item->master_product_id && $item->masterProduct) {
                    $product = $item->masterProduct;
                    $product->increment('stock', $qty);
                    $newStock = $product->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'          => $tenantId,
                        'master_product_id'  => $product->id,
                        'goods_receipt_id'   => $goodsReceipt->id,
                        'user_id'            => $userId,
                        'type'               => 'in',
                        'quantity'           => $qty,
                        'reference'          => 'Terima Barang ' . $goodsReceipt->source_label . ' — ' . $goodsReceipt->receipt_number,
                        'balance_after'      => $newStock,
                    ]);
                }

                // 2. Update PO items received_quantity if linked to PO
                if ($poItemsIndexed) {
                    $key = $item->inventory_item_id ? 'inventory_' . $item->inventory_item_id : 'product_' . $item->master_product_id;
                    $poItem = $poItemsIndexed->get($key);
                    if ($poItem) {
                        $poItem->increment('received_quantity', $qty);
                    }
                }
            }

            // 3. Update PO Status if linked
            if ($goodsReceipt->purchase_order_id && $goodsReceipt->purchaseOrder) {
                $po = $goodsReceipt->purchaseOrder->fresh();
                $allItemsComplete = $po->items->every(function ($poItem) {
                    return (int) $poItem->received_quantity >= (int) $poItem->quantity;
                });
                $po->update([
                    'status' => $allItemsComplete ? 'received' : 'partially_received',
                ]);
            }

            // 4. Update status Goods Receipt
            $goodsReceipt->update([
                'status'      => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
            ]);
        });

        return redirect()->route('goods_receipts.show', $goodsReceipt)
            ->with('success', 'Penerimaan barang berhasil disetujui. Stok telah masuk ke departemen.');
    }

    /**
     * Batalkan Penerimaan (Hanya untuk yang statusnya pending atau approved)
     */
    public function destroy(GoodsReceipt $goodsReceipt)
    {
        abort_unless($goodsReceipt->tenant_id === Auth::user()->tenant_id, 403);

        DB::transaction(function () use ($goodsReceipt) {
            $tenantId = Auth::user()->tenant_id;
            $userId   = Auth::id();

            // Jika statusnya approved, kita harus reverse stok
            if ($goodsReceipt->status === 'approved') {
                $goodsReceipt->load(['items.inventoryItem', 'items.masterProduct', 'purchaseOrder.items']);

                $poItemsIndexed = null;
                if ($goodsReceipt->purchase_order_id && $goodsReceipt->purchaseOrder) {
                    $poItemsIndexed = $goodsReceipt->purchaseOrder->items->keyBy(function ($item) {
                        return $item->inventory_item_id ? 'inventory_' . $item->inventory_item_id : 'product_' . $item->master_product_id;
                    });
                }

                foreach ($goodsReceipt->items as $item) {
                    $qty = $item->quantity;

                    // Kurangi stok kembali
                    if ($item->inventory_item_id && $item->inventoryItem) {
                        $item->inventoryItem->decrement('stock', $qty);
                        $newStock = $item->inventoryItem->fresh()->stock;

                        StockMovement::create([
                            'tenant_id'         => $tenantId,
                            'inventory_item_id' => $item->inventory_item_id,
                            'user_id'           => $userId,
                            'type'              => 'adjustment',
                            'quantity'          => -$qty,
                            'reference'         => 'Batal Penerimaan (Stok Keluar) — ' . $goodsReceipt->receipt_number,
                            'balance_after'     => $newStock,
                        ]);
                    } elseif ($item->master_product_id && $item->masterProduct) {
                        $item->masterProduct->decrement('stock', $qty);
                        $newStock = $item->masterProduct->fresh()->stock;

                        StockMovement::create([
                            'tenant_id'        => $tenantId,
                            'master_product_id' => $item->master_product_id,
                            'user_id'          => $userId,
                            'type'             => 'adjustment',
                            'quantity'         => -$qty,
                            'reference'        => 'Batal Penerimaan (Stok Keluar) — ' . $goodsReceipt->receipt_number,
                            'balance_after'    => $newStock,
                        ]);
                    }

                    // Kembalikan received_quantity di PO items
                    if ($poItemsIndexed) {
                        $key = $item->inventory_item_id ? 'inventory_' . $item->inventory_item_id : 'product_' . $item->master_product_id;
                        $poItem = $poItemsIndexed->get($key);
                        if ($poItem) {
                            $poItem->decrement('received_quantity', min($qty, $poItem->received_quantity));
                        }
                    }
                }

                // Kembalikan status PO ke ordered / partially_received
                if ($goodsReceipt->purchase_order_id && $goodsReceipt->purchaseOrder) {
                    $po = $goodsReceipt->purchaseOrder->fresh();
                    $anyReceived = $po->items->contains(function ($poItem) {
                        return $poItem->received_quantity > 0;
                    });
                    $po->update([
                        'status' => $anyReceived ? 'partially_received' : 'ordered',
                    ]);
                }
            }

            // Hapus/Batal
            $goodsReceipt->delete();
        });

        return redirect()->route('goods_receipts.index')
            ->with('success', 'Penerimaan berhasil dihapus/dibatalkan.');
    }
}
