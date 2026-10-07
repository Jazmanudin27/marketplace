<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Order;
use App\Models\OfflineSale;
use App\Models\MasterProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class LaporanPenjualanController extends Controller
{
    /**
     * Display V2 Laporan Penjualan (Escrow Released & Multi-format).
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $categories = Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands     = Brand::where('tenant_id', $tenantId)->orderBy('name')->get();
        $stores     = Store::where('tenant_id', $tenantId)->with('channel')->orderBy('store_name')->get();

        $dateFrom     = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo       = $request->get('date_to', Carbon::now()->toDateString());
        $storeId      = $request->get('store_id');
        $channelCode  = $request->get('channel_code', 'all');
        $categoryId   = $request->get('category_id');
        $brandId      = $request->get('brand_id');
        $reportFormat = $request->get('report_format', 'ringkasan_penghasilan');
        $search       = trim($request->get('search', ''));

        // Query Orders (Completed / Released)
        $completedStatuses = ['COMPLETED', 'SELESAI', 'FINISHED'];
        $ordersQuery = Order::with(['store.channel', 'items'])
            ->where('tenant_id', $tenantId)
            ->whereIn('order_status', $completedStatuses)
            ->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                  ->orWhere(function ($q2) use ($dateFrom, $dateTo) {
                      $q2->whereNull('completed_at')
                         ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                  });
            });

        if (!empty($storeId)) {
            $ordersQuery->where('store_id', $storeId);
        } elseif ($channelCode !== 'all' && $channelCode !== 'offline' && $channelCode !== 'online') {
            $ordersQuery->whereHas('store.channel', fn($q) => $q->where('code', strtolower($channelCode)));
        }

        $onlineOrders = ($channelCode === 'offline') ? collect() : $ordersQuery->get();

        // Query Offline Sales (Completed)
        $offlineSales = collect();
        if ($channelCode === 'all' || $channelCode === 'offline') {
            $offlineQuery = OfflineSale::with('items')
                ->where('tenant_id', $tenantId)
                ->where('status', OfflineSale::STATUS_COMPLETED)
                ->whereBetween('sold_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            $offlineSales = $offlineQuery->get();
        }

        // Summary Calculations
        $totalOrdersOnline  = $onlineOrders->count();
        $totalOrdersOffline = $offlineSales->count();
        $totalOrders        = $totalOrdersOnline + $totalOrdersOffline;

        $grossRevenueOnline  = (float) $onlineOrders->sum('total_amount');
        $grossRevenueOffline = (float) $offlineSales->sum('grand_total');
        $grossRevenue        = $grossRevenueOnline + $grossRevenueOffline;

        $marketplaceFee = 0.0;
        $feePlatform    = 0.0;
        $feeFreeShipping= 0.0;
        $feeService     = 0.0;
        $feePromo       = 0.0;
        $feeOther       = 0.0;

        foreach ($onlineOrders as $order) {
            $breakdown = $order->fee_breakdown_details;
            $orderFee  = abs($breakdown['total_fee'] ?? $order->marketplace_fee ?? 0);
            $marketplaceFee  += $orderFee;
            $feePlatform     += abs($breakdown['platform_fee'] ?? $order->fee_platform_amount ?? 0);
            $feeFreeShipping += abs($breakdown['free_shipping'] ?? $order->fee_free_shipping_amount ?? 0);
            $feeService      += abs($breakdown['service_fee'] ?? $order->fee_service_amount ?? 0);
            $feePromo        += abs($breakdown['promo_fee'] ?? $order->fee_promo_amount ?? 0);
            $feeOther        += abs($breakdown['other_fee'] ?? 0);
        }

        // Refunds / Returns
        $totalRefunds = (float) $onlineOrders->sum('refund_amount');

        // Net Released
        $netReleasedOnline = (float) $onlineOrders->sum('net_amount');
        if ($netReleasedOnline <= 0 && $grossRevenueOnline > 0) {
            $netReleasedOnline = max(0.0, $grossRevenueOnline - $marketplaceFee - $totalRefunds);
        }
        $netReleased = $netReleasedOnline + $grossRevenueOffline;

        $summary = [
            'total_orders'    => $totalOrders,
            'gross_revenue'   => $grossRevenue,
            'total_refunds'   => $totalRefunds,
            'marketplace_fee' => $marketplaceFee,
            'net_released'    => $netReleased,
            'fee_platform'    => $feePlatform,
            'fee_free_shipping' => $feeFreeShipping,
            'fee_service'     => $feeService,
            'fee_promo'       => $feePromo,
            'fee_other'       => $feeOther,
        ];

        // Format-specific data for preview table
        $reportData = [];

        if ($reportFormat === 'per_channel') {
            // Group by Channel / Store
            $channelGroups = [];
            foreach ($onlineOrders as $o) {
                $stName = $o->store ? ($o->store->store_name . ' (' . strtoupper($o->store->channel->code ?? 'MP') . ')') : 'Marketplace Lain';
                if (!isset($channelGroups[$stName])) {
                    $channelGroups[$stName] = [
                        'name' => $stName,
                        'type' => 'Online Marketplace',
                        'orders' => 0,
                        'qty' => 0,
                        'omset' => 0.0,
                        'fee' => 0.0,
                        'net' => 0.0,
                    ];
                }
                $channelGroups[$stName]['orders'] += 1;
                $channelGroups[$stName]['qty']    += (int) $o->items->sum('quantity');
                $channelGroups[$stName]['omset']  += (float) $o->total_amount;
                $channelGroups[$stName]['fee']    += abs($o->marketplace_fee ?? 0);
                $channelGroups[$stName]['net']    += (float) ($o->net_amount > 0 ? $o->net_amount : ($o->total_amount - abs($o->marketplace_fee ?? 0)));
            }

            if ($offlineSales->count() > 0) {
                $channelGroups['POS Store (Offline)'] = [
                    'name' => 'POS Store (Offline)',
                    'type' => 'Toko Fisik',
                    'orders' => $offlineSales->count(),
                    'qty' => (int) $offlineSales->sum(fn($s) => $s->items->sum('quantity')),
                    'omset' => (float) $offlineSales->sum('grand_total'),
                    'fee' => 0.0,
                    'net' => (float) $offlineSales->sum('grand_total'),
                ];
            }

            $reportData['channels'] = array_values($channelGroups);

        } elseif ($reportFormat === 'per_produk') {
            // Group by Product SKU/Name
            $productGroups = [];
            foreach ($onlineOrders as $o) {
                foreach ($o->items as $it) {
                    $sku = trim($it->sku ?: $it->product_name ?: 'NO-SKU');
                    if ($search !== '' && stripos($sku, $search) === false && stripos($it->product_name, $search) === false) {
                        continue;
                    }
                    if (!isset($productGroups[$sku])) {
                        $productGroups[$sku] = [
                            'sku' => $sku,
                            'name' => $it->product_name ?: $sku,
                            'qty_online' => 0,
                            'qty_offline' => 0,
                            'total_qty' => 0,
                            'omset' => 0.0,
                            'fee' => 0.0,
                            'net' => 0.0,
                        ];
                    }
                    $itemOmset = (float) ($it->total_price ?? ($it->unit_price * $it->quantity));
                    $itemFee = ($o->total_amount > 0) ? (abs($o->marketplace_fee ?? 0) * ($itemOmset / $o->total_amount)) : 0;
                    $productGroups[$sku]['qty_online'] += (int) $it->quantity;
                    $productGroups[$sku]['total_qty']  += (int) $it->quantity;
                    $productGroups[$sku]['omset']      += $itemOmset;
                    $productGroups[$sku]['fee']        += $itemFee;
                    $productGroups[$sku]['net']        += ($itemOmset - $itemFee);
                }
            }

            foreach ($offlineSales as $s) {
                foreach ($s->items as $it) {
                    $sku = trim($it->sku ?: $it->product_name ?: 'NO-SKU');
                    if ($search !== '' && stripos($sku, $search) === false && stripos($it->product_name, $search) === false) {
                        continue;
                    }
                    if (!isset($productGroups[$sku])) {
                        $productGroups[$sku] = [
                            'sku' => $sku,
                            'name' => $it->product_name ?: $sku,
                            'qty_online' => 0,
                            'qty_offline' => 0,
                            'total_qty' => 0,
                            'omset' => 0.0,
                            'fee' => 0.0,
                            'net' => 0.0,
                        ];
                    }
                    $itemSubtotal = (float) $it->subtotal;
                    $productGroups[$sku]['qty_offline'] += (int) $it->quantity;
                    $productGroups[$sku]['total_qty']   += (int) $it->quantity;
                    $productGroups[$sku]['omset']       += $itemSubtotal;
                    $productGroups[$sku]['net']         += $itemSubtotal;
                }
            }

            // Sort by omset desc
            usort($productGroups, fn($a, $b) => $b['omset'] <=> $a['omset']);
            $reportData['products'] = $productGroups;

        } elseif ($reportFormat === 'per_tanggal') {
            // Group by Date
            $dateGroups = [];
            foreach ($onlineOrders as $o) {
                $d = Carbon::parse($o->completed_at ?: $o->order_date)->format('Y-m-d');
                if (!isset($dateGroups[$d])) {
                    $dateGroups[$d] = [
                        'date' => $d,
                        'orders' => 0,
                        'qty' => 0,
                        'omset' => 0.0,
                        'fee' => 0.0,
                        'net' => 0.0,
                    ];
                }
                $dateGroups[$d]['orders'] += 1;
                $dateGroups[$d]['qty']    += (int) $o->items->sum('quantity');
                $dateGroups[$d]['omset']  += (float) $o->total_amount;
                $dateGroups[$d]['fee']    += abs($o->marketplace_fee ?? 0);
                $dateGroups[$d]['net']    += (float) ($o->net_amount > 0 ? $o->net_amount : ($o->total_amount - abs($o->marketplace_fee ?? 0)));
            }

            foreach ($offlineSales as $s) {
                $d = Carbon::parse($s->sold_at)->format('Y-m-d');
                if (!isset($dateGroups[$d])) {
                    $dateGroups[$d] = [
                        'date' => $d,
                        'orders' => 0,
                        'qty' => 0,
                        'omset' => 0.0,
                        'fee' => 0.0,
                        'net' => 0.0,
                    ];
                }
                $dateGroups[$d]['orders'] += 1;
                $dateGroups[$d]['qty']    += (int) $s->items->sum('quantity');
                $dateGroups[$d]['omset']  += (float) $s->grand_total;
                $dateGroups[$d]['net']    += (float) $s->grand_total;
            }

            krsort($dateGroups);
            $reportData['dates'] = array_values($dateGroups);

        } elseif ($reportFormat === 'detail') {
            // List of individual transactions (max 100 preview)
            $transactions = [];
            foreach ($onlineOrders->take(100) as $o) {
                if ($search !== '' && stripos($o->order_number, $search) === false && stripos($o->customer_name, $search) === false) {
                    continue;
                }
                $transactions[] = [
                    'date' => Carbon::parse($o->order_date)->format('d/m/Y H:i'),
                    'completed_date' => $o->completed_at ? Carbon::parse($o->completed_at)->format('d/m/Y') : '—',
                    'ref' => $o->order_number,
                    'channel' => $o->store ? ($o->store->store_name . ' (' . strtoupper($o->store->channel->code ?? 'MP') . ')') : 'Online',
                    'customer' => $o->customer_name ?: '—',
                    'qty' => (int) $o->items->sum('quantity'),
                    'omset' => (float) $o->total_amount,
                    'fee' => abs($o->marketplace_fee ?? 0),
                    'net' => (float) ($o->net_amount > 0 ? $o->net_amount : ($o->total_amount - abs($o->marketplace_fee ?? 0))),
                    'status' => $o->order_status,
                ];
            }

            foreach ($offlineSales->take(100) as $s) {
                if ($search !== '' && stripos($s->sale_number, $search) === false && stripos($s->customer_name, $search) === false) {
                    continue;
                }
                $transactions[] = [
                    'date' => Carbon::parse($s->sold_at)->format('d/m/Y H:i'),
                    'completed_date' => Carbon::parse($s->sold_at)->format('d/m/Y'),
                    'ref' => $s->sale_number,
                    'channel' => 'POS Store (Offline)',
                    'customer' => $s->customer_name ?: '—',
                    'qty' => (int) $s->items->sum('quantity'),
                    'omset' => (float) $s->grand_total,
                    'fee' => 0.0,
                    'net' => (float) $s->grand_total,
                    'status' => 'COMPLETED',
                ];
            }

            usort($transactions, fn($a, $b) => strcmp($b['date'], $a['date']));
            $reportData['transactions'] = array_slice($transactions, 0, 100);
        }

        return view('v2.laporan.index', compact(
            'categories',
            'brands',
            'stores',
            'dateFrom',
            'dateTo',
            'storeId',
            'channelCode',
            'categoryId',
            'brandId',
            'reportFormat',
            'search',
            'summary',
            'reportData'
        ));
    }
}
