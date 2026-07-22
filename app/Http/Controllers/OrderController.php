<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = Order::with(['store.channel', 'items.masterProduct', 'spks'])
            ->where('tenant_id', $tenantId);

        // Filter Channel
        if ($request->filled('channel_id')) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('channel_id', $request->channel_id);
            });
        }

        // Filter Toko
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter Kurir
        if ($request->filled('courier')) {
            $query->where('courier', 'like', '%' . $request->courier . '%');
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }

        // Filter PO vs Ready Stock
        if ($request->filled('is_po')) {
            if ($request->is_po === 'po') {
                $query->whereHas('items.masterProduct', function ($q) {
                    $q->where('is_preorder', true);
                });
            } elseif ($request->is_po === 'ready') {
                $query->whereDoesntHave('items.masterProduct', function ($q) {
                    $q->where('is_preorder', true);
                });
            }
        }

        // Filter Status SPK
        if ($request->filled('spk_status')) {
            if ($request->spk_status === 'has_spk') {
                $query->has('spks');
            } elseif ($request->spk_status === 'no_spk') {
                $query->doesntHave('spks');
            }
        }

        // Filter Batas Kirim (Deadline Status)
        if ($request->filled('deadline_status')) {
            $deadlineStatus = $request->deadline_status;
            if ($deadlineStatus === 'overdue') {
                $query->whereNotNull('ship_before_date')
                    ->where('ship_before_date', '<', now());
            } elseif ($deadlineStatus === 'urgent') {
                $query->whereNotNull('ship_before_date')
                    ->where('ship_before_date', '>', now())
                    ->where('ship_before_date', '<=', now()->addHours(24));
            } elseif ($deadlineStatus === 'safe') {
                $query->whereNotNull('ship_before_date')
                    ->where('ship_before_date', '>', now()->addHours(24));
            }
        }

        // Filter Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

        // Filter Dropship
        if ($request->filled('is_dropship')) {
            $query->where('is_dropship', $request->is_dropship);
        }

        // Filter Alasan Pembatalan
        if ($request->filled('cancel_reason')) {
            $query->where('cancel_reason', 'like', '%' . $request->cancel_reason . '%');
        }

        $orders = $query->orderByDesc('order_date')
            ->paginate(20)
            ->withQueryString();

        // Data pendukung untuk UI filter
        $channels = \App\Models\Channel::all();
        $stores = \App\Models\Store::where('tenant_id', $tenantId)->get();
        
        $couriers = Order::where('tenant_id', $tenantId)
            ->whereNotNull('courier')
            ->where('courier', '!=', '')
            ->distinct()
            ->pluck('courier');
            
        $statuses = Order::where('tenant_id', $tenantId)
            ->whereNotNull('order_status')
            ->distinct()
            ->pluck('order_status');

        // Pesanan mendekati/melewati batas pengiriman
        $urgentOrders = Order::with('store')
            ->where('tenant_id', $tenantId)
            ->deadlineUrgent()
            ->orderBy('ship_before_date')
            ->get();

        return view('orders.index', compact('orders', 'channels', 'stores', 'couriers', 'statuses', 'urgentOrders'));
    }

    public function show(Order $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        $order->load('items.masterProduct', 'store.channel', 'spks.items');
        return view('orders.show', compact('order'));
    }

    public function process(Order $order)
    {
        // Tetap ada untuk backward compatibility, tapi kita buatkan fungsi ship() yang lebih spesifik
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        $store = $order->store;
        
        if ($store->channel->code === 'shopee') {
            \App\Jobs\ProcessShopeeOrder::dispatch($order);
            return back()->with('success', 'Pesanan sedang diproses ke Shopee (Job dikirim ke antrean). Refresh halaman ini beberapa saat lagi.');
        }

        return back()->with('error', 'Channel tidak didukung.');
    }

    public function ship(Order $order, \App\Services\ShopeeService $shopeeService, \App\Services\TiktokService $tiktokService)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        $store = $order->store;
        $handoverMethod = $store->shipping_handover_method ?? 'DROP_OFF';
        
        if ($store->channel->code === 'shopee') {
            try {
                $accessToken = $store->getValidAccessToken();
                
                $shopeeService->shipOrder(
                    $accessToken,
                    (int) $store->marketplace_store_id,
                    $order->order_marketplace_id,
                    $handoverMethod
                );
                
                try {
                    $trackRes = $shopeeService->getTrackingNumber(
                        $accessToken,
                        (int) $store->marketplace_store_id,
                        $order->order_marketplace_id
                    );
                    if (!empty($trackRes['tracking_number'])) {
                        $order->tracking_number = $trackRes['tracking_number'];
                    }
                } catch (\Exception $e) {
                    // Ignore tracking fetch error
                }
                
                $order->order_status = Order::STATUS_SHIPPED;
                $order->save();

                // ✅ Kirim notifikasi otomatis ke pembeli via Shopee Chat
                try {
                    $resi = $order->tracking_number ?? 'belum tersedia';
                    $msg = "Halo {$order->buyer_name}! 👋\n\n"
                        . "Pesanan Anda *#{$order->invoice_number}* sudah kami kirimkan!\n"
                        . "📦 Kurir: {$order->courier}\n"
                        . "🔖 No. Resi: {$resi}\n\n"
                        . "Anda bisa melacak paket Anda melalui aplikasi Shopee. Terima kasih sudah berbelanja! 🙏";

                    // Cari conversation berdasarkan buyer (jika ada relasi chat)
                    $conversation = \App\Models\ChatConversation::where('store_id', $store->id)
                        ->where('buyer_username', $order->buyer_name)
                        ->latest()
                        ->first();

                    if ($conversation) {
                        $shopeeService->sendChatMessage(
                            $store->access_token,
                            (int) $store->marketplace_store_id,
                            $conversation->conversation_id,
                            $msg
                        );
                    }
                } catch (\Exception $e) {
                    Log::warning('[Order] Gagal kirim notifikasi chat ke pembeli: ' . $e->getMessage());
                }
                
                return back()->with('success', 'Pesanan berhasil diproses pengirimannya ke Shopee. Notifikasi dikirim ke pembeli.');
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal memproses pengiriman Shopee: ' . $e->getMessage());
            }
        } elseif ($store->channel->code === 'tiktok') {
            try {
                $tiktokService->shipOrder(
                    $store->access_token,
                    $store->marketplace_store_id,
                    $order->order_marketplace_id,
                    $handoverMethod
                );
                
                $order->order_status = Order::STATUS_SHIPPED;
                $order->save();
                
                return back()->with('success', 'Pesanan berhasil diproses pengirimannya ke TikTok (' . ($handoverMethod === 'PICK_UP' ? 'Pickup' : 'Drop-off') . ' sukses).');
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal memproses pengiriman TikTok: ' . $e->getMessage());
            }
        } elseif ($store->channel->code === 'lazada') {
            try {
                $lazadaService = app(\App\Services\LazadaService::class);
                $lazadaService->shipOrder(
                    $store->getValidAccessToken(),
                    $store->marketplace_store_id,
                    $order->order_marketplace_id,
                    $handoverMethod
                );
                
                try {
                    $trackRes = $lazadaService->getTrackingNumber(
                        $store->getValidAccessToken(),
                        $store->marketplace_store_id,
                        $order->order_marketplace_id
                    );
                    if (!empty($trackRes['tracking_number'])) {
                        $order->tracking_number = $trackRes['tracking_number'];
                    }
                } catch (\Exception $e) {
                    // Ignore tracking fetch error
                }
                
                $order->order_status = Order::STATUS_SHIPPED;
                $order->save();
                
                return back()->with('success', 'Pesanan Lazada berhasil diproses pengirimannya.');
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal memproses pengiriman Lazada: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'Channel tidak didukung.');
    }


    public function fetchTracking(Order $order, \App\Services\ShopeeService $shopeeService, \App\Services\TiktokService $tiktokService)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        $store = $order->store;
        
        if ($store->channel->code === 'shopee') {
            try {
                $response = $shopeeService->getTrackingNumber(
                    $store->access_token,
                    (int) $store->marketplace_store_id,
                    $order->order_marketplace_id
                );
                
                if (!empty($response['tracking_number'])) {
                    $order->tracking_number = $response['tracking_number'];
                    $order->save();
                    return back()->with('success', 'Resi berhasil ditarik: ' . $order->tracking_number);
                }
                
                return back()->with('error', 'Resi belum tersedia dari kurir.');
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal menarik resi: ' . $e->getMessage());
            }
        } elseif ($store->channel->code === 'tiktok') {
            try {
                $response = $tiktokService->getShippingDocument(
                    $store->access_token,
                    $store->marketplace_store_id,
                    $order->order_marketplace_id
                );
                
                if (!empty($response['doc_url'])) {
                    return redirect($response['doc_url']);
                }
                
                return back()->with('error', 'Resi TikTok belum tersedia atau tidak dikembalikan oleh API.');
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal menarik resi: ' . $e->getMessage());
            }
        } elseif ($store->channel->code === 'lazada') {
            try {
                $lazadaService = app(\App\Services\LazadaService::class);
                $response = $lazadaService->getTrackingNumber(
                    $store->getValidAccessToken(),
                    $store->marketplace_store_id,
                    $order->order_marketplace_id
                );
                
                if (!empty($response['tracking_number'])) {
                    $order->tracking_number = $response['tracking_number'];
                    $order->save();
                    return back()->with('success', 'Resi Lazada berhasil ditarik: ' . $order->tracking_number);
                }
                
                return back()->with('error', 'Resi belum tersedia dari kurir.');
            } catch (\Exception $e) {
                return back()->with('error', 'Gagal menarik resi Lazada: ' . $e->getMessage());
            }
        }

        return back()->with('error', 'Channel tidak didukung.');
    }


    /**
     * Ambil detail tracking resi secara real-time dari Shopee API (AJAX/JSON).
     */
    public function trackingDetail(Order $order, \App\Services\ShopeeService $shopeeService)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);

        $store = $order->store;

        if ($store->channel->code !== 'shopee') {
            return response()->json(['error' => 'Tracking detail hanya tersedia untuk pesanan Shopee.'], 422);
        }

        if (empty($order->order_marketplace_id)) {
            return response()->json(['error' => 'ID marketplace pesanan tidak ditemukan.'], 422);
        }

        try {
            $trackingData = $shopeeService->getTrackingInfo(
                $store->access_token,
                (int) $store->marketplace_store_id,
                $order->order_marketplace_id
            );

            return response()->json([
                'success'       => true,
                'tracking_info' => $trackingData,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function sync(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        // Ambil semua toko shopee, tiktok, tokopedia & lazada milik tenant ini
        $stores = \App\Models\Store::where('tenant_id', $tenantId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['shopee', 'tiktok', 'tokopedia', 'lazada']);
            })->get();

        if ($stores->isEmpty()) {
            return back()->with('error', 'Anda belum mengintegrasikan toko.');
        }

        // Tarik pesanan 14 hari terakhir sebagai default (Shopee max limit adalah 15 hari)
        $timeTo = time();
        $timeFrom = strtotime('-14 days', $timeTo);

        foreach ($stores as $store) {
            if ($store->channel->code === 'shopee') {
                \App\Jobs\PullOrdersFromShopee::dispatch($store, $timeFrom, $timeTo);
            } elseif (in_array($store->channel->code, ['tiktok', 'tokopedia'])) {
                \App\Jobs\PullOrdersFromTiktok::dispatch($store, $timeFrom, $timeTo);
            } elseif ($store->channel->code === 'lazada') {
                \App\Jobs\PullOrdersFromLazada::dispatch($store, $timeFrom, $timeTo);
            }
        }

        return back()->with('success', 'Perintah tarik pesanan telah dikirim. Pesanan akan segera muncul dalam beberapa saat.');
    }


    public function print(Order $order, \App\Services\ShopeeService $shopeeService, \App\Services\TiktokService $tiktokService)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        $order->load('items.masterProduct', 'store.channel');
        
        $store = $order->store;

        // Coba untuk fetch dokumen pengiriman dari marketplace API
        try {
            if (in_array($store->channel->code, ['tiktok', 'tokopedia'])) {
                $response = $tiktokService->getShippingDocument(
                    $store->access_token,
                    $store->marketplace_store_id,
                    $order->order_marketplace_id
                );
                if (!empty($response['doc_url'])) {
                    return redirect($response['doc_url']);
                }
            }
        } catch (\Exception $e) {
            // Jika error, gunakan invoice standar lokal
        }

        return view('orders.print', compact('order'));
    }

    public function cancel(Order $order, Request $request)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        $request->validate([
            'cancel_reason' => 'required|string|max:500',
        ]);
        
        $order->order_status = Order::STATUS_CANCELLED;
        $order->cancel_reason = $request->cancel_reason;
        $order->cancelled_by = Auth::user()->name . ' (ERP Admin)';
        $order->save();
        
        // Kembalikan stok jika belum dikembalikan
        $order->processStockDeduction();
        
        return back()->with('success', 'Pesanan berhasil dibatalkan secara manual.');
    }

    public function export(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = Order::with('store.channel')
            ->where('tenant_id', $tenantId);

        // Filter Channel
        if ($request->filled('channel_id')) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('channel_id', $request->channel_id);
            });
        }

        // Filter Toko
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Filter Kurir
        if ($request->filled('courier')) {
            $query->where('courier', 'like', '%' . $request->courier . '%');
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('order_status', $request->status);
        }

        // Filter Batas Kirim (Deadline Status)
        if ($request->filled('deadline_status')) {
            $deadlineStatus = $request->deadline_status;
            if ($deadlineStatus === 'overdue') {
                $query->whereNotNull('ship_before_date')
                    ->where('ship_before_date', '<', now());
            } elseif ($deadlineStatus === 'urgent') {
                $query->whereNotNull('ship_before_date')
                    ->where('ship_before_date', '>', now())
                    ->where('ship_before_date', '<=', now()->addHours(24));
            } elseif ($deadlineStatus === 'safe') {
                $query->whereNotNull('ship_before_date')
                    ->where('ship_before_date', '>', now()->addHours(24));
            }
        }

        // Filter Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

        // Filter Dropship
        if ($request->filled('is_dropship')) {
            $query->where('is_dropship', $request->is_dropship);
        }

        // Filter Alasan Pembatalan
        if ($request->filled('cancel_reason')) {
            $query->where('cancel_reason', 'like', '%' . $request->cancel_reason . '%');
        }

        $orders = $query->orderByDesc('order_date')->get();

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename=orders_report_' . date('Y-m-d') . '.csv',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0'
        ];

        $columns = ['Tanggal', 'Invoice / ID Marketplace', 'Toko', 'Channel', 'Pembeli', 'Total (Rp)', 'Status', 'Alasan Batal', 'Dibatalkan Oleh'];

        $callback = function() use ($orders, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->order_date ? $order->order_date->format('Y-m-d H:i:s') : '-',
                    $order->invoice_number ?? $order->order_marketplace_id,
                    $order->store->store_name,
                    $order->store->channel->name,
                    $order->buyer_name ?? '-',
                    $order->total_amount,
                    $order->order_status,
                    $order->cancel_reason ?? '-',
                    $order->cancelled_by ?? '-'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $stores = \App\Models\Store::where('tenant_id', $tenantId)->get();
        $products = \App\Models\MasterProduct::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $departments = \App\Models\Department::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        return view('orders.create', compact('stores', 'products', 'departments'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'store_id' => 'required|exists:stores,id',
            'invoice_number' => 'nullable|string|max:100',
            'buyer_name' => 'required|string|max:255',
            'buyer_phone' => 'nullable|string|max:50',
            'shipping_address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.master_product_id' => 'required|exists:master_products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
        ]);

        $store = \App\Models\Store::where('tenant_id', $tenantId)->findOrFail($request->store_id);

        $order = null;
        \Illuminate\Support\Facades\DB::transaction(function() use ($request, $tenantId, $store, &$order) {
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += $item['price'] * $item['quantity'];
            }

            // Auto-generate invoice number if not provided (formatted as REQ-YYYYMMDD-XXXX)
            $invoiceNumber = $request->invoice_number;
            if (empty($invoiceNumber)) {
                $today = date('Ymd');
                $count = Order::where('invoice_number', 'like', "REQ-{$today}-%")->count();
                $invoiceNumber = 'REQ-' . $today . '-' . sprintf('%04d', $count + 1);
            }

            // Create Order
            $order = Order::create([
                'tenant_id' => $tenantId,
                'store_id' => $store->id,
                'order_marketplace_id' => 'MANUAL-' . time() . '-' . rand(100, 999),
                'invoice_number' => $invoiceNumber,
                'order_status' => Order::STATUS_READY_TO_SHIP, // Bypass approval, directly ready to ship/produce
                'buyer_name' => $request->buyer_name,
                'buyer_phone' => $request->buyer_phone,
                'shipping_address' => $request->shipping_address,
                'total_amount' => $totalAmount,
                'net_amount' => $totalAmount,
                'order_date' => now(),
                'approved_warehouse_at' => now(),
                'approved_warehouse_by' => Auth::id(),
                'approved_production_at' => now(),
                'approved_production_by' => Auth::id(),
            ]);

            // Create Order Items
            foreach ($request->items as $itemData) {
                $prod = \App\Models\MasterProduct::where('tenant_id', $tenantId)->findOrFail($itemData['master_product_id']);

                $order->items()->create([
                    'master_product_id' => $prod->id,
                    'sku' => $prod->sku,
                    'product_name' => $prod->name,
                    'price' => $itemData['price'],
                    'quantity' => $itemData['quantity'],
                    'total_price' => $itemData['price'] * $itemData['quantity'],
                    'cost_price' => $prod->cost_price ?: 0,
                    'hpp_subtotal' => ($prod->cost_price ?: 0) * $itemData['quantity'],
                ]);
            }
        });

        return redirect()->route('orders.show', $order->id)
            ->with('success', 'Permintaan produksi manual (PO) berhasil diajukan dan siap diproses!');
    }

    public function approveWarehouse(Order $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        if ($order->order_status !== Order::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'Pesanan ini tidak sedang menunggu persetujuan.');
        }

        $order->approved_warehouse_at = now();
        $order->approved_warehouse_by = Auth::id();
        
        if ($order->approved_production_at) {
            $order->order_status = Order::STATUS_READY_TO_SHIP;
        }
        
        $order->save();

        return back()->with('success', 'Persetujuan Gudang Jadi berhasil direkam!');
    }

    public function approveProduction(Order $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        if ($order->order_status !== Order::STATUS_PENDING_APPROVAL) {
            return back()->with('error', 'Pesanan ini tidak sedang menunggu persetujuan.');
        }

        $order->approved_production_at = now();
        $order->approved_production_by = Auth::id();
        
        if ($order->approved_warehouse_at) {
            $order->order_status = Order::STATUS_READY_TO_SHIP;
        }
        
        $order->save();

        return back()->with('success', 'Persetujuan Produksi berhasil direkam!');
    }
}
