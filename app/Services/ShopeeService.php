<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopeeService
{
    private int $partnerId;
    private string $partnerKey;
    private string $baseUrl;
    private string $redirectUrl;

    public function __construct()
    {
        $this->partnerId = (int) config('shopee.partner_id');
        $this->partnerKey = config('shopee.partner_key');
        $this->baseUrl = rtrim(config('shopee.base_url'), '/');
        $this->redirectUrl = config('shopee.redirect_url');
    }
    public function getAuthorizationUrl(): string
    {
        $path = '/api/v2/shop/auth_partner';
        $timestamp = time();
        $sign = $this->signBaseRequest($path, $timestamp);

        $params = http_build_query([
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'redirect' => $this->redirectUrl,
        ]);

        $url = $this->baseUrl . $path . '?' . $params;

        Log::info('[Shopee] Authorization URL generated', [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'path' => $path,
            'base_string' => $this->partnerId . $path . $timestamp,
            'sign' => $sign,
            'url' => $url,
        ]);

        return $url;
    }

    public function getAccessToken(string $code, int $shopId): array
    {
        $path = '/api/v2/auth/token/get';
        $timestamp = time();

        // ✅ SIGN HARUS BASIC SAJA
        $sign = $this->signBaseRequest($path, $timestamp);

        $url = $this->baseUrl . $path . '?' . http_build_query([
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
        ]);

        $response = Http::asJson()->post($url, [
            'code' => $code,
            'shop_id' => $shopId,
            'partner_id' => $this->partnerId,
        ]);

        return $response->json();
    }

    public function refreshAccessToken(string $refreshToken, int $shopId): array
    {
        $path = '/api/v2/auth/access_token/get';
        $timestamp = time();
        $sign = $this->signBaseRequest($path, $timestamp);

        $queryString = http_build_query([
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
        ]);

        $url = $this->baseUrl . $path . '?' . $queryString;

        $response = Http::asJson()->post($url, [
            'refresh_token' => $refreshToken,
            'shop_id' => $shopId,
            'partner_id' => $this->partnerId,
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal refresh token Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            throw new \RuntimeException(
                'Shopee refresh token error [' . $data['error'] . ']: ' . ($data['message'] ?? '')
            );
        }

        return $data;
    }

    public function getShopInfo(string $accessToken, int $shopId): array
    {
        $path = '/api/v2/shop/get_shop_info';
        $timestamp = time();
        $sign = $this->signShopRequest($path, $timestamp, $accessToken, $shopId);

        $response = Http::get($this->baseUrl . $path, [
            'partner_id' => $this->partnerId,
            'timestamp' => $timestamp,
            'sign' => $sign,
            'access_token' => $accessToken,
            'shop_id' => $shopId,
        ]);

        Log::info('[Shopee] getShopInfo response', [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal ambil info toko Shopee: ' . $response->body());
        }

        $data = $response->json();

        if (!empty($data['error']) && $data['error'] !== '') {
            // Jika gagal ambil info toko, tidak perlu throw — kembalikan array kosong
            Log::warning('[Shopee] getShopInfo error (non-fatal)', ['data' => $data]);
            return [];
        }

        return $data['response'] ?? [];
    }


    public function debugSign(string $path): array
    {
        $timestamp = time();
        $sign = $this->signBaseRequest($path, $timestamp);
        $baseString = $this->partnerId . $path . $timestamp;

        return [
            'partner_id' => $this->partnerId,
            'path' => $path,
            'timestamp' => $timestamp,
            'base_string' => $baseString,
            'sign' => $sign,
        ];
    }
    private function signTokenRequest(string $path, int $timestamp, string $code, int $shopId): string
    {
        $base = $this->partnerId . $path . $timestamp . $code . $shopId;
        return hash_hmac('sha256', $base, $this->partnerKey);
    }
    private function signBaseRequest(string $path, int $timestamp): string
    {
        $base = $this->partnerId . $path . $timestamp;
        return hash_hmac('sha256', $base, $this->partnerKey);
    }

    private function signShopRequest(string $path, int $timestamp, string $accessToken, int $shopId): string
    {
        $base = $this->partnerId . $path . $timestamp . $accessToken . $shopId;
        return hash_hmac('sha256', $base, $this->partnerKey);
    }
}
