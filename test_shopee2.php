<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$shopeeService = app(\App\Services\ShopeeService::class);
// Get token from DB
$store = \App\Models\Store::first();
try {
    $path = '/api/v2/product/get_attributes';
    $timestamp = time();
    $sign = $shopeeService->signShopRequest($path, $timestamp, $store->access_token, $store->marketplace_store_id);

    $response = \Illuminate\Support\Facades\Http::get(config('shopee.base_url') . $path, [
        'partner_id' => config('shopee.partner_id'),
        'timestamp' => $timestamp,
        'sign' => $sign,
        'access_token' => $store->access_token,
        'shop_id' => $store->marketplace_store_id,
        'language' => 'id',
        'category_id' => 100013 // Sample cat id
    ]);
    echo "get_attributes: " . $response->body() . "\n";
} catch (\Exception $e) {}

try {
    $path = '/api/v2/product/get_attribute';
    // wait, is it get_attributes or get_attribute ? let's try get_attribute
    $timestamp = time();
    $sign = $shopeeService->signShopRequest($path, $timestamp, $store->access_token, $store->marketplace_store_id);

    $response = \Illuminate\Support\Facades\Http::get(config('shopee.base_url') . $path, [
        'partner_id' => config('shopee.partner_id'),
        'timestamp' => $timestamp,
        'sign' => $sign,
        'access_token' => $store->access_token,
        'shop_id' => $store->marketplace_store_id,
        'language' => 'id',
        'category_id' => 100013 // Sample cat id
    ]);
    echo "get_attribute: " . $response->body() . "\n";
} catch (\Exception $e) {}
