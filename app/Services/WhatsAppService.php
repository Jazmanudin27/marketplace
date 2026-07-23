<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{

    public static function send(
        string $to,
        ?string $message = null,
        ?string $mediaUrl = null,
        ?string $filename = null,
        ?string $session = null
    ) {
        $gatewayUrl = env('WHATSAPP_GATEWAY_URL', 'https://wa.aspartech.com/api/send-message');

        // Ambil API Key dari file .env Laravel Anda
        $apiKey = env('WHATSAPP_API_KEY', 'my_secure_super_secret_key_123');

        try {
            $payload = [
                'to' => $to,
                'message' => $message,
                'mediaUrl' => $mediaUrl,
                'filename' => $filename,
                'session' => $session,
            ];

            // Hapus parameter null agar request lebih bersih
            $payload = array_filter($payload, fn($value) => !is_null($value));

            $response = Http::timeout(3)->withHeaders([
                'x-api-key' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post($gatewayUrl, $payload);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('WA Gateway Error: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('WA Gateway Connection Failed: ' . $e->getMessage());
            return false;
        }
    }
}
