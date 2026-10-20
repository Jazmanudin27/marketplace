<?php

namespace App\Http\Controllers;

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OfflineSale;
use App\Models\ProductionOrder;
use App\Models\BankAccount;
use App\Models\Expense;
use App\Models\Supplier;
use App\Models\SupplierConsignment;
use App\Models\SupplierConsignmentItem;
use App\Models\SupplierConsignmentSettlement;
use App\Models\SupplierConsignmentSettlementItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        } elseif (in_array($role, ['production', 'produksi', 'admin_produksi'])) {
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

    public function produksiDashboard(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search = $request->input('search');

        $query = \App\Models\Spk::with(['items.masterProduct', 'proses', 'items.progres'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('tanggal');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('no_spk', 'like', "%{$search}%")
                  ->orWhere('no_produksi', 'like', "%{$search}%")
                  ->orWhere('pemesan', 'like', "%{$search}%")
                  ->orWhere('instansi', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($i) use ($search) {
                      $i->where('nama_produk', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

        $spks = $query->paginate(10)->withQueryString();

        $pendingOrders = ProductionOrder::where('tenant_id', $tenantId)
            ->with('masterProduct', 'requestedBy')
            ->where('status', 'pending')
            ->orderBy('created_at')
            ->get();

        return view('mobile.produksi', compact('spks', 'search', 'pendingOrders'));
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

    public function ownerSales(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search = $request->input('search');
        $storeId = $request->input('store_id');
        $channelId = $request->input('channel_id');

        $query = Order::where('tenant_id', $tenantId)
            ->with('store.channel')
            ->orderByDesc('order_date');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('buyer_name', 'like', "%{$search}%")
                  ->orWhere('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('store', function($s) use ($search) {
                      $s->where('store_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        if ($channelId) {
            $query->whereHas('store', function ($q) use ($channelId) {
                $q->where('channel_id', $channelId);
            });
        }

        $orders = $query->paginate(15)->withQueryString();

        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        $revenueQueryToday = Order::where('tenant_id', $tenantId)
            ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SHIPPED', 'READY_TO_SHIP'])
            ->whereDate('order_date', $today);

        $revenueQueryMonth = Order::where('tenant_id', $tenantId)
            ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SHIPPED', 'READY_TO_SHIP'])
            ->where('order_date', '>=', $startOfMonth);

        if ($storeId) {
            $revenueQueryToday->where('store_id', $storeId);
            $revenueQueryMonth->where('store_id', $storeId);
        }
        if ($channelId) {
            $revenueQueryToday->whereHas('store', function ($q) use ($channelId) {
                $q->where('channel_id', $channelId);
            });
            $revenueQueryMonth->whereHas('store', function ($q) use ($channelId) {
                $q->where('channel_id', $channelId);
            });
        }

        $todayRevenue = $revenueQueryToday->sum('net_amount');
        $monthRevenue = $revenueQueryMonth->sum('net_amount');

        $stores = \App\Models\Store::where('tenant_id', $tenantId)->get();
        $channels = \App\Models\Channel::whereHas('stores', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId);
        })->get();

        return view('mobile.owner_sales', compact('orders', 'todayRevenue', 'monthRevenue', 'search', 'stores', 'channels', 'storeId', 'channelId'));
    }

    public function ownerPenjualanOffline(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search = $request->input('search');
        $tab = $request->input('tab', 'all');

        $query = OfflineSale::with(['store', 'items', 'spks'])
            ->where('tenant_id', $tenantId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('sale_number', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhere('institution_name', 'like', "%{$search}%")
                  ->orWhere('buyer_phone', 'like', "%{$search}%");
            });
        }

        if ($tab === 'unpacked') {
            $query->where(function($q) {
                $q->whereNull('packing_status')
                  ->orWhereNotIn('packing_status', ['verified', 'packed']);
            })->where('status', '!=', 'cancelled');
        } elseif ($tab === 'piutang') {
            $query->whereRaw('paid_amount < grand_total')->where('status', '!=', 'cancelled');
        } elseif ($tab === 'po') {
            $query->where('is_po', 1)->where('status', '!=', 'cancelled');
        } elseif ($tab === 'completed') {
            $query->where('status', 'completed');
        }

        // Summary KPI
        $summary = [
            'total_omset' => (float) OfflineSale::where('tenant_id', $tenantId)->where('status', '!=', 'cancelled')->sum('grand_total'),
            'total_piutang' => (float) (OfflineSale::where('tenant_id', $tenantId)->where('status', '!=', 'cancelled')->whereRaw('paid_amount < grand_total')->selectRaw('SUM(grand_total - paid_amount) as piutang')->value('piutang') ?? 0),
            'unpacked_count' => OfflineSale::where('tenant_id', $tenantId)->where('status', '!=', 'cancelled')->where(function($q) {
                $q->whereNull('packing_status')->orWhereNotIn('packing_status', ['verified', 'packed']);
            })->count(),
            'po_count' => OfflineSale::where('tenant_id', $tenantId)->where('status', '!=', 'cancelled')->where('is_po', 1)->count(),
            'total_count' => OfflineSale::where('tenant_id', $tenantId)->count(),
        ];

        $sales = $query->orderBy('sold_at', 'desc')->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return view('mobile.owner_penjualan_offline', compact('sales', 'summary', 'search', 'tab'));
    }

    public function ownerStokProduk(Request $request)
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

        $products = $query->orderBy('name')->paginate(15)->withQueryString();

        $totalStockValue = MasterProduct::where('tenant_id', $tenantId)->get()->sum(function ($p) {
            return $p->stock * (float)$p->cost_price;
        });

        $lowStockCount = MasterProduct::where('tenant_id', $tenantId)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        return view('mobile.owner_stok_produk', compact('products', 'totalStockValue', 'lowStockCount', 'search'));
    }

    public function ownerStokBarang(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search = $request->input('search');

        $query = \App\Models\InventoryItem::where('tenant_id', $tenantId);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('name')->paginate(15)->withQueryString();

        $totalItemsCount = \App\Models\InventoryItem::where('tenant_id', $tenantId)->count();
        $lowStockCount = \App\Models\InventoryItem::where('tenant_id', $tenantId)
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        return view('mobile.owner_stok_barang', compact('items', 'totalItemsCount', 'lowStockCount', 'search'));
    }

    public function ownerStokProdukDetail($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $product = MasterProduct::where('tenant_id', $tenantId)->findOrFail($id);

        $movements = \App\Models\StockMovement::where('tenant_id', $tenantId)
            ->where('master_product_id', $product->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($m) {
                return [
                    'date' => $m->created_at->format('d M Y H:i'),
                    'type' => strtoupper($m->type),
                    'quantity' => $m->quantity,
                    'reference' => $m->reference ?: '-',
                    'balance_after' => $m->balance_after,
                    'operator' => $m->user->name ?? 'System',
                ];
            });

        return response()->json([
            'success' => true,
            'product' => [
                'name' => $product->name,
                'sku' => $product->sku,
                'stock' => $product->stock,
                'min_stock' => $product->min_stock,
                'cost_price' => number_format($product->cost_price, 0, ',', '.'),
                'price' => number_format($product->price, 0, ',', '.'),
                'description' => $product->description ?: 'Tidak ada deskripsi.',
                'image_url' => $product->image_url ?: '/images/placeholder.png',
            ],
            'movements' => $movements,
        ]);
    }

    public function ownerStokBarangDetail($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $item = \App\Models\InventoryItem::where('tenant_id', $tenantId)->findOrFail($id);

        $movements = \App\Models\StockMovement::where('tenant_id', $tenantId)
            ->where('inventory_item_id', $item->id)
            ->with('user')
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($m) {
                return [
                    'date' => $m->created_at->format('d M Y H:i'),
                    'type' => strtoupper($m->type),
                    'quantity' => $m->quantity,
                    'reference' => $m->reference ?: '-',
                    'balance_after' => $m->balance_after,
                    'operator' => $m->user->name ?? 'System',
                ];
            });

        return response()->json([
            'success' => true,
            'item' => [
                'name' => $item->name,
                'sku' => $item->sku,
                'stock' => $item->stock,
                'min_stock' => $item->min_stock,
                'unit' => $item->unit ?: 'pcs',
                'category' => ucfirst($item->category ?? 'Umum'),
                'description' => $item->description ?: 'Tidak ada deskripsi.',
            ],
            'movements' => $movements,
        ]);
    }

    public function ownerSpk(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search = $request->input('search');
        $tipeSpk = $request->input('tipe_spk');

        $query = \App\Models\Spk::with(['items.masterProduct'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('tanggal');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('no_spk', 'like', "%{$search}%")
                  ->orWhere('no_produksi', 'like', "%{$search}%")
                  ->orWhere('pemesan', 'like', "%{$search}%")
                  ->orWhere('instansi', 'like', "%{$search}%")
                  ->orWhereHas('items', function ($i) use ($search) {
                      $i->where('nama_produk', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

        if ($tipeSpk && in_array($tipeSpk, ['pesanan_pelanggan', 'stok_gudang'])) {
            $query->where('tipe_spk', $tipeSpk);
        }

        $spks = $query->paginate(10)->withQueryString();

        return view('mobile.owner_spk', compact('spks', 'search', 'tipeSpk'));
    }

    public function showSpkDetail($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $spk = \App\Models\Spk::where('tenant_id', $tenantId)->findOrFail($id);
        
        $spk->load(['penginput', 'items.extras', 'items.progres', 'items.pickups.pemberi', 'proses']);
        
        if ($spk->proses->isEmpty()) {
            \App\Models\MasterProductionStage::seedDefaultsForTenant($tenantId);
            $masterStages = \App\Models\MasterProductionStage::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            if ($masterStages->isNotEmpty()) {
                \Illuminate\Support\Facades\DB::transaction(function () use ($spk, $masterStages) {
                    $seq = 1;
                    foreach ($masterStages as $stage) {
                        $prosesRecord = \App\Models\SpkProses::firstOrCreate(
                            ['spk_id' => $spk->id, 'nama_proses' => $stage->name],
                            ['urutan' => $seq]
                        );
                        $seq++;

                        foreach ($spk->items as $item) {
                            \App\Models\SpkItemProgres::firstOrCreate(
                                ['spk_item_id' => $item->id, 'spk_proses_id' => $prosesRecord->id],
                                ['qty_done' => 0]
                            );
                        }
                    }
                });
                $spk->load(['proses', 'items.progres']);
            }
        }

        $progresMap = [];
        foreach ($spk->items as $item) {
            foreach ($item->progres as $pg) {
                $progresMap[$item->id][$pg->spk_proses_id] = $pg;
            }
        }

        return view('mobile.spk_detail', compact('spk', 'progresMap'));
    }

    public function ownerProfitLoss(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', Carbon::now()->toDateString());

        // Online Sales (Marketplace - Penjualan Dilepas / Selesai)
        $onlineOrders = Order::where('tenant_id', $tenantId)
            ->whereIn('order_status', ['COMPLETED', 'SELESAI', 'FINISHED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR'])
            ->where(function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                  ->orWhere(function($subQ) use ($dateFrom, $dateTo) {
                      $subQ->whereNull('completed_at')
                           ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                  });
            })
            ->get();

        $onlineRevenue = (float) $onlineOrders->sum('net_amount');
        $onlineGrossSales = (float) $onlineOrders->sum('total_amount');
        $onlineRefunds = 0.0;
        $onlineMarketplaceFee = 0.0;
        $onlineHpp = 0.0;
        foreach ($onlineOrders as $order) {
            $onlineRefunds += (float) $order->refund_amount;
            if ($order->recon_status === 'RECONCILED') {
                $onlineMarketplaceFee += abs((float) $order->getRawOriginal('marketplace_fee'));
            } else {
                $absFee = (float) $order->marketplace_fee;
                if ($absFee <= 0 && !empty($order->fee_breakdown_details['total_fee'])) {
                    $absFee = abs((float) $order->fee_breakdown_details['total_fee']);
                }
                $onlineMarketplaceFee += $absFee;
            }
            $onlineHpp += $order->hpp_total;
        }

        $offlineSales = \App\Models\OfflineSale::where('tenant_id', $tenantId)
            ->where('status', \App\Models\OfflineSale::STATUS_COMPLETED)
            ->whereBetween('sold_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
            ->get();

        $offlineRevenue = (float) $offlineSales->sum('grand_total');
        $offlineHpp = 0.0;
        foreach ($offlineSales as $sale) {
            $offlineHpp += $sale->hpp_total;
        }

        // Pemasukan Lain-Lain Ditiadakan
        $otherIncomes = collect();
        $totalOtherIncome = 0.0;

        $totalSalesRevenue = $onlineRevenue + $offlineRevenue;
        $totalHpp = $onlineHpp + $offlineHpp;
        $grossProfit = $totalSalesRevenue - $totalHpp;

        $expenses = \App\Models\Expense::where('tenant_id', $tenantId)
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->get();

        // Load dynamic finance categories
        $allExpenseCats = \App\Models\FinanceCategory::where('tenant_id', $tenantId)->expense()->get();
        if ($allExpenseCats->isEmpty()) {
            \App\Models\FinanceCategory::seedDefaultsForTenant($tenantId);
            $allExpenseCats = \App\Models\FinanceCategory::where('tenant_id', $tenantId)->expense()->get();
        }

        // Filter kategori pengeluaran modal/HPP yang tidak boleh masuk beban operasional
        $excludedExpenseCodes = ['pembelian_supplier', 'bahan_baku', 'biaya_produksi', 'supplier_payable'];

        $operatingExpenses = $expenses->filter(function ($exp) use ($excludedExpenseCodes) {
            return !in_array(strtolower($exp->category), $excludedExpenseCodes);
        });

        $defaultLabels = [
            'salary'               => 'Beban Gaji Karyawan',
            'rent'                 => 'Beban Sewa & Tempat',
            'utilities'            => 'Beban Utilitas & Operasional',
            'other'                => 'Beban Lain-Lain',
        ];

        $expensesCategoryList = [];
        $groupedExpenses = $operatingExpenses->groupBy('category');

        foreach ($allExpenseCats as $cat) {
            if (in_array(strtolower($cat->code), $excludedExpenseCodes)) {
                continue;
            }
            $catAmount = (float) ($groupedExpenses[$cat->code] ?? collect())->sum('amount');
            $expensesCategoryList[$cat->code] = [
                'name'   => $cat->name,
                'amount' => $catAmount,
            ];
        }

        foreach ($groupedExpenses as $catCode => $group) {
            if (in_array(strtolower($catCode), $excludedExpenseCodes)) {
                continue;
            }
            if (!isset($expensesCategoryList[$catCode])) {
                $catName = $defaultLabels[$catCode] ?? ucwords(str_replace('_', ' ', $catCode));
                $expensesCategoryList[$catCode] = [
                    'name'   => $catName,
                    'amount' => (float) $group->sum('amount'),
                ];
            }
        }

        $expensesByCategory = [];
        foreach ($expensesCategoryList as $code => $item) {
            $expensesByCategory[$code] = $item['amount'];
        }

        $totalExpenses = (float) $operatingExpenses->sum('amount');

        $netProfit = $grossProfit - $totalExpenses;
        $netRevenueWithOther = $totalSalesRevenue;
        $profitMargin = $totalSalesRevenue > 0 ? round(($netProfit / $totalSalesRevenue) * 100, 2) : 0;

        return view('mobile.owner_profit_loss', compact(
            'dateFrom', 'dateTo',
            'onlineRevenue', 'onlineGrossSales', 'onlineRefunds', 'onlineMarketplaceFee', 'onlineHpp',
            'offlineRevenue', 'offlineHpp',
            'totalOtherIncome', 'totalSalesRevenue', 'totalHpp',
            'grossProfit', 'expensesByCategory', 'expensesCategoryList', 'totalExpenses',
            'netProfit', 'profitMargin'
        ));
    }

    /**
     * Mobile: Daftar Riwayat Setoran Pembayaran Titipan Supplier.
     */
    public function ownerTitipanSettlement(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();

        $query = SupplierConsignmentSettlement::where('tenant_id', $tenantId)
            ->with(['supplier', 'creator', 'bankAccount', 'items.masterProduct']);

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->supplier_id);
        }

        if ($request->filled('search')) {
            $s = trim($request->search);
            $query->where(function ($q) use ($s) {
                $q->where('settlement_number', 'like', "%{$s}%")
                  ->orWhere('reference_number', 'like', "%{$s}%");
            });
        }

        $settlements = $query->orderByDesc('settlement_date')->orderByDesc('id')->paginate(15)->withQueryString();

        $totalAmountPaid = (float) SupplierConsignmentSettlement::where('tenant_id', $tenantId)->sum('total_amount_paid');
        $totalQtySettled = (int) SupplierConsignmentSettlement::where('tenant_id', $tenantId)->sum('total_qty_settled');
        $totalCount = SupplierConsignmentSettlement::where('tenant_id', $tenantId)->count();

        return view('mobile.owner_titipan_settlement', compact('settlements', 'suppliers', 'totalAmountPaid', 'totalQtySettled', 'totalCount'));
    }

    /**
     * Mobile: Form Pembayaran Setoran Titipan ke Supplier.
     */
    public function ownerTitipanSettlementCreate(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $suppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get();
        $bankAccounts = BankAccount::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('bank_name')->get();

        $selectedSupplierId = $request->supplier_id ?: ($suppliers->first() ? $suppliers->first()->id : null);
        $availableItems = [];

        if ($selectedSupplierId) {
            $items = SupplierConsignmentItem::whereHas('consignment', function ($q) use ($tenantId, $selectedSupplierId) {
                $q->where('tenant_id', $tenantId)
                  ->where('supplier_id', $selectedSupplierId)
                  ->where('status', 'approved');
            })->with(['masterProduct', 'consignment'])->get();

            foreach ($items as $item) {
                $product = $item->masterProduct;
                if (!$product) continue;

                $qtyReceived  = $item->qty_received;
                $currentStock = $product->stock;
                $actualSold   = (int) ($item->qty_sold ?? 0);
                $qtySold      = $actualSold > 0 ? min($qtyReceived, $actualSold) : max(0, $qtyReceived - $currentStock);

                $qtySettled = (int) SupplierConsignmentSettlementItem::where('supplier_consignment_item_id', $item->id)
                    ->whereHas('settlement', fn($q) => $q->where('status', 'approved'))
                    ->sum('qty_settled');

                $basisQty     = $actualSold > 0 ? $qtySold : $qtyReceived;
                $qtyUnsettled = max(0, $basisQty - $qtySettled);

                if ($qtyUnsettled > 0) {
                    $availableItems[] = [
                        'consignment_item_id' => $item->id,
                        'ref_number'          => $item->consignment ? $item->consignment->reference_number : '-',
                        'consignment_date'    => $item->consignment ? $item->consignment->consignment_date->format('d/m/Y') : '-',
                        'master_product_id'   => $product->id,
                        'sku'                 => $product->sku,
                        'name'                => $product->name,
                        'qty_received'        => $qtyReceived,
                        'qty_sold'            => $qtySold,
                        'qty_settled'         => $qtySettled,
                        'qty_unsettled'       => $qtyUnsettled,
                        'unit_cost_price'     => (float)$item->unit_cost_price,
                    ];
                }
            }
        }

        $settlementNumber = SupplierConsignmentSettlement::generateSettlementNumber();

        return view('mobile.owner_titipan_settlement_create', compact(
            'suppliers',
            'bankAccounts',
            'selectedSupplierId',
            'availableItems',
            'settlementNumber'
        ));
    }

    /**
     * Mobile: Simpan Pembayaran Setoran Titipan ke Supplier & Sinkron ke Mutasi Kas.
     */
    public function ownerTitipanSettlementStore(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'supplier_id'     => 'required|exists:suppliers,id',
            'settlement_date' => 'required|date',
            'payment_method'  => 'required|in:cash,transfer',
            'bank_account_id' => 'required_if:payment_method,transfer|nullable|exists:bank_accounts,id',
            'notes'           => 'nullable|string|max:1000',
            'items'           => 'required|array|min:1',
            'items.*.consignment_item_id' => 'required|exists:supplier_consignment_items,id',
            'items.*.master_product_id'   => 'required|exists:master_products,id',
            'items.*.qty_settled'         => 'required|integer|min:1',
            'items.*.unit_cost_price'     => 'required|numeric|min:0',
        ]);

        $settlement = DB::transaction(function () use ($request, $tenantId) {
            $settlementNumber = SupplierConsignmentSettlement::generateSettlementNumber();
            $totalQtySettled  = 0;
            $totalAmountPaid  = 0;

            $settlement = SupplierConsignmentSettlement::create([
                'tenant_id'         => $tenantId,
                'supplier_id'       => $request->supplier_id,
                'settlement_number' => $settlementNumber,
                'settlement_date'   => $request->settlement_date,
                'payment_method'    => $request->payment_method,
                'bank_account_id'   => $request->payment_method === 'transfer' ? $request->bank_account_id : null,
                'reference_number'  => $request->reference_number,
                'status'            => 'approved',
                'notes'             => $request->notes,
                'created_by'        => Auth::id(),
            ]);

            foreach ($request->items as $row) {
                $qtySettled = (int) $row['qty_settled'];
                $costPrice  = (float) $row['unit_cost_price'];
                $subtotal   = $qtySettled * $costPrice;

                if ($qtySettled > 0) {
                    $totalQtySettled += $qtySettled;
                    $totalAmountPaid += $subtotal;

                    $settlement->items()->create([
                        'supplier_consignment_item_id' => $row['consignment_item_id'],
                        'master_product_id'           => $row['master_product_id'],
                        'qty_settled'                 => $qtySettled,
                        'unit_cost_price'             => $costPrice,
                        'subtotal'                    => $subtotal,
                    ]);
                }
            }

            $settlement->update([
                'total_qty_settled' => $totalQtySettled,
                'total_amount_paid' => $totalAmountPaid,
            ]);

            // Sinkronisasi otomatis ke Mutasi Kas (Expense)
            $supplier = Supplier::find($request->supplier_id);
            $supplierName = $supplier ? $supplier->name : 'Supplier';

            $paymentSource = 'kas_besar';
            if ($request->payment_method === 'transfer' && $request->bank_account_id) {
                $bank = BankAccount::where('tenant_id', $tenantId)->find($request->bank_account_id);
                $paymentSource = $bank ? ($bank->bank_name ?: (string)$bank->id) : (string)$request->bank_account_id;
            }

            Expense::create([
                'tenant_id'      => $tenantId,
                'title'          => "Setoran Titipan Konsinyasi: {$supplierName} ({$settlementNumber})",
                'category'       => 'Setoran Barang Konsinyasi',
                'payment_source' => $paymentSource,
                'amount'         => $totalAmountPaid,
                'expense_date'   => $request->settlement_date,
                'description'    => "Setoran Penjualan Barang Konsinyasi {$supplierName} ({$settlementNumber}) — Total {$totalQtySettled} PCS." . ($request->notes ? " Catatan: {$request->notes}" : ''),
            ]);

            return $settlement;
        });

        return redirect()->route('mobile.owner.titipan_settlement')
            ->with('success', 'Pembayaran setoran titipan berhasil disimpan dan masuk ke mutasi kas.');
    }

    /**
     * Mobile: Detail Bukti Pembayaran Setoran Titipan Supplier.
     */
    public function ownerTitipanSettlementShow($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $settlement = SupplierConsignmentSettlement::where('tenant_id', $tenantId)
            ->with(['supplier', 'creator', 'bankAccount', 'items.masterProduct', 'items.consignmentItem.consignment'])
            ->findOrFail($id);

        return view('mobile.owner_titipan_settlement_show', compact('settlement'));
    }
}
