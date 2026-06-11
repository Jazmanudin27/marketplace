<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterProductController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ShopeeController;
use App\Http\Controllers\StoreController;
use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\TiktokController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPrintController;
use App\Http\Controllers\IncomingGoodController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\MarketplaceProductController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReturnOrderController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\FulfillmentController;
use App\Http\Controllers\ProfitController;
use App\Http\Controllers\OfflineSaleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\FundTransferController;
use App\Http\Controllers\FinancialReportController;


// =========================================================================
// Auth Routes (hanya untuk guest)
// =========================================================================
Route::middleware('guest')->group(function () {
    Route::get('/', fn() => redirect()->route('login'));
    Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.post');
});

// =========================================================================
// Webhooks (Tanpa CSRF, harus dikecualikan di VerifyCsrfToken middleware)
// =========================================================================
Route::post('/api/webhooks/shopee', [WebhookController::class, 'shopee'])->name('webhooks.shopee');
Route::post('/api/webhooks/tiktok', [WebhookController::class, 'tiktok'])->name('webhooks.tiktok');

// =========================================================================
// Shopee OAuth Callback
// Shopee OAuth Callback — TIDAK memerlukan auth karena Shopee redirect langsung
// (user sudah login sebelum di-redirect, session masih aktif)
Route::get('/shopee/callback', [ShopeeController::class, 'callback'])->name('shopee.callback');

