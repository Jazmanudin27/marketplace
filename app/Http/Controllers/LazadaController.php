<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\LazadaService;
use App\Models\Store;
use App\Models\Channel;
use App\Jobs\PullProductsFromLazada;
use App\Jobs\PullOrdersFromLazada;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LazadaController extends Controller
{
    protected $lazadaService;

    public function __construct(LazadaService $lazadaService)
    {
        $this->lazadaService = $lazadaService;
    }

    /**
     * Redirect ke Lazada OAuth
     */
    public function authorizeLazada(Request $request)
    {
        // Simpan tenant_id di state OAuth agar tersedia di callback
        $state = base64_encode(json_encode([
            'tenant_id' => Auth::user()->tenant_id,
            'nonce' => Str::random(10),
        ]));

        $url = $this->lazadaService->getAuthUrl($state);

        Log::info('[Lazada OAuth] Redirect ke authorization URL', [
            'tenant_id' => Auth::user()->tenant_id,
            'url' => $url,
        ]);

        return redirect()->away($url);
    }

    /**
     * Handle Lazada OAuth Callback
     */
    public function callback(Request $request)
    {
        try {
            $code = $request->query('code');
            $stateRaw = $request->query('state');

            if (!$code) {
                return redirect()->route('stores.index')->with('error', 'Otorisasi Lazada dibatalkan.');
            }

            $state = json_decode(base64_decode($stateRaw), true);
            $tenantId = $state['tenant_id'] ?? null;

            if (!$tenantId) {
                // Fallback ke user login
                $tenantId = Auth::user()->tenant_id ?? null;
            }

            if (!$tenantId) {
                return redirect()->route('stores.index')->with('error', 'Invalid State parameter.');
            }

            // Dapatkan Access Token dari Lazada
            $tokenData = $this->lazadaService->getAccessToken($code);
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'];

            // Dapatkan info toko
            $shopInfo = $this->lazadaService->getShopInfo($accessToken);
            $shopName = $shopInfo['shop_name'] ?? 'Toko Lazada';
            $realShopId = $shopInfo['seller_id'] ?? ('LAZ-' . time());

            // Pastikan channel ada di database
            Channel::ensureChannelsExist();
            $channelModel = Channel::where('code', 'lazada')->first();

            if (!$channelModel) {
                $channelModel = Channel::create([
                    'code' => 'lazada',
                    'name' => 'Lazada',
                    'logo_url' => 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/a5/Lazada_Logo.svg/512px-Lazada_Logo.svg.png',
                    'status' => true,
                ]);
            }

            // Simpan atau update store
            $store = Store::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'channel_id' => $channelModel->id,
                    'marketplace_store_id' => $realShopId,
                ],
                [
                    'store_name' => $shopName,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 604800),
                    'status' => 'connected',
                ]
            );

            Log::info('[Lazada OAuth] Toko berhasil dihubungkan', [
                'store_id' => $store->id,
                'shop_id' => $realShopId,
                'store_name' => $shopName,
                'tenant_id' => $tenantId,
            ]);

            return redirect()->route('stores.index')
                ->with('success', "✅ Toko Lazada \"{$shopName}\" berhasil dihubungkan!");

        } catch (\Throwable $e) {
            Log::error('[Lazada OAuth] Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('stores.index')
                ->with('error', 'Gagal menghubungkan toko Lazada: ' . $e->getMessage());
        }
    }

    /**
     * Sinkronisasi produk dari Lazada
     */
    public function syncProducts(Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($store->channel->code === 'lazada', 400, 'Bukan toko Lazada.');
        abort_if($store->status === 'disconnected', 400, 'Toko telah dinonaktifkan.');

        try {
            PullProductsFromLazada::dispatch($store);
            return back()->with('success', "Sinkronisasi produk Lazada sedang berjalan di latar belakang.");
        } catch (\Exception $e) {
            Log::error('[Lazada Sync Products] Gagal memulai sync', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal memulai sinkronisasi produk: ' . $e->getMessage());
        }
    }

    /**
     * Sinkronisasi pesanan dari Lazada
     */
    public function syncOrders(Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless($store->channel->code === 'lazada', 400, 'Bukan toko Lazada.');
        abort_if($store->status === 'disconnected', 400, 'Toko telah dinonaktifkan.');

        try {
            $timeFrom = now()->subDays(15)->timestamp;
            $timeTo = now()->timestamp;

            PullOrdersFromLazada::dispatch($store, $timeFrom, $timeTo);
            return back()->with('success', "Sinkronisasi pesanan Lazada sedang berjalan di latar belakang.");
        } catch (\Exception $e) {
            Log::error('[Lazada Sync Orders] Gagal memulai sync', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal memulai sinkronisasi pesanan: ' . $e->getMessage());
        }
    }
}
