<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseOrderController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = PurchaseOrder::with(['supplier'])
            ->where('tenant_id', $tenantId)
            ->orderBy('po_date', 'desc');

        if ($request->filled('search')) {
            $query->where('po_number', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        $purchaseOrders = $query->paginate(20)->withQueryString();
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->get();

        return view('inventory.purchase_orders.index', compact('purchaseOrders', 'suppliers'));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $departments = \App\Models\Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $materials = \App\Models\InventoryItem::where('tenant_id', $tenantId)->where('is_active', true)->whereIn('type', ['bahan', 'kemasan'])->orderBy('name')->get();
        $inventoryItems = \App\Models\InventoryItem::where('tenant_id', $tenantId)->where('is_active', true)->whereIn('type', ['atk', 'inventaris'])->orderBy('name')->get();

        return view('inventory.purchase_orders.create', compact('suppliers', 'departments', 'materials', 'inventoryItems'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'department_id' => 'nullable|exists:departments,id',
            'po_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|string|in:material,inventory,product',
            'items.*.item_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        // Verify supplier belongs to this tenant
        $supplier = Supplier::where('tenant_id', $tenantId)->find($request->supplier_id);
        if (!$supplier) {
            return back()->withErrors(['supplier_id' => 'Supplier tidak valid.']);
        }

        DB::transaction(function () use ($request, $tenantId) {
            $poNumber = PurchaseOrder::generatePoNumber();
            $totalAmount = 0;

            $po = PurchaseOrder::create([
                'tenant_id' => $tenantId,
                'supplier_id' => $request->supplier_id,
                'department_id' => $request->department_id,
                'po_number' => $poNumber,
                'po_date' => $request->po_date,
                'status' => 'draft',
                'notes' => $request->notes,
                'total_amount' => 0,
            ]);

            foreach ($request->items as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $subtotal;

                $itemData = [
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'received_quantity' => 0,
                ];

                if ($item['item_type'] === 'material' || $item['item_type'] === 'inventory') {
                    $itemData['inventory_item_id'] = $item['item_id'];
                } else {
                    $itemData['master_product_id'] = $item['item_id'];
                }

                $po->items()->create($itemData);
            }

            $po->update(['total_amount' => $totalAmount]);
        });

        return redirect()->route('purchase_orders.index')
            ->with('success', 'Purchase Order berhasil dibuat sebagai draf.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->tenant_id === Auth::user()->tenant_id, 403);
        $purchaseOrder->load(['supplier', 'department', 'items.masterProduct', 'items.inventoryItem']);

        return view('inventory.purchase_orders.show', compact('purchaseOrder'));
    }

    public function edit(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->tenant_id === Auth::user()->tenant_id, 403);
        
        if (!in_array($purchaseOrder->status, ['draft', 'ordered'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Purchase Order tidak dapat diubah pada status saat ini.');
        }

        $tenantId = Auth::user()->tenant_id;
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $departments = \App\Models\Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $materials = \App\Models\InventoryItem::where('tenant_id', $tenantId)->where('is_active', true)->whereIn('type', ['bahan', 'kemasan'])->orderBy('name')->get();
        $inventoryItems = \App\Models\InventoryItem::where('tenant_id', $tenantId)->where('is_active', true)->whereIn('type', ['atk', 'inventaris'])->orderBy('name')->get();
        $purchaseOrder->load('items');

        return view('inventory.purchase_orders.edit', compact('purchaseOrder', 'suppliers', 'departments', 'materials', 'inventoryItems'));
    }

    public function update(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->tenant_id === Auth::user()->tenant_id, 403);
        
        if (!in_array($purchaseOrder->status, ['draft', 'ordered'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Purchase Order tidak dapat diubah pada status saat ini.');
        }

        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'department_id' => 'nullable|exists:departments,id',
            'po_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.item_type' => 'required|string|in:material,inventory,product',
            'items.*.item_id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $purchaseOrder) {
            $totalAmount = 0;

            $purchaseOrder->update([
                'supplier_id' => $request->supplier_id,
                'department_id' => $request->department_id,
                'po_date' => $request->po_date,
                'notes' => $request->notes,
            ]);

            // Clear old items and recreate
            $purchaseOrder->items()->delete();

            foreach ($request->items as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $subtotal;

                $itemData = [
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'received_quantity' => $item['received_quantity'] ?? 0,
                ];

                if ($item['item_type'] === 'material' || $item['item_type'] === 'inventory') {
                    $itemData['inventory_item_id'] = $item['item_id'];
                } else {
                    $itemData['master_product_id'] = $item['item_id'];
                }

                $purchaseOrder->items()->create($itemData);
            }

            $purchaseOrder->update(['total_amount' => $totalAmount]);
        });

        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Purchase Order berhasil diperbarui.');
    }

    public function destroy(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->tenant_id === Auth::user()->tenant_id, 403);
        
        if ($purchaseOrder->status !== 'draft') {
            return back()->with('error', 'Hanya draf Purchase Order yang dapat dihapus.');
        }

        $purchaseOrder->delete();

        return redirect()->route('purchase_orders.index')
            ->with('success', 'Purchase Order berhasil dihapus.');
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'status' => 'required|in:ordered,cancelled,draft',
        ]);

        if ($purchaseOrder->status === 'received') {
            return back()->with('error', 'Tidak bisa mengubah status PO yang sudah selesai diterima.');
        }

        $purchaseOrder->update(['status' => $request->status]);

        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Status Purchase Order berhasil diperbarui menjadi: ' . $purchaseOrder->status_label);
    }

    public function print(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->tenant_id === Auth::user()->tenant_id, 403);
        $purchaseOrder->load(['supplier', 'department', 'items.masterProduct', 'items.inventoryItem', 'tenant']);

        return view('inventory.purchase_orders.print', compact('purchaseOrder'));
    }

    public function getItems(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->tenant_id === Auth::user()->tenant_id, 403);
        
        $items = $purchaseOrder->items()->with(['masterProduct', 'inventoryItem'])->get()->map(function ($item) {
            $sku = $item->item_sku;
            $name = $item->item_name;
            
            $type = 'product';
            $itemId = $item->master_product_id;
            if ($item->inventory_item_id) {
                $type = ($item->inventoryItem && in_array($item->inventoryItem->type, ['bahan', 'kemasan'])) ? 'material' : 'inventory';
                $itemId = $item->inventory_item_id;
            }

            return [
                'id' => $item->id,
                'item_type' => $type,
                'item_id' => $itemId,
                'product_name' => $name,
                'sku' => $sku,
                'ordered_quantity' => $item->quantity,
                'received_quantity' => $item->received_quantity,
                'cost_price' => $item->unit_price,
            ];
        });

        return response()->json([
            'success' => true,
            'supplier_id' => $purchaseOrder->supplier_id,
            'items' => $items,
        ]);
    }

    public function purchaseReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $query = PurchaseOrder::where('tenant_id', $tenantId)
            ->whereBetween('po_date', [$startDate, $endDate]);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $orders = $query->with(['supplier', 'department'])->orderBy('po_date', 'desc')->get();

        // Calculate KPI metrics
        $totalBelanja = $orders->where('status', '!=', 'cancelled')->sum('total_amount');
        $totalPoTerbuka = $orders->whereIn('status', ['ordered', 'partially_received'])->count();
        $totalPoSelesai = $orders->where('status', 'received')->count();
        $avgPoValue = $orders->count() > 0 ? ($totalBelanja / $orders->count()) : 0;

        // Grouping breakdowns
        $supplierBreakdown = $orders->where('status', '!=', 'cancelled')->groupBy('supplier_id')->map(function ($group) {
            return [
                'name' => $group->first()->supplier ? $group->first()->supplier->name : 'Supplier Terhapus',
                'amount' => $group->sum('total_amount'),
                'count' => $group->count(),
            ];
        })->sortByDesc('amount');

        $departmentBreakdown = $orders->where('status', '!=', 'cancelled')->groupBy('department_id')->map(function ($group) {
            return [
                'name' => $group->first()->department ? $group->first()->department->name : 'Umum / Operasional',
                'amount' => $group->sum('total_amount'),
                'count' => $group->count(),
            ];
        })->sortByDesc('amount');

        // Trend calculations
        $trendData = $orders->where('status', '!=', 'cancelled')
            ->groupBy(function($order) {
                return \Carbon\Carbon::parse($order->po_date)->format('d M');
            })->map(function ($group) {
                return $group->sum('total_amount');
            });

        // Purchased items breakdown
        $orderIds = $orders->pluck('id');
        $itemsBreakdown = \App\Models\PurchaseOrderItem::whereIn('purchase_order_id', $orderIds)
            ->with(['masterProduct', 'inventoryItem'])
            ->get()
            ->groupBy(function($item) {
                return $item->master_product_id ? 'prod_' . $item->master_product_id : 'item_' . $item->inventory_item_id;
            })->map(function ($group) {
                $first = $group->first();
                return [
                    'name' => $first->item_name,
                    'sku' => $first->item_sku,
                    'type' => $first->master_product_id ? 'Produk Jadi' : ($first->inventoryItem ? ucfirst($first->inventoryItem->type) : 'Barang Operasional'),
                    'unit' => $first->inventoryItem ? $first->inventoryItem->unit : 'PCS',
                    'qty_ordered' => $group->sum('quantity'),
                    'qty_received' => $group->sum('received_quantity'),
                    'avg_price' => $group->avg('unit_price'),
                    'total_amount' => $group->sum(function($item) {
                        return $item->quantity * $item->unit_price;
                    }),
                ];
            })->sortByDesc('total_amount');

        $suppliers = Supplier::where('tenant_id', $tenantId)->orderBy('name')->get();
        $departments = \App\Models\Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('inventory.purchase_orders.report', compact(
            'orders', 'totalBelanja', 'totalPoTerbuka', 'totalPoSelesai', 'avgPoValue',
            'supplierBreakdown', 'departmentBreakdown', 'trendData', 'itemsBreakdown',
            'suppliers', 'departments', 'startDate', 'endDate'
        ));
    }

    public function printPurchaseReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->format('Y-m-d'));

        $query = PurchaseOrder::where('tenant_id', $tenantId)
            ->whereBetween('po_date', [$startDate, $endDate]);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        $orders = $query->with(['supplier', 'department'])->orderBy('po_date', 'desc')->get();

        // Calculate KPI metrics
        $totalBelanja = $orders->where('status', '!=', 'cancelled')->sum('total_amount');
        $totalPoTerbuka = $orders->whereIn('status', ['ordered', 'partially_received'])->count();
        $totalPoSelesai = $orders->where('status', 'received')->count();

        // Purchased items breakdown
        $orderIds = $orders->pluck('id');
        $itemsBreakdown = \App\Models\PurchaseOrderItem::whereIn('purchase_order_id', $orderIds)
            ->with(['masterProduct', 'inventoryItem'])
            ->get()
            ->groupBy(function($item) {
                return $item->master_product_id ? 'prod_' . $item->master_product_id : 'item_' . $item->inventory_item_id;
            })->map(function ($group) {
                $first = $group->first();
                return [
                    'name' => $first->item_name,
                    'sku' => $first->item_sku,
                    'type' => $first->master_product_id ? 'Produk Jadi' : ($first->inventoryItem ? ucfirst($first->inventoryItem->type) : 'Barang Operasional'),
                    'unit' => $first->inventoryItem ? $first->inventoryItem->unit : 'PCS',
                    'qty_ordered' => $group->sum('quantity'),
                    'qty_received' => $group->sum('received_quantity'),
                    'avg_price' => $group->avg('unit_price'),
                    'total_amount' => $group->sum(function($item) {
                        return $item->quantity * $item->unit_price;
                    }),
                ];
            })->sortByDesc('total_amount');

        return view('inventory.purchase_orders.print_report', compact(
            'orders', 'totalBelanja', 'totalPoTerbuka', 'totalPoSelesai',
            'itemsBreakdown', 'startDate', 'endDate'
        ));
    }
}
