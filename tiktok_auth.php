<?php

$code = 'ROW_M_6rTgAAAAC6ssh0aCKFuZHi8yOhaLYPWR85W6RH0hw3BLQL4aqzMpBq4hvB-CQirUQTfM6Qc16FyM-BqqpYPFE1PXQoKoBVnUVRFlUHGX2Xw741ss8SO32j8nX2keji--fTL0q8f3q70K4wzX9X8V9Q4Tyfdv1qgWmKNakORcpm8d6EOEzUXrcoaLz6T6kQ7AkwbuDNm5M';
$tenantId = 1;

try {
    $tokenData = app(App\Services\TiktokService::class)->getAccessToken($code);

    $accessToken = $tokenData['access_token'];
    $refreshToken = $tokenData['refresh_token'];
    $openId = $tokenData['open_id'] ?? '';

    $channel = App\Models\Channel::where('code', 'tiktok')->first();
    if (!$channel) {
        $channel = App\Models\Channel::create([
            'code' => 'tiktok',
            'name' => 'TikTok Shop',
            'logo_url' => 'https://sf-tb-sg.ibytedtos.com/obj/eden-sg/uhtyvueh7nulogpoguhm/tiktok-icon2.png',
            'status' => 'active',
        ]);
    }

    $store = App\Models\Store::updateOrCreate(
        [
            'tenant_id' => $tenantId,
            'channel_id' => $channel->id,
            'marketplace_store_id' => $openId ?: 'Tiktok_Shop_' . uniqid(),
        ],
        [
            'store_name' => 'TikTok Shop ' . substr($openId, 0, 5),
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'token_expires_at' => now()->addSeconds($tokenData['access_token_expire_in'] ?? 0),
            'status' => 'connected',
        ]
    );

    echo "Toko berhasil terhubung: " . $store->store_name . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
