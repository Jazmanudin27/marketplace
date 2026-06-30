<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterProductController;
use App\Http\Controllers\MarketplaceProductController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\ReturnOrderController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReconciliationController;
use App\Http\Controllers\FulfillmentController;
use App\Http\Controllers\ProfitController;
use App\Http\Controllers\OfflineSaleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\IncomeController;
use App\Http\Controllers\FundTransferController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\OrderPrintController;
use App\Http\Controllers\ShopeeController;
use App\Http\Controllers\TiktokController;
use App\Http\Controllers\LazadaController;
use App\Http\Controllers\Hrd\PayrollController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Employee\EmployeeAuthController;
use App\Http\Controllers\Employee\EmployeeAttendanceController;
// Master
use App\Http\Controllers\Master\BrandController;
use App\Http\Controllers\Master\CategoryController;
use App\Http\Controllers\Master\SupplierController;
use App\Http\Controllers\Master\CustomerController;
// HRD
use App\Http\Controllers\Hrd\EmployeeController;
use App\Http\Controllers\Hrd\AttendanceController;
use App\Http\Controllers\Hrd\HolidayController;
use App\Http\Controllers\Hrd\OvertimeController;
use App\Http\Controllers\Hrd\CashAdvanceController;
use App\Http\Controllers\Hrd\AllowanceTypeController;
use App\Http\Controllers\Hrd\LatePenaltyRuleController;
use App\Http\Controllers\Hrd\LeaveRequestController;
// Inventory
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\Inventory\IncomingGoodController;
use App\Http\Controllers\Inventory\StockOpnameController;
// Marketplace
use App\Http\Controllers\Marketplace\StoreController;
// Settings
use App\Http\Controllers\Settings\UserController;
use App\Http\Controllers\Settings\RoleController;
use App\Http\Controllers\Marketing\AdsController;


// =========================================================================
// Employee Self-Service (Presensi Mandiri Karyawan)
// =========================================================================

