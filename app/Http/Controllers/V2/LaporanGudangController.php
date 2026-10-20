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

        // Limit default produk awal agar tidak dump ribuan baris bikin CPU 100%
        $products   = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'sku', 'is_bundle', 'stock']);

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

    /**
     * AJAX Search Products for Select2 (Cepat, Ringan & Hemat CPU).
     */
    public function searchProducts(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search   = trim($request->input('q', ''));

        $query = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('sku', 'like', '%' . $search . '%');
            });
        }

        $items = $query->select(['id', 'name', 'sku', 'stock', 'is_bundle'])
            ->orderBy('name')
            ->limit(40)
            ->get();

        $results = $items->map(function ($p) {
            return [
                'id'        => $p->id,
                'text'      => '[' . $p->sku . '] ' . $p->name . ' (Stok: ' . $p->stock . ')' . ($p->is_bundle ? ' [BUNDLE]' : ''),
                'name'      => $p->name,
                'sku'       => $p->sku,
                'stock'     => (int) $p->stock,
                'is_bundle' => (bool) $p->is_bundle,
            ];
        });

        return response()->json([
            'results' => $results,
        ]);
    }
}
