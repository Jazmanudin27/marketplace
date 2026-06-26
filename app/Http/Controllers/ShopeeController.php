<?php

namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\Store;
use App\Services\ShopeeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ShopeeController extends Controller
{
    public function __construct(private ShopeeService $shopee)
    {
    }


    public function authorize()
    {
        // Simpan tenant_id di session agar bisa digunakan saat callback
        session(['shopee_oauth_tenant_id' => Auth::user()->tenant_id]);

        $authUrl = $this->shopee->getAuthorizationUrl();

        Log::info('Shopee OAuth: Redirecting to authorization URL', [
            'tenant_id' => Auth::user()->tenant_id,
            'url' => $authUrl,
        ]);

        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        // Validasi parameter dari Shopee
        if ($request->has('error')) {
            Log::warning('Shopee OAuth: User cancelled or error', ['params' => $request->all()]);
            return redirect()->route('stores.index')
                ->with('error', 'Otorisasi Shopee dibatalkan: ' . $request->get('error'));
        }

        $code = $request->get('code');
        $shopId = (int) $request->get('shop_id');

        if (!$code || !$shopId) {
            return redirect()->route('stores.index')
                ->with('error', 'Parameter callback dari Shopee tidak lengkap.');
        }

        // Ambil tenant dari session
        $tenantId = session('shopee_oauth_tenant_id');
        if (!$tenantId) {
            // Fallback ke user yang sedang login jika session hilang
            $tenantId = Auth::user()->tenant_id ?? null;
        }

        if (!$tenantId) {
            return redirect()->route('login')
                ->with('error', 'Sesi habis. Silakan login ulang dan coba lagi.');
        }

        try {
            // STEP 2a: Tukar code → access_token
            $tokenData = $this->shopee->getAccessToken($code, $shopId);

            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'];
            $expireIn = $tokenData['expire_in'] ?? 3600; // detik

            // STEP 2b: Ambil info nama toko dari Shopee
            $shopInfo = $this->shopee->getShopInfo($accessToken, $shopId);
            $storeName = $shopInfo['shop_name'] ?? ('Shopee Toko ' . $shopId);

            // STEP 2c: Cari channel Shopee
            Channel::ensureChannelsExist();
            $channel = Channel::where('code', 'shopee')->firstOrFail();

            // STEP 2d: Simpan / update store di database
            $store = Store::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'channel_id' => $channel->id,
                    'marketplace_store_id' => (string) $shopId,
                ],
                [
                    'store_name' => $storeName,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => now()->addSeconds($expireIn),
                    'status' => 'connected',
                ]
            );

            Log::info('Shopee OAuth: Store connected successfully', [
                'store_id' => $store->id,
                'shop_id' => $shopId,
                'store_name' => $storeName,
                'tenant_id' => $tenantId,
            ]);

            // Hapus session
            session()->forget('shopee_oauth_tenant_id');

            return redirect()->route('stores.index')
                ->with('success', "✅ Toko Shopee \"{$storeName}\" berhasil terhubung!");

        } catch (\Throwable $e) {
            Log::error('Shopee OAuth callback error', [
                'message' => $e->getMessage(),
                'code' => $code,
                'shop_id' => $shopId,
                'tenant_id' => $tenantId,
            ]);

            return redirect()->route('stores.index')
                ->with('error', 'Gagal menghubungkan toko Shopee: ' . $e->getMessage());
        }
    }

    public function refreshToken(Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($store->channel->code === 'shopee', 400, 'Bukan toko Shopee.');

        try {
            $shopId = (int) $store->marketplace_store_id;
            $tokenData = $this->shopee->refreshAccessToken($store->refresh_token, $shopId);

            $store->update([
                'access_token' => $tokenData['access_token'],
                'refresh_token' => $tokenData['refresh_token'] ?? $store->refresh_token,
                'token_expires_at' => now()->addSeconds($tokenData['expire_in'] ?? 3600),
                'status' => 'connected',
            ]);

            return redirect()->route('stores.index')
                ->with('success', "Token \"{$store->store_name}\" berhasil diperbarui.");

        } catch (\Throwable $e) {
            $store->update(['status' => 'expired']);

            return redirect()->route('stores.index')
                ->with('error', 'Gagal refresh token: ' . $e->getMessage());
        }
    }

    public function syncProducts(Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($store->channel->code === 'shopee', 400, 'Bukan toko Shopee.');
        abort_if($store->status !== 'connected', 400, 'Toko belum terhubung.');

        try {
            $shopId = (int) $store->marketplace_store_id;
            $accessToken = $store->getValidAccessToken();

            $offset = 0;
            $pageSize = 50;
            $hasMore = true;
            $totalSynced = 0;

            while ($hasMore) {
                // 1. Get Item List
                $listData = $this->shopee->getItemList($accessToken, $shopId, $offset, $pageSize);
                $items = $listData['item'] ?? [];

                if (empty($items)) {
                    break;
                }

                $itemIds = collect($items)->pluck('item_id')->toArray();

                // 2. Get Item Base Info
                $infoData = $this->shopee->getItemBaseInfo($accessToken, $shopId, $itemIds);
                $itemList = $infoData['item_list'] ?? [];

                // 3. Save to database
                foreach ($itemList as $item) {
                    $imageUrl = null;
                    if (!empty($item['image']['image_url_list'][0])) {
                        $imageUrl = $item['image']['image_url_list'][0];
                    }

                    if (!empty($item['has_model'])) {
                        // Jika punya varian (model), harus panggil API get_model_list
                        try {
                            $modelData = $this->shopee->getModelList($accessToken, $shopId, $item['item_id']);
                            $models = $modelData['model'] ?? [];
                            $tierVariations = $modelData['tier_variation'] ?? [];

                            $variantImages = [];
                            foreach ($tierVariations as $tier) {
                                foreach ($tier['option_list'] ?? [] as $option) {
                                    if (!empty($option['image']['image_url_list'][0])) {
                                        // Shopee tier_variation option name
                                        $variantImages[trim($option['option'])] = $option['image']['image_url_list'][0];
                                    }
                                }
                            }

                            if (count($models) > 0) {
                                foreach ($models as $model) {
                                    $price = $model['price_info'][0]['original_price'] ?? 0;
                                    $stock = $model['stock_info_v2']['summary_info']['total_available_stock'] ?? 0;
                                    $variantName = $item['item_name'] . ' - ' . $model['model_name'];

                                    $finalImageUrl = $imageUrl; // Fallback ke induk

                                    // Cari jika ada gambar khusus varian ini
                                    $options = explode(',', $model['model_name']);
                                    foreach ($options as $opt) {
                                        $opt = trim($opt);
                                        if (isset($variantImages[$opt])) {
                                            $finalImageUrl = $variantImages[$opt];
                                            break;
                                        }
                                    }

                                    \App\Models\MarketplaceProduct::updateOrCreate(
                                        [
                                            'store_id' => $store->id,
                                            'marketplace_product_id' => (string) $item['item_id'],
                                            'marketplace_variant_id' => (string) $model['model_id'],
                                        ],
                                        [
                                            'marketplace_sku' => $model['model_sku'] ?? null,
                                            'name' => $variantName,
                                            'price' => $price,
                                            'stock' => $stock,
                                            'image_url' => $finalImageUrl,
                                            'last_synced_at' => now(),
                                        ]
                                    );
                                    $totalSynced++;
                                }
                            }
                        } catch (\Exception $e) {
                            Log::warning("Gagal ambil model untuk item {$item['item_id']}", ['error' => $e->getMessage()]);
                        }
                    } else {
                        // Jika tidak ada varian, ambil langsung dari base info
                        $price = $item['price_info'][0]['original_price'] ?? 0;
                        $stock = $item['stock_info_v2']['summary_info']['total_available_stock'] ?? 0;

                        \App\Models\MarketplaceProduct::updateOrCreate(
                            [
                                'store_id' => $store->id,
                                'marketplace_product_id' => (string) $item['item_id'],
                                'marketplace_variant_id' => null,
                            ],
                            [
                                'marketplace_sku' => $item['item_sku'] ?? null,
                                'name' => $item['item_name'],
                                'price' => $price,
                                'stock' => $stock,
                                'image_url' => $imageUrl,
                                'last_synced_at' => now(),
                            ]
                        );
                        $totalSynced++;
                    }
                }

                $hasMore = $listData['has_next_page'] ?? false;
                $offset += $pageSize;
            }

            return redirect()->route('stores.index')
                ->with('success', "Berhasil menarik $totalSynced produk dari {$store->store_name}.");

        } catch (\Throwable $e) {
            Log::error('Gagal sync produk Shopee', ['store_id' => $store->id, 'error' => $e->getMessage()]);
            return redirect()->route('stores.index')
                ->with('error', 'Gagal sync produk: ' . $e->getMessage());
        }
    }

    public function syncOrders(Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($store->channel->code === 'shopee', 400, 'Bukan toko Shopee.');
        abort_if($store->status !== 'connected', 400, 'Toko belum terhubung.');

        try {
            $shopId = (int) $store->marketplace_store_id;
            $accessToken = $store->getValidAccessToken();

            $timeTo = time();
            $timeFrom = $timeTo - (15 * 86400); // 15 hari terakhir

            $cursor = '';
            $hasMore = true;
            $totalSynced = 0;

            while ($hasMore) {
                // 1. Get Order List
                $listData = $this->shopee->getOrderList($accessToken, $shopId, $timeFrom, $timeTo, 'create_time', $cursor);
                $orders = $listData['order_list'] ?? [];

                if (empty($orders)) {
                    break;
                }

                $orderSns = collect($orders)->pluck('order_sn')->toArray();

                // 2. Get Order Detail
                $detailData = $this->shopee->getOrderDetail($accessToken, $shopId, $orderSns);
                $orderList = $detailData['order_list'] ?? [];

                // 3. Save to database
                foreach ($orderList as $order) {
                    \Illuminate\Support\Facades\DB::transaction(function () use ($order, $store) {
                        $orderDate = \Carbon\Carbon::createFromTimestamp($order['create_time']);

                        $addressData = $order['recipient_address'] ?? [];
                        $address = ($addressData['full_address'] ?? '') . ', ' . ($addressData['city'] ?? '') . ', ' . ($addressData['state'] ?? '') . ' ' . ($addressData['zipcode'] ?? '');
                        $phone = $addressData['phone'] ?? null;

                        // Buat atau update order
                        $localOrder = \App\Models\Order::updateOrCreate(
                            [
                                'tenant_id' => $store->tenant_id,
                                'store_id' => $store->id,
                                'order_marketplace_id' => $order['order_sn'],
                            ],
                            [
                                'order_status' => $order['order_status'],
                                'buyer_name' => $order['buyer_username'] ?? $addressData['name'] ?? null,
                                'buyer_phone' => $phone,
                                'shipping_address' => $address,
                                'total_amount' => $order['total_amount'] ?? 0,
                                'shipping_fee' => $order['actual_shipping_fee'] ?? $order['estimated_shipping_fee'] ?? 0,
                                'courier' => $order['shipping_carrier'] ?? null,
                                'tracking_number' => $order['tracking_no'] ?? null,
                                'order_date' => $orderDate,
                            ]
                        );

                        // Simpan order items
                        $itemList = $order['item_list'] ?? [];
                        foreach ($itemList as $item) {
                            $modelId = $item['model_id'] ?? null;
                            $query = \App\Models\MarketplaceProduct::where('store_id', $store->id)
                                ->where('marketplace_product_id', (string) $item['item_id']);
                            if ($modelId) {
                                $query->where('marketplace_variant_id', (string) $modelId);
                            }
                            $marketplaceProduct = $query->first();

                            // Fallback without model_id
                            if (!$marketplaceProduct && $modelId) {
                                $marketplaceProduct = \App\Models\MarketplaceProduct::where('store_id', $store->id)
                                    ->where('marketplace_product_id', (string) $item['item_id'])
                                    ->first();
                            }

                            $price = $item['model_discounted_price'] ?? $item['model_original_price'] ?? 0;
                            $qty = $item['model_quantity_purchased'] ?? 1;
                            $itemSku = $item['model_sku'] ?: ($item['item_sku'] ?? null);

                            // Resolve MasterProduct
                            $masterProduct = $marketplaceProduct ? $marketplaceProduct->masterProduct : null;
                            
                            // Fallback to SKU matching if mapping not resolved yet
                            if (!$masterProduct && $itemSku) {
                                $masterProduct = \App\Models\MasterProduct::where('tenant_id', $store->tenant_id)
                                    ->where('sku', $itemSku)
                                    ->first();
                            }

                            $masterProductId = $masterProduct ? $masterProduct->id : null;

                            \App\Models\OrderItem::updateOrCreate(
                                [
                                    'order_id' => $localOrder->id,
                                    'sku' => $itemSku,
                                ],
                                [
                                    'marketplace_product_id' => $marketplaceProduct ? $marketplaceProduct->id : null,
                                    'master_product_id' => $masterProductId,
                                    'product_name' => $item['item_name'] . (!empty($item['model_name']) ? ' - ' . $item['model_name'] : ''),
                                    'price' => $price,
                                    'quantity' => $qty,
                                    'total_price' => $price * $qty,
                                ]
                            );
                        }
                    });
                    $totalSynced++;
                }

                $hasMore = $listData['more'] ?? false;
                $cursor = $listData['next_cursor'] ?? '';
            }

            return redirect()->route('stores.index')
                ->with('success', "Berhasil menarik $totalSynced pesanan dari {$store->store_name}.");

        } catch (\Throwable $e) {
            Log::error('Gagal sync pesanan Shopee', ['store_id' => $store->id, 'error' => $e->getMessage()]);
            return redirect()->route('stores.index')
                ->with('error', 'Gagal sync pesanan: ' . $e->getMessage());
        }
    }
}
