<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TiktokService;
use App\Models\Store;
use App\Models\Channel;
use App\Jobs\PullProductsFromTiktok;
use App\Jobs\PullOrdersFromTiktok;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class TiktokController extends Controller
{
    protected $tiktokService;

    public function __construct(TiktokService $tiktokService)
    {
        $this->tiktokService = $tiktokService;
    }

    /**
     * Redirect ke TikTok OAuth
     * Parameter opsional: ?channel=tokopedia untuk menghubungkan sebagai toko Tokopedia
     */
    public function authorizeTiktok(Request $request)
    {
        // 'channel' bisa 'tiktok' atau 'tokopedia'
        $channel = $request->query('channel', 'tiktok');

        // Simpan tenant_id dan channel di state OAuth agar tersedia di callback
        $state = base64_encode(json_encode([
            'tenant_id' => Auth::user()->tenant_id,
            'channel' => $channel,
            'nonce' => Str::random(10),
        ]));

        $url = $this->tiktokService->getAuthUrl($state);

        Log::info('[TikTok OAuth] Redirect ke authorization URL', [
            'tenant_id' => Auth::user()->tenant_id,
            'channel' => $channel,
            'url' => $url,
        ]);

        return redirect()->away($url);
    }

    /**
     * Handle TikTok OAuth Callback
     * Mendukung channel 'tiktok' dan 'tokopedia' dari state
     */
    public function callback(Request $request)
    {
        try {
            $code = $request->query('code');
            $stateRaw = $request->query('state');

            if (!$code) {
                return redirect()->route('stores.index')->with('error', 'Otorisasi TikTok dibatalkan.');
            }

            $state = json_decode(base64_decode($stateRaw), true);
            $tenantId = $state['tenant_id'] ?? null;
            $channel = $state['channel'] ?? 'tiktok'; // 'tiktok' atau 'tokopedia'

            if (!$tenantId) {
                return redirect()->route('stores.index')->with('error', 'Invalid State parameter.');
            }

            // Dapatkan Access Token dari TikTok
            $tokenData = $this->tiktokService->getAccessToken($code);
            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'];
            $openId = $tokenData['open_id'] ?? '';

            // Dapatkan info toko
            $shopInfo = $this->tiktokService->getShopInfo($accessToken);
            $shops = $shopInfo['shops'] ?? [];

            if (empty($shops)) {
                return redirect()->route('stores.index')->with('error', 'Tidak ada toko yang ditemukan untuk akun ini.');
            }

            // Ambil toko pertama (primary)
            $shop = $shops[0];
            $realShopId = $shop['id'];
            $shopName = $shop['name'] ?? ('Toko ' . substr($openId, 0, 5));
            $shopCipher = $shop['cipher'];

            // Pastikan channel ada di database
            Channel::ensureChannelsExist();
            $channelModel = Channel::where('code', $channel)->first();

            if (!$channelModel) {
                // Fallback: buat channel jika belum ada
                $channelName = $channel === 'tokopedia' ? 'Tokopedia' : 'TikTok Shop';
                $channelModel = Channel::create([
                    'code' => $channel,
                    'name' => $channelName,
                    'logo_url' => $channel === 'tokopedia'
                        ? 'https://images.tokopedia.net/img/tokopedia-logo.png'
                        : 'https://sf-tb-sg.ibytedtos.com/obj/eden-sg/uhtyvueh7nulogpoguhm/tiktok-icon2.png',
                    'status' => 'active',
                ]);
            }

            // Buat label nama toko sesuai channel
            if ($channel === 'tokopedia') {
                $storeName = $shopName; // Nama toko dari TikTok = nama Tokopedia (sudah merged)
            } else {
                $storeName = $shopName;
            }

            // Simpan atau update store
            $store = Store::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'channel_id' => $channelModel->id,
                    'marketplace_store_id' => $realShopId,
                ],
                [
                    'store_name' => $storeName,
                    'shop_cipher' => $shopCipher,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => date('Y-m-d H:i:s', $tokenData['access_token_expire_in'] ?? (time() + 86400)),
                    'status' => 'connected',
                ]
            );

            Log::info('[TikTok OAuth] Toko berhasil dihubungkan', [
                'store_id' => $store->id,
                'shop_id' => $realShopId,
                'store_name' => $storeName,
                'channel' => $channel,
                'tenant_id' => $tenantId,
            ]);

            $platformLabel = $channel === 'tokopedia' ? 'Tokopedia' : 'TikTok Shop';
            return redirect()->route('stores.index')
                ->with('success', "✅ Toko {$platformLabel} \"{$storeName}\" berhasil dihubungkan!");

        } catch (\Throwable $e) {
            Log::error('[TikTok OAuth] Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('stores.index')
                ->with('error', 'Gagal menghubungkan toko: ' . $e->getMessage());
        }
    }

    public function syncProducts(Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless(in_array($store->channel->code, ['tiktok', 'tokopedia']), 400, 'Bukan toko TikTok/Tokopedia.');
        abort_if($store->status !== 'connected', 400, 'Toko belum terhubung.');

        try {
            PullProductsFromTiktok::dispatch($store);

            $platform = $store->channel->code === 'tokopedia' ? 'Tokopedia' : 'TikTok Shop';
            return back()->with('success', "Sinkronisasi produk {$platform} sedang berjalan di latar belakang.");
        } catch (\Exception $e) {
            Log::error('[TikTok Sync Products] Gagal memulai sync', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal memulai sinkronisasi produk: ' . $e->getMessage());
        }
    }

    /**
     * Sinkronisasi pesanan via TikTok Shop API
     * Mendukung channel 'tiktok' dan 'tokopedia'
     */
    public function syncOrders(Store $store)
    {
        abort_unless($store->tenant_id === Auth::user()->tenant_id, 403);
        abort_unless(in_array($store->channel->code, ['tiktok', 'tokopedia']), 400, 'Bukan toko TikTok/Tokopedia.');
        abort_if($store->status !== 'connected', 400, 'Toko belum terhubung.');

        try {
            $timeFrom = now()->subDays(15)->timestamp;
            $timeTo = now()->timestamp;

            PullOrdersFromTiktok::dispatch($store, $timeFrom, $timeTo);

            $platform = $store->channel->code === 'tokopedia' ? 'Tokopedia' : 'TikTok Shop';
            return back()->with('success', "Sinkronisasi pesanan {$platform} sedang berjalan di latar belakang.");
        } catch (\Exception $e) {
            Log::error('[TikTok Sync Orders] Gagal memulai sync', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);
            return back()->with('error', 'Gagal memulai sinkronisasi pesanan: ' . $e->getMessage());
        }
    }
}
