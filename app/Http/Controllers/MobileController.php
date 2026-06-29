<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\ProductionOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class MobileController extends Controller
{
    public function index()
    {
        $role = Auth::user()->role;

        if (in_array($role, ['admin', 'owner', 'finance'])) {
            return redirect()->route('mobile.owner');
        } elseif (in_array($role, ['warehouse', 'gudang'])) {
            return redirect()->route('mobile.gudang');
        } elseif (in_array($role, ['production', 'produksi'])) {
            return redirect()->route('mobile.produksi');
        }

        abort(403, 'Anda tidak memiliki akses ke dasbor mobile.');
    }

    public function ownerDashboard()
    {
        $tenantId = Auth::user()->tenant_id;
        
        // Revenue calculations
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $todayRevenue = Order::where('tenant_id', $tenantId)
            ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SHIPPED', 'READY_TO_SHIP'])
            ->whereDate('order_date', $today)
            ->sum('net_amount');

        $monthRevenue = Order::where('tenant_id', $tenantId)
            ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SHIPPED', 'READY_TO_SHIP'])
            ->where('order_date', '>=', $startOfMonth)
            ->sum('net_amount');

        // Stock calculations
        $products = MasterProduct::where('tenant_id', $tenantId)->get();
        $totalStockValue = $products->sum(function ($p) {
            return $p->stock * (float)$p->cost_price;
        });

        $lowStockCount = MasterProduct::where('tenant_id', $tenantId)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        // Recent orders
        $recentOrders = Order::where('tenant_id', $tenantId)
            ->with('store.channel')
            ->orderByDesc('order_date')
            ->limit(5)
            ->get();

        // Low stock products list
        $lowStockProducts = MasterProduct::where('tenant_id', $tenantId)
            ->whereColumn('stock', '<=', 'min_stock')
            ->limit(10)
            ->get();

        // Calculate Ads stats for mobile owner
        $monthSpend = \App\Models\AdsPerformanceLog::where('tenant_id', $tenantId)
            ->where('date', '>=', $startOfMonth)
            ->sum('ad_spend');

        $monthAdsRevenue = Order::where('tenant_id', $tenantId)
            ->whereNotNull('ads_campaign_id')
            ->where('order_date', '>=', $startOfMonth)
            ->whereNotIn('order_status', [Order::STATUS_CANCELLED])
            ->sum('net_amount');

        $monthRoas = $monthSpend > 0 ? (float)($monthAdsRevenue / $monthSpend) : 0.0;

        return view('mobile.owner', compact(
            'todayRevenue', 
            'monthRevenue', 
            'totalStockValue', 
            'lowStockCount', 
            'recentOrders', 
            'lowStockProducts',
            'monthSpend',
            'monthRoas'
        ));
    }

    public function gudangDashboard(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search = $request->input('search');

        $query = MasterProduct::where('tenant_id', $tenantId);
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }
        $products = $query->orderBy('name')->paginate(15);

        // Active production orders requested by warehouse
        $activeProductionRequests = ProductionOrder::where('tenant_id', $tenantId)
            ->with('masterProduct', 'requestedBy')
            ->whereIn('status', ['pending', 'producing'])
            ->orderByDesc('created_at')
            ->get();

        // Completed/cancelled history
        $productionHistory = ProductionOrder::where('tenant_id', $tenantId)
            ->with('masterProduct', 'requestedBy')
            ->whereIn('status', ['completed', 'cancelled'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return view('mobile.gudang', compact('products', 'activeProductionRequests', 'productionHistory', 'search'));
    }

    public function gudangScan(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $sku = $request->input('sku');

        if ($request->ajax()) {
            $product = MasterProduct::where('tenant_id', $tenantId)
                ->where('sku', $sku)
                ->first();

            if (!$product) {
                return response()->json(['success' => false, 'message' => 'Produk tidak ditemukan.'], 404);
            }

            return response()->json([
                'success' => true,
                'product' => [
                    'id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'stock' => $product->stock,
                    'cost_price' => $product->cost_price,
                    'price' => $product->price,
                    'image_url' => $product->image_url ?: '/images/placeholder.png',
                ]
            ]);
        }

        return view('mobile.scan');
    }

    public function gudangAdjustStock(Request $request, $id)
    {
        $tenantId = Auth::user()->tenant_id;
        $product = MasterProduct::where('tenant_id', $tenantId)->findOrFail($id);

        $request->validate([
            'quantity' => 'required|integer',
            'type' => 'required|in:in,out,adj',
            'reference' => 'required|string|max:255',
        ]);

        $product->recordStockMovement(
            $request->quantity,
            $request->type,
            $request->reference,
            Auth::id()
        );

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Stok berhasil diperbarui.', 'new_stock' => $product->fresh()->stock]);
        }

        return back()->with('success', 'Stok berhasil diperbarui.');
    }

    public function gudangRequestProduction(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'master_product_id' => 'required|exists:master_products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // Verify product belongs to tenant
        $product = MasterProduct::where('tenant_id', $tenantId)->findOrFail($request->master_product_id);

        $productionOrder = ProductionOrder::create([
            'tenant_id' => $tenantId,
            'master_product_id' => $product->id,
            'quantity' => $request->quantity,
            'status' => 'pending',
            'requested_by' => Auth::id(),
        ]);

        if ($request->ajax()) {
            return response()->json(['success' => true, 'message' => 'Permintaan produksi berhasil dibuat.']);
        }

        return back()->with('success', 'Permintaan produksi berhasil dikirim ke bagian produksi.');
    }

    public function produksiDashboard()
    {
        $tenantId = Auth::user()->tenant_id;

        // Pending production orders
        $pendingOrders = ProductionOrder::where('tenant_id', $tenantId)
            ->with('masterProduct', 'requestedBy')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        // Active producing orders
        $producingOrders = ProductionOrder::where('tenant_id', $tenantId)
            ->with('masterProduct', 'requestedBy')
            ->where('status', 'producing')
            ->orderBy('updated_at')
            ->get();

        // Completed production orders
        $completedOrders = ProductionOrder::where('tenant_id', $tenantId)
            ->with('masterProduct', 'requestedBy')
            ->whereIn('status', ['completed', 'cancelled'])
            ->orderByDesc('updated_at')
            ->limit(15)
            ->get();

        return view('mobile.produksi', compact('pendingOrders', 'producingOrders', 'completedOrders'));
    }

    public function produksiStart(ProductionOrder $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        $order->update(['status' => 'producing']);

        return back()->with('success', 'Proses produksi barang #' . $order->id . ' telah dimulai.');
    }

    public function produksiComplete(ProductionOrder $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        if ($order->status !== 'producing') {
            return back()->with('error', 'Pesanan harus dalam status Sedang Diproduksi untuk dapat diselesaikan.');
        }

        // Update status
        $order->update(['status' => 'completed']);

        // Record stock movement (tambahkan stok ke MasterProduct)
        $order->masterProduct->recordStockMovement(
            $order->quantity,
            'in',
            'Penerimaan Produksi Selesai #' . $order->id,
            Auth::id()
        );

        // Cari semua pesanan yang belum terpotong stoknya dan memiliki item produk ini
        $pendingOrders = \App\Models\Order::where('tenant_id', $order->tenant_id)
            ->where('is_stock_deducted', false)
            ->whereIn('order_status', [\App\Models\Order::STATUS_READY_TO_SHIP, \App\Models\Order::STATUS_UNPAID])
            ->whereHas('items', function ($query) use ($order) {
                $query->where('master_product_id', $order->master_product_id);
            })
            ->orderBy('order_date', 'asc') // Urutan FIFO
            ->get();

        foreach ($pendingOrders as $pendingOrder) {
            $pendingOrder->processStockDeduction();
        }

        return back()->with('success', 'Produksi selesai! Stok produk ' . $order->masterProduct->name . ' otomatis ditambahkan sebanyak ' . $order->quantity . ' pcs dan dialokasikan ke pesanan PO yang tertunda.');
    }

    public function produksiCancel(ProductionOrder $order)
    {
        abort_unless($order->tenant_id === Auth::user()->tenant_id, 403);
        
        $order->update(['status' => 'cancelled']);

        return back()->with('success', 'Permintaan produksi #' . $order->id . ' telah dibatalkan.');
    }
}
