<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shopee Open Platform Configuration
    |--------------------------------------------------------------------------
    |
    | Konfigurasi untuk integrasi dengan Shopee Open Platform API.
    | Gunakan environment TEST untuk pengembangan.
    |
    */

    'partner_id'   => env('SHOPEE_PARTNER_ID'),
    'partner_key'  => env('SHOPEE_PARTNER_KEY'),
    'base_url'     => env('SHOPEE_BASE_URL', 'https://partner.test-stable.shopeemobile.com'),
    'redirect_url' => env('SHOPEE_REDIRECT_URL'),

];
