<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProdukController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = MasterProduct::with(['category', 'brand', 'marketplaceProducts.store.channel'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('sku')) {
            $sku = $request->sku;
            $query->where(function($q) use ($sku) {
                $q->where('sku', 'like', '%' . $sku . '%')
                  ->orWhere('sku_induk', 'like', '%' . $sku . '%');
            });
        }

        if ($request->filled('is_bundle')) {
            if ($request->is_bundle === '1') {
                $query->where('is_bundle', true);
            } elseif ($request->is_bundle === '0') {
                $query->where(function($q) {
                    $q->where('is_bundle', false)->orWhereNull('is_bundle');
                });
            }
        }

        if ($request->filled('is_preorder')) {
            if ($request->is_preorder === '1') {
                $query->where('is_preorder', true);
            } elseif ($request->is_preorder === '0') {
                $query->where(function($q) {
                    $q->where('is_preorder', false)->orWhereNull('is_preorder');
                });
            }
        }

        if ($request->filled('channel_id')) {
            $query->whereHas('marketplaceProducts.store', function($q) use ($request) {
                $q->where('channel_id', $request->channel_id);
            });
        }

        if ($request->filled('store_id')) {
            $query->whereHas('marketplaceProducts', function($q) use ($request) {
                $q->where('store_id', $request->store_id);
            });
        }

        if ($request->filled('link_status')) {
            if ($request->link_status === 'unlinked') {
                $query->where(function($q) {
                    $q->whereDoesntHave('marketplaceProducts')
                      ->orWhereDoesntHave('marketplaceProducts', function($mq) {
                          $mq->whereRaw('LOWER(TRIM(marketplace_sku)) = LOWER(TRIM(master_products.sku))');
                      });
                });
            }
        }

        $products = $query->orderBy('name')->paginate(25)->withQueryString();

        $stores = Store::with('channel')->where('tenant_id', $tenantId)->where('status', 'connected')->get();
        $channels = Channel::all();

        $poCount = MasterProduct::where('tenant_id', $tenantId)->where('is_preorder', true)->count();
        $readyCount = MasterProduct::where('tenant_id', $tenantId)->where(function($q) {
            $q->where('is_preorder', false)->orWhereNull('is_preorder');
        })->count();
        $bundleCount = MasterProduct::where('tenant_id', $tenantId)->where('is_bundle', true)->count();
        $singleCount = MasterProduct::where('tenant_id', $tenantId)->where(function($q) {
            $q->where('is_bundle', false)->orWhereNull('is_bundle');
        })->count();
        $unlinkedCount = MasterProduct::where('tenant_id', $tenantId)
            ->where(function($q) {
                $q->whereDoesntHave('marketplaceProducts')
                  ->orWhereDoesntHave('marketplaceProducts', function($mq) {
                      $mq->whereRaw('LOWER(TRIM(marketplace_sku)) = LOWER(TRIM(master_products.sku))');
                  });
            })->count();

        $counts = [
            'total' => MasterProduct::where('tenant_id', $tenantId)->count(),
            'single' => $singleCount,
            'bundle' => $bundleCount,
            'ready' => $readyCount,
            'po' => $poCount,
            'unlinked' => $unlinkedCount,
        ];

        return view('v2.produk.index', compact(
            'products',
            'stores',
            'channels',
            'counts'
        ));
    }
}
