<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MasterProduct;
use App\Models\MarketplaceProduct;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class FulfillmentController extends Controller
{
    /**
     * Tampilkan daftar pesanan Siap Kirim beserta status kemasnya
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = Order::with(['store.channel', 'items.masterProduct', 'spks'])
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

        // Filter Tanggal Order
        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

        $orders = $query->orderByDesc('order_date')->paginate(20)->withQueryString();

        // Hitung ringkasan statistik (Optimasi 1 Single Query)
        $statsRaw = Order::where('tenant_id', $tenantId)
            ->where('order_status', Order::STATUS_READY_TO_SHIP)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN packing_status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN packing_status = 'packing' THEN 1 ELSE 0 END) as packing_count,
                SUM(CASE WHEN packing_status = 'verified' THEN 1 ELSE 0 END) as verified_count,
                SUM(CASE WHEN is_printed = 1 THEN 1 ELSE 0 END) as printed_count,
                SUM(CASE WHEN is_printed = 0 OR is_printed IS NULL THEN 1 ELSE 0 END) as unprinted_count
            ")
            ->first();

        $stats = [
            'total'     => (int) ($statsRaw->total ?? 0),
            'pending'   => (int) ($statsRaw->pending_count ?? 0),
            'packing'   => (int) ($statsRaw->packing_count ?? 0),
            'verified'  => (int) ($statsRaw->verified_count ?? 0),
            'printed'   => (int) ($statsRaw->printed_count ?? 0),
            'unprinted' => (int) ($statsRaw->unprinted_count ?? 0),
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
        $cleanIdentifier = trim($identifier);

        if (empty($cleanIdentifier)) {
            return response()->json([
                'success' => false,
                'message' => 'Silakan masukkan nomor resi atau invoice pesanan.'
            ], 400);
        }

        $order = Order::with(['items.masterProduct', 'items.marketplaceProduct', 'store.channel'])
            ->where('tenant_id', Auth::user()->tenant_id)
            ->where(function ($q) use ($cleanIdentifier) {
                $q->where('invoice_number', $cleanIdentifier)
                  ->orWhere('order_marketplace_id', $cleanIdentifier)
                  ->orWhere('tracking_number', $cleanIdentifier)
                  ->orWhere('package_id', $cleanIdentifier);
            })
            ->first();

        // Fallback jika scanner atau sistem memiliki perbedaan spasi/karakter tersembunyi
        if (!$order) {
            $order = Order::with(['items.masterProduct', 'items.marketplaceProduct', 'store.channel'])
                ->where('tenant_id', Auth::user()->tenant_id)
                ->where(function ($q) use ($cleanIdentifier) {
                    $q->where('tracking_number', 'like', "%{$cleanIdentifier}%")
                      ->orWhere('invoice_number', 'like', "%{$cleanIdentifier}%")
                      ->orWhere('order_marketplace_id', 'like', "%{$cleanIdentifier}%");
                })
                ->first();
        }

        if (!$order) {
            return response()->json([
                'success' => false, 
                'message' => "Pesanan dengan nomor invoice / resi '{$cleanIdentifier}' tidak ditemukan."
            ], 404);
        }

        if ($order->order_status !== Order::STATUS_READY_TO_SHIP) {
            return response()->json([
                'success' => false,
                'message' => "Pesanan ini tidak dalam status SIAP KIRIM (Status saat ini: {$order->order_status})."
            ], 400);
        }

        if (!$order->is_printed) {
            // Jika discan menggunakan nomor resi atau sudah memiliki resi ekspedisi, tandai sudah tercetak
            if (!empty($order->tracking_number)) {
                $order->update([
                    'is_printed' => true,
                    'printed_at' => now(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Pesanan '{$cleanIdentifier}' BELUM DICETAK RESINYA! Silakan cetak resi terlebih dahulu sebelum dikemas."
                ], 400);
            }
        }

        // Set status packing ke 'packing' secara otomatis jika sebelumnya 'pending'
        if ($order->packing_status === 'pending') {
            $order->update(['packing_status' => 'packing']);
        }

        $items = [];
        foreach ($order->items as $item) {
            $masterProduct = $item->masterProduct;
            if ($masterProduct && $masterProduct->is_bundle) {
                // Ambil komponen bundle
                $components = $masterProduct->components;
                
                // Jika relasi components kosong di DB, cek apakah ada ProductRecipe (BOM)
                if ($components->isEmpty() && $masterProduct->activeRecipe && $masterProduct->activeRecipe->items) {
                    foreach ($masterProduct->activeRecipe->items as $recipeItem) {
                        $compProduct = $recipeItem->ingredientProduct ?? \App\Models\MasterProduct::find($recipeItem->ingredient_master_product_id ?? $recipeItem->component_id);
                        if ($compProduct) {
                            $qty = (int) ($recipeItem->quantity ?? 1);
                            $items[] = [
                                'id' => $item->id . '-' . $compProduct->id, // custom unique key
                                'sku' => $compProduct->sku,
                                'barcode' => $compProduct->barcode ?? null,
                                'name' => '[Setelan Component] ' . $compProduct->name . ' (From ' . $masterProduct->name . ')',
                                'image' => $compProduct->image_url ?: '/images/placeholder.png',
                                'quantity' => $item->quantity * $qty,
                            ];
                        }
                    }
                } else {
                    foreach ($components as $comp) {
                        $qty = (int) ($comp->pivot->quantity ?? 1);
                        $items[] = [
                            'id' => $item->id . '-' . $comp->id, // custom unique key
                            'sku' => $comp->sku,
                            'barcode' => $comp->barcode ?? null,
                            'name' => '[Setelan Component] ' . $comp->name . ' (From ' . $masterProduct->name . ')',
                            'image' => $comp->image_url ?: '/images/placeholder.png',
                            'quantity' => $item->quantity * $qty,
                        ];
                    }
                }
                
                // Jika bundle tidak memiliki komponen sama sekali, fallback ke parent
                if (empty($items)) {
                    $sku = $item->sku ?? ($masterProduct->sku ?? ($item->marketplaceProduct->marketplace_sku ?? ''));
                    $name = $item->product_name ?? ($masterProduct->name ?? 'Produk Tanpa Nama');
                    $image = $item->product_image ?? ($masterProduct->image_url ?? ($item->marketplaceProduct->image_url ?? ''));
                    $items[] = [
                        'id' => $item->id,
                        'sku' => $sku,
                        'barcode' => $masterProduct->barcode ?? null,
                        'name' => $name,
                        'image' => $image,
                        'quantity' => $item->quantity,
                        'is_substituted' => (bool) $item->is_substituted,
                        'original_sku' => $item->original_sku,
                        'original_product_name' => $item->original_product_name,
                        'substitution_note' => $item->substitution_note,
                    ];
                }
            } else {
                $sku = $item->sku ?? ($masterProduct->sku ?? ($item->marketplaceProduct->marketplace_sku ?? ''));
                $name = $item->product_name ?? ($masterProduct->name ?? 'Produk Tanpa Nama');
                $image = $item->product_image ?? ($masterProduct->image_url ?? ($item->marketplaceProduct->image_url ?? ''));
                $items[] = [
                    'id' => $item->id,
                    'sku' => $sku,
                    'barcode' => $masterProduct->barcode ?? null,
                    'name' => $name,
                    'image' => $image,
                    'quantity' => $item->quantity,
                    'is_substituted' => (bool) $item->is_substituted,
                    'original_sku' => $item->original_sku,
                    'original_product_name' => $item->original_product_name,
                    'substitution_note' => $item->substitution_note,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'order' => [
                'id' => $order->id,
                'invoice_number' => $order->invoice_number ?? $order->order_marketplace_id,
                'tracking_number' => $order->tracking_number ?? null,
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
                        $eMsg = strtolower($e->getMessage());
                        if (
                            str_contains($eMsg, 'already_shipped') ||
                            str_contains($eMsg, 'already been shipped') ||
                            str_contains($eMsg, 'already shipped') ||
                            str_contains($eMsg, 'shipping_method_already_set')
                        ) {
                            Log::info("[Fulfillment] Pesanan Shopee {$order->invoice_number} ({$order->order_marketplace_id}) sudah pernah di-ship di Shopee.");
                        } elseif (str_contains($eMsg, 'invalid_access_token') || str_contains($eMsg, 'invalid_acceess_token')) {
                            Log::info("[Fulfillment] Access token Shopee tidak valid (expired/revoked), melakukan force refresh token...");
                            $accessToken = $store->getValidAccessToken(true);
                            try {
                                $shopeeService->shipOrder(
                                    $accessToken,
                                    (int) $store->marketplace_store_id,
                                    $order->order_marketplace_id,
                                    $handoverMethod
                                );
                            } catch (\Exception $e2) {
                                $eMsg2 = strtolower($e2->getMessage());
                                if (
                                    str_contains($eMsg2, 'already_shipped') ||
                                    str_contains($eMsg2, 'already been shipped') ||
                                    str_contains($eMsg2, 'already shipped') ||
                                    str_contains($eMsg2, 'shipping_method_already_set')
                                ) {
                                    Log::info("[Fulfillment] Pesanan Shopee {$order->invoice_number} sudah pernah di-ship (setelah refresh token).");
                                } else {
                                    throw $e2;
                                }
                            }
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
                } elseif (in_array(strtolower($store->channel->code ?? ''), ['tiktok', 'tokopedia'])) {
                    $tiktokService = app(\App\Services\TiktokService::class);
                    $accessToken = $store->getValidAccessToken();
                    $shopCipher  = $store->shop_cipher ?: $store->marketplace_store_id;

                    try {
                        $shipRes = $tiktokService->shipOrder(
                            $accessToken,
                            $shopCipher,
                            $order->order_marketplace_id,
                            $handoverMethod,
                            $order->package_id
                        );

                        if (!empty($shipRes['package_id']) && empty($order->package_id)) {
                            $order->package_id = $shipRes['package_id'];
                        }
                    } catch (\Exception $e) {
                        $eMsg = strtolower($e->getMessage());
                        if (
                            str_contains($eMsg, 'already') ||
                            str_contains($eMsg, 'not awaiting shipment') ||
                            str_contains($eMsg, 'does not allow this operation') ||
                            str_contains($eMsg, 'cannot be shipped')
                        ) {
                            Log::info("[Fulfillment] Pesanan TikTok {$order->invoice_number} sudah pernah diatur pengirimannya: " . $e->getMessage());
                        } else {
                            throw $e;
                        }
                    }

                    // Ambil nomor resi TikTok & perbarui package_id jika belum lengkap
                    try {
                        $detailData = $tiktokService->getOrderDetail($accessToken, $shopCipher, [$order->order_marketplace_id]);
                        $tOrders = $detailData['orders'] ?? $detailData['order_list'] ?? [];
                        if (!empty($tOrders[0])) {
                            $tOrder = $tOrders[0];
                            $tNo = $tOrder['tracking_number'] ?? $tOrder['tracking_no'] ?? $tOrder['express_tracking_number'] ?? null;
                            if (empty($tNo) && !empty($tOrder['packages'])) {
                                foreach ($tOrder['packages'] as $pkg) {
                                    $tNo = $pkg['tracking_number'] ?? $pkg['tracking_no'] ?? $pkg['express_tracking_number'] ?? null;
                                    if (!empty($tNo)) break;
                                }
                            }
                            if ($tNo) {
                                $order->tracking_number = $tNo;
                            }
                            if (!empty($tOrder['packages'][0]['id']) && empty($order->package_id)) {
                                $order->package_id = (string) $tOrder['packages'][0]['id'];
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning("[Fulfillment] Gagal menarik tracking number TikTok: " . $e->getMessage());
                    }

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
     * Cari produk MasterProduct untuk kebutuhan penukaran / substitusi SKU di layar scanner
     */
    public function searchProducts(Request $request)
    {
        try {
            $tenantId = Auth::user()->tenant_id;
            $q = trim((string) $request->get('q', ''));

            if (strlen($q) < 1) {
                return response()->json([]);
            }

            $hasBarcode = Schema::hasColumn('master_products', 'barcode');

            $query = MasterProduct::where('tenant_id', $tenantId)
                ->where(function ($sub) use ($q, $hasBarcode) {
                    $sub->where('sku', 'LIKE', "%{$q}%")
                        ->orWhere('name', 'LIKE', "%{$q}%");
                    if ($hasBarcode) {
                        $sub->orWhere('barcode', 'LIKE', "%{$q}%");
                    }
                });

            // Urutkan prioritas exact match SKU
            if ($hasBarcode) {
                $query->orderByRaw("
                    CASE 
                        WHEN UPPER(sku) = UPPER(?) THEN 1
                        WHEN UPPER(barcode) = UPPER(?) THEN 1
                        WHEN UPPER(sku) LIKE UPPER(?) THEN 2
                        WHEN UPPER(name) LIKE UPPER(?) THEN 3
                        ELSE 4
                    END ASC, name ASC
                ", [$q, $q, "{$q}%", "%{$q}%"]);
            } else {
                $query->orderByRaw("
                    CASE 
                        WHEN UPPER(sku) = UPPER(?) THEN 1
                        WHEN UPPER(sku) LIKE UPPER(?) THEN 2
                        WHEN UPPER(name) LIKE UPPER(?) THEN 3
                        ELSE 4
                    END ASC, name ASC
                ", [$q, "{$q}%", "%{$q}%"]);
            }

            $selectCols = ['id', 'sku', 'name', 'stock', 'image_url', 'cost_price'];
            if ($hasBarcode) {
                $selectCols[] = 'barcode';
            }

            $products = $query->select($selectCols)->limit(30)->get();

            $formatted = $products->map(function ($p) use ($hasBarcode) {
                return [
                    'id'         => $p->id,
                    'sku'        => $p->sku,
                    'barcode'    => $hasBarcode ? ($p->barcode ?? null) : null,
                    'name'       => $p->name,
                    'stock'      => $p->stock,
                    'image_url'  => $p->image_url,
                    'cost_price' => $p->cost_price,
                ];
            });

            // Fallback: jika tidak ditemukan di MasterProduct, coba cari dari MarketplaceProduct yang terhubung
            if ($formatted->isEmpty()) {
                $mpList = MarketplaceProduct::whereHas('store', function ($s) use ($tenantId) {
                        $s->where('tenant_id', $tenantId);
                    })
                    ->where(function ($query) use ($q) {
                        $query->where('marketplace_sku', 'LIKE', "%{$q}%")
                              ->orWhere('name', 'LIKE', "%{$q}%");
                    })
                    ->whereNotNull('master_product_id')
                    ->with('masterProduct')
                    ->limit(15)
                    ->get();

                $seen = [];
                $fallback = [];
                foreach ($mpList as $mp) {
                    if ($mp->masterProduct && !isset($seen[$mp->masterProduct->id])) {
                        $seen[$mp->masterProduct->id] = true;
                        $prod = $mp->masterProduct;
                        $fallback[] = [
                            'id'         => $prod->id,
                            'sku'        => $prod->sku,
                            'barcode'    => $hasBarcode ? ($prod->barcode ?? null) : null,
                            'name'       => $prod->name,
                            'stock'      => $prod->stock,
                            'image_url'  => $prod->image_url,
                            'cost_price' => $prod->cost_price,
                        ];
                    }
                }
                if (!empty($fallback)) {
                    return response()->json($fallback);
                }
            }

            return response()->json($formatted);
        } catch (\Throwable $e) {
            Log::error("Fulfillment searchProducts error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error'   => true,
                'message' => 'Gagal memuat produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Tukar / Substitusi SKU Item Pesanan (karena stok asli kosong, ganti varian atas kesepakatan pembeli)
     */
    public function substituteItem(Request $request, $orderItemId)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'new_master_product_id' => 'required|integer',
            'reason'                => 'nullable|string|max:255',
        ]);

        $item = OrderItem::with('order')->findOrFail($orderItemId);
        $order = $item->order;

        if ($order->tenant_id !== $tenantId) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        if (in_array($order->order_status, [Order::STATUS_CANCELLED, Order::STATUS_COMPLETED])) {
            return response()->json([
                'success' => false,
                'message' => 'Pesanan yang sudah dibatalkan atau selesai tidak dapat disubstitusi.'
            ], 400);
        }

        $newMasterProduct = MasterProduct::where('tenant_id', $tenantId)
            ->findOrFail($request->new_master_product_id);

        $oldMasterProductId = $item->master_product_id;
        $oldMasterProduct   = $oldMasterProductId ? MasterProduct::find($oldMasterProductId) : null;
        $oldSku             = $item->sku;
        $oldName            = $item->product_name;
        $reason             = $request->input('reason', 'Kesepakatan Chat Pembeli');

        DB::transaction(function () use ($item, $order, $oldMasterProduct, $newMasterProduct, $oldSku, $oldName, $reason) {
            // 1. Cek apakah item ini sudah pernah tercatat mutasi keluar (stock deduction)
            $wasDeducted = false;
            if ($oldMasterProduct) {
                $wasDeducted = StockMovement::where('master_product_id', $oldMasterProduct->id)
                    ->where('reference', 'LIKE', '%' . $order->order_marketplace_id . '%')
                    ->where('type', 'out')
                    ->exists();
            }

            // 2. Sesuaikan mutasi stok jika sudah pernah dipotong sebelumnya
            if ($wasDeducted && $oldMasterProduct) {
                // Kembalikan stok produk lama (IN)
                $oldMasterProduct->recordStockMovement(
                    $item->quantity,
                    'in',
                    'Substitusi Barang (Batal): ' . $order->invoice_number . ' (' . $order->order_marketplace_id . ') -> diganti ' . $newMasterProduct->sku
                );

                // Potong stok produk baru (OUT)
                $newMasterProduct->recordStockMovement(
                    $item->quantity,
                    'out',
                    'Substitusi Barang (Kirim): ' . $order->invoice_number . ' (' . $order->order_marketplace_id . ') -> ganti dari ' . ($oldMasterProduct->sku ?: $oldSku)
                );
            }

            // 3. Update OrderItem
            $item->update([
                'is_substituted'        => true,
                'original_sku'          => $item->original_sku ?: ($item->seller_sku ?: $oldSku),
                'original_product_name' => $item->original_product_name ?: $oldName,
                'substitution_note'     => $reason,
                'master_product_id'     => $newMasterProduct->id,
                'sku'                   => $newMasterProduct->sku,
                'seller_sku'            => $newMasterProduct->sku,
                'product_name'          => $newMasterProduct->name,
                'product_image'         => $newMasterProduct->image_url ?: $item->product_image,
                'cost_price'            => $newMasterProduct->cost_price ?: $item->cost_price,
                'hpp_subtotal'          => ($newMasterProduct->cost_price ?: ($item->cost_price ?? 0)) * $item->quantity,
            ]);

            // 4. Catat catatan audit di order
            $auditNote = "[Substitusi " . now()->format('d/m/Y H:i') . "] Item '{$oldSku}' diganti menjadi '{$newMasterProduct->sku}'. Alasan: {$reason}";
            $order->update([
                'seller_note' => trim(($order->seller_note ? $order->seller_note . "\n" : '') . $auditNote)
            ]);
        });

        // Kembalikan data item yang sudah terupdate
        $item->refresh();

        return response()->json([
            'success' => true,
            'message' => "Item '{$oldSku}' sukses diganti ke '{$newMasterProduct->sku}'.",
            'item' => [
                'id'                    => $item->id,
                'sku'                   => $item->sku,
                'barcode'               => $newMasterProduct->barcode ?? null,
                'name'                  => $item->product_name,
                'image'                 => $item->product_image ?: ($newMasterProduct->image_url ?: '/images/placeholder.png'),
                'quantity'              => $item->quantity,
                'is_substituted'        => true,
                'original_sku'          => $item->original_sku,
                'original_product_name' => $item->original_product_name,
                'substitution_note'     => $item->substitution_note,
            ]
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
            ->where('order_status', Order::STATUS_READY_TO_SHIP);

        if (!empty($ids)) {
            // Jika user mencentang pesanan tertentu, ambil pesanan terpilih
            $query->whereIn('id', $ids);
        } else {
            // Jika tidak mencentang pesanan spesifik, ambil pesanan yang SUDAH DIPRINT
            $query->where('is_printed', true);

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
                } elseif (in_array(strtolower($store->channel->code ?? ''), ['tiktok', 'tokopedia'])) {
                    $tiktokService = app(\App\Services\TiktokService::class);
                    $tiktokService->shipOrder(
                        $store->getValidAccessToken(),
                        $store->shop_cipher ?: $store->marketplace_store_id,
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
