<?php

namespace App\Http\Controllers;

use App\Models\ReturnOrder;
use App\Models\Store;
use App\Jobs\PullReturnsFromShopee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReturnOrderController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search = $request->query('search');

        $returns = ReturnOrder::with(['order.customer', 'store', 'items.orderItem.marketplaceProduct.masterProduct'])
            ->where('tenant_id', $tenantId)
            ->when($search, function ($query, $search) {
                return $query->where('return_sn', 'like', "%{$search}%")
                             ->orWhereHas('order', function ($q) use ($search) {
                                 $q->where('invoice_number', 'like', "%{$search}%")
                                   ->orWhere('order_marketplace_id', 'like', "%{$search}%");
                             });
            })
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('returns.index', compact('returns', 'search'));
    }

    public function sync()
    {
        $tenantId = Auth::user()->tenant_id;
        $stores = Store::where('tenant_id', $tenantId)->where('status', 'connected')->get();

        if ($stores->isEmpty()) {
            return back()->with('error', 'Tidak ada toko yang terhubung untuk sinkronisasi retur.');
        }

        foreach ($stores as $store) {
            // Kita bisa execute langsung atau via queue, tapi karena manual click, kita dispatchSync agar langsung jadi
            PullReturnsFromShopee::dispatchSync($store);
        }

        return back()->with('success', 'Berhasil menarik data retur terbaru dari Marketplace.');
    }

    public function restock(Request $request, ReturnOrder $returnOrder)
    {
        abort_unless($returnOrder->tenant_id === Auth::user()->tenant_id, 403);

        if ($returnOrder->is_restocked) {
            return back()->with('error', 'Barang retur ini sudah dikembalikan ke stok gudang sebelumnya.');
        }

        $request->validate([
            'inspection_status' => 'required|in:GOOD,DEFECTIVE',
            'inspection_notes' => 'nullable|string',
        ]);

        $status = $request->input('inspection_status');
        $notes = $request->input('inspection_notes');

        if ($status === 'GOOD') {
            // Kembalikan setiap item ke stok
            foreach ($returnOrder->items as $rItem) {
                $masterProduct = $rItem->orderItem->marketplaceProduct->masterProduct ?? null;
                if ($masterProduct) {
                    // Catat pergerakan masuk (restock)
                    $masterProduct->recordStockMovement(
                        $rItem->quantity,
                        'in',
                        'Terima Retur (Layak Jual): ' . $returnOrder->return_sn . ($notes ? ' - ' . $notes : ''),
                        Auth::id()
                    );
                }
            }
            $message = 'Barang retur berhasil diterima (Layak Jual) dan stok gudang otomatis bertambah.';
        } else {
            $message = 'Barang retur berhasil diproses sebagai (Rusak/Defective). Stok gudang tidak bertambah.';
        }

        // Tandai sudah restocked / diproses beserta hasil inspeksi
        $returnOrder->update([
            'is_restocked' => true,
            'inspection_status' => $status,
            'inspection_notes' => $notes,
        ]);

        return back()->with('success', $message);
    }
}