// Guest: belum login sebagai karyawan
Route::prefix('employee')->name('employee.')->group(function () {
    Route::middleware('guest:employee')->group(function () {
        Route::get('/login', [EmployeeAuthController::class, 'showLogin'])->name('login');
        Route::post('/login', [EmployeeAuthController::class, 'login'])->name('login.post');
    });

    // Protected: sudah login sebagai karyawan
    Route::middleware('employee.auth')->group(function () {
        Route::post('/logout', [EmployeeAuthController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [EmployeeAttendanceController::class, 'dashboard'])->name('dashboard');
        Route::post('/clock-in', [EmployeeAttendanceController::class, 'clockIn'])->name('clock-in');
        Route::post('/clock-out', [EmployeeAttendanceController::class, 'clockOut'])->name('clock-out');
        Route::post('/leaves', [EmployeeAttendanceController::class, 'storeLeave'])->name('leaves.store');
    });
});

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

// =========================================================================
// Lazada OAuth Callback
// =========================================================================
Route::get('/lazada/callback', [LazadaController::class, 'callback'])->name('lazada.callback');


// =========================================================================
// Mobile Complaint Form (Publik — tanpa login, identifikasi via tenant_id)
// =========================================================================
Route::get('/m/complaint/{tenant_id}', [ComplaintController::class, 'mobileCreate'])->name('complaints.mobile.create');
Route::post('/m/complaint/{tenant_id}', [ComplaintController::class, 'mobileStore'])->name('complaints.mobile.store');
Route::get('/m/complaint/{tenant_id}/success', [ComplaintController::class, 'mobileSuccess'])->name('complaints.mobile.success');

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
        Route::get('/brands/create', [BrandController::class, 'create'])->name('brands.create');
        Route::post('/brands', [BrandController::class, 'store'])->name('brands.store');
        Route::get('/brands/{brand}/edit', [BrandController::class, 'edit'])->name('brands.edit');
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

    // Customers
    Route::middleware('permission:manage-customers')->group(function () {
        Route::resource('customers', CustomerController::class)->only(['index', 'show', 'update']);
    });

    // Employees
    Route::middleware('permission:manage-employees')->group(function () {
        Route::resource('employees', EmployeeController::class)->except(['show']);
        Route::put('employees/{employee}/salary', [EmployeeController::class, 'updateSalary'])->name('employees.salary.update');
        Route::put('employees/{employee}/credentials', [EmployeeController::class, 'updateCredentials'])->name('employees.credentials.update');

        // Kepegawaian & HRD
        Route::prefix('hr')->name('hr.')->group(function () {
            // Attendance
            Route::middleware('permission:view-attendance')->group(function () {
                Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
                Route::get('attendance/report', [AttendanceController::class, 'report'])->name('attendance.report');
            });

            Route::post('attendance/corrections', [AttendanceController::class, 'storeCorrection'])
                ->name('attendance.corrections.store')
                ->middleware('permission:propose-attendance-correction');

            Route::post('attendance/corrections/{correction}/approve', [AttendanceController::class, 'approveCorrection'])
                ->name('attendance.corrections.approve')
                ->middleware('permission:approve-attendance-correction');

            Route::post('attendance/corrections/{correction}/reject', [AttendanceController::class, 'rejectCorrection'])
                ->name('attendance.corrections.reject')
                ->middleware('permission:approve-attendance-correction');

            Route::middleware('permission:propose-attendance-correction')->group(function () {
                Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
                Route::put('attendance/{attendance}', [AttendanceController::class, 'update'])->name('attendance.update');
                Route::delete('attendance/{attendance}', [AttendanceController::class, 'destroy'])->name('attendance.destroy');
            });

            // Holidays
            Route::resource('holidays', HolidayController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::post('holidays/{holiday}/employees', [HolidayController::class, 'updateEmployees'])->name('holidays.employees.update');

            // Overtime
            Route::resource('overtime', OvertimeController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::post('overtime/{overtime}/approve', [OvertimeController::class, 'approve'])->name('overtime.approve');
            Route::post('overtime/{overtime}/reject', [OvertimeController::class, 'reject'])->name('overtime.reject');

            // Cash Advance (Kasbon)
            Route::resource('cash-advances', CashAdvanceController::class)->only(['index', 'store', 'update', 'destroy']);
            Route::post('cash-advances/{cashAdvance}/approve', [CashAdvanceController::class, 'approve'])->name('cash-advances.approve');
            Route::post('cash-advances/{cashAdvance}/reject', [CashAdvanceController::class, 'reject'])->name('cash-advances.reject');

            // Allowance Types (Master Tunjangan)
            Route::resource('allowance-types', AllowanceTypeController::class)->except(['create', 'show', 'edit']);

            // Late Penalties (Denda Keterlambatan)
            Route::resource('late-penalties', LatePenaltyRuleController::class)->except(['create', 'show', 'edit']);

            // Leave Requests (Pengajuan Izin, Cuti, Sakit)
            Route::resource('leaves', LeaveRequestController::class)->except(['create', 'show', 'edit']);
            Route::post('leaves/{leave}/approve', [LeaveRequestController::class, 'approve'])->name('leaves.approve');
            Route::post('leaves/{leave}/reject', [LeaveRequestController::class, 'reject'])->name('leaves.reject');

            // Payroll
            Route::get('payroll', [PayrollController::class, 'index'])->name('payroll.index');
            Route::post('payroll/generate', [PayrollController::class, 'generate'])->name('payroll.generate');
            Route::get('payroll/{payroll}', [PayrollController::class, 'show'])->name('payroll.show');
            Route::put('payroll/{payroll}', [PayrollController::class, 'update'])->name('payroll.update');
            Route::post('payroll/{payroll}/pay', [PayrollController::class, 'pay'])->name('payroll.pay');
            Route::delete('payroll/{payroll}', [PayrollController::class, 'destroy'])->name('payroll.destroy');
        });
    });

    // Users & Roles (Hak Akses)
    Route::middleware('permission:manage-users')->group(function () {
        Route::get('users/{user}/permissions', [UserController::class, 'editPermissions'])->name('users.permissions.edit');
        Route::put('users/{user}/permissions', [UserController::class, 'updatePermissions'])->name('users.permissions.update');
        Route::resource('users', UserController::class)->except(['create', 'show', 'edit']);
        Route::resource('roles', RoleController::class)->except(['create', 'show', 'edit']);
    });

    // Pengaturan Perusahaan (Tenant Settings)
    Route::middleware('permission:settings.tenant.edit')->group(function () {
        Route::get('/settings/tenant', [\App\Http\Controllers\Settings\TenantSettingsController::class, 'edit'])->name('settings.tenant.edit');
        Route::put('/settings/tenant', [\App\Http\Controllers\Settings\TenantSettingsController::class, 'update'])->name('settings.tenant.update');
    });

    // Vouchers (Voucher POS)
    Route::middleware('permission:vouchers.index')->group(function () {
        Route::get('/vouchers', [\App\Http\Controllers\VoucherController::class, 'index'])->name('vouchers.index');
    });
    Route::middleware('permission:vouchers.create')->group(function () {
        Route::get('/vouchers/create', [\App\Http\Controllers\VoucherController::class, 'create'])->name('vouchers.create');
        Route::post('/vouchers', [\App\Http\Controllers\VoucherController::class, 'store'])->name('vouchers.store');
    });
    Route::middleware('permission:vouchers.edit')->group(function () {
        Route::get('/vouchers/{voucher}/edit', [\App\Http\Controllers\VoucherController::class, 'edit'])->name('vouchers.edit');
        Route::put('/vouchers/{voucher}', [\App\Http\Controllers\VoucherController::class, 'update'])->name('vouchers.update');
        Route::post('/vouchers/{voucher}/sync-shopee', [\App\Http\Controllers\VoucherController::class, 'syncToShopee'])->name('vouchers.sync_shopee');
        Route::post('/vouchers/{voucher}/end-shopee', [\App\Http\Controllers\VoucherController::class, 'endOnShopee'])->name('vouchers.end_shopee');
    });
    Route::middleware('permission:vouchers.destroy')->group(function () {
        Route::delete('/vouchers/{voucher}', [\App\Http\Controllers\VoucherController::class, 'destroy'])->name('vouchers.destroy');
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

        // TikTok Shop sync routes (via controller)
        Route::post('/tiktok/{store}/sync-orders', [TiktokController::class, 'syncOrders'])->name('tiktok.sync_orders');
        Route::post('/tiktok/{store}/sync-products', [TiktokController::class, 'syncProducts'])->name('tiktok.sync_products');

        // Tokopedia → Menggunakan TikTok Shop OAuth (platform sudah merger)
        // 'tokopedia.connect' di-redirect ke TikTok Auth dengan channel=tokopedia
        Route::get('/tokopedia/connect', fn() => redirect()->route('tiktok.auth', ['channel' => 'tokopedia']))
            ->name('tokopedia.connect');
        Route::post('/tokopedia/{store}/sync-products', [TiktokController::class, 'syncProducts'])->name('tokopedia.sync_products');
        Route::post('/tokopedia/{store}/sync-orders', [TiktokController::class, 'syncOrders'])->name('tokopedia.sync_orders');

        // Lazada
        Route::get('/lazada/auth', [LazadaController::class, 'authorizeLazada'])->name('lazada.authorize');
        Route::post('/lazada/{store}/sync-products', [LazadaController::class, 'syncProducts'])->name('lazada.sync_products');
        Route::post('/lazada/{store}/sync-orders', [LazadaController::class, 'syncOrders'])->name('lazada.sync_orders');
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
        Route::post('/marketplace-products/{product}/unlink', [MarketplaceProductController::class, 'unlink'])->name('marketplace_products.unlink');
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

    // Pengaduan Barang Rusak
    Route::middleware('permission:manage-complaints')->group(function () {
        Route::resource('complaints', ComplaintController::class);
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

    // Route untuk menjalankan migrasi via browser (Bypass permission check, diproteksi role === admin/owner kustom)
    Route::get('/finance/run-migrations', function () {
        if (!in_array(auth()->user()->role, ['admin', 'owner']) && !auth()->user()->hasAnyRole(['admin', 'owner'])) {
            abort(403, 'Hanya Administrator atau Owner yang dapat menjalankan migrasi database.');
        }
        \Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
        return '<pre>' . \Illuminate\Support\Facades\Artisan::output() . '</pre><br><a href="' . route('dashboard') . '">Kembali ke Dashboard</a>';
    })->name('finance.run_migrations');

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/chart-data', [DashboardController::class, 'getChartData'])->name('dashboard.chart-data');

    // Marketing & Ads Tracking
    Route::middleware('permission:view-warehouse-reports|manage-finance')->group(function () {
        Route::get('/marketing/ads', [AdsController::class, 'index'])->name('marketing.ads.index');
        Route::get('/marketing/ads/campaigns', [AdsController::class, 'campaigns'])->name('marketing.ads.campaigns');
        Route::post('/marketing/ads/campaigns', [AdsController::class, 'storeCampaign'])->name('marketing.ads.campaigns.store');
        Route::put('/marketing/ads/campaigns/{campaign}', [AdsController::class, 'updateCampaign'])->name('marketing.ads.campaigns.update');
        Route::delete('/marketing/ads/campaigns/{campaign}', [AdsController::class, 'destroyCampaign'])->name('marketing.ads.campaigns.destroy');
        Route::post('/marketing/ads/campaigns/{campaign}/toggle', [AdsController::class, 'toggleCampaign'])->name('marketing.ads.toggle');
        Route::get('/marketing/ads/logs', [AdsController::class, 'logs'])->name('marketing.ads.logs');
        Route::post('/marketing/ads/logs', [AdsController::class, 'storeLog'])->name('marketing.ads.logs.store');
        Route::post('/marketing/ads/attribute-order', [AdsController::class, 'attributeOrder'])->name('marketing.ads.attribute');
        Route::post('/marketing/ads/sync', [AdsController::class, 'syncAll'])->name('marketing.ads.sync');
        Route::get('/marketing/ads/tiktok/connect', [\App\Http\Controllers\Marketing\TiktokAdsAuthController::class, 'connect'])->name('marketing.ads.tiktok.connect');
        Route::get('/marketing/ads/tiktok/callback', [\App\Http\Controllers\Marketing\TiktokAdsAuthController::class, 'callback'])->name('marketing.ads.tiktok.callback');
        Route::post('/marketing/ads/tiktok/select', [\App\Http\Controllers\Marketing\TiktokAdsAuthController::class, 'selectAccount'])->name('marketing.ads.tiktok.select');
    });


    // FAQ & Tutorials
    Route::get('/faq', [\App\Http\Controllers\FaqController::class, 'index'])->name('faq.index');

    // FAQ CRUD Management (Admin Only)
    Route::get('/faq/manage', [\App\Http\Controllers\FaqManagementController::class, 'manage'])->name('faq.manage');
    Route::post('/faq/categories', [\App\Http\Controllers\FaqManagementController::class, 'storeCategory'])->name('faq.categories.store');
    Route::put('/faq/categories/{category}', [\App\Http\Controllers\FaqManagementController::class, 'updateCategory'])->name('faq.categories.update');
    Route::delete('/faq/categories/{category}', [\App\Http\Controllers\FaqManagementController::class, 'destroyCategory'])->name('faq.categories.destroy');
    Route::post('/faq/items', [\App\Http\Controllers\FaqManagementController::class, 'storeItem'])->name('faq.items.store');
    Route::put('/faq/items/{item}', [\App\Http\Controllers\FaqManagementController::class, 'updateItem'])->name('faq.items.update');
    Route::delete('/faq/items/{item}', [\App\Http\Controllers\FaqManagementController::class, 'destroyItem'])->name('faq.items.destroy');

    // =========================================================================
    // Mobile Views (Secured by separate middlewares)
    // =========================================================================
    Route::get('/mobile', [\App\Http\Controllers\MobileController::class, 'index'])->name('mobile.index');

    // Owner Mobile Dashboard (Laporan Omset, Stok dll)
    Route::middleware('mobile.owner')->group(function () {
        Route::get('/mobile/owner', [\App\Http\Controllers\MobileController::class, 'ownerDashboard'])->name('mobile.owner');
    });

    // Gudang Mobile Dashboard (Scan Kemasan Produk & Request Produksi)
    Route::middleware('mobile.gudang')->group(function () {
        Route::get('/mobile/gudang', [\App\Http\Controllers\MobileController::class, 'gudangDashboard'])->name('mobile.gudang');
        Route::get('/mobile/gudang/scan', [\App\Http\Controllers\MobileController::class, 'gudangScan'])->name('mobile.gudang.scan');
        Route::post('/mobile/gudang/adjust-stock/{id}', [\App\Http\Controllers\MobileController::class, 'gudangAdjustStock'])->name('mobile.gudang.adjust_stock');
        Route::post('/mobile/gudang/request-production', [\App\Http\Controllers\MobileController::class, 'gudangRequestProduction'])->name('mobile.gudang.request_production');
    });

    // Produksi Mobile Dashboard (Pesanan Produksi)
    Route::middleware('mobile.produksi')->group(function () {
        Route::get('/mobile/produksi', [\App\Http\Controllers\MobileController::class, 'produksiDashboard'])->name('mobile.produksi');
        Route::post('/mobile/produksi/{order}/start', [\App\Http\Controllers\MobileController::class, 'produksiStart'])->name('mobile.produksi.start');
        Route::post('/mobile/produksi/{order}/complete', [\App\Http\Controllers\MobileController::class, 'produksiComplete'])->name('mobile.produksi.complete');
        Route::post('/mobile/produksi/{order}/cancel', [\App\Http\Controllers\MobileController::class, 'produksiCancel'])->name('mobile.produksi.cancel');
    });

    // Tenant Switcher (Super Admin)
    Route::post('/settings/switch-tenant', function (\Illuminate\Http\Request $request) {
        if (auth()->user() && auth()->user()->isSuperAdmin()) {
            $tenantId = $request->input('tenant_id');
            if (\App\Models\Tenant::where('id', $tenantId)->exists()) {
                session(['selected_tenant_id' => $tenantId]);
                return back()->with('success', 'Berhasil beralih perusahaan.');
            }
        }
        return back()->with('error', 'Gagal beralih perusahaan.');
    })->name('switch-tenant');
});


