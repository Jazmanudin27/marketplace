<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\ProductionOrder;
use App\Models\MasterProduct;
use App\Models\ProductRecipe;
use App\Models\Department;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionOrderController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        $pendingOrders = ProductionOrder::where('tenant_id', $tenantId)
            ->with('masterProduct', 'requestedBy')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        $producingOrders = ProductionOrder::where('tenant_id', $tenantId)
            ->with('masterProduct', 'requestedBy')
            ->where('status', 'producing')
            ->orderBy('updated_at', 'desc')
            ->get();

        $completedOrders = ProductionOrder::where('tenant_id', $tenantId)
            ->with('masterProduct', 'requestedBy')
            ->whereIn('status', ['completed', 'cancelled'])
            ->orderByDesc('updated_at')
            ->paginate(15);

        return view('inventory.production_orders.index', compact('pendingOrders', 'producingOrders', 'completedOrders'));
    }

    public function start(ProductionOrder $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        $order->update(['status' => 'producing']);
        return back()->with('success', 'Proses produksi barang #' . $order->id . ' telah dimulai.');
    }

    public function cancel(ProductionOrder $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        $order->update(['status' => 'cancelled']);
        return back()->with('success', 'Permintaan produksi #' . $order->id . ' telah dibatalkan.');
    }

    public function complete(Request $request, ProductionOrder $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);

        if ($order->status !== 'producing') {
            return back()->with('error', 'Pesanan harus dalam status Sedang Diproduksi untuk dapat diselesaikan.');
        }

        $request->validate([
            'produced_qty' => 'required|integer|min:1',
            'items' => 'nullable|array',
            'items.*.inventory_item_id' => 'required|exists:inventory_items,id',
            'items.*.actual_qty' => 'required|numeric|min:0',
            'labor_items' => 'nullable|array',
            'labor_items.*.service_name' => 'required|string|max:255',
            'labor_items.*.actual_cost' => 'required|numeric|min:0',
        ]);

        $producedQty = $request->produced_qty;

        // Auto-heal/get Produksi department ID
        $produksiDept = Department::where('tenant_id', Auth::user()->tenant_id)
            ->where(function($q) {
                $q->where('name', 'like', '%produksi%')
                  ->orWhere('code', 'like', '%produksi%');
            })
            ->first();
        if (!$produksiDept) {
            $produksiDept = Department::create([
                'tenant_id' => Auth::user()->tenant_id,
                'name'      => 'Produksi',
                'code'      => 'PRODUKSI',
                'is_active' => true,
            ]);
        }
        $produksiDeptId = $produksiDept->id;

        // Auto-heal/get Gudang Jadi department ID
        $gudangJadiDept = Department::where('tenant_id', Auth::user()->tenant_id)
            ->where(function($q) {
                $q->where('name', 'like', '%gudang jadi%')
                  ->orWhere('code', 'like', '%jadi%');
            })
            ->first();
        if (!$gudangJadiDept) {
            $gudangJadiDept = Department::create([
                'tenant_id' => Auth::user()->tenant_id,
                'name'      => 'Gudang Jadi',
                'code'      => 'GUDANG_JADI',
                'is_active' => true,
            ]);
        }
        $gudangJadiDeptId = $gudangJadiDept->id;

        DB::transaction(function() use ($request, $order, $producedQty, $produksiDeptId, $gudangJadiDeptId) {
            $totalMaterialCost = 0;

            // Process actual material consumption
            if ($request->has('items')) {
                foreach ($request->items as $itemInput) {
                    $material = \App\Models\InventoryItem::findOrFail($itemInput['inventory_item_id']);
                    $actualQty = (float) $itemInput['actual_qty'];

                    if ($actualQty > 0) {
                        // Deduct stock of raw material
                        $material->recordStockMovement(
                            -$actualQty,
                            'out',
                            "Konsumsi Produksi SPK #{$order->id}",
                            Auth::id()
                        );

                        // Tag StockMovement with department
                        $movement = \App\Models\StockMovement::where('inventory_item_id', $material->id)
                            ->where('reference', "Konsumsi Produksi SPK #{$order->id}")
                            ->orderBy('id', 'desc')
                            ->first();
                        if ($movement) {
                            $movement->update(['department_id' => $produksiDeptId]);
                        }

                        // Material HPP contribution
                        $totalMaterialCost += ($actualQty * ($material->cost_price ?: 0));
                    }
                }
            }

            // Save actual labor costs
            $totalLaborCost = 0;
            if ($request->has('labor_items')) {
                foreach ($request->labor_items as $laborInput) {
                    $order->actualLabors()->create([
                        'service_name' => $laborInput['service_name'],
                        'actual_cost'  => $laborInput['actual_cost'],
                    ]);
                    $totalLaborCost += $laborInput['actual_cost'];
                }
            }

            // Calculate new HPP for finished good (MasterProduct)
            $totalCostOfBatch = $totalMaterialCost + $totalLaborCost;
            $costPerUnitNew = $totalCostOfBatch / $producedQty;

            $product = $order->masterProduct;
            $currentStock = $product->stock ?: 0;
            $currentHpp = $product->cost_price ?: 0;

            // Weighted Average Formula
            $newHpp = (($currentStock * $currentHpp) + ($producedQty * $costPerUnitNew)) / ($currentStock + $producedQty);

            // Update product HPP (cost_price)
            $product->update([
                'cost_price' => $newHpp
            ]);

            // Add stock of finished good
            $product->recordStockMovement(
                $producedQty,
                'in',
                "Penerimaan Produksi Selesai SPK #{$order->id}",
                Auth::id()
            );

            // Tag product stock movement with department (Gudang Jadi)
            $productMovement = \App\Models\StockMovement::where('master_product_id', $product->id)
                ->where('reference', "Penerimaan Produksi Selesai SPK #{$order->id}")
                ->orderBy('id', 'desc')
                ->first();
            if ($productMovement) {
                $productMovement->update(['department_id' => $gudangJadiDeptId]);
            }

            // Update production order status and quantity
            $order->update([
                'status' => 'completed',
                'quantity' => $producedQty
            ]);

            // Trigger stock deduction for pending orders
            $pendingOrders = Order::where('tenant_id', $order->tenant_id)
                ->where('is_stock_deducted', false)
                ->whereIn('order_status', [Order::STATUS_READY_TO_SHIP, Order::STATUS_UNPAID])
                ->whereHas('items', function ($query) use ($order) {
                    $query->where('master_product_id', $order->master_product_id);
                })
                ->orderBy('order_date', 'asc')
                ->get();

            foreach ($pendingOrders as $pendingOrder) {
                $pendingOrder->processStockDeduction();
            }
        });

        return redirect()->route('production_orders.show', $order)->with('success', 'Produksi berhasil diselesaikan dan HPP dihitung ulang.');
    }

    public function show(ProductionOrder $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);

        $order->load(['masterProduct', 'requestedBy', 'actualLabors']);

        // Load actual raw materials consumed from StockMovement records
        $movements = \App\Models\StockMovement::where('tenant_id', $order->tenant_id)
            ->where('reference', "Konsumsi Produksi SPK #{$order->id}")
            ->whereNotNull('inventory_item_id')
            ->with('inventoryItem')
            ->get();

        $materialCostDetails = [];
        $totalMaterialCost = 0;

        foreach ($movements as $m) {
            $qtyConsumed = abs($m->quantity);
            $price = $m->inventoryItem->cost_price ?: 0;
            $costOfItem = $qtyConsumed * $price;

            $materialCostDetails[] = [
                'name' => $m->inventoryItem->name,
                'sku' => $m->inventoryItem->sku,
                'unit' => $m->inventoryItem->unit,
                'qty' => $qtyConsumed,
                'price' => $price,
                'total_cost' => $costOfItem
            ];

            $totalMaterialCost += $costOfItem;
        }

        $totalLaborCost = $order->actualLabors->sum('actual_cost');
        $totalProductionCost = $totalMaterialCost + $totalLaborCost;
        $calculatedHpp = $order->quantity > 0 ? $totalProductionCost / $order->quantity : 0;

        return view('inventory.production_orders.show', compact(
            'order',
            'materialCostDetails',
            'totalMaterialCost',
            'totalLaborCost',
            'totalProductionCost',
            'calculatedHpp'
        ));
    }

    public function orderRequirements()
    {
        $tenantId = Auth::user()->tenant_id;

        // Fetch Online Items
        $onlineItems = \App\Models\OrderItem::whereHas('order', function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->whereIn('order_status', [\App\Models\Order::STATUS_READY_TO_SHIP, \App\Models\Order::STATUS_UNPAID]);
        })->with(['order.store.channel', 'masterProduct'])->get();

        // Fetch Offline Items
        $offlineItems = \App\Models\OfflineSaleItem::whereHas('offlineSale', function($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)
              ->where('status', \App\Models\OfflineSale::STATUS_PENDING);
        })->with(['offlineSale', 'masterProduct'])->get();

        $requirements = collect();

        foreach ($onlineItems as $item) {
            if (!$item->master_product_id) continue;
            $stock = (float) ($item->masterProduct ? $item->masterProduct->stock : 0);
            $qty = (float) $item->quantity;
            $shortage = $qty > $stock ? $qty - $stock : 0;

            $requirements->push((object)[
                'id' => 'online_' . $item->id,
                'source' => 'Online',
                'channel' => $item->order->store->channel->name ?? 'Marketplace',
                'store' => $item->order->store->store_name ?? '—',
                'ref_number' => $item->order->invoice_number ?? $item->order->order_number,
                'product_id' => $item->master_product_id,
                'product_name' => $item->masterProduct->name ?? $item->product_name,
                'sku' => $item->masterProduct->sku ?? $item->sku,
                'unit' => $item->masterProduct->unit ?? 'pcs',
                'qty_ordered' => $qty,
                'current_stock' => $stock,
                'shortage' => $shortage,
                'order_date' => $item->order->order_date ?? $item->order->created_at,
            ]);
        }

        foreach ($offlineItems as $item) {
            if (!$item->master_product_id) continue;
            $stock = (float) ($item->masterProduct ? $item->masterProduct->stock : 0);
            $qty = (float) $item->quantity;
            $shortage = $qty > $stock ? $qty - $stock : 0;

            $requirements->push((object)[
                'id' => 'offline_' . $item->id,
                'source' => 'Offline',
                'channel' => 'Offline Store',
                'store' => 'Toko Fisik',
                'ref_number' => $item->offlineSale->sale_number,
                'product_id' => $item->master_product_id,
                'product_name' => $item->masterProduct->name ?? $item->product_name,
                'sku' => $item->masterProduct->sku ?? $item->sku,
                'unit' => $item->masterProduct->unit ?? 'pcs',
                'qty_ordered' => $qty,
                'current_stock' => $stock,
                'shortage' => $shortage,
                'order_date' => $item->offlineSale->sold_at ?? $item->offlineSale->created_at,
            ]);
        }

        $requirements = $requirements->sortByDesc('order_date');

        return view('inventory.production_orders.requirements', compact('requirements'));
    }

    public function createFromOrder(Request $request)
    {
        $request->validate([
            'master_product_id' => 'required|exists:master_products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = \App\Models\MasterProduct::where('tenant_id', Auth::user()->tenant_id)
            ->findOrFail($request->master_product_id);

        ProductionOrder::create([
            'tenant_id' => Auth::user()->tenant_id,
            'master_product_id' => $product->id,
            'quantity' => $request->quantity,
            'status' => 'pending',
            'requested_by' => Auth::id(),
        ]);

        return redirect()->route('production_orders.index')
            ->with('success', "Permintaan SPK untuk {$product->name} (Qty: {$request->quantity}) berhasil ditambahkan ke antrean.");
    }
}
