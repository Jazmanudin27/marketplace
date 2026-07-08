<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReceivePurchaseOrderController extends Controller
{
    /**
     * Tampilkan form penerimaan barang untuk sebuah PO.
     */
    public function show(PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->tenant_id === Auth::user()->tenant_id, 403);

        if (!in_array($purchaseOrder->status, ['ordered', 'partially_received'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Purchase Order tidak dalam status yang bisa diterima barangnya.');
        }

        $purchaseOrder->load(['supplier', 'department', 'items.inventoryItem', 'items.masterProduct']);

        return view('inventory.purchase_orders.receive', compact('purchaseOrder'));
    }

    /**
     * Proses penerimaan barang: update received_qty, tambah stok, catat movement.
     */
    public function store(Request $request, PurchaseOrder $purchaseOrder)
    {
        abort_unless($purchaseOrder->tenant_id === Auth::user()->tenant_id, 403);

        if (!in_array($purchaseOrder->status, ['ordered', 'partially_received'])) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('error', 'Purchase Order tidak dalam status yang bisa diterima barangnya.');
        }

        $request->validate([
            'items'                => 'required|array|min:1',
            'items.*.item_id'      => 'required|integer',
            'items.*.received_qty' => 'required|integer|min:0',
            'receive_date'         => 'required|date',
            'notes'                => 'nullable|string|max:500',
        ]);

        $purchaseOrder->load('items.inventoryItem');
        $tenantId = Auth::user()->tenant_id;
        $userId   = Auth::id();

        DB::transaction(function () use ($request, $purchaseOrder, $tenantId, $userId) {
            $allReceived    = true;
            $anyReceived    = false;
            $receiveDate    = $request->receive_date;
            $poItemsIndexed = $purchaseOrder->items->keyBy('id');

            foreach ($request->items as $row) {
                $poItem = $poItemsIndexed->get($row['item_id']);
                if (!$poItem) continue;

                $newQty = (int) $row['received_qty'];
                if ($newQty <= 0) continue;

                // Hitung tambahan qty (yang belum pernah diterima)
                $alreadyReceived = (int) $poItem->received_quantity;
                $maxCanReceive   = (int) $poItem->quantity - $alreadyReceived;

                if ($maxCanReceive <= 0) continue;

                $actualReceive = min($newQty, $maxCanReceive);
                $anyReceived   = true;

                // Update received_quantity di PO item
                $poItem->increment('received_quantity', $actualReceive);

                // Cek apakah item ini masih kurang
                $newReceived = $alreadyReceived + $actualReceive;
                if ($newReceived < (int) $poItem->quantity) {
                    $allReceived = false;
                }

                // Tambah stok inventory_item
                if ($poItem->inventory_item_id && $poItem->inventoryItem) {
                    $item = $poItem->inventoryItem;
                    $item->increment('stock', $actualReceive);
                    $newStock = $item->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'inventory_item_id' => $item->id,
                        'department_id'     => $purchaseOrder->department_id,
                        'purchase_order_id' => $purchaseOrder->id,
                        'user_id'           => $userId,
                        'type'              => 'in',
                        'quantity'          => $actualReceive,
                        'reference'         => 'Terima PO ' . $purchaseOrder->po_number . ($request->notes ? ' — ' . $request->notes : ''),
                        'balance_after'     => $newStock,
                        'created_at'        => $receiveDate,
                        'updated_at'        => $receiveDate,
                    ]);
                }

                // Jika master product
                if ($poItem->master_product_id && $poItem->masterProduct) {
                    $product = $poItem->masterProduct;
                    $product->increment('stock', $actualReceive);
                    $newStock = $product->fresh()->stock;

                    StockMovement::create([
                        'tenant_id'         => $tenantId,
                        'master_product_id' => $product->id,
                        'purchase_order_id' => $purchaseOrder->id,
                        'user_id'           => $userId,
                        'type'              => 'in',
                        'quantity'          => $actualReceive,
                        'reference'         => 'Terima PO ' . $purchaseOrder->po_number . ($request->notes ? ' — ' . $request->notes : ''),
                        'balance_after'     => $newStock,
                        'created_at'        => $receiveDate,
                        'updated_at'        => $receiveDate,
                    ]);
                }
            }

            if (!$anyReceived) {
                throw new \Exception('Tidak ada qty yang diisi untuk diterima.');
            }

            // Update status PO
            $purchaseOrder->refresh();
            $allItemsComplete = $purchaseOrder->items->every(function ($item) {
                return (int) $item->received_quantity >= (int) $item->quantity;
            });

            $purchaseOrder->update([
                'status' => $allItemsComplete ? 'received' : 'partially_received',
            ]);
        });

        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('success', 'Penerimaan barang berhasil dicatat. Stok telah diperbarui.');
    }
}
