<?php

namespace App\Http\Controllers;

use App\Models\ReturnOrder;
use App\Models\ReturnOrderItem;
use App\Models\Store;
use App\Models\Order;
use App\Models\OrderItem;
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

        $reasonsStats = ReturnOrder::where('tenant_id', $tenantId)
            ->whereNotNull('reason')
            ->selectRaw('reason, count(*) as count')
            ->groupBy('reason')
            ->orderByDesc('count')
            ->limit(5)
            ->get();

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
            'defectiveCount',
            'reasonsStats'
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
            'items' => 'required|array',
            'items.*.inspection_status' => 'required|in:GOOD,DEFECTIVE',
            'items.*.inspection_notes' => 'nullable|string',
            'items.*.photo' => 'nullable|image|max:4096',
        ]);

        $itemInputs = $request->input('items');
        $hasGood = false;

        foreach ($returnOrder->items as $rItem) {
            $input = $itemInputs[$rItem->id] ?? null;
            if (!$input) continue;

            $status = $input['inspection_status'];
            $notes = $input['inspection_notes'] ?? null;

            // Handle photo upload
            $photoPath = null;
            if ($request->hasFile("items.{$rItem->id}.photo")) {
                $file = $request->file("items.{$rItem->id}.photo");
                
                $uploadDir = public_path('uploads/returns');
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }
                
                $filename = 'qc_' . $rItem->id . '_' . time() . '.' . $file->getClientOriginalExtension();
                $file->move($uploadDir, $filename);
                $photoPath = 'uploads/returns/' . $filename;
            }

            // Update status per item
            $updateData = [
                'inspection_status' => $status,
                'inspection_notes' => $notes,
            ];
            
            if ($photoPath) {
                $updateData['inspection_photo'] = $photoPath;
            }

            $rItem->update($updateData);

            if ($status === 'GOOD') {
                $hasGood = true;
                $masterProduct = $rItem->orderItem->marketplaceProduct->masterProduct ?? null;
                if ($masterProduct) {
                    $masterProduct->recordStockMovement(
                        $rItem->quantity,
                        'in',
                        'Terima Retur (Layak Jual): ' . $returnOrder->return_sn . ($notes ? ' - ' . $notes : ''),
                        Auth::id()
                    );
                }
            }
        }

        // Tandai sudah restocked / diproses beserta hasil inspeksi fallback & audit trail
        $overallStatus = $hasGood ? 'GOOD' : 'DEFECTIVE';
        $returnOrder->update([
            'is_restocked' => true,
            'inspection_status' => $overallStatus,
            'checked_by' => Auth::id(),
        ]);

        return back()->with('success', 'Hasil pemeriksaan fisik barang retur berhasil disimpan.');
    }

    public function createReplacementOrder(Request $request, ReturnOrder $returnOrder)
    {
        abort_unless($returnOrder->tenant_id === Auth::user()->tenant_id, 403);

        if ($returnOrder->replacement_order_id) {
            return back()->with('error', 'Pesanan pengganti untuk retur ini sudah dibuat sebelumnya.');
        }

        if (!$returnOrder->is_restocked) {
            return back()->with('error', 'Silakan selesaikan pemeriksaan QC terlebih dahulu sebelum mengirim barang pengganti.');
        }

        $originalOrder = $returnOrder->order;
        if (!$originalOrder) {
            return back()->with('error', 'Pesanan asli tidak ditemukan.');
        }

        // Generate unique replacement invoice and marketplace order ID
        $replInvoice = 'REPL-' . ($originalOrder->invoice_number ?: $originalOrder->id) . '-' . time();
        $replMarketplaceId = 'REPL-' . ($originalOrder->order_marketplace_id ?: $originalOrder->id);

        // Create the new replacement Order
        $replacementOrder = Order::create([
            'tenant_id' => $returnOrder->tenant_id,
            'store_id' => $returnOrder->store_id,
            'customer_id' => $originalOrder->customer_id,
            'order_marketplace_id' => $replMarketplaceId,
            'invoice_number' => $replInvoice,
            'order_status' => Order::STATUS_READY_TO_SHIP,
            'packing_status' => 'PENDING',
            'buyer_name' => $originalOrder->buyer_name,
            'buyer_phone' => $originalOrder->buyer_phone,
            'shipping_address' => $originalOrder->shipping_address,
            'total_amount' => 0.00,
            'shipping_fee' => 0.00,
            'discount_amount' => 0.00,
            'marketplace_fee' => 0.00,
            'net_amount' => 0.00,
            'courier' => $originalOrder->courier,
            'order_date' => now(),
            'is_stock_deducted' => true,
            'recon_status' => 'resolved',
            'recon_notes' => 'Replacement order for return ' . $returnOrder->return_sn,
        ]);

        // Copy return items to the new order and deduct stock
        foreach ($returnOrder->items as $rItem) {
            $origOrderItem = $rItem->orderItem;
            if (!$origOrderItem) continue;

            $masterProductId = $origOrderItem->master_product_id;
            if (!$masterProductId && $origOrderItem->marketplace_product_id) {
                $mp = \App\Models\MarketplaceProduct::find($origOrderItem->marketplace_product_id);
                if ($mp) {
                    $masterProductId = $mp->master_product_id;
                }
            }
            
            $newOrderItem = OrderItem::create([
                'order_id' => $replacementOrder->id,
                'marketplace_product_id' => $origOrderItem->marketplace_product_id,
                'master_product_id' => $masterProductId,
                'product_name' => '[PENGGANTI] ' . $origOrderItem->product_name,
                'quantity' => $rItem->quantity,
                'price' => 0.00,
                'total_price' => 0.00,
            ]);

            // Deduct stock from warehouse
            $masterProduct = $newOrderItem->masterProduct;
            if ($masterProduct) {
                $masterProduct->recordStockMovement(
                    $rItem->quantity,
                    'out',
                    'Kirim Barang Pengganti Retur: ' . $returnOrder->return_sn,
                    Auth::id()
                );
            }
        }

        // Link replacement order to the return order
        $returnOrder->update([
            'replacement_order_id' => $replacementOrder->id,
        ]);

        return back()->with('success', 'Pesanan pengganti berhasil dibuat (' . $replInvoice . ') dan otomatis masuk ke antrean pengemasan.');
    }
}
