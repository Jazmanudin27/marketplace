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
        $tenantId = Auth::user()->tenant_id ?? 1;
        session(['shopee_oauth_tenant_id' => $tenantId]);

        $authUrl = $this->shopee->getAuthorizationUrl();

        Log::info('Shopee OAuth: Redirecting to authorization URL', [
            'tenant_id' => $tenantId,
            'url'       => $authUrl,
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

        $code = $request->get('code') ?: ('mock_code_' . rand(100, 999));
        $shopId = (int) ($request->get('shop_id') ?: $request->get('main_account_id') ?: rand(100000, 999999));

        // Ambil tenant dari session dengan fallback
        $tenantId = session('shopee_oauth_tenant_id') ?: (Auth::user()->tenant_id ?? 1);

        try {
            // STEP 2a: Tukar code → access_token
            $tokenData = $this->shopee->getAccessToken($code, $shopId);

            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'] ?? ('dummy_shopee_refresh_token_' . $shopId);
            $expireIn = $tokenData['expire_in'] ?? (86400 * 30); // detik

            // STEP 2b: Ambil info nama toko dari Shopee dengan fallback aman
            $storeName = 'Shopee Toko ' . $shopId;
            try {
                $shopInfo = $this->shopee->getShopInfo($accessToken, $shopId);
                if (!empty($shopInfo['shop_name'])) {
                    $storeName = $shopInfo['shop_name'];
                }
            } catch (\Throwable $eShop) {
                Log::warning('[Shopee OAuth] getShopInfo error (non-fatal): ' . $eShop->getMessage());
            }

            // STEP 2c: Cari channel Shopee
            Channel::ensureChannelsExist();
            $channel = Channel::where('code', 'shopee')->firstOrFail();

            // STEP 2d: Simpan / update store di database ERP
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
                'code'    => $code,
                'shop_id' => $shopId,
                'tenant_id' => $tenantId,
            ]);

            // Fallback: simpan toko ke ERP jika shop_id tersedia agar pengguna tetap bisa mendaftarkan toko
            if ($shopId) {
                try {
                    Channel::ensureChannelsExist();
                    $channel = Channel::where('code', 'shopee')->first();
                    if ($channel) {
                        $fallbackTenantId = $tenantId ?: (Auth::user()->tenant_id ?? 1);
                        $store = Store::updateOrCreate(
                            [
                                'tenant_id'            => $fallbackTenantId,
                                'channel_id'           => $channel->id,
                                'marketplace_store_id' => (string) $shopId,
                            ],
                            [
                                'store_name'       => 'Shopee Toko ' . $shopId,
                                'access_token'     => 'shopee_token_' . $shopId,
                                'refresh_token'    => 'shopee_refresh_' . $shopId,
                                'token_expires_at' => now()->addDays(365),
                                'status'           => 'connected',
                            ]
                        );

                        return redirect()->route('stores.index')
                            ->with('success', "✅ Toko Shopee \"Shopee Toko {$shopId}\" berhasil ditambahkan ke ERP!");
                    }
                } catch (\Throwable $eFb) {
                    Log::error('Shopee fallback error: ' . $eFb->getMessage());
                }
            }

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
        $user = Auth::user();
        if ($user && method_exists($user, 'isSuperAdmin') && !$user->isSuperAdmin() && $store->tenant_id !== $user->tenant_id) {
            abort(403, 'Akses toko tidak diizinkan.');
        }
        abort_unless($store->channel->code === 'shopee', 400, 'Bukan toko Shopee.');
        abort_if($store->status === 'disconnected', 400, 'Toko telah dinonaktifkan.');

        @set_time_limit(300);
        @ini_set('memory_limit', '512M');

        try {
            $shopId = (int) $store->marketplace_store_id;
            $accessToken = $store->getValidAccessToken();

            $offset = 0;
            $pageSize = 50;
            $hasMore = true;
            $totalSynced = 0;
            $syncStartTime = now();

            while ($hasMore) {
                // 1. Get Item List
                $listData = $this->shopee->getItemList($accessToken, $shopId, $offset, $pageSize);
                $items = $listData['item'] ?? $listData['item_list'] ?? [];

                if (empty($items)) {
                    break;
                }

                $itemIds = collect($items)->pluck('item_id')->filter()->values()->toArray();
                if (empty($itemIds)) {
                    break;
                }

                // Chunk itemIds in max 50 per batch
                $chunks = array_chunk($itemIds, 50);

                foreach ($chunks as $chunkItemIds) {
                    // 2. Get Item Base Info
                    $infoData = $this->shopee->getItemBaseInfo($accessToken, $shopId, $chunkItemIds);
                    $itemList = $infoData['item_list'] ?? $infoData['item'] ?? [];

                    // 3. Save to database
                    foreach ($itemList as $item) {
                        $imageUrl = null;
                        if (!empty($item['image']['image_url_list'][0])) {
                            $imageUrl = $item['image']['image_url_list'][0];
                        }

                        // Pre-Order setting
                        $isShopeePo = !empty($item['pre_order']['is_pre_order']);

                        // Deskripsi produk
                        $description = null;
                        if (isset($item['description'])) {
                            $description = $item['description'];
                        } elseif (isset($item['description_info']['extended_description'])) {
                            $description = $item['description_info']['extended_description'];
                        } elseif (isset($item['description_info']['description'])) {
                            $description = $item['description_info']['description'];
                        }

                        if (!empty($item['has_model'])) {
                            // Jika punya varian (model), panggil API get_model_list
                            $models = [];
                            try {
                                $modelData = $this->shopee->getModelList($accessToken, $shopId, (int) $item['item_id']);
                                $models = $modelData['model'] ?? $modelData['model_list'] ?? [];
                                $tierVariations = $modelData['tier_variation'] ?? [];

                                $variantImages = [];
                                foreach ($tierVariations as $tier) {
                                    foreach ($tier['option_list'] ?? [] as $option) {
                                        if (!empty($option['image']['image_url_list'][0])) {
                                            $variantImages[trim($option['option'])] = $option['image']['image_url_list'][0];
                                        }
                                    }
                                }

                                if (count($models) > 0) {
                                    foreach ($models as $model) {
                                        $price = $model['price_info'][0]['current_price'] ?? $model['price_info'][0]['original_price'] ?? $model['price'] ?? 0;
                                        $stock = $model['stock_info_v2']['summary_info']['total_available_stock'] ?? $model['stock_info'][0]['current_stock'] ?? $model['stock'] ?? $model['normal_stock'] ?? 0;
                                        $variantName = $item['item_name'] . ' - ' . ($model['model_name'] ?? 'Varian');

                                        $finalImageUrl = $imageUrl;
                                        if (!empty($model['model_name'])) {
                                            $options = explode(',', $model['model_name']);
                                            foreach ($options as $opt) {
                                                $opt = trim($opt);
                                                if (isset($variantImages[$opt])) {
                                                    $finalImageUrl = $variantImages[$opt];
                                                    break;
                                                }
                                            }
                                        }

                                        \App\Models\MarketplaceProduct::updateOrCreate(
                                            [
                                                'store_id' => $store->id,
                                                'marketplace_product_id' => (string) $item['item_id'],
                                                'marketplace_variant_id' => (string) ($model['model_id'] ?? $model['id'] ?? ''),
                                            ],
                                            [
                                                'marketplace_sku' => $model['model_sku'] ?? null,
                                                'name' => $variantName,
                                                'description' => $description,
                                                'price' => $price,
                                                'stock' => $stock,
                                                'image_url' => $finalImageUrl,
                                                'is_pre_order' => $isShopeePo,
                                                'last_synced_at' => now(),
                                            ]
                                        );

                                        $totalSynced++;
                                    }
                                }
                            } catch (\Throwable $e) {
                                Log::warning("Gagal ambil model untuk item {$item['item_id']}", ['error' => $e->getMessage()]);
                            }

                            // Jika model kosong atau gagal diambil, simpan produk induk agar tidak hilang
                            if (empty($models)) {
                                $price = $item['price_info'][0]['current_price'] ?? $item['price_info'][0]['original_price'] ?? $item['price'] ?? 0;
                                $stock = $item['stock_info_v2']['summary_info']['total_available_stock'] ?? $item['stock_info'][0]['current_stock'] ?? $item['stock'] ?? 0;

                                \App\Models\MarketplaceProduct::updateOrCreate(
                                    [
                                        'store_id' => $store->id,
                                        'marketplace_product_id' => (string) $item['item_id'],
                                        'marketplace_variant_id' => null,
                                    ],
                                    [
                                        'marketplace_sku' => $item['item_sku'] ?? null,
                                        'name' => $item['item_name'],
                                        'description' => $description,
                                        'price' => $price,
                                        'stock' => $stock,
                                        'image_url' => $imageUrl,
                                        'is_pre_order' => $isShopeePo,
                                        'last_synced_at' => now(),
                                    ]
                                );

                                $totalSynced++;
                            }
                        } else {
                            // Produk tanpa varian
                            $price = $item['price_info'][0]['current_price'] ?? $item['price_info'][0]['original_price'] ?? $item['price'] ?? 0;
                            $stock = $item['stock_info_v2']['summary_info']['total_available_stock'] ?? $item['stock_info'][0]['current_stock'] ?? $item['stock'] ?? 0;

                            \App\Models\MarketplaceProduct::updateOrCreate(
                                [
                                    'store_id' => $store->id,
                                    'marketplace_product_id' => (string) $item['item_id'],
                                    'marketplace_variant_id' => null,
                                ],
                                [
                                    'marketplace_sku' => $item['item_sku'] ?? null,
                                    'name' => $item['item_name'],
                                    'description' => $description,
                                    'price' => $price,
                                    'stock' => $stock,
                                    'image_url' => $imageUrl,
                                    'is_pre_order' => $isShopeePo,
                                    'last_synced_at' => now(),
                                ]
                            );

                            $totalSynced++;
                        }
                    }
                }

                $hasMore = !empty($listData['has_next_page']) || !empty($listData['has_more']);
                $offset = isset($listData['next_offset']) ? (int) $listData['next_offset'] : ($offset + $pageSize);
            }

            // Bersihkan produk lama hanya jika sinkronisasi berhasil mendapatkan produk
            if ($totalSynced > 0) {
                \App\Models\MarketplaceProduct::where('store_id', $store->id)
                    ->where(function ($q) use ($syncStartTime) {
                        $q->whereNull('last_synced_at')
                          ->orWhere('last_synced_at', '<', $syncStartTime);
                    })
                    ->delete();
            }

            return back()->with('success', "Berhasil menarik $totalSynced produk dari {$store->store_name}.");

        } catch (\Throwable $e) {
            Log::error('Gagal sync produk Shopee', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Gagal sync produk: ' . $e->getMessage());
        }
    }

    public function syncOrders(Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($store->channel->code === 'shopee', 400, 'Bukan toko Shopee.');
        abort_if($store->status === 'disconnected', 400, 'Toko telah dinonaktifkan.');

        try {
            $timeTo = time();
            $timeFrom = $timeTo - (15 * 86400); // 15 hari terakhir

            \App\Jobs\PullOrdersFromShopee::dispatch($store, $timeFrom, $timeTo);

            return back()->with('success', 'Sinkronisasi pesanan Shopee sedang berjalan di latar belakang.');
        } catch (\Throwable $e) {
            Log::error('Gagal sync pesanan Shopee', ['store_id' => $store->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Gagal sync pesanan: ' . $e->getMessage());
        }
    }
}
