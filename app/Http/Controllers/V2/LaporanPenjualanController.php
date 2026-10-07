<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ReportController;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LaporanPenjualanController extends Controller
{
    /**
     * Display V2 Laporan Penjualan with Tabs (Penjualan Dilepas & Rekap Penjualan).
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands     = Brand::where('tenant_id', $tenantId)->orderBy('name')->get();
        $stores     = Store::where('tenant_id', $tenantId)->with('channel')->orderBy('store_name')->get();

        // Customer Categories for sales report tab
        $masterCustCats = Customer::where('tenant_id', $tenantId)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->pluck('category')
            ->unique()
            ->toArray();
        $customerCategories = array_values(array_unique(array_merge(array_keys(Customer::CATEGORIES), $masterCustCats)));
        $customerCategoryLabels = Customer::CATEGORIES;

        // Current active tab: 'dilepas' or 'semua'
        $activeTab = $request->get('tab', 'dilepas');

        // Filter Parameters
        $dateFrom       = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo         = $request->get('date_to', Carbon::now()->toDateString());
        $categoryId     = $request->get('category_id');
        $brandId        = $request->get('brand_id');
        $storeId        = $request->get('store_id');
        $channelCode    = $request->get('channel_code', 'online');
        $reportFormat   = $request->get('report_format', ($activeTab === 'dilepas' ? 'per_produk' : 'per_produk'));
        $poStatus       = $request->get('po_status');
        $customerCat    = $request->get('customer_category', 'all');
        $dropshipFilter = $request->get('is_dropship', 'all');
        $statusFilter   = $request->get('status', 'all');

        // Summary calculated from ReportController
        $reportCtrl = app(ReportController::class);
        $summary = $reportCtrl->getReleasedSalesSummary(
            $tenantId,
            $dateFrom,
            $dateTo,
            $channelCode === 'all' ? 'online' : $channelCode,
            'all',
            $storeId
        );

        return view('v2.laporan.index', compact(
            'activeTab',
            'categories',
            'brands',
            'stores',
            'customerCategories',
            'customerCategoryLabels',
            'dateFrom',
            'dateTo',
            'categoryId',
            'brandId',
            'storeId',
            'channelCode',
            'reportFormat',
            'poStatus',
            'customerCat',
            'dropshipFilter',
            'statusFilter',
            'summary'
        ));
    }
}
