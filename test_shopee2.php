<?php
require 'vendor/autoload.php';

$partnerId = 1235283;
$partnerKey = '63674f556f474374636c63496f4e7176706a577468616e61494946685741';
$baseUrl = 'https://partner.test-stable.shopeemobile.com';
$path = '/api/v2/auth/token/get';
$timestamp = time();

$base = $partnerId . $path . $timestamp;
$sign = hash_hmac('sha256', $base, $partnerKey);

$queryString = http_build_query([
    'partner_id' => $partnerId,
    'timestamp' => $timestamp,
    'sign' => $sign,
]);
$url = $baseUrl . $path . '?' . $queryString;

$client = new GuzzleHttp\Client();
try {
    $response = $client->post($url, [
        'json' => [
            'code' => 'dummy_code',
            'shop_id' => 123,
            'partner_id' => $partnerId,
        ]
    ]);
    echo $response->getBody();
} catch (\GuzzleHttp\Exception\ClientException $e) {
    echo $e->getResponse()->getBody();
} catch (\Exception $e) {
    echo $e->getMessage();
}
