<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Brand;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LaporanGudangController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands     = Brand::where('tenant_id', $tenantId)->orderBy('name')->get();
        $products   = MasterProduct::where('tenant_id', $tenantId)->orderBy('name')->get(['id', 'name', 'sku', 'is_bundle', 'stock']);

        $activeTab  = $request->get('tab', 'rekap');
        $dateFrom   = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo     = $request->get('date_to', Carbon::now()->toDateString());

        return view('v2.laporan_gudang.index', compact(
            'activeTab',
            'categories',
            'brands',
            'products',
            'dateFrom',
            'dateTo'
        ));
    }
}
