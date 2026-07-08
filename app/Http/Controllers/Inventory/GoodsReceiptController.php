<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\GoodsReceipt;
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
        $query = GoodsReceipt::with(['supplier', 'department'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('receipt_date');

        if ($request->filled('search')) {
            $query->where('receipt_number', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('source')) {
            $query->where('source', $request->source);
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

        DB::transaction(function () use ($request, $tenantId) {
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

                // Tambah stok
                if ($row['item_type'] === 'inventory') {
                    $invItem = InventoryItem::where('tenant_id', $tenantId)->find($row['item_id']);
                    if ($invItem) {
                        $invItem->increment('stock', $qty);
                        $newStock = $invItem->fresh()->stock;

                        StockMovement::create([
                            'tenant_id'          => $tenantId,
                            'inventory_item_id'  => $invItem->id,
                            'department_id'      => $request->department_id,
                            'goods_receipt_id'   => $receipt->id,
                            'user_id'            => $userId,
                            'type'               => 'in',
                            'quantity'           => $qty,
                            'reference'          => 'Penerimaan Langsung — ' . $receiptNumber,
                            'balance_after'      => $newStock,
                            'created_at'         => $request->receipt_date,
                            'updated_at'         => $request->receipt_date,
                        ]);
                    }
                } else {
                    $product = MasterProduct::where('tenant_id', $tenantId)->find($row['item_id']);
                    if ($product) {
                        $product->increment('stock', $qty);
                        $newStock = $product->fresh()->stock;

                        StockMovement::create([
                            'tenant_id'          => $tenantId,
                            'master_product_id'  => $product->id,
                            'goods_receipt_id'   => $receipt->id,
                            'user_id'            => $userId,
                            'type'               => 'in',
                            'quantity'           => $qty,
                            'reference'          => 'Penerimaan Langsung — ' . $receiptNumber,
                            'balance_after'      => $newStock,
                            'created_at'         => $request->receipt_date,
                            'updated_at'         => $request->receipt_date,
                        ]);
                    }
                }
            }

            $receipt->update(['total_amount' => $totalAmount]);
        });

        return redirect()->route('goods_receipts.index')
            ->with('success', 'Penerimaan barang langsung berhasil dicatat. Stok telah diperbarui.');
    }

    public function show(GoodsReceipt $goodsReceipt)
    {
        abort_unless($goodsReceipt->tenant_id === Auth::user()->tenant_id, 403);
        $goodsReceipt->load(['supplier', 'department', 'items.inventoryItem', 'items.masterProduct', 'createdBy']);

        return view('inventory.goods_receipts.show', compact('goodsReceipt'));
    }

    public function destroy(GoodsReceipt $goodsReceipt)
    {
        abort_unless($goodsReceipt->tenant_id === Auth::user()->tenant_id, 403);

        DB::transaction(function () use ($goodsReceipt) {
            $tenantId = Auth::user()->tenant_id;

            // Kembalikan/kurangi stok
            foreach ($goodsReceipt->items()->with(['inventoryItem', 'masterProduct'])->get() as $item) {
                if ($item->inventory_item_id && $item->inventoryItem) {
                    $item->inventoryItem->decrement('stock', $item->quantity);
                    $newStock = $item->inventoryItem->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'inventory_item_id' => $item->inventory_item_id,
                        'user_id'           => Auth::id(),
                        'type'              => 'adjustment',
                        'quantity'          => -$item->quantity,
                        'reference'         => 'Batal Penerimaan Langsung — ' . $goodsReceipt->receipt_number,
                        'balance_after'     => $newStock,
                    ]);
                }
                if ($item->master_product_id && $item->masterProduct) {
                    $item->masterProduct->decrement('stock', $item->quantity);
                    $newStock = $item->masterProduct->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'master_product_id' => $item->master_product_id,
                        'user_id'           => Auth::id(),
                        'type'              => 'adjustment',
                        'quantity'          => -$item->quantity,
                        'reference'         => 'Batal Penerimaan Langsung — ' . $goodsReceipt->receipt_number,
                        'balance_after'     => $newStock,
                    ]);
                }
            }

            $goodsReceipt->delete();
        });

        return redirect()->route('goods_receipts.index')
            ->with('success', 'Penerimaan langsung berhasil dihapus. Stok dikembalikan.');
    }
}
