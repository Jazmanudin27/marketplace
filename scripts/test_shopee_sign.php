<?php
/**
 * Test Shopee HMAC-SHA256 Sign
 * Jalankan: php test_shopee_sign.php
 */

$partnerId  = 1235283;
$partnerKey = 'shpk63674f556f474374636c63496f4e7176706a577468616e61494946685741';
$path       = '/api/v2/shop/auth_partner';
$timestamp  = time();

// Base string: partner_id + path + timestamp
$baseString = $partnerId . $path . $timestamp;
$sign       = hash_hmac('sha256', $baseString, $partnerKey);

echo "=== Shopee Sign Debug ===\n";
echo "Partner ID   : {$partnerId}\n";
echo "Path         : {$path}\n";
echo "Timestamp    : {$timestamp}\n";
echo "Base String  : {$baseString}\n";
echo "Sign         : {$sign}\n\n";

// Build auth URL
$params = http_build_query([
    'partner_id' => $partnerId,
    'timestamp'  => $timestamp,
    'sign'       => $sign,
    'redirect'   => 'http://127.0.0.1:8001/shopee/callback',
]);

$authUrl = 'https://partner.test-stable.shopeemobile.com/api/v2/shop/auth_partner?' . $params;
echo "Auth URL:\n{$authUrl}\n\n";

// Test token/get sign
$path2       = '/api/v2/auth/token/get';
$timestamp2  = time();
$base2       = $partnerId . $path2 . $timestamp2;
$sign2       = hash_hmac('sha256', $base2, $partnerKey);

echo "=== Token/Get Sign ===\n";
echo "Path         : {$path2}\n";
echo "Base String  : {$base2}\n";
echo "Sign         : {$sign2}\n";
