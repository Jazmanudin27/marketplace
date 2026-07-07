<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class IncomingGoodController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = StockMovement::with(['user'])
            ->select('reference', 'created_at', 'user_id', DB::raw('COUNT(*) as total_items'), DB::raw('SUM(quantity) as total_qty'))
            ->where('tenant_id', $tenantId)
            ->where('type', 'in')
            ->groupBy('reference', 'created_at', 'user_id')
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $query->where('reference', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $incomings = $query->paginate(20)->withQueryString();

        return view('inventory.incoming_goods.index', compact('incomings'));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $products = MasterProduct::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $materials = \App\Models\Material::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $inventoryItems = \App\Models\InventoryItem::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $departments = \App\Models\Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $purchaseOrders = \App\Models\PurchaseOrder::where('tenant_id', $tenantId)
            ->whereIn('status', ['ordered', 'partially_received'])
            ->orderBy('po_number', 'desc')
            ->get();

        return view('inventory.incoming_goods.create', compact('products', 'materials', 'inventoryItems', 'departments', 'suppliers', 'purchaseOrders'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'source_type' => 'required|string|in:supplier,return,other',
            'order_id' => 'nullable|string|max:255',
            'incoming_date' => 'required|date',
            'reference' => 'required|string|max:255',
            'department_id' => 'nullable|exists:departments,id',
            'item_types' => 'required|array|min:1',
            'item_types.*' => 'required|string|in:product,material,inventory',
            'item_ids' => 'required|array|min:1',
            'quantities' => 'required|array|min:1',
            'quantities.*' => 'required|numeric|min:0.01',
            'cost_prices' => 'array',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
        ]);

        $sourceType = $request->source_type;
        $reference = "";

        if ($sourceType === 'supplier') {
            $supplierLabel = '';
            if ($request->filled('supplier_id')) {
                $supplier = Supplier::where('tenant_id', $tenantId)->find($request->supplier_id);
                if (!$supplier) {
                    return back()->withErrors(['supplier_id' => 'Supplier tidak valid untuk perusahaan Anda.']);
                }
                $supplierLabel = " (" . $supplier->name . ")";
            }
            $reference = "Pembelian - " . $request->reference . $supplierLabel;
        } elseif ($sourceType === 'return') {
            $reference = "Retur Penjualan - Order ID: " . ($request->order_id ?? $request->reference);
        } else {
            $reference = "Penerimaan Lainnya - " . $request->reference;
        }

        $date = \Carbon\Carbon::parse($request->incoming_date)->format('Y-m-d H:i:s');

        $itemTypes = $request->item_types;
        $itemIds = $request->item_ids;
        $quantities = $request->quantities;
        $costPrices = $request->cost_prices ?? [];
        $departmentId = $request->department_id;

        $itemsCount = 0;
        $totalQty = 0;

        DB::transaction(function () use ($tenantId, $request, $date, $itemTypes, $itemIds, $quantities, $costPrices, $departmentId, &$itemsCount, &$totalQty, $reference) {
            foreach ($itemIds as $index => $itemId) {
                $type = $itemTypes[$index];
                $qty = $quantities[$index];
                $costPrice = $costPrices[$index] ?? null;

                if ($qty <= 0) {
                    continue;
                }

                if ($type === 'material') {
                    $item = \App\Models\Material::where('tenant_id', $tenantId)->find($itemId);
                    if ($item) {
                        if ($request->source_type === 'supplier' && $costPrice !== null && $costPrice !== '') {
                            $item->update(['cost_price' => $costPrice]);
                        }

                        $item->recordStockMovement($qty, 'in', $reference, Auth::id(), $date);
                        
                        $movement = StockMovement::where('material_id', $item->id)
                            ->where('reference', $reference)
                            ->where('tenant_id', $tenantId)
                            ->orderByDesc('id')
                            ->first();
                        if ($movement) {
                            $movement->update(['department_id' => $departmentId]);
                        }

                        if ($request->filled('purchase_order_id')) {
                            $poItem = \App\Models\PurchaseOrderItem::where('purchase_order_id', $request->purchase_order_id)
                                ->where('material_id', $itemId)
                                ->first();
                            if ($poItem) {
                                $poItem->increment('received_quantity', $qty);
                            }
                            if ($movement) {
                                $movement->update(['purchase_order_id' => $request->purchase_order_id]);
                            }
                        }

                        $itemsCount++;
                        $totalQty += $qty;
                    }
                } elseif ($type === 'inventory') {
                    $item = \App\Models\InventoryItem::where('tenant_id', $tenantId)->find($itemId);
                    if ($item) {
                        if ($request->source_type === 'supplier' && $costPrice !== null && $costPrice !== '') {
                            $item->update(['cost_price' => $costPrice]);
                        }

                        $item->recordStockMovement($qty, 'in', $reference, Auth::id(), $date);
                        
                        $movement = StockMovement::where('inventory_item_id', $item->id)
                            ->where('reference', $reference)
                            ->where('tenant_id', $tenantId)
                            ->orderByDesc('id')
                            ->first();
                        if ($movement) {
                            $movement->update(['department_id' => $departmentId]);
                        }

                        if ($request->filled('purchase_order_id')) {
                            $poItem = \App\Models\PurchaseOrderItem::where('purchase_order_id', $request->purchase_order_id)
                                ->where('inventory_item_id', $itemId)
                                ->first();
                            if ($poItem) {
                                $poItem->increment('received_quantity', $qty);
                            }
                            if ($movement) {
                                $movement->update(['purchase_order_id' => $request->purchase_order_id]);
                            }
                        }

                        $itemsCount++;
                        $totalQty += $qty;
                    }
                } else {
                    $item = MasterProduct::where('tenant_id', $tenantId)->find($itemId);
                    if ($item) {
                        if ($request->source_type === 'supplier' && $costPrice !== null && $costPrice !== '') {
                            $item->update(['cost_price' => $costPrice]);
                        }

                        $item->recordStockMovement($qty, 'in', $reference, Auth::id(), $date);
                        
                        $movement = StockMovement::where('master_product_id', $item->id)
                            ->where('reference', $reference)
                            ->where('tenant_id', $tenantId)
                            ->orderByDesc('id')
                            ->first();
                        if ($movement) {
                            $movement->update(['department_id' => $departmentId]);
                        }

                        if ($request->filled('purchase_order_id')) {
                            $poItem = \App\Models\PurchaseOrderItem::where('purchase_order_id', $request->purchase_order_id)
                                ->where('master_product_id', $itemId)
                                ->first();
                            if ($poItem) {
                                $poItem->increment('received_quantity', $qty);
                            }
                            if ($movement) {
                                $movement->update(['purchase_order_id' => $request->purchase_order_id]);
                            }
                        }

                        $itemsCount++;
                        $totalQty += $qty;
                    }
                }
            }

            if ($request->filled('purchase_order_id')) {
                $po = \App\Models\PurchaseOrder::find($request->purchase_order_id);
                if ($po) {
                    if ($departmentId && !$po->department_id) {
                        $po->update(['department_id' => $departmentId]);
                    }
                    $isFullyReceived = true;
                    foreach ($po->items as $item) {
                        if ($item->received_quantity < $item->quantity) {
                            $isFullyReceived = false;
                            break;
                        }
                    }
                    $po->update([
                        'status' => $isFullyReceived ? 'received' : 'partially_received'
                    ]);
                }
            }
        });

        return redirect()->route('incoming_goods.index')->with('success', "Penerimaan barang berhasil disimpan. Total {$itemsCount} jenis barang dengan {$totalQty} Qty masuk.");
    }
}
