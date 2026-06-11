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
        $categories = \App\Models\Category::orderBy('name')->get();
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
        
        $saldoAwal = $movements->count() > 0 
            ? $movements->first()->balance_after - $movements->first()->quantity 
            : $product->stock;

        return view('reports.print_ledger', compact('product', 'movements', 'saldoAwal'));
    }

    public function summaryReport(Request $request)
    {
        $categories = \App\Models\Category::orderBy('name')->get();
        $brands = \App\Models\Brand::orderBy('name')->get();
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
                $ref = strtolower($mov->reference);

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
                    if ($type === 'out') {
                        if (str_contains($ref, 'shopee')) {
                            $outShopee += $absQty;
                        } elseif (str_contains($ref, 'tiktok')) {
                            $outTiktok += $absQty;
                        } elseif (str_contains($ref, 'tokopedia')) {
                            $outTokopedia += $absQty;
                        } elseif (str_contains($ref, 'lazada')) {
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
}
