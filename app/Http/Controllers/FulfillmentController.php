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
        
        $query = Order::with(['store.channel', 'items'])
            ->where('tenant_id', $tenantId)
            ->where('order_status', Order::STATUS_READY_TO_SHIP);

        if ($request->has('packing_status') && in_array($request->packing_status, ['pending', 'packing', 'verified'])) {
            $query->where('packing_status', $request->packing_status);
        }

        $orders = $query->orderByDesc('order_date')->paginate(20);

        // Hitung ringkasan statistik
        $stats = [
            'total' => Order::where('tenant_id', $tenantId)->where('order_status', Order::STATUS_READY_TO_SHIP)->count(),
            'pending' => Order::where('tenant_id', $tenantId)->where('order_status', Order::STATUS_READY_TO_SHIP)->where('packing_status', 'pending')->count(),
            'packing' => Order::where('tenant_id', $tenantId)->where('order_status', Order::STATUS_READY_TO_SHIP)->where('packing_status', 'packing')->count(),
            'verified' => Order::where('tenant_id', $tenantId)->where('order_status', Order::STATUS_READY_TO_SHIP)->where('packing_status', 'verified')->count(),
        ];

        return view('fulfillment.index', compact('orders', 'stats'));
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
            try {
                if ($store->channel->code === 'shopee') {
                    $shopeeService = app(\App\Services\ShopeeService::class);
                    $accessToken = $store->getValidAccessToken();
                    
                    try {
                        $shopeeService->shipOrder(
                            $accessToken,
                            (int) $store->marketplace_store_id,
                            $order->order_marketplace_id
                        );
                    } catch (\Exception $e) {
                        if (str_contains($e->getMessage(), 'invalid_access_token') || str_contains($e->getMessage(), 'invalid_acceess_token')) {
                            Log::info("[Fulfillment] Access token Shopee tidak valid (expired/revoked), melakukan force refresh token...");
                            $accessToken = $store->getValidAccessToken(true);
                            $shopeeService->shipOrder(
                                $accessToken,
                                (int) $store->marketplace_store_id,
                                $order->order_marketplace_id
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
                        $order->order_marketplace_id
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
}
