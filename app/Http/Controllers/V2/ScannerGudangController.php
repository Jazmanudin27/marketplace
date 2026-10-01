<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\MasterProduct;
use App\Models\MarketplaceProduct;
use App\Models\StockMovement;
use App\Models\SupplierConsignmentItem;
use App\Models\SupplierConsignmentDeduction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScannerGudangController extends Controller
{
    /**
     * Halaman Layar Scanner Gudang V2 (Pick & Pack Barcode Scanner).
     */
    public function index()
    {
        return view('v2.scanner_gudang.index');
    }

    /**
     * Ambil detail pesanan & item untuk keperluan pemindaian barcode (AJAX V2).
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

        $tenantId = Auth::user()->tenant_id;

        $order = Order::with(['items.masterProduct', 'items.marketplaceProduct', 'store.channel'])
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($cleanIdentifier) {
                $q->where('invoice_number', $cleanIdentifier)
                  ->orWhere('order_marketplace_id', $cleanIdentifier)
                  ->orWhere('tracking_number', $cleanIdentifier)
                  ->orWhere('package_id', $cleanIdentifier);
            })
            ->first();

        // Fallback pencarian parsial
        if (!$order) {
            $order = Order::with(['items.masterProduct', 'items.marketplaceProduct', 'store.channel'])
                ->where('tenant_id', $tenantId)
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
                'message' => "Pesanan ini tidak dalam status SIAP KIRIM (Status saat ini: " . strtoupper($order->order_status) . ")."
            ], 400);
        }

        if (!$order->is_printed) {
            if (!empty($order->tracking_number)) {
                $order->update([
                    'is_printed' => true,
                    'printed_at' => now(),
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Pesanan '{$cleanIdentifier}' BELUM DICETAK RESINYA! Cetak resi terlebih dahulu sebelum dikemas."
                ], 400);
            }
        }

        if ($order->packing_status === 'pending') {
            $order->update(['packing_status' => 'packing']);
        }

        $items = [];
        foreach ($order->items as $item) {
            $masterProduct = $item->masterProduct;

            if ($masterProduct && $masterProduct->is_bundle) {
                $components = $masterProduct->components;
                if ($components->isEmpty() && $masterProduct->activeRecipe && $masterProduct->activeRecipe->items) {
                    foreach ($masterProduct->activeRecipe->items as $recipeItem) {
                        $compProduct = $recipeItem->ingredientProduct ?? MasterProduct::find($recipeItem->ingredient_master_product_id ?? $recipeItem->component_id);
                        if ($compProduct) {
                            $qty = (int) ($recipeItem->quantity ?? 1);
                            $items[] = [
                                'id'                    => $item->id . '-' . $compProduct->id,
                                'sku'                   => $compProduct->sku,
                                'barcode'               => $compProduct->barcode ?? null,
                                'name'                  => '[Bundle Item] ' . $compProduct->name . ' (' . $masterProduct->name . ')',
                                'image'                 => $compProduct->image_url ?: '/images/placeholder.png',
                                'quantity'              => $item->quantity * $qty,
                                'is_substituted'        => false,
                                'master_product_id'     => $compProduct->id,
                                'active_consignments'   => [],
                                'total_titipan_stock'   => 0,
                                'gudang_stock'          => (int) $compProduct->stock,
                            ];
                        }
                    }
                } else {
                    foreach ($components as $comp) {
                        $qty = (int) ($comp->pivot->quantity ?? 1);
                        $items[] = [
                            'id'                    => $item->id . '-' . $comp->id,
                            'sku'                   => $comp->sku,
                            'barcode'               => $comp->barcode ?? null,
                            'name'                  => '[Bundle Item] ' . $comp->name . ' (' . $masterProduct->name . ')',
                            'image'                 => $comp->image_url ?: '/images/placeholder.png',
                            'quantity'              => $item->quantity * $qty,
                            'is_substituted'        => false,
                            'master_product_id'     => $comp->id,
                            'active_consignments'   => [],
                            'total_titipan_stock'   => 0,
                            'gudang_stock'          => (int) $comp->stock,
                        ];
                    }
                }

                if (empty($items)) {
                    $sku   = $item->sku ?? ($masterProduct->sku ?? ($item->marketplaceProduct->marketplace_sku ?? ''));
                    $name  = $item->product_name ?? ($masterProduct->name ?? 'Produk Tanpa Nama');
                    $image = $item->product_image ?? ($masterProduct->image_url ?? ($item->marketplaceProduct->image_url ?? ''));

                    $items[] = [
                        'id'                    => $item->id,
                        'sku'                   => $sku,
                        'barcode'               => $masterProduct->barcode ?? null,
                        'name'                  => $name,
                        'image'                 => $image,
                        'quantity'              => $item->quantity,
                        'is_substituted'        => (bool) $item->is_substituted,
                        'original_sku'          => $item->original_sku,
                        'original_product_name' => $item->original_product_name,
                        'substitution_note'     => $item->substitution_note,
                        'master_product_id'     => $masterProduct ? $masterProduct->id : null,
                        'active_consignments'   => [],
                        'total_titipan_stock'   => 0,
                        'gudang_stock'          => $masterProduct ? (int) $masterProduct->stock : 0,
                    ];
                }
            } else {
                $sku   = $item->sku ?? ($masterProduct->sku ?? ($item->marketplaceProduct->marketplace_sku ?? ''));
                $name  = $item->product_name ?? ($masterProduct->name ?? 'Produk Tanpa Nama');
                $image = $item->product_image ?? ($masterProduct->image_url ?? ($item->marketplaceProduct->image_url ?? ''));

                $activeConsignments = [];
                if ($masterProduct) {
                    $cItems = SupplierConsignmentItem::where('master_product_id', $masterProduct->id)
                        ->whereHas('consignment', function ($q) use ($order) {
                            $q->where('tenant_id', $order->tenant_id)->where('status', 'approved');
                        })
                        ->with('consignment.supplier')
                        ->get();

                    foreach ($cItems as $ci) {
                        $sisa = max(0, (int) $ci->qty_received - (int) ($ci->qty_sold ?? 0));
                        if ($sisa > 0) {
                            $activeConsignments[] = [
                                'consignment_item_id' => $ci->id,
                                'consignment_id'      => $ci->supplier_consignment_id,
                                'reference_number'    => $ci->consignment ? $ci->consignment->reference_number : '-',
                                'supplier_name'       => ($ci->consignment && $ci->consignment->supplier) ? $ci->consignment->supplier->name : 'Supplier',
                                'sisa_stok'           => $sisa,
                            ];
                        }
                    }
                }
                $totalTitipanStock = array_sum(array_column($activeConsignments, 'sisa_stok'));

                $items[] = [
                    'id'                    => $item->id,
                    'sku'                   => $sku,
                    'barcode'               => $masterProduct->barcode ?? null,
                    'name'                  => $name,
                    'image'                 => $image,
                    'quantity'              => $item->quantity,
                    'is_substituted'        => (bool) $item->is_substituted,
                    'original_sku'          => $item->original_sku,
                    'original_product_name' => $item->original_product_name,
                    'substitution_note'     => $item->substitution_note,
                    'master_product_id'     => $masterProduct ? $masterProduct->id : null,
                    'active_consignments'   => $activeConsignments,
                    'total_titipan_stock'   => $totalTitipanStock,
                    'gudang_stock'          => $masterProduct ? (int) $masterProduct->stock : 0,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'order' => [
                'id'              => $order->id,
                'invoice_number'  => $order->invoice_number ?? $order->order_marketplace_id,
                'tracking_number' => $order->tracking_number ?? null,
                'buyer_name'      => $order->buyer_name ?? '-',
                'courier'         => $order->courier ?? '-',
                'store_name'      => $order->store ? $order->store->store_name : '-',
                'channel_code'    => ($order->store && $order->store->channel) ? $order->store->channel->code : 'general',
                'channel_name'    => ($order->store && $order->store->channel) ? $order->store->channel->name : 'General',
                'packing_status'  => $order->packing_status,
                'items'           => $items,
            ]
        ]);
    }

    /**
     * Konfirmasi verifikasi packing & opsi kirim langsung via API Marketplace (AJAX V2).
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
            'packed_at'      => now(),
        ]);

        // Deduct stock physically
        $order->processStockDeduction();

        // Process Consignment Deduction if scanned from supplier consignment item
        $itemSources = $request->input('item_sources', []);
        if (is_array($itemSources) && !empty($itemSources)) {
            foreach ($itemSources as $orderItemId => $sources) {
                $orderItem = $order->items->firstWhere('id', (int) $orderItemId);
                if (!$orderItem) continue;

                if (is_array($sources)) {
                    foreach ($sources as $source) {
                        $srcType           = $source['source'] ?? 'warehouse';
                        $consignmentItemId = $source['consignment_item_id'] ?? null;
                        $barcode           = $source['barcode'] ?? null;

                        if ($srcType === 'consignment' && $consignmentItemId) {
                            $consItem = SupplierConsignmentItem::where('id', $consignmentItemId)
                                ->whereHas('consignment', fn($q) => $q->where('tenant_id', $order->tenant_id))
                                ->first();

                            if ($consItem) {
                                $consItem->increment('qty_sold', 1);

                                $orderItem->update([
                                    'supplier_consignment_item_id' => $consItem->id,
                                    'fulfillment_source'           => 'consignment',
                                ]);

                                SupplierConsignmentDeduction::create([
                                    'tenant_id'                    => $order->tenant_id,
                                    'supplier_consignment_item_id' => $consItem->id,
                                    'order_id'                     => $order->id,
                                    'order_item_id'                => $orderItem->id,
                                    'quantity'                     => 1,
                                    'scanned_barcode'              => $barcode,
                                    'user_id'                      => Auth::id(),
                                ]);

                                StockMovement::create([
                                    'tenant_id'         => $order->tenant_id,
                                    'master_product_id' => $consItem->master_product_id,
                                    'user_id'           => Auth::id(),
                                    'type'              => 'out',
                                    'quantity'          => 1,
                                    'balance_after'     => $consItem->masterProduct ? $consItem->masterProduct->stock : 0,
                                    'reference'         => "Pengurangan Stok Titipan ({$consItem->consignment->reference_number}) - Order {$order->invoice_number}",
                                ]);
                            }
                        } else {
                            $orderItem->update([
                                'fulfillment_source' => ($srcType === 'spk' ? 'spk' : 'warehouse'),
                            ]);
                        }
                    }
                }
            }
        }

        $autoShip = $request->boolean('auto_ship');
        $shipped = false;
        $message = "Verifikasi pesanan '{$order->invoice_number}' sukses disimpan ke database.";

        if ($autoShip && $order->store && $order->store->channel) {
            $store = $order->store;
            $handoverMethod = $store->shipping_handover_method ?? 'DROP_OFF';
            try {
                if ($store->channel->code === 'shopee') {
                    $shopeeService = app(\App\Services\ShopeeService::class);
                    $accessToken   = $store->getValidAccessToken();

                    try {
                        $shopeeService->shipOrder(
                            $accessToken,
                            (int) $store->marketplace_store_id,
                            $order->order_marketplace_id,
                            $handoverMethod
                        );
                    } catch (\Exception $e) {
                        Log::info("[Scanner V2] Ship Shopee info: " . $e->getMessage());
                    }

                    $order->order_status = Order::STATUS_SHIPPED;
                    $order->save();
                    $shipped = true;
                    $message = "Kemas sukses! Pesanan berhasil dikirim ke Shopee.";
                } elseif (in_array(strtolower($store->channel->code ?? ''), ['tiktok', 'tokopedia'])) {
                    $tiktokService = app(\App\Services\TiktokService::class);
                    $accessToken   = $store->getValidAccessToken();
                    $shopCipher    = $store->shop_cipher ?: $store->marketplace_store_id;

                    try {
                        $tiktokService->shipOrder(
                            $accessToken,
                            $shopCipher,
                            $order->order_marketplace_id,
                            $handoverMethod,
                            $order->package_id
                        );
                    } catch (\Exception $e) {
                        Log::info("[Scanner V2] Ship TikTok info: " . $e->getMessage());
                    }

                    $order->order_status = Order::STATUS_SHIPPED;
                    $order->save();
                    $shipped = true;
                    $message = "Kemas sukses! Pesanan berhasil dikirim ke TikTok / Tokopedia.";
                }
            } catch (\Exception $e) {
                Log::error("[Scanner V2] Gagal ship order {$order->id}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'shipped' => $shipped,
            'message' => $message,
        ]);
    }

    /**
     * Pencarian Produk Pengganti (Substitusi Item) V2.
     */
    public function searchProducts(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search   = trim($request->input('q', ''));

        $query = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%')
                  ->orWhere('barcode', 'like', '%' . $search . '%');
            });
        }

        $products = $query->select(['id', 'sku', 'barcode', 'name', 'stock', 'unit', 'price'])
            ->orderBy('name')
            ->limit(30)
            ->get();

        return response()->json([
            'success'  => true,
            'products' => $products
        ]);
    }

    /**
     * Terapkan Substitusi Produk pada Order Item V2.
     */
    public function substituteItem(Request $request, OrderItem $orderItem)
    {
        $tenantId = Auth::user()->tenant_id;
        abort_unless($orderItem->order->tenant_id === $tenantId, 403);

        $request->validate([
            'new_master_product_id' => 'required|exists:master_products,id',
            'note'                  => 'nullable|string|max:255',
        ]);

        $newProduct = MasterProduct::where('tenant_id', $tenantId)
            ->where('id', $request->new_master_product_id)
            ->firstOrFail();

        $originalSku  = $orderItem->original_sku ?: $orderItem->sku;
        $originalName = $orderItem->original_product_name ?: $orderItem->product_name;

        $orderItem->update([
            'master_product_id'     => $newProduct->id,
            'sku'                   => $newProduct->sku,
            'product_name'          => $newProduct->name,
            'is_substituted'        => true,
            'original_sku'          => $originalSku,
            'original_product_name' => $originalName,
            'substitution_note'     => $request->note ?: 'Ditukar melalui Layar Scanner Gudang V2',
        ]);

        return response()->json([
            'success' => true,
            'message' => "Produk berhasil ditukar ke '{$newProduct->name}' [SKU: {$newProduct->sku}].",
            'item'    => [
                'id'           => $orderItem->id,
                'sku'          => $newProduct->sku,
                'barcode'      => $newProduct->barcode,
                'name'         => $newProduct->name,
                'gudang_stock' => (int) $newProduct->stock,
            ]
        ]);
    }
}
