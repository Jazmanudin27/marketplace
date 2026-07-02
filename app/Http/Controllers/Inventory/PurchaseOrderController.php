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
        $products = MasterProduct::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('inventory.purchase_orders.create', compact('suppliers', 'products'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'po_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.master_product_id' => 'required|exists:master_products,id',
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
                'po_number' => $poNumber,
                'po_date' => $request->po_date,
                'status' => 'draft',
                'notes' => $request->notes,
                'total_amount' => 0,
            ]);

            foreach ($request->items as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $subtotal;

                $po->items()->create([
                    'master_product_id' => $item['master_product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'received_quantity' => 0,
                ]);
            }

            $po->update(['total_amount' => $totalAmount]);
        });

        return redirect()->route('purchase_orders.index')
            ->with('success', 'Purchase Order berhasil dibuat sebagai draf.');
    }

    public function show(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->tenant_id === Auth::user()->tenant_id, 403);
        $purchaseOrder->load(['supplier', 'items.masterProduct']);

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
        $products = MasterProduct::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $purchaseOrder->load('items');

        return view('inventory.purchase_orders.edit', compact('purchaseOrder', 'suppliers', 'products'));
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
            'po_date' => 'required|date',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.master_product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        DB::transaction(function () use ($request, $purchaseOrder) {
            $totalAmount = 0;

            $purchaseOrder->update([
                'supplier_id' => $request->supplier_id,
                'po_date' => $request->po_date,
                'notes' => $request->notes,
            ]);

            // Clear old items and recreate
            $purchaseOrder->items()->delete();

            foreach ($request->items as $item) {
                $subtotal = $item['quantity'] * $item['unit_price'];
                $totalAmount += $subtotal;

                $purchaseOrder->items()->create([
                    'master_product_id' => $item['master_product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'received_quantity' => $item['received_quantity'] ?? 0,
                ]);
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
        $purchaseOrder->load(['supplier', 'items.masterProduct', 'tenant']);

        return view('inventory.purchase_orders.print', compact('purchaseOrder'));
    }

    public function getItems(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->tenant_id === Auth::user()->tenant_id, 403);
        
        $items = $purchaseOrder->items()->with('masterProduct')->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'master_product_id' => $item->master_product_id,
                'product_name' => $item->masterProduct->name,
                'sku' => $item->masterProduct->sku,
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
}
