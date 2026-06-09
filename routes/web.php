<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterProductController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ShopeeController;
use App\Http\Controllers\StoreController;
use Illuminate\Support\Facades\Route;

// =========================================================================
// Auth Routes (hanya untuk guest)
// =========================================================================
Route::middleware('guest')->group(function () {
    Route::get('/', fn() => redirect()->route('login'));
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

// =========================================================================
// Shopee OAuth Callback
// Shopee OAuth Callback — TIDAK memerlukan auth karena Shopee redirect langsung
// (user sudah login sebelum di-redirect, session masih aktif)
Route::get('/shopee/callback', [ShopeeController::class, 'callback'])->name('shopee.callback');

// DEBUG — Verifikasi sign (hapus sebelum production!)
Route::get('/shopee/debug-sign', function () {
    $shopee = app(\App\Services\ShopeeService::class);
    return response()->json([
        'auth_partner' => $shopee->debugSign('/api/v2/shop/auth_partner'),
        'token_get' => $shopee->debugSign('/api/v2/auth/token/get'),
        'auth_url' => $shopee->getAuthorizationUrl(),
    ]);
})->middleware('auth');


// =========================================================================
// Routes yang memerlukan login
// =========================================================================
Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::resource('categories', App\Http\Controllers\CategoryController::class);
    Route::resource('brands', App\Http\Controllers\BrandController::class);

    // Marketplace Integration Routes
    Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
    
    // Orders Routes
    Route::get('/orders', [App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::post('/orders/sync', [App\Http\Controllers\OrderController::class, 'sync'])->name('orders.sync');
    Route::get('/orders/{order}', [App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order}/process', [App\Http\Controllers\OrderController::class, 'process'])->name('orders.process');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Shopee OAuth – authorize & refresh token
    Route::get('/shopee/authorize', [ShopeeController::class, 'authorize'])->name('shopee.authorize');
    Route::post('/shopee/refresh/{store}', [ShopeeController::class, 'refreshToken'])->name('shopee.refresh');
    Route::post('/shopee/sync-products/{store}', [ShopeeController::class, 'syncProducts'])->name('shopee.sync_products');
    Route::post('/shopee/sync-orders/{store}', [ShopeeController::class, 'syncOrders'])->name('shopee.sync_orders');


    // Manajemen Toko
    Route::resource('stores', StoreController::class);

    // Master Produk
    Route::resource('products', MasterProductController::class);

    // Produk Marketplace (Mapping)
    Route::get('/marketplace-products', [\App\Http\Controllers\MarketplaceProductController::class, 'index'])->name('marketplace_products.index');
    Route::post('/marketplace-products/{product}/promote', [\App\Http\Controllers\MarketplaceProductController::class, 'promote'])->name('marketplace_products.promote');
    Route::post('/marketplace-products/{product}/link', [\App\Http\Controllers\MarketplaceProductController::class, 'link'])->name('marketplace_products.link');


    // Pesanan
    Route::resource('orders', OrderController::class)->only(['index', 'show']);
});
