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

    'partner_id' => trim(env('SHOPEE_PARTNER_ID', '')),
    'partner_key' => trim(env('SHOPEE_PARTNER_KEY', '')),
    'base_url' => trim(env('SHOPEE_BASE_URL', 'https://partner.test-stable.shopeemobile.com')),
    'redirect_url' => trim(env('SHOPEE_REDIRECT_URL', '')),

];