// =========================================================================
// TikTok OAuth Callback
// =========================================================================
Route::get('/tiktok/auth', [TiktokController::class, 'authorizeTiktok'])->name('tiktok.auth')->middleware('auth');
Route::get('/tiktok/callback', [TiktokController::class, 'callback'])->name('tiktok.callback');
Route::get('/callback/tiktok', [TiktokController::class, 'callback']);

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

    // =========================================================================
    // Master Data & Pengaturan Hak Akses
    // =========================================================================

    // Categories
    Route::middleware('permission:manage-categories')->group(function () {
        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::get('/categories/{category}/edit', [CategoryController::class, 'edit'])->name('categories.edit');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
    });

    // Brands
    Route::middleware('permission:manage-brands')->group(function () {
        Route::get('/brands', [BrandController::class, 'index'])->name('brands.index');
        Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
        Route::put('/brands/{brand}', [BrandController::class, 'update'])->name('brands.update');
        Route::delete('/brands/{brand}', [BrandController::class, 'destroy'])->name('brands.destroy');
    });

    // Suppliers
    Route::middleware('permission:manage-suppliers')->group(function () {
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::get('/suppliers/{supplier}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');
        Route::put('/suppliers/{supplier}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
    });

    // Employees
    Route::middleware('permission:manage-employees')->group(function () {
        Route::resource('employees', EmployeeController::class)->except(['create', 'show', 'edit']);
    });

    // Users & Roles (Hak Akses)
    Route::middleware('permission:manage-users')->group(function () {
        Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);
        Route::resource('roles', RoleController::class)->except(['create', 'show', 'edit']);
    });

    // Customers (Pelanggan)
    Route::middleware('permission:manage-customers')->group(function () {
        Route::resource('customers', CustomerController::class)->only(['index', 'show', 'update']);
    });

    // Stores (Toko)
    Route::middleware('permission:manage-stores')->group(function () {
        Route::get('/stores/create', [StoreController::class, 'create'])->name('stores.create');
        Route::post('/stores', [StoreController::class, 'store'])->name('stores.store');
        Route::get('/stores/{store}/edit', [StoreController::class, 'edit'])->name('stores.edit');
        Route::put('/stores/{store}', [StoreController::class, 'update'])->name('stores.update');
        Route::delete('/stores/{store}', [StoreController::class, 'destroy'])->name('stores.destroy');
        Route::get('/stores', [StoreController::class, 'index'])->name('stores.index');
        Route::get('/shopee/auth', [ShopeeController::class, 'authorize'])->name('shopee.authorize');
        Route::post('/shopee/{store}/sync-products', [ShopeeController::class, 'syncProducts'])->name('shopee.sync_products');
        Route::post('/shopee/{store}/sync-orders', [ShopeeController::class, 'syncOrders'])->name('shopee.sync_orders');

        Route::post('/tiktok/{store}/sync-orders', function (\App\Models\Store $store) {
            if ($store->channel->code !== 'tiktok')
                abort(404);
            $timeFrom = now()->subDays(30)->timestamp;
            $timeTo = now()->timestamp;
            \App\Jobs\PullOrdersFromTiktok::dispatch($store, $timeFrom, $timeTo);
            return back()->with('success', 'Sinkronisasi pesanan TikTok sedang berjalan di latar belakang.');
        })->name('tiktok.sync_orders');

        Route::post('/tiktok/{store}/sync-products', function (\App\Models\Store $store) {
            if ($store->channel->code !== 'tiktok')
                abort(404);
            \App\Jobs\PullProductsFromTiktok::dispatch($store);
            return back()->with('success', 'Sinkronisasi produk TikTok sedang berjalan di latar belakang.');
        })->name('tiktok.sync_products');
    });


    // =========================================================================
    // Produk & Inventory (Warehouse & Admin)
    // =========================================================================

    // Master Produk & Mapping Marketplace
    Route::middleware('permission:manage-products')->group(function () {
        Route::get('/products', [MasterProductController::class, 'index'])->name('products.index');
        Route::get('/products/create', [MasterProductController::class, 'create'])->name('products.create');
        Route::post('/products', [MasterProductController::class, 'store'])->name('products.store');
        Route::get('/products/{product}/edit', [MasterProductController::class, 'edit'])->name('products.edit');
        Route::put('/products/{product}', [MasterProductController::class, 'update'])->name('products.update');
        Route::delete('/products/{product}', [MasterProductController::class, 'destroy'])->name('products.destroy');
        Route::get('/products/{product}/publish', [MasterProductController::class, 'publish'])->name('products.publish');
        Route::post('/products/{product}/publish', [MasterProductController::class, 'storePublish'])->name('products.publish.store');
        Route::get('/api/shopee/categories', [MasterProductController::class, 'shopeeCategories'])->name('shopee.categories');
        Route::get('/api/tiktok/categories', [MasterProductController::class, 'tiktokCategories'])->name('tiktok.categories');
        Route::post('/products/publish/retry/{log}', [MasterProductController::class, 'retryPublish'])->name('products.publish.retry');
        Route::post('/products/mappings/brand', [MasterProductController::class, 'storeBrandMapping'])->name('products.mappings.brand.store');
        Route::delete('/products/mappings/category/{mapping}', [MasterProductController::class, 'destroyCategoryMapping'])->name('products.mappings.category.destroy');
        Route::delete('/products/mappings/brand/{mapping}', [MasterProductController::class, 'destroyBrandMapping'])->name('products.mappings.brand.destroy');

        Route::get('/marketplace-products', [MarketplaceProductController::class, 'index'])->name('marketplace_products.index');
        Route::post('/marketplace-products/{product}/promote', [MarketplaceProductController::class, 'promote'])->name('marketplace_products.promote');
        Route::post('/marketplace-products/{product}/link', [MarketplaceProductController::class, 'link'])->name('marketplace_products.link');
        Route::put('/marketplace-products/{product}/update-settings', [MarketplaceProductController::class, 'updateSettings'])->name('marketplace_products.update_settings');
        Route::post('/marketplace-products/{product}/clone-and-publish', [MarketplaceProductController::class, 'cloneAndPublish'])->name('marketplace_products.clone_and_publish');
    });

    // Orders (Pesanan Masuk)
    Route::middleware('permission:manage-orders')->group(function () {
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::post('/orders/sync', [OrderController::class, 'sync'])->name('orders.sync');
        Route::post('/orders/mass-print', [OrderPrintController::class, 'massPrint'])->name('orders.mass_print');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::post('/orders/{order}/process', [OrderController::class, 'process'])->name('orders.process');
        Route::post('/orders/{order}/ship', [OrderController::class, 'ship'])->name('orders.ship');
        Route::post('/orders/{order}/tracking', [OrderController::class, 'fetchTracking'])->name('orders.tracking');
        Route::get('/orders/{order}/tracking-detail', [OrderController::class, 'trackingDetail'])->name('orders.tracking.detail');
        Route::get('/orders/{order}/print', [OrderController::class, 'print'])->name('orders.print');
    });

    // Fulfillment (Kemas Pesanan)
    Route::middleware('permission:manage-fulfillment')->group(function () {
        Route::get('/fulfillment', [FulfillmentController::class, 'index'])->name('fulfillment.index');
        Route::get('/fulfillment/scan', [FulfillmentController::class, 'scanPage'])->name('fulfillment.scan_page');
        Route::get('/fulfillment/order/{identifier}', [FulfillmentController::class, 'getOrderDetails'])->name('fulfillment.order_details');
        Route::post('/fulfillment/order/{order}/complete', [FulfillmentController::class, 'completePack'])->name('fulfillment.complete_pack');
    });

    // Inbox Chat
    Route::middleware('permission:manage-chats')->group(function () {
        Route::get('/chats', [ChatController::class, 'index'])->name('chats.index');
        Route::get('/chats/{chatConversation}', [ChatController::class, 'show'])->name('chats.show');
        Route::post('/chats/{chatConversation}/reply', [ChatController::class, 'reply'])->name('chats.reply');
        Route::post('/chats/sync', [ChatController::class, 'sync'])->name('chats.sync');
    });

    // Inventory & Stock
    Route::middleware('permission:manage-inventory')->group(function () {
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::get('/incoming-goods', [IncomingGoodController::class, 'index'])->name('incoming_goods.index');
        Route::get('/incoming-goods/create', [IncomingGoodController::class, 'create'])->name('incoming_goods.create');
        Route::post('/incoming-goods', [IncomingGoodController::class, 'store'])->name('incoming_goods.store');
        Route::get('/stock-opnames', [StockOpnameController::class, 'index'])->name('stock_opnames.index');
        Route::get('/stock-opnames/create', [StockOpnameController::class, 'create'])->name('stock_opnames.create');
        Route::post('/stock-opnames', [StockOpnameController::class, 'store'])->name('stock_opnames.store');
        Route::get('/inventory/{product}/ledger', [InventoryController::class, 'ledger'])->name('inventory.ledger');
        Route::post('/inventory/{product}/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    });

    // Pesanan Retur
    Route::middleware('permission:manage-returns')->group(function () {
        Route::get('/returns', [ReturnOrderController::class, 'index'])->name('returns.index');
        Route::post('/returns/sync', [ReturnOrderController::class, 'sync'])->name('returns.sync');
        Route::post('/returns/{returnOrder}/restock', [ReturnOrderController::class, 'restock'])->name('returns.restock');
    });

    // Penjualan Offline (Manual Order)
    Route::middleware('permission:manage-offline-sales')->group(function () {
        Route::get('/offline-sales', [OfflineSaleController::class, 'index'])->name('offline_sales.index');
        Route::get('/offline-sales/create', [OfflineSaleController::class, 'create'])->name('offline_sales.create');
        Route::post('/offline-sales', [OfflineSaleController::class, 'store'])->name('offline_sales.store');
        Route::get('/offline-sales/{offlineSale}', [OfflineSaleController::class, 'show'])->name('offline_sales.show');
        Route::post('/offline-sales/{offlineSale}/complete', [OfflineSaleController::class, 'complete'])->name('offline_sales.complete');
        Route::post('/offline-sales/{offlineSale}/cancel', [OfflineSaleController::class, 'cancel'])->name('offline_sales.cancel');
        Route::get('/offline-sales/{offlineSale}/print', [OfflineSaleController::class, 'printReceipt'])->name('offline_sales.print');
    });

    // =========================================================================
    // Laporan Gudang (Admin & Finance / Warehouse)
    // =========================================================================
    Route::middleware('permission:view-warehouse-reports')->group(function () {
        Route::get('/reports/summary', [ReportController::class, 'summaryReport'])->name('reports.summary');
        Route::get('/reports/summary/print', [ReportController::class, 'printSummaryReport'])->name('reports.summary.print');
        Route::get('/reports/stock', [ReportController::class, 'stockReport'])->name('reports.stock');
        Route::get('/reports/stock/print', [ReportController::class, 'printStockReport'])->name('reports.stock.print');
        Route::get('/reports/ledger', [ReportController::class, 'ledgerReport'])->name('reports.ledger');
        Route::get('/reports/ledger/print', [ReportController::class, 'printLedgerReport'])->name('reports.ledger.print');
        Route::get('/reports/opname', [ReportController::class, 'opnameReport'])->name('reports.opname');
        Route::get('/reports/opname/print', [ReportController::class, 'printOpnameReport'])->name('reports.opname.print');
        Route::get('/reports/analytics', [ReportController::class, 'inventoryAnalytics'])->name('reports.analytics');
    });

    // =========================================================================
    // Keuangan (Finance & Admin)
    // =========================================================================

    // Laporan Profit & Laba Rugi
    Route::middleware('permission:view-financial-reports')->group(function () {
        Route::get('/profit', [ProfitController::class, 'index'])->name('profit.index');
        Route::get('/finance/profit-loss', [FinancialReportController::class, 'profitLoss'])->name('finance.profit_loss');
    });

    // Manajemen Transaksi Keuangan
    Route::middleware('permission:manage-finance')->group(function () {
        Route::get('/reconciliation', [ReconciliationController::class, 'index'])->name('finance.reconciliation');

        // CRUD Incomes (Pemasukan Lain-lain)
        Route::resource('finance/incomes', IncomeController::class)->except(['create', 'show', 'edit'])->names([
            'index' => 'finance.incomes.index',
            'store' => 'finance.incomes.store',
            'update' => 'finance.incomes.update',
            'destroy' => 'finance.incomes.destroy',
        ]);

        // CRUD Expenses (Pengeluaran)
        Route::resource('finance/expenses', ExpenseController::class)->except(['create', 'show', 'edit'])->names([
            'index' => 'finance.expenses.index',
            'store' => 'finance.expenses.store',
            'update' => 'finance.expenses.update',
            'destroy' => 'finance.expenses.destroy',
        ]);

        // CRUD Fund Transfers (Transfer Dana)
        Route::resource('finance/transfers', FundTransferController::class)->except(['create', 'show', 'edit'])->names([
            'index' => 'finance.transfers.index',
            'store' => 'finance.transfers.store',
            'update' => 'finance.transfers.update',
            'destroy' => 'finance.transfers.destroy',
        ]);
    });

    // Route untuk menjalankan migrasi via browser (Bypass permission check, diproteksi role === admin kustom)
    Route::get('/finance/run-migrations', function () {
        if (auth()->user()->role !== 'admin' && !auth()->user()->hasRole('admin')) {
            abort(403, 'Hanya Administrator yang dapat menjalankan migrasi database.');
        }
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return '<pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre><br><a href="' . route('dashboard') . '">Kembali ke Dashboard</a>';
    })->name('finance.run_migrations');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});
