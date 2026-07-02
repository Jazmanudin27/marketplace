<?php

namespace App\Http\Controllers;

use App\Models\ReturnOrder;
use App\Models\Store;
use App\Jobs\PullReturnsFromShopee;
use App\Jobs\PullReturnsFromTiktok;
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

        $totalReturns = ReturnOrder::where('tenant_id', $tenantId)->count();
        $pendingQc = ReturnOrder::where('tenant_id', $tenantId)->where('is_restocked', false)->count();
        $goodCount = ReturnOrder::where('tenant_id', $tenantId)->where('is_restocked', true)->where('inspection_status', 'GOOD')->count();
        $defectiveCount = ReturnOrder::where('tenant_id', $tenantId)->where('is_restocked', true)->where('inspection_status', 'DEFECTIVE')->count();

        return view('returns.index', compact(
            'returns',
            'search',
            'channels',
            'stores',
            'statuses',
            'channelId',
            'storeId',
            'status',
            'isRestocked',
            'totalReturns',
            'pendingQc',
            'goodCount',
            'defectiveCount'
        ));
    }

    public function export(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search = $request->query('search');
        $channelId = $request->query('channel_id');
        $storeId = $request->query('store_id');
        $status = $request->query('status');
        $isRestocked = $request->query('is_restocked');

        $query = ReturnOrder::with(['order', 'store.channel', 'items.orderItem'])
            ->where('tenant_id', $tenantId);

        // Apply filters (same logic as index)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('return_sn', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($o) use ($search) {
                      $o->where('invoice_number', 'like', "%{$search}%")
                        ->orWhere('order_marketplace_id', 'like', "%{$search}%");
                  });
            });
        }
        if ($channelId) {
            $query->whereHas('store', function ($q) use ($channelId) {
                $q->where('channel_id', $channelId);
            });
        }
        if ($storeId) {
            $query->where('store_id', $storeId);
        }
        if ($status) {
            $query->where('status', $status);
        }
        if ($isRestocked !== null && $isRestocked !== '') {
            $query->where('is_restocked', $isRestocked);
        }

        $returns = $query->orderByDesc('created_at')->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="data_retur_pesanan_' . date('Ymd_His') . '.csv"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $callback = function () use ($returns) {
            $file = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel compliance
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($file, [
                'WAKTU DIBUAT',
                'SN RETUR',
                'CHANNEL',
                'TOKO',
                'INVOICE ASLI',
                'MARKETPLACE ORDER ID',
                'PEMBELI',
                'BARANG YANG DIRETUR',
                'ALASAN RETUR',
                'STATUS RETUR',
                'NOMINAL REFUND',
                'STATUS INSPEKSI GUDANG',
                'CATATAN INSPEKSI'
            ]);

            foreach ($returns as $ret) {
                $itemsStr = '';
                foreach ($ret->items as $rItem) {
                    $itemsStr .= $rItem->quantity . 'x ' . ($rItem->orderItem->product_name ?? 'Item') . '; ';
                }
                $itemsStr = rtrim($itemsStr, '; ');

                $qcStatus = 'Belum QC';
                if ($ret->is_restocked) {
                    $qcStatus = $ret->inspection_status === 'GOOD' ? 'Layak Jual' : 'Rusak / Cacat';
                }

                fputcsv($file, [
                    $ret->created_at->format('Y-m-d H:i:s'),
                    $ret->return_sn,
                    $ret->store->channel->name ?? '-',
                    $ret->store->store_name ?? '-',
                    $ret->order->invoice_number ?? '-',
                    $ret->order->order_marketplace_id ?? '-',
                    $ret->order->buyer_name ?? '-',
                    $itemsStr,
                    $ret->reason ?? '-',
                    $ret->status ?? '-',
                    $ret->refund_amount,
                    $qcStatus,
                    $ret->inspection_notes ?? '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function sync()
    {
        $tenantId = Auth::user()->tenant_id;
        $stores = Store::where('tenant_id', $tenantId)->where('status', 'connected')->get();

        if ($stores->isEmpty()) {
            return back()->with('error', 'Tidak ada toko yang terhubung untuk sinkronisasi retur.');
        }

        foreach ($stores as $store) {
            // Rute penarikan retur sesuai channel masing-masing
            if ($store->channel && $store->channel->code === 'shopee') {
                PullReturnsFromShopee::dispatchSync($store);
            } elseif ($store->channel && $store->channel->code === 'tiktok') {
                PullReturnsFromTiktok::dispatchSync($store);
            }
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
