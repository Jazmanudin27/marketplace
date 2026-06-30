<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\AdsAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TiktokAdsAuthController extends Controller
{
    private $appId;
    private $secret;
    private $redirectUri;
    private $apiBaseUrl;

    public function __construct()
    {
        $this->appId = config('services.tiktok_ads.app_id');
        $this->secret = config('services.tiktok_ads.secret');
        $this->redirectUri = config('services.tiktok_ads.redirect_uri');
        $isSandbox = config('services.tiktok_ads.sandbox', false);
        $this->apiBaseUrl = $isSandbox ? 'https://sandbox-ads.tiktok.com/open_api/v1.3/' : 'https://business-api.tiktok.com/open_api/v1.3/';
    }

    /**
     * Meredirect user ke halaman login/otorisasi TikTok Ads
     */
    public function connect()
    {
        if (empty($this->appId) || empty($this->secret) || empty($this->redirectUri)) {
            return redirect()->route('marketing.ads.campaigns')
                ->with('error', 'Kredensial TikTok Ads belum dikonfigurasi di file .env server Anda.');
        }

        $state = csrf_token();
        $authUrl = "https://business-api.tiktok.com/portal/auth?" . http_build_query([
            'app_id' => $this->appId,
            'state' => $state,
            'redirect_uri' => $this->redirectUri,
        ]);

        return redirect()->away($authUrl);
    }

    /**
     * Menangani callback dari TikTok setelah otorisasi disetujui
     */
    public function callback(Request $request)
    {
        $authCode = $request->query('auth_code');

        if (empty($authCode)) {
            return redirect()->route('marketing.ads.campaigns')
                ->with('error', 'Otorisasi TikTok Ads dibatalkan oleh pengguna.');
        }

        try {
            // 1. Tukar auth_code dengan access_token
            $tokenUrl = $this->apiBaseUrl . "oauth2/access_token/";
            $response = Http::timeout(30)->post($tokenUrl, [
                'app_id' => $this->appId,
                'secret' => $this->secret,
                'auth_code' => $authCode,
            ]);

            if ($response->failed()) {
                Log::error('[TikTok Ads Auth] Exchange token failed', ['status' => $response->status(), 'body' => $response->body()]);
                return redirect()->route('marketing.ads.campaigns')
                    ->with('error', 'Gagal menukarkan token dengan TikTok: ' . $response->body());
            }

            $data = $response->json();
            if (($data['code'] ?? 0) !== 0) {
                Log::error('[TikTok Ads Auth] Exchange token API error', ['data' => $data]);
                return redirect()->route('marketing.ads.campaigns')
                    ->with('error', 'TikTok API Error: ' . ($data['message'] ?? 'Unknown Error'));
            }

            $accessToken = $data['data']['access_token'] ?? null;
            if (empty($accessToken)) {
                return redirect()->route('marketing.ads.campaigns')
                    ->with('error', 'Access token tidak ditemukan dalam respons TikTok.');
            }

            // 2. Tarik daftar Advertiser Accounts menggunakan access_token
            $advUrl = $this->apiBaseUrl . "oauth2/advertiser/get/";
            $advResponse = Http::timeout(30)
                ->withHeaders(['Access-Token' => $accessToken])
                ->get($advUrl, [
                    'app_id' => $this->appId,
                    'secret' => $this->secret,
                ]);

            if ($advResponse->failed()) {
                Log::error('[TikTok Ads Auth] Fetch advertisers failed', ['status' => $advResponse->status(), 'body' => $advResponse->body()]);
                return redirect()->route('marketing.ads.campaigns')
                    ->with('error', 'Gagal mengambil daftar Akun Iklan TikTok.');
            }

            $advData = $advResponse->json();
            if (($advData['code'] ?? 0) !== 0) {
                Log::error('[TikTok Ads Auth] Fetch advertisers API error', ['data' => $advData]);
                return redirect()->route('marketing.ads.campaigns')
                    ->with('error', 'TikTok API Error: ' . ($advData['message'] ?? 'Unknown Error'));
            }

            $advertisers = $advData['data']['list'] ?? [];

            if (empty($advertisers)) {
                return redirect()->route('marketing.ads.campaigns')
                    ->with('error', 'Tidak ada Advertiser Account (Akun Iklan) yang terasosiasi dengan akun TikTok Anda.');
            }

            // Tampilkan halaman seleksi akun
            return view('marketing.ads.select_advertiser', [
                'advertisers' => $advertisers,
                'accessToken' => $accessToken,
            ]);

        } catch (\Exception $e) {
            Log::error('[TikTok Ads Auth] Exception', ['message' => $e->getMessage()]);
            return redirect()->route('marketing.ads.campaigns')
                ->with('error', 'Terjadi kesalahan sistem saat otentikasi TikTok: ' . $e->getMessage());
        }
    }

    /**
     * Menyimpan Advertiser Account yang dipilih oleh user ke database
     */
    public function selectAccount(Request $request)
    {
        $request->validate([
            'advertiser_id' => 'required|string',
            'advertiser_name' => 'required|string',
            'access_token' => 'required|string',
        ]);

        $tenantId = Auth::user()->tenant_id;

        AdsAccount::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'platform' => 'tiktok',
                'account_id' => $request->advertiser_id,
            ],
            [
                'account_name' => $request->advertiser_name,
                'access_token' => $request->access_token,
                'is_active' => true,
            ]
        );

        return redirect()->route('marketing.ads.campaigns')
            ->with('success', 'Akun Iklan TikTok "' . $request->advertiser_name . '" berhasil ditautkan!');
    }
}
