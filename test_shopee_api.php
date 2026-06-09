<?php
/**
 * Test Shopee API - Detailed debugging
 * Mencoba berbagai format request untuk menemukan yang benar
 */

$partnerId  = 1235283;
$partnerKey = 'shpk63674f556f474374636c63496f4e7176706a577468616e61494946685741';

echo "=== Shopee Sign Test - Detailed ===\n\n";
echo "Partner ID  : {$partnerId}\n";
echo "Partner Key : " . substr($partnerKey, 0, 10) . "...\n\n";

// Test 1: auth_partner sign (untuk validasi URL)
$path      = '/api/v2/shop/auth_partner';
$timestamp = time();
$base      = $partnerId . $path . $timestamp;
$sign      = hash_hmac('sha256', $base, $partnerKey);

echo "--- Auth Partner URL ---\n";
echo "Base String : {$base}\n";
echo "Sign        : {$sign}\n";
$authUrl = 'https://partner.test-stable.shopeemobile.com/api/v2/shop/auth_partner'
         . '?partner_id=' . $partnerId
         . '&timestamp='  . $timestamp
         . '&sign='       . $sign
         . '&redirect='   . urlencode('http://127.0.0.1:8001/shopee/callback');
echo "Full URL    : {$authUrl}\n\n";

// Test 2: Coba GET ke auth_partner URL untuk lihat apakah sign diterima
echo "--- Testing auth_partner sign via HTTP GET ---\n";
$ch = curl_init($authUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false, // Jangan follow redirect, kita mau lihat response awal
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HEADER         => true,  // Include headers
    CURLOPT_NOBODY         => false,
    CURLOPT_USERAGENT      => 'Mozilla/5.0 ERP-Test/1.0',
]);

$response = curl_exec($ch);
$httpCode  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers   = substr($response, 0, $headerSize);
$body      = substr($response, $headerSize);
curl_close($ch);

echo "HTTP Status : {$httpCode}\n";
echo "Headers     :\n{$headers}\n";
echo "Body (first 300 chars): " . substr($body, 0, 300) . "\n\n";

// Jika 302 redirect ke Shopee login = sign BENAR
// Jika 200 dan ada JSON error = sign SALAH
if ($httpCode === 302 || $httpCode === 301) {
    // Ambil Location header
    preg_match('/Location:\s*(.+)\r?\n/i', $headers, $m);
    echo "✅ REDIRECT! Sign benar. Location: " . ($m[1] ?? 'N/A') . "\n";
} elseif (str_contains($body, 'error_sign') || str_contains($body, 'Wrong sign')) {
    echo "❌ Sign masih salah (error_sign)\n";
} elseif (str_contains($body, 'login') || str_contains($body, 'Shopee')) {
    echo "✅ Redirect ke halaman Shopee — Sign benar!\n";
} else {
    echo "⚠️  Response tidak dikenali, periksa body di atas.\n";
}
