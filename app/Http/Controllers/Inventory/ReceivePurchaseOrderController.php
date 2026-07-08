<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
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
     * Proses pembuatan draft Penerimaan Barang (Goods Receipt) dari PO.
     * Status Goods Receipt = 'pending', belum mempengaruhi stok atau PO qty.
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

        $goodsReceipt = DB::transaction(function () use ($request, $purchaseOrder, $tenantId, $userId) {
            $anyReceived    = false;
            $totalAmount    = 0;
            $poItemsIndexed = $purchaseOrder->items->keyBy('id');

            // Generate GR
            $receiptNumber = GoodsReceipt::generateReceiptNumber();
            $receipt = GoodsReceipt::create([
                'tenant_id'         => $tenantId,
                'supplier_id'       => $purchaseOrder->supplier_id,
                'purchase_order_id' => $purchaseOrder->id,
                'department_id'     => $purchaseOrder->department_id,
                'receipt_number'    => $receiptNumber,
                'receipt_date'      => $request->receive_date,
                'source'            => 'po',
                'status'            => 'pending', // menunggu approval
                'notes'             => $request->notes,
                'total_amount'      => 0,
                'created_by'        => $userId,
            ]);

            foreach ($request->items as $row) {
                $poItem = $poItemsIndexed->get($row['item_id']);
                if (!$poItem) continue;

                $qty = (int) $row['received_qty'];
                if ($qty <= 0) continue;

                // Batasi qty yang bisa diterima
                $alreadyReceived = (int) $poItem->received_quantity;
                $maxCanReceive   = (int) $poItem->quantity - $alreadyReceived;
                $actualReceive   = min($qty, $maxCanReceive);

                if ($actualReceive <= 0) continue;
                $anyReceived = true;

                $subtotal = $actualReceive * $poItem->unit_price;
                $totalAmount += $subtotal;

                // Buat item GR
                $receipt->items()->create([
                    'inventory_item_id' => $poItem->inventory_item_id,
                    'master_product_id' => $poItem->master_product_id,
                    'quantity'          => $actualReceive,
                    'unit_price'        => $poItem->unit_price,
                    'notes'             => $row['notes'] ?? null,
                ]);
            }

            if (!$anyReceived) {
                throw new \Exception('Tidak ada qty barang yang valid untuk diterima.');
            }

            $receipt->update(['total_amount' => $totalAmount]);

            return $receipt;
        });

        return redirect()->route('goods_receipts.show', $goodsReceipt)
            ->with('success', 'Penerimaan barang PO berhasil dicatat. Silakan lakukan Approval agar stok bertambah.');
    }
}
