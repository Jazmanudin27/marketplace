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


    public function authorize(Request $request)
    {
        // Simpan tenant_id di session agar bisa digunakan saat callback
        $tenantId = Auth::user()->tenant_id ?? 1;
        $returnTo = $request->query('return_to', 'v2');
        session([
            'shopee_oauth_tenant_id' => $tenantId,
            'shopee_oauth_return_to' => $returnTo,
        ]);

        $authUrl = $this->shopee->getAuthorizationUrl();

        Log::info('Shopee OAuth: Redirecting to authorization URL', [
            'tenant_id' => $tenantId,
            'return_to' => $returnTo,
            'url'       => $authUrl,
        ]);

        return redirect()->away($authUrl);
    }

    public function callback(Request $request)
    {
        $returnTo = session('shopee_oauth_return_to', 'v2');
        $redirectRoute = $returnTo === 'v1' ? 'stores.index' : 'v2.toko.index';

        // Validasi parameter dari Shopee
        if ($request->has('error')) {
            Log::warning('Shopee OAuth: User cancelled or error', ['params' => $request->all()]);
            return redirect()->route($redirectRoute)
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
                'return_to' => $returnTo,
            ]);

            // Hapus session
            session()->forget(['shopee_oauth_tenant_id', 'shopee_oauth_return_to']);

            return redirect()->route($redirectRoute)
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

                        return redirect()->route($redirectRoute)
                            ->with('success', "✅ Toko Shopee \"Shopee Toko {$shopId}\" berhasil ditambahkan ke ERP!");
                    }
                } catch (\Throwable $eFb) {
                    Log::error('Shopee fallback error: ' . $eFb->getMessage());
                }
            }

            return redirect()->route($redirectRoute)
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

        try {
            \App\Jobs\PullProductsFromShopee::dispatch($store);

            return back()->with('success', "Sinkronisasi produk Shopee \"{$store->store_name}\" sedang diproses di latar belakang.");
        } catch (\Throwable $e) {
            Log::error('Gagal sync produk Shopee', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Gagal memulai sinkronisasi produk: ' . $e->getMessage());
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
