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
        $channelId = $request->query('channel_id');
        $storeId = $request->query('store_id');
        $status = $request->query('status');
        $isRestocked = $request->query('is_restocked');

        $query = ReturnOrder::with(['order.customer', 'store.channel', 'items.orderItem.marketplaceProduct.masterProduct'])
            ->where('tenant_id', $tenantId);

        // Filter Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('return_sn', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($o) use ($search) {
                      $o->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('order_marketplace_id', 'like', "%{$search}%");
                  });
            });
        }

        // Filter Channel
        if ($channelId) {
            $query->whereHas('store', function ($q) use ($channelId) {
                $q->where('channel_id', $channelId);
            });
        }

        // Filter Toko
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        // Filter Status Retur
        if ($status) {
            $query->where('status', $status);
        }

        // Filter Tindakan Gudang (is_restocked)
        if ($isRestocked !== null && $isRestocked !== '') {
            $query->where('is_restocked', $isRestocked);
        }

        $returns = $query->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        // Support data for filters
        $channels = \App\Models\Channel::all();
        $stores = \App\Models\Store::where('tenant_id', $tenantId)->get();
        $statuses = ReturnOrder::where('tenant_id', $tenantId)
            ->whereNotNull('status')
            ->distinct()
            ->pluck('status');

        return view('returns.index', compact(
            'returns',
            'search',
            'channels',
            'stores',
            'statuses',
            'channelId',
            'storeId',
            'status',
            'isRestocked'
        ));
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
