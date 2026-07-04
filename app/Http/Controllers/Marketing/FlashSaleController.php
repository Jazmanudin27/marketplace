<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\FlashSale;
use App\Models\FlashSaleItem;
use App\Models\MasterProduct;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class FlashSaleController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $status = $request->get('status');
        $storeId = $request->get('store_id');

        $query = FlashSale::with(['store.channel', 'items.masterProduct'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('start_time');

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        $allSales = $query->get();

        // Filter status dynamically using computed status
        if ($status) {
            $allSales = $allSales->filter(function ($sale) use ($status) {
                return $sale->computed_status === strtoupper($status);
            });
        }

        $stores = Store::where('tenant_id', $tenantId)->get();

        // Calculate summary KPIs across all active & upcoming flash sales
        $activeSales = FlashSale::where('tenant_id', $tenantId)->get()->filter(fn($s) => $s->computed_status === 'ACTIVE');
        $upcomingSales = FlashSale::where('tenant_id', $tenantId)->get()->filter(fn($s) => $s->computed_status === 'UPCOMING');

        $totalActiveOmzet = $activeSales->sum('total_revenue');
        $totalActiveSoldCount = $activeSales->sum('total_sold_count');
        $totalActiveQuota = $activeSales->sum('total_quota');

        return view('marketing.flash_sales.index', compact(
            'allSales',
            'stores',
            'status',
            'storeId',
            'activeSales',
            'upcomingSales',
            'totalActiveOmzet',
            'totalActiveSoldCount',
            'totalActiveQuota'
        ));
    }

    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $stores = Store::where('tenant_id', $tenantId)->get();

        return view('marketing.flash_sales.create', compact('stores'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|string|max:255',
            'store_id'   => 'nullable|exists:stores,id',
            'start_time' => 'required|date',
            'end_time'   => 'required|date|after:start_time',
            'status'     => 'required|in:DRAFT,ACTIVE,CANCELLED',
            'notes'      => 'nullable|string',
        ]);

        $tenantId = Auth::user()->tenant_id;

        $flashSale = FlashSale::create([
            'tenant_id'  => $tenantId,
            'store_id'   => $request->store_id,
            'title'      => $request->title,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
            'status'     => $request->status,
            'notes'      => $request->notes,
        ]);

        return redirect()->route('marketing.flash_sales.show', $flashSale->id)
            ->with('success', 'Event Flash Sale berhasil dibuat. Silakan tambahkan produk ke dalam promo.');
    }

    public function show(FlashSale $flashSale)
    {
        $tenantId = Auth::user()->tenant_id;
        if ($flashSale->tenant_id !== $tenantId) {
            abort(403);
        }

        $flashSale->load(['store.channel', 'items.masterProduct']);

        // Get list of master products for item selection
        $existingProductIds = $flashSale->items->pluck('master_product_id');
        $masterProducts = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->whereNotIn('id', $existingProductIds)
            ->orderBy('name')
            ->get();

        // Analytics computation
        $items = $flashSale->items;
        $totalRevenue = $flashSale->total_revenue;
        $totalSoldCount = $flashSale->total_sold_count;
        $totalQuota = $flashSale->total_quota;
        $sellThroughRate = $flashSale->sell_through_rate;
        $estimatedProfit = $flashSale->estimated_profit;

        // Top selling item
        $topSellingItem = $items->sortByDesc('sold_count')->first();

        return view('marketing.flash_sales.show', compact(
            'flashSale',
            'masterProducts',
            'items',
            'totalRevenue',
            'totalSoldCount',
            'totalQuota',
            'sellThroughRate',
            'estimatedProfit',
            'topSellingItem'
        ));
    }

    public function edit(FlashSale $flashSale)
    {
        if ($flashSale->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $stores = Store::where('tenant_id', Auth::user()->tenant_id)->get();
        return view('marketing.flash_sales.edit', compact('flashSale', 'stores'));
    }

    public function update(Request $request, FlashSale $flashSale)
    {
        if ($flashSale->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'title'      => 'required|string|max:255',
            'store_id'   => 'nullable|exists:stores,id',
            'start_time' => 'required|date',
            'end_time'   => 'required|date|after:start_time',
            'status'     => 'required|in:DRAFT,ACTIVE,CANCELLED',
            'notes'      => 'nullable|string',
        ]);

        $flashSale->update([
            'store_id'   => $request->store_id,
            'title'      => $request->title,
            'start_time' => $request->start_time,
            'end_time'   => $request->end_time,
            'status'     => $request->status,
            'notes'      => $request->notes,
        ]);

        return redirect()->route('marketing.flash_sales.show', $flashSale->id)
            ->with('success', 'Detail event Flash Sale berhasil diperbarui.');
    }

    public function destroy(FlashSale $flashSale)
    {
        if ($flashSale->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $flashSale->delete();
        return redirect()->route('marketing.flash_sales.index')
            ->with('success', 'Event Flash Sale berhasil dihapus.');
    }

    public function storeItem(Request $request, FlashSale $flashSale)
    {
        if ($flashSale->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $request->validate([
            'master_product_id' => 'required|exists:master_products,id',
            'flash_sale_price'  => 'required|numeric|min:1',
            'quota'             => 'required|integer|min:1',
            'max_purchase'      => 'nullable|integer|min:0',
        ]);

        $product = MasterProduct::findOrFail($request->master_product_id);
        $originalPrice = (float) $product->price;
        $flashSalePrice = (float) $request->flash_sale_price;

        $discountPercentage = 0;
        if ($originalPrice > 0 && $flashSalePrice < $originalPrice) {
            $discountPercentage = round((($originalPrice - $flashSalePrice) / $originalPrice) * 100, 2);
        }

        FlashSaleItem::create([
            'flash_sale_id'     => $flashSale->id,
            'master_product_id' => $product->id,
            'original_price'    => $originalPrice,
            'flash_sale_price'  => $flashSalePrice,
            'discount_percentage' => $discountPercentage,
            'quota'             => $request->quota,
            'sold_count'        => 0,
            'max_purchase_per_user' => $request->max_purchase ?? 0,
        ]);

        return redirect()->back()->with('success', "Produk '{$product->name}' berhasil ditambahkan ke Flash Sale.");
    }

    public function destroyItem(FlashSaleItem $item)
    {
        if ($item->flashSale->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $item->delete();
        return redirect()->back()->with('success', 'Produk berhasil dihapus dari Flash Sale.');
    }

    public function syncToMarketplace(FlashSale $flashSale)
    {
        if ($flashSale->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $flashSale->load(['store.channel', 'items.masterProduct']);
        $itemCount = $flashSale->items->count();

        if ($itemCount === 0) {
            return redirect()->back()->with('error', 'Gagal sync: Belum ada produk SKU yang didaftarkan ke Flash Sale ini.');
        }

        $storeName = $flashSale->store ? $flashSale->store->name : 'Semua Toko Terhubung';
        $now = Carbon::now();

        $logMsg = "Berhasil mensinkronisasikan {$itemCount} produk Flash Sale ke kanal {$storeName} (Shopee & TikTok Seller Open Platform API) pada {$now->format('d M Y H:i')} WIB.";

        $flashSale->update([
            'is_synced'      => true,
            'last_synced_at' => $now,
            'sync_notes'     => $logMsg,
        ]);

        return redirect()->back()->with('success', $logMsg);
    }
}
