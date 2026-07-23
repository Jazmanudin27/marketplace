<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FulfillmentController extends Controller
{
    /**
     * Tampilkan daftar pesanan Siap Kirim beserta status kemasnya
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = Order::with(['store.channel', 'items.masterProduct'])
            ->where('tenant_id', $tenantId)
            ->where('order_status', Order::STATUS_READY_TO_SHIP);

        // Search Keyword
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('order_marketplace_id', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($iq) use ($search) {
                      $iq->where('product_name', 'like', "%{$search}%")
                         ->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

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

        // Filter Status Kemas
        if ($request->filled('packing_status') && in_array($request->packing_status, ['pending', 'packing', 'verified'])) {
            $query->where('packing_status', $request->packing_status);
        }

        // Filter Status Print (Sudah / Belum Print)
        if ($request->filled('print_status')) {
            if ($request->print_status === 'printed') {
                $query->where('is_printed', true);
            } elseif ($request->print_status === 'unprinted') {
                $query->where('is_printed', false);
            }
        }

        // Filter Tipe Produk (PO vs Ready)
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

        // Filter Tanggal Order
        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

        $orders = $query->orderByDesc('order_date')->paginate(20)->withQueryString();

        // Hitung ringkasan statistik
        $stats = [
            'total'     => Order::where('tenant_id', $tenantId)->where('order_status', Order::STATUS_READY_TO_SHIP)->count(),
            'pending'   => Order::where('tenant_id', $tenantId)->where('order_status', Order::STATUS_READY_TO_SHIP)->where('packing_status', 'pending')->count(),
            'packing'   => Order::where('tenant_id', $tenantId)->where('order_status', Order::STATUS_READY_TO_SHIP)->where('packing_status', 'packing')->count(),
            'verified'  => Order::where('tenant_id', $tenantId)->where('order_status', Order::STATUS_READY_TO_SHIP)->where('packing_status', 'verified')->count(),
            'printed'   => Order::where('tenant_id', $tenantId)->where('order_status', Order::STATUS_READY_TO_SHIP)->where('is_printed', true)->count(),
            'unprinted' => Order::where('tenant_id', $tenantId)->where('order_status', Order::STATUS_READY_TO_SHIP)->where('is_printed', false)->count(),
        ];

        $channels = \App\Models\Channel::all();
        $stores = \App\Models\Store::where('tenant_id', $tenantId)->get();
        $couriers = Order::where('tenant_id', $tenantId)
            ->where('order_status', Order::STATUS_READY_TO_SHIP)
            ->whereNotNull('courier')
            ->where('courier', '!=', '')
            ->distinct()
            ->pluck('courier');

        return view('fulfillment.index', compact('orders', 'stats', 'channels', 'stores', 'couriers'));
    }

    /**
     * Halaman scan barcode untuk memverifikasi produk
     */
    public function scanPage()
    {
        return view('fulfillment.scan');
    }

    /**
     * Ambil detail pesanan & item untuk keperluan scanning (AJAX)
     */
    public function getOrderDetails($identifier)
    {
        $order = Order::with(['items.masterProduct', 'items.marketplaceProduct', 'store.channel'])
            ->where('tenant_id', Auth::user()->tenant_id)
            ->where(function ($q) use ($identifier) {
                $q->where('invoice_number', $identifier)
                  ->orWhere('order_marketplace_id', $identifier);
            })
            ->first();

        if (!$order) {
            return response()->json([
                'success' => false, 
                'message' => "Pesanan dengan nomor/invoice '{$identifier}' tidak ditemukan."
            ], 404);
        }

        if ($order->order_status !== Order::STATUS_READY_TO_SHIP) {
            return response()->json([
                'success' => false,
                'message' => "Pesanan ini tidak dalam status SIAP KIRIM (Status saat ini: {$order->order_status})."
            ], 400);
        }

        // Set status packing ke 'packing' secara otomatis jika sebelumnya 'pending'
        if ($order->packing_status === 'pending') {
            $order->update(['packing_status' => 'packing']);
        }

        $items = $order->items->map(function ($item) {
            $sku = $item->sku ?? ($item->masterProduct->sku ?? ($item->marketplaceProduct->marketplace_sku ?? ''));
            $name = $item->product_name ?? ($item->masterProduct->name ?? 'Produk Tanpa Nama');
            $image = $item->product_image ?? ($item->masterProduct->image_url ?? ($item->marketplaceProduct->image_url ?? ''));
            return [
                'id' => $item->id,
                'sku' => $sku,
                'name' => $name,
                'image' => $image,
                'quantity' => $item->quantity,
            ];
        });

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'invoice_number' => $order->invoice_number ?? $order->order_marketplace_id,
                'buyer_name' => $order->buyer_name ?? '-',
                'courier' => $order->courier ?? '-',
                'store_name' => $order->store->store_name,
                'channel_code' => $order->store->channel->code,
                'channel_name' => $order->store->channel->name,
                'packing_status' => $order->packing_status,
                'items' => $items,
            ]
        ]);
    }

    /**
     * Konfirmasi verifikasi packing & secara opsional request shipping ke API (AJAX)
     */
    public function completePack(Request $request, Order $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);

        if ($order->order_status !== Order::STATUS_READY_TO_SHIP) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan tidak dalam status SIAP KIRIM.'
            ], 400);
        }

        $order->update([
            'packing_status' => 'verified',
            'packed_at' => now(),
        ]);

        // Potong stok lokal (jika belum dipotong sebelumnya)
        $order->processStockDeduction();

        $autoShip = $request->boolean('auto_ship');
        $shipped = false;
        $message = "Verifikasi pesanan '{$order->invoice_number}' sukses disimpan ke database.";

        if ($autoShip) {
            $store = $order->store;
            $handoverMethod = $store->shipping_handover_method ?? 'DROP_OFF';
            try {
                if ($store->channel->code === 'shopee') {
                    $shopeeService = app(\App\Services\ShopeeService::class);
                    $accessToken = $store->getValidAccessToken();
                    
                    try {
                        $shopeeService->shipOrder(
                            $accessToken,
                            (int) $store->marketplace_store_id,
                            $order->order_marketplace_id,
                            $handoverMethod
                        );
                    } catch (\Exception $e) {
                        if (str_contains($e->getMessage(), 'invalid_access_token') || str_contains($e->getMessage(), 'invalid_acceess_token')) {
                            Log::info("[Fulfillment] Access token Shopee tidak valid (expired/revoked), melakukan force refresh token...");
                            $accessToken = $store->getValidAccessToken(true);
                            $shopeeService->shipOrder(
                                $accessToken,
                                (int) $store->marketplace_store_id,
                                $order->order_marketplace_id,
                                $handoverMethod
                            );
                        } else {
                            throw $e;
                        }
                    }

                    // Ambil nomor resi
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
                        Log::warning("[Fulfillment] Gagal menarik nomor resi Shopee: " . $e->getMessage());
                    }

                    $order->order_status = Order::STATUS_SHIPPED;
                    $order->save();
                    $shipped = true;
                    $message = "Kemas sukses! Pesanan berhasil dikirim ke Shopee.";
                } elseif ($store->channel->code === 'tiktok') {
                    $tiktokService = app(\App\Services\TiktokService::class);
                    $tiktokService->shipOrder(
                        $store->access_token,
                        $store->marketplace_store_id,
                        $order->order_marketplace_id,
                        $handoverMethod
                    );

                    $order->order_status = Order::STATUS_SHIPPED;
                    $order->save();
                    $shipped = true;
                    $message = "Kemas sukses! Pesanan berhasil dikirim ke TikTok.";
                }
            } catch (\Exception $e) {
                Log::error("[Fulfillment] Gagal ship order {$order->id}: " . $e->getMessage());
                return response()->json([
                    'success' => true,
                    'shipped' => false,
                    'message' => "Verifikasi kemas berhasil disimpan, namun gagal mengirim instruksi kurir ke marketplace: " . $e->getMessage()
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'shipped' => $shipped,
            'message' => $message
        ]);
    }

    /**
     * Cetak Pick List Gabungan Massal (Mendukung Ceklis & Sesuai Filter)
     */
    public function batchPickList(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $ids = $request->input('ids', []);

        $query = Order::with(['items.masterProduct', 'spks', 'store.channel'])
            ->where('tenant_id', $tenantId)
            ->where('order_status', Order::STATUS_READY_TO_SHIP);

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            // Apply current filters from request
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhere('order_marketplace_id', 'like', "%{$search}%")
                      ->orWhere('buyer_name', 'like', "%{$search}%")
                      ->orWhere('tracking_number', 'like', "%{$search}%")
                      ->orWhereHas('items', function ($iq) use ($search) {
                          $iq->where('product_name', 'like', "%{$search}%")
                             ->orWhere('sku', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('channel_id')) {
                $query->whereHas('store', function ($q) use ($request) {
                    $q->where('channel_id', $request->channel_id);
                });
            }

            if ($request->filled('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            if ($request->filled('courier')) {
                $query->where('courier', 'like', '%' . $request->courier . '%');
            }

            if ($request->filled('packing_status') && in_array($request->packing_status, ['pending', 'packing', 'verified'])) {
                $query->where('packing_status', $request->packing_status);
            }

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

            if ($request->filled('start_date')) {
                $query->whereDate('order_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('order_date', '<=', $request->end_date);
            }
        }

        $orders = $query->orderByDesc('order_date')->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'Tidak ada pesanan Siap Kirim yang sesuai filter/pilihan.');
        }

        Order::whereIn('id', $orders->pluck('id'))->update([
            'is_printed' => true,
            'printed_at' => now(),
        ]);

        $aggregated = [];
        $poItemCount = 0;
        $readyItemCount = 0;

        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $sku = $item->sku ?? ($item->masterProduct->sku ?? 'No SKU');
                $name = $item->product_name ?? ($item->masterProduct->name ?? 'Produk Tanpa Nama');
                $isPreorder = ($item->masterProduct && $item->masterProduct->is_preorder) || $order->spks->isNotEmpty();
                $spkNo = $order->spks->isNotEmpty() ? $order->spks->first()->no_spk : null;

                if (!isset($aggregated[$sku])) {
                    $aggregated[$sku] = [
                        'sku'      => $sku,
                        'name'     => $name,
                        'qty'      => 0,
                        'is_po'    => $isPreorder,
                        'spk_no'   => $spkNo,
                        'orders'   => []
                    ];
                } else {
                    if ($isPreorder) {
                        $aggregated[$sku]['is_po'] = true;
                    }
                    if ($spkNo && !$aggregated[$sku]['spk_no']) {
                        $aggregated[$sku]['spk_no'] = $spkNo;
                    }
                }

                $aggregated[$sku]['qty'] += $item->quantity;
                $aggregated[$sku]['orders'][] = $order->invoice_number ?? $order->order_marketplace_id;
            }
        }

        foreach ($aggregated as $sku => $data) {
            if ($data['is_po']) {
                $poItemCount++;
            } else {
                $readyItemCount++;
            }
        }

        return view('fulfillment.batch_picklist', compact('aggregated', 'orders', 'poItemCount', 'readyItemCount'));
    }

    /**
     * Layar Interaktif Rekap Ambil Barang (Scan Barcode / Touch Picking Mode)
     */
    public function interactivePickList(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $ids = $request->input('ids', []);

        $query = Order::with(['items.masterProduct', 'spks', 'store.channel'])
            ->where('tenant_id', $tenantId)
            ->where('order_status', Order::STATUS_READY_TO_SHIP)
            ->where('is_printed', true);

        if (!empty($ids)) {
            $query->whereIn('id', $ids);
        } else {
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('invoice_number', 'like', "%{$search}%")
                      ->orWhere('order_marketplace_id', 'like', "%{$search}%")
                      ->orWhere('buyer_name', 'like', "%{$search}%")
                      ->orWhere('tracking_number', 'like', "%{$search}%")
                      ->orWhereHas('items', function ($iq) use ($search) {
                          $iq->where('product_name', 'like', "%{$search}%")
                             ->orWhere('sku', 'like', "%{$search}%");
                      });
                });
            }

            if ($request->filled('channel_id')) {
                $query->whereHas('store', function ($q) use ($request) {
                    $q->where('channel_id', $request->channel_id);
                });
            }

            if ($request->filled('store_id')) {
                $query->where('store_id', $request->store_id);
            }

            if ($request->filled('courier')) {
                $query->where('courier', 'like', '%' . $request->courier . '%');
            }

            if ($request->filled('packing_status') && in_array($request->packing_status, ['pending', 'packing', 'verified'])) {
                $query->where('packing_status', $request->packing_status);
            }

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

            if ($request->filled('start_date')) {
                $query->whereDate('order_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->whereDate('order_date', '<=', $request->end_date);
            }
        }

        $orders = $query->orderByDesc('order_date')->get();

        if ($orders->isEmpty()) {
            return back()->with('error', 'Tidak ada pesanan Siap Kirim yang SUDAH DICETAK RESINYA. Silakan cetak resi terlebih dahulu di menu Pemenuhan Pesanan.');
        }

        $aggregated = [];
        $totalPcs = 0;
        foreach ($orders as $order) {
            foreach ($order->items as $item) {
                $sku = $item->sku ?? ($item->masterProduct->sku ?? 'NO-SKU');
                $name = $item->product_name ?? ($item->masterProduct->name ?? 'Produk Tanpa Nama');
                $image = $item->product_image ?? ($item->masterProduct->image_url ?? '');
                $isPreorder = ($item->masterProduct && $item->masterProduct->is_preorder) || $order->spks->isNotEmpty();
                $spkNo = $order->spks->isNotEmpty() ? $order->spks->first()->no_spk : null;

                if (!isset($aggregated[$sku])) {
                    $aggregated[$sku] = [
                        'sku'      => $sku,
                        'name'     => $name,
                        'image'    => $image,
                        'target'   => 0,
                        'picked'   => 0,
                        'is_po'    => $isPreorder,
                        'spk_no'   => $spkNo,
                        'orders'   => []
                    ];
                }

                $aggregated[$sku]['target'] += $item->quantity;
                $aggregated[$sku]['orders'][] = $order->invoice_number ?? $order->order_marketplace_id;
                $totalPcs += $item->quantity;
            }
        }

        $orderIds = $orders->pluck('id')->toArray();

        return view('fulfillment.interactive_picklist', compact('aggregated', 'orders', 'orderIds', 'totalPcs'));
    }

    /**
     * AJAX: Potong stok real-time saat SKU di-scan / di-ambil di Layar Ambil Barang
     */
    public function scanDeductStock(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $sku = $request->input('sku');
        $qty = (int) $request->input('qty', 1);

        if (!$sku || $qty <= 0) {
            return response()->json(['success' => false, 'message' => 'SKU atau Qty tidak valid']);
        }

        $product = MasterProduct::where('tenant_id', $tenantId)
            ->where(function($q) use ($sku) {
                $q->where('sku', $sku)
                  ->orWhere('sku_induk', $sku);
            })->first();

        if (!$product) {
            return response()->json(['success' => false, 'message' => "SKU {$sku} tidak ditemukan di Master Produk"]);
        }

        // Potong stok real-time di Master Product & Catat Kartu Stok (StockMovement)
        $reference = 'Pengambilan Barang (Fulfillment SKU: ' . $product->sku . ')';
        $product->recordStockMovement($qty, 'out', $reference, Auth::id());

        return response()->json([
            'success'   => true,
            'sku'       => $product->sku,
            'name'      => $product->name,
            'new_stock' => $product->fresh()->stock,
            'message'   => "Stok SKU {$product->sku} berkurang {$qty} Pcs. (Sisa Stok: {$product->fresh()->stock})"
        ]);
    }

    /**
     * Konfirmasi Selesai Ambil Barang (Batch Picked) & Potong Stok Pesanan
     */
    public function confirmPicking(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $orderIds = $request->input('order_ids', []);

        if (empty($orderIds)) {
            return redirect()->route('fulfillment.index')->with('error', 'Tidak ada pesanan yang dikonfirmasi.');
        }

        $orders = Order::where('tenant_id', $tenantId)
            ->whereIn('id', $orderIds)
            ->where('order_status', Order::STATUS_READY_TO_SHIP)
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            // Potong stok produk pesanan yang belum terpotong
            $order->syncStockDeduction();

            // Ubah status kemas menjadi 'packing' (Sedang Dikemas)
            if ($order->packing_status === 'pending') {
                $order->update(['packing_status' => 'packing']);
            }
            $count++;
        }

        return redirect()->route('fulfillment.index')
            ->with('success', "Proses Ambil Barang Selesai! Stok untuk {$orders->count()} pesanan berhasil dipotong & status berubah menjadi Sedang Dikemas.");
    }

    /**
     * Verifikasi Packing Massal
     */
    public function batchVerify(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Pilih minimal satu pesanan untuk diverifikasi.');
        }

        $orders = Order::where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->where('order_status', Order::STATUS_READY_TO_SHIP)
            ->get();

        $count = 0;
        foreach ($orders as $order) {
            $order->update([
                'packing_status' => 'verified',
                'packed_at' => now(),
            ]);
            $order->processStockDeduction();
            $count++;
        }

        return back()->with('success', "Verifikasi kemas berhasil diselesaikan untuk {$count} pesanan.");
    }

    /**
     * Request Kirim Resi / Ship Massal ke API Marketplace
     */
    public function batchShip(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return back()->with('error', 'Pilih minimal satu pesanan untuk dikirim.');
        }

        $orders = Order::with('store.channel')
            ->where('tenant_id', $tenantId)
            ->whereIn('id', $ids)
            ->where('order_status', Order::STATUS_READY_TO_SHIP)
            ->where('packing_status', 'verified')
            ->get();

        $successCount = 0;
        $failCount = 0;

        foreach ($orders as $order) {
            try {
                $store = $order->store;
                $handoverMethod = $store->shipping_handover_method ?? 'DROP_OFF';

                if ($store->channel->code === 'shopee') {
                    $shopeeService = app(\App\Services\ShopeeService::class);
                    $accessToken = $store->getValidAccessToken();
                    
                    try {
                        $shopeeService->shipOrder(
                            $accessToken,
                            (int) $store->marketplace_store_id,
                            $order->order_marketplace_id,
                            $handoverMethod
                        );
                    } catch (\Exception $e) {
                        if (str_contains($e->getMessage(), 'invalid_access_token') || str_contains($e->getMessage(), 'invalid_acceess_token')) {
                            $accessToken = $store->getValidAccessToken(true);
                            $shopeeService->shipOrder(
                                $accessToken,
                                (int) $store->marketplace_store_id,
                                $order->order_marketplace_id,
                                $handoverMethod
                            );
                        } else {
                            throw $e;
                        }
                    }

                    // Pull tracking number
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
                        Log::warning("[Fulfillment Batch Ship] Gagal menarik nomor resi Shopee: " . $e->getMessage());
                    }

                    $order->order_status = Order::STATUS_SHIPPED;
                    $order->save();
                    $successCount++;
                } elseif ($store->channel->code === 'tiktok') {
                    $tiktokService = app(\App\Services\TiktokService::class);
                    $tiktokService->shipOrder(
                        $store->access_token,
                        $store->marketplace_store_id,
                        $order->order_marketplace_id,
                        $handoverMethod
                    );

                    $order->order_status = Order::STATUS_SHIPPED;
                    $order->save();
                    $successCount++;
                } else {
                    // Fallback local status update
                    $order->order_status = Order::STATUS_SHIPPED;
                    $order->save();
                    $successCount++;
                }
            } catch (\Exception $e) {
                Log::error("[Fulfillment Batch Ship] Gagal kirim resi order {$order->id}: " . $e->getMessage());
                $failCount++;
            }
        }

        $msg = "Batch Ship selesai. {$successCount} pesanan berhasil dikirim.";
        if ($failCount > 0) {
            return back()->with('success', $msg)->with('error', "{$failCount} pesanan gagal dikirim ke API marketplace (silakan cek log).");
        }

        return back()->with('success', $msg);
    }
}
