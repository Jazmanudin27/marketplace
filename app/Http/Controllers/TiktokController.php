<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\TiktokService;
use App\Models\Store;
use App\Models\Channel;
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
     * Redirect to TikTok OAuth
     */
    public function authorizeTiktok()
    {
        // Store random state to prevent CSRF, we can encode tenant_id
        $state = base64_encode(json_encode([
            'tenant_id' => Auth::user()->tenant_id,
            'nonce' => Str::random(10)
        ]));

        $url = $this->tiktokService->getAuthUrl($state);
        return redirect()->away($url);
    }

    /**
     * Handle TikTok OAuth Callback
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

            if (!$tenantId) {
                return redirect()->route('stores.index')->with('error', 'Invalid State parameter.');
            }

            // Dapatkan Access Token
            $tokenData = $this->tiktokService->getAccessToken($code);

            $accessToken = $tokenData['access_token'];
            $refreshToken = $tokenData['refresh_token'];
            $openId = $tokenData['open_id'] ?? '';
            // Dapatkan Shop Info
            $shopInfo = $this->tiktokService->getShopInfo($accessToken);
            $shops = $shopInfo['shops'] ?? [];
            if (empty($shops)) {
                return redirect()->route('stores.index')->with('error', 'Tidak ada toko yang ditemukan untuk akun TikTok ini.');
            }

            // Ambil toko pertama (primary)
            $shop = $shops[0];
            $realShopId = $shop['id'];
            $shopName = $shop['name'] ?? 'TikTok Shop ' . substr($openId, 0, 5);
            $shopCipher = $shop['cipher'];

            Channel::ensureChannelsExist();
            $channel = Channel::where('code', 'tiktok')->first();
            if (!$channel) {
                $channel = Channel::create([
                    'code' => 'tiktok',
                    'name' => 'TikTok Shop',
                    'logo_url' => 'https://sf-tb-sg.ibytedtos.com/obj/eden-sg/uhtyvueh7nulogpoguhm/tiktok-icon2.png',
                    'status' => 'active',
                ]);
            }

            // Create or update store
            $store = Store::updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'channel_id' => $channel->id,
                    'marketplace_store_id' => $realShopId,
                ],
                [
                    'store_name' => $shopName,
                    'shop_cipher' => $shopCipher,
                    'access_token' => $accessToken,
                    'refresh_token' => $refreshToken,
                    'token_expires_at' => date('Y-m-d H:i:s', $tokenData['access_token_expire_in'] ?? time()),
                    'status' => 'connected',
                ]
            );

            return redirect()->route('stores.index')->with('success', 'Toko TikTok berhasil dihubungkan!');

        } catch (\Throwable $e) {
            Log::error('[TikTok OAuth] Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return redirect()->route('stores.index')->with('error', 'Gagal menghubungkan TikTok: ' . $e->getMessage());
        }
    }
}
