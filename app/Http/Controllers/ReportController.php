<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use App\Models\Brand;
use App\Models\MasterProduct;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    public function stockReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $categories = Category::where('tenant_id', $tenantId)->get();
        $brands = Brand::where('tenant_id', $tenantId)->get();
        $products = MasterProduct::where('tenant_id', $tenantId)->orderBy('name')->get();

        return view('reports.stock', compact('categories', 'brands', 'products'));
    }

    public function printStockReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = MasterProduct::with(['category', 'brand'])
                    ->where('tenant_id', $tenantId);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('product_id')) {
            $query->where('id', $request->product_id);
        }

        $products = $query->orderBy('name')->get();

        return view('reports.print_stock', compact('products'));
    }

    public function opnameReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $categories = \App\Models\Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        return view('reports.opname', compact('categories'));
    }

    public function printOpnameReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = \App\Models\StockMovement::with(['masterProduct.category', 'user'])
            ->where('tenant_id', $tenantId)
            ->where('reference', 'like', 'Stock Opname Massal%')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('masterProduct', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        $histories = $query->get();

        return view('reports.print_opname', compact('histories'));
    }

    public function ledgerReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $products = \App\Models\MasterProduct::where('tenant_id', $tenantId)->orderBy('name')->get();
        return view('reports.ledger', compact('products'));
    }

    public function printLedgerReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $request->validate([
            'product_id' => 'required|exists:master_products,id',
        ]);

        $product = \App\Models\MasterProduct::where('tenant_id', $tenantId)->findOrFail($request->product_id);

        $query = \App\Models\StockMovement::with('user')
            ->where('master_product_id', $product->id)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc');

        if ($request->filled('start_date')) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $movements = $query->get();
        
        if ($request->filled('start_date')) {
            $prevMovement = \App\Models\StockMovement::where('master_product_id', $product->id)
                ->where('tenant_id', $tenantId)
                ->whereDate('created_at', '<', $request->start_date)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->first();
            
            if ($prevMovement) {
                $saldoAwal = $prevMovement->balance_after;
            } elseif ($movements->count() > 0) {
                $saldoAwal = $movements->first()->balance_after - $movements->first()->quantity;
            } else {
                $saldoAwal = 0;
            }
        } else {
            $saldoAwal = $movements->count() > 0 
                ? $movements->first()->balance_after - $movements->first()->quantity 
                : $product->stock;
        }

        return view('reports.print_ledger', compact('product', 'movements', 'saldoAwal'));
    }

    public function summaryReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $categories = \App\Models\Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands = \App\Models\Brand::where('tenant_id', $tenantId)->orderBy('name')->get();
        return view('reports.summary', compact('categories', 'brands'));
    }

    public function printSummaryReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = \App\Models\MasterProduct::with(['category', 'brand'])
            ->where('tenant_id', $tenantId)
            ->orderBy('name');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        $products = $query->get();

        $startDate = $request->start_date ? \Carbon\Carbon::parse($request->start_date)->startOfDay() : null;
        $endDate = $request->end_date ? \Carbon\Carbon::parse($request->end_date)->endOfDay() : null;

        $productIds = $products->pluck('id');
        $movementQuery = \App\Models\StockMovement::whereIn('master_product_id', $productIds)
            ->where('tenant_id', $tenantId);
            
        $allMovements = $movementQuery->get()->groupBy('master_product_id');

        // Extract all order marketplace IDs from references to query their channels in bulk
        $orderIds = [];
        foreach ($allMovements as $productId => $movements) {
            foreach ($movements as $mov) {
                $ref = $mov->reference;
                if (str_starts_with($ref, 'Pesanan Masuk: ')) {
                    $orderIds[] = substr($ref, strlen('Pesanan Masuk: '));
                } elseif (str_starts_with($ref, 'Pembatalan Pesanan: ')) {
                    $orderIds[] = substr($ref, strlen('Pembatalan Pesanan: '));
                }
            }
        }
        $orderIds = array_unique(array_filter($orderIds));

        $orders = \App\Models\Order::with('store.channel')
            ->whereIn('order_marketplace_id', $orderIds)
            ->where('tenant_id', $tenantId)
            ->get()
            ->keyBy('order_marketplace_id');

        $reportData = [];

        foreach ($products as $product) {
            $movements = $allMovements->get($product->id, collect());
            
            $periodMovements = $movements;
            if ($startDate) {
                $periodMovements = $periodMovements->where('created_at', '>=', $startDate);
            }
            if ($endDate) {
                $periodMovements = $periodMovements->where('created_at', '<=', $endDate);
            }

            $futureMovementsSum = 0;
            if ($endDate) {
                $futureMovementsSum = $movements->where('created_at', '>', $endDate)->sum('quantity');
            }
            
            $stokAkhir = $product->stock - $futureMovementsSum;
            $totalPeriodQty = $periodMovements->sum('quantity');
            $stokAwal = $stokAkhir - $totalPeriodQty;

            $inPembelian = 0;
            $inPenyesuaian = 0;
            $inLainnya = 0;

            $outShopee = 0;
            $outTiktok = 0;
            $outTokopedia = 0;
            $outLazada = 0;
            $outLain = 0;
            $outPenyesuaian = 0;

            foreach ($periodMovements as $mov) {
                $qty = $mov->quantity;
                $type = $mov->type;
                $ref = $mov->reference;

                $channelCode = null;
                $orderIdFromRef = null;
                if (str_starts_with($ref, 'Pesanan Masuk: ')) {
                    $orderIdFromRef = substr($ref, strlen('Pesanan Masuk: '));
                } elseif (str_starts_with($ref, 'Pembatalan Pesanan: ')) {
                    $orderIdFromRef = substr($ref, strlen('Pembatalan Pesanan: '));
                }
                
                if ($orderIdFromRef && isset($orders[$orderIdFromRef])) {
                    $channelCode = $orders[$orderIdFromRef]->store->channel->code ?? null;
                }

                if ($qty > 0) {
                    if ($type === 'in') {
                        $inPembelian += $qty;
                    } elseif ($type === 'adj') {
                        $inPenyesuaian += $qty;
                    } else {
                        $inLainnya += $qty;
                    }
                } elseif ($qty < 0) {
                    $absQty = abs($qty);
                    $refLower = strtolower($ref);
                    if ($type === 'out') {
                        if ($channelCode === 'shopee' || str_contains($refLower, 'shopee')) {
                            $outShopee += $absQty;
                        } elseif ($channelCode === 'tiktok' || str_contains($refLower, 'tiktok')) {
                            $outTiktok += $absQty;
                        } elseif ($channelCode === 'tokopedia' || str_contains($refLower, 'tokopedia')) {
                            $outTokopedia += $absQty;
                        } elseif ($channelCode === 'lazada' || str_contains($refLower, 'lazada')) {
                            $outLazada += $absQty;
                        } else {
                            $outLain += $absQty;
                        }
                    } elseif ($type === 'adj') {
                        $outPenyesuaian += $absQty;
                    } else {
                        $outLain += $absQty;
                    }
                }
            }

            $reportData[] = [
                'product' => $product,
                'stok_awal' => $stokAwal,
                'in_pembelian' => $inPembelian,
                'in_penyesuaian' => $inPenyesuaian,
                'in_lainnya' => $inLainnya,
                'out_shopee' => $outShopee,
                'out_tiktok' => $outTiktok,
                'out_tokopedia' => $outTokopedia,
                'out_lazada' => $outLazada,
                'out_lain' => $outLain,
                'out_penyesuaian' => $outPenyesuaian,
                'stok_akhir' => $stokAkhir,
            ];
        }

        return view('reports.print_summary', compact('reportData', 'startDate', 'endDate'));
    }

    public function inventoryAnalytics(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Filter parameters
        $deadstockDays  = (int) $request->get('deadstock_days', 90);
        $targetCoverage = (int) $request->get('target_coverage', 30);

        // Fetch all tenant products
        $products = MasterProduct::where('tenant_id', $tenantId)->get();

        $thirtyDaysAgo = now()->subDays(30);

        // 1. Fetch total sales in last 30 days for each product (excluding cancelled orders)
        $salesLast30Days = \App\Models\OrderItem::whereHas('order', function($q) use ($tenantId, $thirtyDaysAgo) {
                $q->where('tenant_id', $tenantId)
                  ->whereNotIn('order_status', [\App\Models\Order::STATUS_CANCELLED])
                  ->where('order_date', '>=', $thirtyDaysAgo);
            })
            ->select('master_product_id', \DB::raw('SUM(quantity) as total_qty'))
            ->groupBy('master_product_id')
            ->pluck('total_qty', 'master_product_id')
            ->toArray();

        // 2. Fetch latest sale date for each product (excluding cancelled orders)
        $lastSales = \App\Models\OrderItem::whereHas('order', function($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)
                  ->whereNotIn('order_status', [\App\Models\Order::STATUS_CANCELLED]);
            })
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->select('order_items.master_product_id', \DB::raw('MAX(orders.order_date) as last_sale_date'))
            ->groupBy('order_items.master_product_id')
            ->pluck('last_sale_date', 'order_items.master_product_id')
            ->toArray();

        $processedProducts = [];
        $totalDeadstockItems = 0;
        $totalDeadstockValue = 0.0;
        $totalReorderAlerts = 0;

        foreach ($products as $product) {
            $sold30 = (int) ($salesLast30Days[$product->id] ?? 0);
            $runRate = $sold30 / 30.0;

            // Last sale date fallback to product creation date
            $lastSaleDateStr = $lastSales[$product->id] ?? null;
            $lastSaleDate = $lastSaleDateStr ? \Carbon\Carbon::parse($lastSaleDateStr) : $product->created_at;
            $daysSinceLastSale = (int) abs(now()->diffInDays($lastSaleDate));

            $daysOfCover = $runRate > 0 ? ($product->stock / $runRate) : PHP_INT_MAX;
            $recommendedQty = max(0, (int) ceil(($runRate * $targetCoverage) - $product->stock));

            $isDeadstock = $product->stock > 0 && $daysSinceLastSale >= $deadstockDays;

            if ($isDeadstock) {
                $totalDeadstockItems++;
                $totalDeadstockValue += ($product->stock * (float)($product->cost_price ?: 0.0));
            }

            $isLowStock = $product->stock <= $product->min_stock;
            $isRunOutSoon = $runRate > 0 && $daysOfCover <= 7;
            $isOutOfStockWithDemand = $product->stock == 0 && $sold30 > 0;

            if ($isLowStock || $isRunOutSoon || $isOutOfStockWithDemand) {
                $totalReorderAlerts++;
            }

            $processedProducts[] = [
                'id'                  => $product->id,
                'sku'                 => $product->sku,
                'name'                => $product->name,
                'stock'               => $product->stock,
                'min_stock'           => $product->min_stock,
                'cost_price'          => (float)($product->cost_price ?: 0.0),
                'price'               => (float)($product->price ?: 0.0),
                'sold_30'             => $sold30,
                'run_rate'            => $runRate,
                'last_sale_date'      => $lastSaleDate,
                'days_since_last_sale'=> $daysSinceLastSale,
                'days_of_cover'       => $daysOfCover,
                'recommended_qty'     => $recommendedQty,
                'is_deadstock'        => $isDeadstock,
            ];
        }

        // Collection 1: Deadstock Products (stock > 0 AND days since last sale >= filter)
        $deadstockProducts = collect($processedProducts)
            ->filter(fn($p) => $p['is_deadstock'])
            ->sortByDesc('days_since_last_sale')
            ->values();

        // Collection 2: Forecast & Restock (sort by days of cover ascending, so items running out first show up first)
        $forecastProducts = collect($processedProducts)
            ->sortBy('days_of_cover')
            ->values();

        return view('reports.analytics', compact(
            'deadstockProducts',
            'forecastProducts',
            'deadstockDays',
            'targetCoverage',
            'totalDeadstockItems',
            'totalDeadstockValue',
            'totalReorderAlerts'
        ));
    }

    public function storeSalesReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $dateFrom = $request->get('date_from', now()->subDays(30)->toDateString());
        $dateTo   = $request->get('date_to', now()->toDateString());

        // A. Group by Store
        $onlineStores = \App\Models\Store::where('tenant_id', $tenantId)->with('channel')->get();
        
        $storeStats = [];
        foreach ($onlineStores as $store) {
            $orders = \App\Models\Order::where('tenant_id', $tenantId)
                ->where('store_id', $store->id)
                ->whereNotIn('order_status', ['CANCELLED'])
                ->whereDate('order_date', '>=', $dateFrom)
                ->whereDate('order_date', '<=', $dateTo)
                ->get();
                
            $salesVal = (float) $orders->sum('net_amount');
            $orderCount = $orders->count();
            $qtySold = 0;
            foreach ($orders as $order) {
                $qtySold += $order->items()->sum('quantity');
            }
            
            $storeStats[] = [
                'name' => $store->store_name,
                'channel' => $store->channel->name,
                'sales' => $salesVal,
                'orders' => $orderCount,
                'quantity' => $qtySold,
                'aov' => $orderCount > 0 ? $salesVal / $orderCount : 0.0,
            ];
        }

        // Add POS Offline
        $offlineSales = \App\Models\OfflineSale::where('tenant_id', $tenantId)
            ->where('status', \App\Models\OfflineSale::STATUS_COMPLETED)
            ->whereDate('sold_at', '>=', $dateFrom)
            ->whereDate('sold_at', '<=', $dateTo)
            ->get();
            
        $offlineSalesVal = (float) $offlineSales->sum('grand_total');
        $offlineOrderCount = $offlineSales->count();
        $offlineQtySold = 0;
        foreach ($offlineSales as $sale) {
            $offlineQtySold += $sale->items()->sum('quantity');
        }
        
        $storeStats[] = [
            'name' => 'POS Offline (Toko Fisik)',
            'channel' => 'Offline POS',
            'sales' => $offlineSalesVal,
            'orders' => $offlineOrderCount,
            'quantity' => $offlineQtySold,
            'aov' => $offlineOrderCount > 0 ? $offlineSalesVal / $offlineOrderCount : 0.0,
        ];

        usort($storeStats, fn($a, $b) => $b['sales'] <=> $a['sales']);

        // B. Group by Channel
        $channelStats = [];
        foreach ($storeStats as $stat) {
            $ch = $stat['channel'];
            if (!isset($channelStats[$ch])) {
                $channelStats[$ch] = [
                    'name' => $ch,
                    'sales' => 0.0,
                    'orders' => 0,
                    'quantity' => 0,
                ];
            }
            $channelStats[$ch]['sales'] += $stat['sales'];
            $channelStats[$ch]['orders'] += $stat['orders'];
            $channelStats[$ch]['quantity'] += $stat['quantity'];
        }
        foreach ($channelStats as &$ch) {
            $ch['aov'] = $ch['orders'] > 0 ? $ch['sales'] / $ch['orders'] : 0.0;
        }
        unset($ch);

        // C. Top 5 Products sold per Channel/Store
        $topProducts = \App\Models\OrderItem::whereHas('order', function($q) use ($tenantId, $dateFrom, $dateTo) {
                $q->where('tenant_id', $tenantId)
                  ->whereNotIn('order_status', ['CANCELLED'])
                  ->whereDate('order_date', '>=', $dateFrom)
                  ->whereDate('order_date', '<=', $dateTo);
            })
            ->select('master_product_id', \DB::raw('SUM(quantity) as total_qty'), \DB::raw('SUM(total_price) as total_rev'))
            ->groupBy('master_product_id')
            ->orderByDesc('total_qty')
            ->limit(5)
            ->with('masterProduct')
            ->get();

        return view('reports.store_sales', compact('storeStats', 'channelStats', 'topProducts', 'dateFrom', 'dateTo'));
    }

    public function resellerReceivablesReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // A. Reseller Balances
        $resellers = \App\Models\Customer::where('tenant_id', $tenantId)
            ->where('balance', '>', 0)
            ->orderByDesc('balance')
            ->get();
            
        $totalResellerBalance = (float) $resellers->sum('balance');

        // B. Receivables
        $receivableSales = \App\Models\OfflineSale::with('customer')
            ->where('tenant_id', $tenantId)
            ->where('status', \App\Models\OfflineSale::STATUS_COMPLETED)
            ->where('payment_method', 'piutang')
            ->whereRaw('grand_total > paid_amount')
            ->get();

        $agingSummary = [
            'current' => 0.0,
            '31_60'   => 0.0,
            '61_90'   => 0.0,
            '90_plus' => 0.0,
            'total'   => 0.0,
        ];

        $customerAging = [];

        foreach ($receivableSales as $sale) {
            $receivableVal = (float) ($sale->grand_total - $sale->paid_amount);
            $days = (int) abs(now()->diffInDays($sale->sold_at));
            
            $category = 'current';
            if ($days > 90) {
                $category = '90_plus';
            } elseif ($days > 60) {
                $category = '61_90';
            } elseif ($days > 30) {
                $category = '31_60';
            }

            $agingSummary[$category] += $receivableVal;
            $agingSummary['total'] += $receivableVal;

            $cId = $sale->customer_id ?: 0;
            $cName = $sale->customer ? $sale->customer->name : ($sale->buyer_name ?: 'General Buyer');
            
            if (!isset($customerAging[$cId])) {
                $customerAging[$cId] = [
                    'name' => $cName,
                    'phone' => $sale->customer ? $sale->customer->phone : ($sale->buyer_phone ?: '-'),
                    'current' => 0.0,
                    '31_60' => 0.0,
                    '61_90' => 0.0,
                    '90_plus' => 0.0,
                    'total' => 0.0,
                ];
            }

            $customerAging[$cId][$category] += $receivableVal;
            $customerAging[$cId]['total'] += $receivableVal;
        }

        return view('reports.reseller_receivables', compact('resellers', 'totalResellerBalance', 'customerAging', 'agingSummary'));
    }

    public function inventoryTurnoverReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $dateFrom = $request->get('date_from', now()->subDays(30)->toDateString());
        $dateTo   = $request->get('date_to', now()->toDateString());

        $daysInPeriod = max(1, (int) abs(\Carbon\Carbon::parse($dateFrom)->diffInDays(\Carbon\Carbon::parse($dateTo))));

        $products = \App\Models\MasterProduct::where('tenant_id', $tenantId)->get();

        $turnoverData = [];
        $totalCogsValue = 0.0;
        $totalAvgStockValue = 0.0;

        foreach ($products as $product) {
            $costPrice = (float) ($product->cost_price ?: 0.0);

            $movements = \App\Models\StockMovement::where('master_product_id', $product->id)
                ->where('tenant_id', $tenantId)
                ->whereDate('created_at', '>=', $dateFrom)
                ->whereDate('created_at', '<=', $dateTo)
                ->get();

            $inQty = (int) $movements->filter(fn($m) => $m->quantity > 0)->sum('quantity');
            $outQty = (int) abs($movements->filter(fn($m) => $m->quantity < 0)->sum('quantity'));

            $movementsAfter = \App\Models\StockMovement::where('master_product_id', $product->id)
                ->where('tenant_id', $tenantId)
                ->whereDate('created_at', '>', $dateTo)
                ->sum('quantity');
                
            $endingStock = max(0, $product->stock - $movementsAfter);
            $startingStock = max(0, $endingStock - $inQty + $outQty);
            $avgStock = ($startingStock + $endingStock) / 2.0;

            $avgStockValue = $avgStock * $costPrice;
            $cogs = $outQty * $costPrice;

            $turnoverRatio = $avgStockValue > 0 ? $cogs / $avgStockValue : 0.0;
            $dsi = $turnoverRatio > 0 ? $daysInPeriod / $turnoverRatio : 999.0;

            $totalCogsValue += $cogs;
            $totalAvgStockValue += $avgStockValue;

            $turnoverData[] = [
                'sku' => $product->sku,
                'name' => $product->name,
                'cost_price' => $costPrice,
                'starting_stock' => $startingStock,
                'ending_stock' => $endingStock,
                'avg_stock' => $avgStock,
                'qty_sold' => $outQty,
                'cogs' => $cogs,
                'ratio' => $turnoverRatio,
                'dsi' => $dsi,
            ];
        }

        $totalTurnoverRatio = $totalAvgStockValue > 0 ? $totalCogsValue / $totalAvgStockValue : 0.0;
        $totalDsi = $totalTurnoverRatio > 0 ? $daysInPeriod / $totalTurnoverRatio : 999.0;

        usort($turnoverData, fn($a, $b) => $b['cogs'] <=> $a['cogs']);

        return view('reports.inventory_turnover', compact(
            'turnoverData', 'totalCogsValue', 'totalAvgStockValue', 
            'totalTurnoverRatio', 'totalDsi', 'dateFrom', 'dateTo', 'daysInPeriod'
        ));
    }

    public function productionHppReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $products = \App\Models\MasterProduct::where('tenant_id', $tenantId)->orderBy('name')->get();
        return view('reports.production_hpp', compact('products'));
    }

    public function printProductionHppReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = \App\Models\ProductionOrder::with(['masterProduct', 'requestedBy', 'actualLabors'])
            ->where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->orderBy('updated_at', 'desc');

        if ($request->filled('start_date')) {
            $query->whereDate('updated_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('updated_at', '<=', $request->end_date);
        }

        if ($request->filled('product_id')) {
            $query->where('master_product_id', $request->product_id);
        }

        $orders = $query->get();

        $reportData = [];

        foreach ($orders as $order) {
            $movements = \App\Models\StockMovement::where('tenant_id', $tenantId)
                ->where('reference', "Konsumsi Produksi SPK #{$order->id}")
                ->whereNotNull('inventory_item_id')
                ->with('inventoryItem')
                ->get();

            $totalMaterialCost = 0;
            foreach ($movements as $m) {
                $qtyConsumed = abs($m->quantity);
                $price = $m->inventoryItem->cost_price ?: 0;
                $totalMaterialCost += ($qtyConsumed * $price);
            }

            $totalLaborCost = $order->actualLabors->sum('actual_cost');
            $totalProductionCost = $totalMaterialCost + $totalLaborCost;
            $hppPerUnit = $order->quantity > 0 ? ($totalProductionCost / $order->quantity) : 0;

            $reportData[] = [
                'id' => $order->id,
                'product_name' => $order->masterProduct->name ?? '—',
                'sku' => $order->masterProduct->sku ?? '—',
                'completed_at' => $order->updated_at,
                'quantity' => $order->quantity,
                'material_cost' => $totalMaterialCost,
                'labor_cost' => $totalLaborCost,
                'total_cost' => $totalProductionCost,
                'hpp_per_unit' => $hppPerUnit,
            ];
        }

        return view('reports.print_production_hpp', compact('reportData'));
    }

    public function masterProductReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands = Brand::where('tenant_id', $tenantId)->orderBy('name')->get();

        $query = MasterProduct::with(['category', 'brand', 'components'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('sku', 'like', "%{$s}%")
                  ->orWhere('sku_induk', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_bundle')) {
            if ($request->is_bundle === '1') {
                $query->where('is_bundle', true);
            } elseif ($request->is_bundle === '0') {
                $query->where(function ($q) {
                    $q->where('is_bundle', false)->orWhereNull('is_bundle');
                });
            }
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active == '1');
        }

        $products = $query->orderBy('is_bundle', 'desc')->orderBy('name', 'asc')->get();

        $totalCount = $products->count();
        $bundleCount = $products->where('is_bundle', true)->count();
        $singleCount = $totalCount - $bundleCount;
        $totalStockValue = $products->sum(function ($p) {
            return $p->stock * $p->cost_price;
        });

        return view('reports.master_product', compact(
            'products',
            'categories',
            'brands',
            'totalCount',
            'bundleCount',
            'singleCount',
            'totalStockValue'
        ));
    }

    public function printMasterProductReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = MasterProduct::with(['category', 'brand', 'components'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('sku', 'like', "%{$s}%")
                  ->orWhere('sku_induk', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_bundle')) {
            if ($request->is_bundle === '1') {
                $query->where('is_bundle', true);
            } elseif ($request->is_bundle === '0') {
                $query->where(function ($q) {
                    $q->where('is_bundle', false)->orWhereNull('is_bundle');
                });
            }
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active == '1');
        }

        $products = $query->orderBy('is_bundle', 'desc')->orderBy('name', 'asc')->get();

        $totalCount = $products->count();
        $bundleCount = $products->where('is_bundle', true)->count();
        $singleCount = $totalCount - $bundleCount;
        $totalStockValue = $products->sum(function ($p) {
            return $p->stock * $p->cost_price;
        });

        return view('reports.print_master_product', compact(
            'products',
            'totalCount',
            'bundleCount',
            'singleCount',
            'totalStockValue'
        ));
    }

    public function exportMasterProductReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = MasterProduct::with(['category', 'brand', 'components'])
            ->where('tenant_id', $tenantId);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('sku', 'like', "%{$s}%")
                  ->orWhere('sku_induk', 'like', "%{$s}%");
            });
        }

        if ($request->filled('is_bundle')) {
            if ($request->is_bundle === '1') {
                $query->where('is_bundle', true);
            } elseif ($request->is_bundle === '0') {
                $query->where(function ($q) {
                    $q->where('is_bundle', false)->orWhereNull('is_bundle');
                });
            }
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('brand_id')) {
            $query->where('brand_id', $request->brand_id);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active == '1');
        }

        $products = $query->orderBy('is_bundle', 'desc')->orderBy('name', 'asc')->get();

        $filename = 'Laporan_Master_Produk_' . date('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($products) {
            $file = fopen('php://output', 'w');

            // UTF-8 BOM for Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, [
                'No',
                'SKU Produk',
                'SKU Induk',
                'Nama Produk',
                'Tipe Produk',
                'Kategori',
                'Merk',
                'Ukuran',
                'Warna',
                'Detail Komponen (Jika Set/Bundle)',
                'HPP (Modal)',
                'Harga Jual',
                'Stok',
                'Status'
            ]);

            foreach ($products as $i => $p) {
                $type = $p->is_bundle ? 'Set / Bundling' : 'Single';
                $comps = $p->is_bundle 
                    ? $p->components->map(fn($c) => "{$c->pivot->quantity}x {$c->sku} ({$c->name})")->implode(' + ')
                    : '-';

                fputcsv($file, [
                    $i + 1,
                    $p->sku,
                    $p->sku_induk ?? '-',
                    $p->name,
                    $type,
                    $p->category->name ?? '-',
                    $p->brand->name ?? '-',
                    $p->ukuran ?? '-',
                    $p->warna ?? '-',
                    $comps,
                    $p->cost_price,
                    $p->price,
                    $p->stock,
                    $p->is_active ? 'Aktif' : 'Nonaktif'
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function productMarginsReport(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = MasterProduct::where('tenant_id', $tenantId)
            ->with(['category', 'brand']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->orderBy('name')->get();
        $categories = Category::where('tenant_id', $tenantId)->get();

        return view('reports.product_margins', compact('products', 'categories'));
    }
}
