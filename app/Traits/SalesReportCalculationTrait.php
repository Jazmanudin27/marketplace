<?php

namespace App\Traits;

use App\Models\MasterProduct;
use App\Models\Order;
use App\Models\OfflineSale;
use App\Models\Store;
use App\Models\Category;
use App\Models\Brand;
use App\Models\Customer;
use App\Models\ReturnOrder;
use Illuminate\Support\Facades\DB;

trait SalesReportCalculationTrait
{
    public function getReleasedSalesSummary($tenantId, $dateFrom, $dateTo, $channelCode = 'online', $customerCat = 'all', $storeId = null)
    {
        $totalOrders = 0;
        $grossRevenue = 0.0;
        $marketplaceFee = 0.0;
        $netReleased = 0.0;

        // 1. Online Orders (COMPLETED)
        $query = null;
        if ($channelCode !== 'offline') {
            $query = Order::where('tenant_id', $tenantId)
                ->whereIn('order_status', ['COMPLETED', 'DELIVERED', 'SELESAI', 'FINISHED'])
                ->whereNotNull('completed_at')
                ->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

            if (!empty($storeId)) {
                $query->where('store_id', $storeId);
            } elseif ($channelCode !== 'all' && $channelCode !== 'online') {
                $query->whereHas('store.channel', fn($cq) => $cq->where('code', strtolower($channelCode)));
            }

            $orders = $query->get(['total_amount', 'marketplace_fee', 'net_amount', 'recon_status']);
            $totalOrders += $orders->count();
            $gRev = (float) $orders->sum('total_amount');
            $mpFee = (float) $orders->sum('marketplace_fee');
            $grossRevenue += $gRev;
            $marketplaceFee += $mpFee;
            foreach ($orders as $ord) {
                $netVal = (float) $ord->getRawOriginal('net_amount');
                if ($ord->recon_status === 'RECONCILED') {
                    $netReleased += $netVal;
                } else {
                    $netReleased += max(0.0, $netVal);
                }
            }
        }

        // 2. Offline POS Sales (COMPLETED)
        $offSales = null;
        if ($channelCode === 'all' || $channelCode === 'offline') {
            $offQuery = OfflineSale::where('tenant_id', $tenantId)
                ->where('status', OfflineSale::STATUS_COMPLETED)
                ->whereBetween('sold_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

            if ($customerCat === 'dropship') {
                $offQuery->where(function($q) {
                    $q->where('is_dropship', true)
                      ->orWhereHas('customer', fn($cq) => $cq->where('category', 'dropship'));
                });
            } elseif ($customerCat === 'umum') {
                $offQuery->where('is_dropship', false);
            }

            $offSales = $offQuery->get();
            $totalOrders += $offSales->count();
            $offTotal = (float) $offSales->sum('grand_total');
            $grossRevenue += $offTotal;
        }

        // 3. Hitung Otomatis Total Refund / Retur
        $ordersInPeriod = Order::where('tenant_id', $tenantId)
            ->when(!empty($storeId), fn($q) => $q->where('store_id', $storeId))
            ->where(function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                  ->orWhereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            })
            ->get();

        $totalRefunds = 0.0;
        foreach ($ordersInPeriod as $ord) {
            $totalRefunds += $ord->refund_amount;
        }

        if ($netReleased == 0.0) {
            $netReleased = max(0.0, $grossRevenue - $totalRefunds - $marketplaceFee);
        }

        $totalHpp = 0.0;
        if ($channelCode !== 'offline' && $query) {
            $hppOrders = $query->with('items')->get();
            foreach ($hppOrders as $ord) {
                $omsetAmt = (float)$ord->total_amount;
                $isFullRefundOrReturn = in_array(strtoupper((string)$ord->order_status), ['RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'CANCELLED', 'BATAL', 'IN_CANCEL'])
                    || ($omsetAmt > 0 && (float)$ord->refund_amount >= $omsetAmt);
                $totalHpp += $isFullRefundOrReturn ? 0.0 : (float)$ord->hpp_total;
            }
        }
        if ($channelCode === 'all' || $channelCode === 'offline') {
            $totalHpp += (float) ($offSales ? $offSales->sum('hpp_total') : 0);
        }
        $marginRp = $netReleased - $totalHpp;
        $marginPct = $netReleased > 0 ? round(($marginRp / $netReleased) * 100, 2) : 0.0;

        return [
            'total_orders'    => $totalOrders,
            'gross_revenue'   => $grossRevenue,
            'total_refunds'   => $totalRefunds,
            'marketplace_fee' => $marketplaceFee,
            'net_released'    => $netReleased,
            'total_hpp'       => $totalHpp,
            'margin_rp'       => $marginRp,
            'margin_pct'      => $marginPct,
        ];
    }

    public function getSalesReportData($tenantId, $dateFrom, $dateTo, $categoryId = null, $brandId = null, $channelCode = 'all', $customerCat = 'all', $statusFilter = 'all', $search = null, $hideZeroSales = false, $isBundle = null, $isPo = null, $storeId = null, $dateType = 'order_date')
    {
        $allMasterProducts = MasterProduct::where('tenant_id', $tenantId)->with(['category', 'brand'])->get();
        $masterById = $allMasterProducts->keyBy('id');
        $masterBySku = $allMasterProducts->where('sku', '!=', '')->keyBy(fn($mp) => strtolower(trim($mp->sku)));

        $grouped = [];

        // 1. Fetch Offline Sales and aggregate
        if ($channelCode === 'all' || $channelCode === 'offline') {
            $offSalesQuery = OfflineSale::where('tenant_id', $tenantId)
                ->whereBetween('sold_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->with('items');

            $this->applyOfflineStatusFilter($offSalesQuery, $statusFilter);

            if ($customerCat === 'dropship') {
                $offSalesQuery->where(function($dq) {
                    $dq->where('is_dropship', true)
                      ->orWhereHas('customer', fn($cq) => $cq->where('category', 'dropship'));
                });
            } elseif ($customerCat === 'umum') {
                $offSalesQuery->where('is_dropship', false);
            }

            foreach ($offSalesQuery->get() as $sale) {
                if ($sale->items && $sale->items->count() > 0) {
                    foreach ($sale->items as $item) {
                        $mp = null;
                        if ($item->master_product_id && isset($masterById[$item->master_product_id])) {
                            $mp = $masterById[$item->master_product_id];
                        } elseif (!empty($item->sku) && isset($masterBySku[strtolower(trim($item->sku))])) {
                            $mp = $masterBySku[strtolower(trim($item->sku))];
                        }

                        $key = $mp ? ('mp_' . $mp->id) : ('sku_' . strtolower(trim($item->sku ?: ($item->product_name ?: 'unnamed'))));

                        if (!isset($grouped[$key])) {
                            $grouped[$key] = [
                                'master_product' => $mp,
                                'sku'            => $mp ? $mp->sku : ($item->sku ?: '—'),
                                'name'           => $mp ? $mp->name : ($item->product_name ?: 'Produk POS'),
                                'category_name'  => $mp && $mp->category ? $mp->category->name : '—',
                                'brand_name'     => $mp && $mp->brand ? $mp->brand->name : '—',
                                'stock'          => $mp ? (int)$mp->stock : 0,
                                'cost_price'     => $mp ? (float)($mp->cost_price ?? 0) : 0,
                                'qty_offline'    => 0,
                                'qty_online'     => 0,
                                'omset_offline'  => 0.0,
                                'omset_online'   => 0.0,
                                'category_id'    => $mp ? $mp->category_id : null,
                                'brand_id'       => $mp ? $mp->brand_id : null,
                                'is_bundle'      => $mp ? $mp->is_bundle : false,
                                'is_preorder'    => $mp ? $mp->is_preorder : false,
                            ];
                        }

                        $grouped[$key]['qty_offline']   += (int) $item->quantity;
                        $grouped[$key]['omset_offline'] += (float) $item->subtotal;
                    }
                } else {
                    $key = 'unassigned_off';
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'master_product' => null,
                            'sku'            => '—',
                            'name'           => 'Penjualan POS Offline (Tanpa Rincian Item)',
                            'category_name'  => '—',
                            'brand_name'     => '—',
                            'stock'          => 0,
                            'cost_price'     => 0,
                            'qty_offline'    => 0,
                            'qty_online'     => 0,
                            'omset_offline'  => 0.0,
                            'omset_online'   => 0.0,
                            'category_id'    => null,
                            'brand_id'       => null,
                            'is_bundle'      => false,
                            'is_preorder'    => false,
                        ];
                    }
                    $grouped[$key]['qty_offline']   += 1;
                    $grouped[$key]['omset_offline'] += (float) $sale->grand_total;
                }
            }
        }

        // 2. Fetch Online Orders and aggregate
        if ($channelCode === 'all' || $channelCode !== 'offline') {
            $ordersQuery = Order::where('tenant_id', $tenantId)->with('items');

            if ($dateType === 'completed_at') {
                $ordersQuery->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            } else {
                $ordersQuery->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            }

            $this->applyOnlineStatusFilter($ordersQuery, $statusFilter);

            if (!empty($storeId)) {
                $ordersQuery->where('store_id', $storeId);
            } elseif ($channelCode !== 'all' && $channelCode !== 'online') {
                $ordersQuery->whereHas('store.channel', function ($cq) use ($channelCode) {
                    $cq->where('code', strtolower($channelCode));
                });
            }

            $onlineOrders = $ordersQuery->get();
            foreach ($onlineOrders as $order) {
                if ($order->items && $order->items->count() > 0) {
                    $itemSum = (float) $order->items->sum(fn($i) => $i->total_price ?? ($i->unit_price * $i->quantity));
                    $scale = ($itemSum > 0 && abs($itemSum - $order->total_amount) > 1) ? ((float)$order->total_amount / $itemSum) : 1.0;

                    foreach ($order->items as $item) {
                        $mp = null;
                        if ($item->master_product_id && isset($masterById[$item->master_product_id])) {
                            $mp = $masterById[$item->master_product_id];
                        } elseif (!empty($item->sku) && isset($masterBySku[strtolower(trim($item->sku))])) {
                            $mp = $masterBySku[strtolower(trim($item->sku))];
                        }

                        $key = $mp ? ('mp_' . $mp->id) : ('sku_' . strtolower(trim($item->sku ?: ($item->product_name ?: 'unnamed'))));

                        if (!isset($grouped[$key])) {
                            $grouped[$key] = [
                                'master_product' => $mp,
                                'sku'            => $mp ? $mp->sku : ($item->sku ?: '—'),
                                'name'           => $mp ? $mp->name : ($item->product_name ?: 'Produk Marketplace'),
                                'category_name'  => $mp && $mp->category ? $mp->category->name : '—',
                                'brand_name'     => $mp && $mp->brand ? $mp->brand->name : '—',
                                'stock'          => $mp ? (int)$mp->stock : 0,
                                'cost_price'     => $mp ? (float)($mp->cost_price ?? 0) : 0,
                                'qty_offline'    => 0,
                                'qty_online'     => 0,
                                'omset_offline'  => 0.0,
                                'omset_online'   => 0.0,
                                'fee_platform'   => 0.0,
                                'fee_free_shipping' => 0.0,
                                'fee_service'    => 0.0,
                                'fee_promo'      => 0.0,
                                'fee_other'      => 0.0,
                                'total_fee'      => 0.0,
                                'category_id'    => $mp ? $mp->category_id : null,
                                'brand_id'       => $mp ? $mp->brand_id : null,
                                'is_bundle'      => $mp ? $mp->is_bundle : false,
                                'is_preorder'    => $mp ? $mp->is_preorder : false,
                            ];
                        }

                        $rawItemOmset = (float) ($item->total_price ?? ($item->unit_price * $item->quantity));
                        $scaledItemOmset = $rawItemOmset * $scale;

                        $grouped[$key]['qty_online']   += (int) $item->quantity;
                        $grouped[$key]['omset_online']  += $scaledItemOmset;

                        $fees = $order->fee_breakdown_details;
                        $orderFee = abs($fees['total_fee'] ?? $order->marketplace_fee ?? 0);
                        $itemShare = $order->total_amount > 0 ? ($scaledItemOmset / $order->total_amount) : 0;

                        $grouped[$key]['fee_platform']      = ($grouped[$key]['fee_platform'] ?? 0.0) + (abs($fees['platform_fee'] ?? 0) * $itemShare);
                        $grouped[$key]['fee_free_shipping'] = ($grouped[$key]['fee_free_shipping'] ?? 0.0) + (abs($fees['free_shipping'] ?? 0) * $itemShare);
                        $grouped[$key]['fee_service']       = ($grouped[$key]['fee_service'] ?? 0.0) + (abs($fees['service_fee'] ?? 0) * $itemShare);
                        $grouped[$key]['fee_promo']         = ($grouped[$key]['fee_promo'] ?? 0.0) + (abs($fees['promo_fee'] ?? 0) * $itemShare);
                        $grouped[$key]['fee_other']         = ($grouped[$key]['fee_other'] ?? 0.0) + (abs($fees['other_fee'] ?? 0) * $itemShare);
                        $grouped[$key]['total_fee']         = ($grouped[$key]['total_fee'] ?? 0.0) + ($orderFee * $itemShare);
                    }
                } else {
                    $key = 'unassigned_on';
                    if (!isset($grouped[$key])) {
                        $grouped[$key] = [
                            'master_product' => null,
                            'sku'            => '—',
                            'name'           => 'Penjualan Online Marketplace (Tanpa Rincian Item)',
                            'category_name'  => '—',
                            'brand_name'     => '—',
                            'stock'          => 0,
                            'cost_price'     => 0,
                            'qty_offline'    => 0,
                            'qty_online'     => 0,
                            'omset_offline'  => 0.0,
                            'omset_online'   => 0.0,
                            'fee_platform'   => 0.0,
                            'fee_free_shipping' => 0.0,
                            'fee_service'    => 0.0,
                            'fee_promo'      => 0.0,
                            'fee_other'      => 0.0,
                            'total_fee'      => 0.0,
                            'category_id'    => null,
                            'brand_id'       => null,
                            'is_bundle'      => false,
                            'is_preorder'    => false,
                        ];
                    }
                    $grouped[$key]['qty_online']   += 1;
                    $grouped[$key]['omset_online'] += (float) $order->total_amount;

                    $fees = $order->fee_breakdown_details;
                    $grouped[$key]['fee_platform']      = ($grouped[$key]['fee_platform'] ?? 0.0) + abs($fees['platform_fee'] ?? 0);
                    $grouped[$key]['fee_free_shipping'] = ($grouped[$key]['fee_free_shipping'] ?? 0.0) + abs($fees['free_shipping'] ?? 0);
                    $grouped[$key]['fee_service']       = ($grouped[$key]['fee_service'] ?? 0.0) + abs($fees['service_fee'] ?? 0);
                    $grouped[$key]['fee_promo']         = ($grouped[$key]['fee_promo'] ?? 0.0) + abs($fees['promo_fee'] ?? 0);
                    $grouped[$key]['fee_other']         = ($grouped[$key]['fee_other'] ?? 0.0) + abs($fees['other_fee'] ?? 0);
                    $grouped[$key]['total_fee']         = ($grouped[$key]['total_fee'] ?? 0.0) + abs($fees['total_fee'] ?? 0);
                }
            }
        }

        // 3. Apply Product Filters & Aggregate Totals
        $items = [];
        $grandTotalOmset = 0;
        $grandTotalQty = 0;
        $grandTotalHpp = 0;
        $grandTotalProfit = 0;
        $grandPlatformFee = 0;
        $grandFreeShippingFee = 0;
        $grandServiceFee = 0;
        $grandPromoFee = 0;
        $grandOtherFee = 0;
        $grandMarketplaceFee = 0;
        $grandNetReleased = 0;
        $grandTotalMarginRp = 0;

        foreach ($grouped as $row) {
            if (!empty($categoryId) && $row['category_id'] != $categoryId) continue;
            if (!empty($brandId) && $row['brand_id'] != $brandId) continue;
            if ($isBundle !== null && $isBundle !== '' && (bool)$row['is_bundle'] !== (bool)$isBundle) continue;
            if ($isPo !== null && $isPo !== '') {
                if ($isPo === '1' && !$row['is_preorder']) continue;
                if ($isPo === '0' && $row['is_preorder']) continue;
            }
            if (!empty($search)) {
                $sTerm = strtolower(trim($search));
                if (strpos(strtolower($row['name']), $sTerm) === false && strpos(strtolower($row['sku']), $sTerm) === false) {
                    continue;
                }
            }

            $qtyTotal = $row['qty_offline'] + $row['qty_online'];
            if ($qtyTotal <= 0) continue;

            $totalOmset = $row['omset_offline'] + $row['omset_online'];
            $costPrice = $row['cost_price'];
            $totalHpp = $qtyTotal * $costPrice;
            $grossProfit = $totalOmset - $totalHpp;
            $profitMargin = $totalOmset > 0 ? ($grossProfit / $totalOmset) * 100 : 0;

            $feePlatform = $row['fee_platform'] ?? 0;
            $feeFreeShipping = $row['fee_free_shipping'] ?? 0;
            $feeService = $row['fee_service'] ?? 0;
            $feePromo = $row['fee_promo'] ?? 0;
            $feeOther = $row['fee_other'] ?? 0;
            $totalFee = $row['total_fee'] ?? 0;
            $netReleased = max(0.0, $totalOmset - $totalFee);

            $marginRp = $netReleased - $totalHpp;
            $marginPct = $netReleased > 0 ? round(($marginRp / $netReleased) * 100, 2) : 0.0;

            $grandTotalQty += $qtyTotal;
            $grandTotalOmset += $totalOmset;
            $grandTotalHpp += $totalHpp;
            $grandTotalProfit += $grossProfit;
            $grandPlatformFee += $feePlatform;
            $grandFreeShippingFee += $feeFreeShipping;
            $grandServiceFee += $feeService;
            $grandPromoFee += $feePromo;
            $grandOtherFee += $feeOther;
            $grandMarketplaceFee += $totalFee;
            $grandNetReleased += $netReleased;
            $grandTotalMarginRp += $marginRp;

            $items[] = [
                'sku'               => $row['sku'],
                'name'              => $row['name'],
                'category_name'     => $row['category_name'],
                'brand_name'        => $row['brand_name'],
                'stock'             => $row['stock'],
                'qty_offline'       => $row['qty_offline'],
                'qty_online'        => $row['qty_online'],
                'qty_total'         => $qtyTotal,
                'cost_price'        => $costPrice,
                'total_omset'       => $totalOmset,
                'fee_platform'      => $feePlatform,
                'fee_free_shipping' => $feeFreeShipping,
                'fee_service'       => $feeService,
                'fee_promo'         => $feePromo,
                'fee_other'         => $feeOther,
                'total_fee'         => $totalFee,
                'net_released'      => $netReleased,
                'total_hpp'         => $totalHpp,
                'margin_rp'         => $marginRp,
                'margin_pct'        => $marginPct,
                'gross_profit'      => $grossProfit,
                'profit_margin'     => $profitMargin,
            ];
        }

        usort($items, fn($a, $b) => strcmp($a['name'], $b['name']));

        $overallMargin = $grandTotalOmset > 0 ? ($grandTotalProfit / $grandTotalOmset) * 100 : 0;
        $grandOverallMarginPct = $grandNetReleased > 0 ? round(($grandTotalMarginRp / $grandNetReleased) * 100, 2) : 0.0;

        return [
            'items'                 => $items,
            'grandTotalQty'         => $grandTotalQty,
            'grandTotalOmset'       => $grandTotalOmset,
            'grandTotalHpp'         => $grandTotalHpp,
            'grandTotalProfit'      => $grandTotalProfit,
            'overallMargin'         => $overallMargin,
            'grandPlatformFee'      => $grandPlatformFee,
            'grandFreeShippingFee'  => $grandFreeShippingFee,
            'grandServiceFee'       => $grandServiceFee,
            'grandPromoFee'         => $grandPromoFee,
            'grandOtherFee'         => $grandOtherFee,
            'grandMarketplaceFee'   => $grandMarketplaceFee,
            'grandTotalTotalFee'    => $grandMarketplaceFee,
            'grandNetReleased'      => $grandNetReleased,
            'grandTotalMarginRp'    => $grandTotalMarginRp,
            'grandOverallMarginPct' => $grandOverallMarginPct,
            'grandTotalRefund'      => 0,
        ];
    }

    public function getSalesReportPerChannelData($tenantId, $dateFrom, $dateTo, $channelCode = 'all', $customerCat = 'all', $statusFilter = 'all', $storeId = null, $dateType = 'order_date')
    {
        $channels = [];
        $grandTotalOmset = 0;
        $grandTotalQty = 0;
        $grandTotalOrders = 0;
        $grandPlatformFee = 0;
        $grandFreeShippingFee = 0;
        $grandServiceFee = 0;
        $grandPromoFee = 0;
        $grandOtherFee = 0;
        $grandMarketplaceFee = 0;
        $grandNetReleased = 0;
        $grandTotalRefund = 0;
        $grandTotalHpp = 0;
        $grandTotalMarginRp = 0;

        // POS Offline
        if (($channelCode === 'all' || $channelCode === 'offline') && empty($storeId)) {
            $offSalesQuery = OfflineSale::where('tenant_id', $tenantId)
                ->whereBetween('sold_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->with('items');

            $this->applyOfflineStatusFilter($offSalesQuery, $statusFilter);

            if ($customerCat === 'dropship') {
                $offSalesQuery->where(function($q) {
                    $q->where('is_dropship', true)
                      ->orWhereHas('customer', fn($cq) => $cq->where('category', 'dropship'));
                });
            } elseif ($customerCat === 'umum') {
                $offSalesQuery->where('is_dropship', false);
            }

            $offSalesGet = $offSalesQuery->get();
            $groupedOffline = $offSalesGet->groupBy(function($s) {
                return !empty(trim($s->institution_name ?? '')) ? trim($s->institution_name) : 'Toko Fisik / Umum';
            });

            foreach ($groupedOffline as $instName => $salesList) {
                $offOmset = (float) $salesList->sum('grand_total');
                $offOrders = $salesList->count();
                $offQty = 0;
                $offHpp = 0;
                foreach ($salesList as $s) {
                    $iQty = $s->items->sum('quantity');
                    $offQty += ($iQty > 0 ? $iQty : 1);
                    $offHpp += (float) ($s->hpp_total ?? 0);
                }

                $offMarginRp = $offOmset - $offHpp;
                $offMarginPct = $offOmset > 0 ? round(($offMarginRp / $offOmset) * 100, 2) : 0.0;

                $channels[] = [
                    'name' => 'Penjualan Offline (' . $instName . ')',
                    'type' => 'Offline',
                    'orders' => $offOrders,
                    'qty' => $offQty,
                    'omset' => $offOmset,
                    'refund' => 0,
                    'fee_platform' => 0,
                    'fee_free_shipping' => 0,
                    'fee_service' => 0,
                    'fee_promo' => 0,
                    'fee_other' => 0,
                    'total_fee' => 0,
                    'net_released' => $offOmset,
                    'hpp' => $offHpp,
                    'margin_rp' => $offMarginRp,
                    'margin_pct' => $offMarginPct,
                    'aov' => $offOrders > 0 ? $offOmset / $offOrders : 0,
                ];

                $grandTotalOmset += $offOmset;
                $grandTotalQty += $offQty;
                $grandTotalOrders += $offOrders;
                $grandNetReleased += $offOmset;
                $grandTotalHpp += $offHpp;
                $grandTotalMarginRp += $offMarginRp;
            }
        }

        // Marketplace Online Stores
        if ($channelCode !== 'offline') {
            $ordersQuery = Order::where('tenant_id', $tenantId)->with(['store.channel', 'items']);

            if ($dateType === 'completed_at') {
                $ordersQuery->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            } else {
                $ordersQuery->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            }

            $this->applyOnlineStatusFilter($ordersQuery, $statusFilter);

            if (!empty($storeId)) {
                $ordersQuery->where('store_id', $storeId);
            } elseif ($channelCode !== 'all' && $channelCode !== 'online') {
                $ordersQuery->whereHas('store.channel', fn($cq) => $cq->where('code', strtolower($channelCode)));
            }

            $allOnlineOrders = $ordersQuery->get();
            $groupedByStore = $allOnlineOrders->groupBy('store_id');

            foreach ($groupedByStore as $stId => $ordersGet) {
                $st = $ordersGet->first()->store ?? null;
                $storeName = $st ? $st->store_name . ' (' . ($st->channel->name ?? 'Online') . ')' : 'Marketplace (Lainnya)';
                $channelType = $st->channel->name ?? 'Online';

                $omset = (float) $ordersGet->sum('total_amount');
                $ordCount = $ordersGet->count();
                $qty = 0;
                $feePlatform = 0;
                $feeFreeShipping = 0;
                $feeService = 0;
                $feePromo = 0;
                $feeOther = 0;
                $totalFee = 0;
                $refundTotal = 0;
                $netReleased = 0;
                $channelHpp = 0;

                foreach ($ordersGet as $o) {
                    $iQty = $o->items->sum('quantity');
                    $qty += ($iQty > 0 ? $iQty : 1);

                    $refAmt = (float)$o->refund_amount;
                    $refundTotal += $refAmt;

                    $details = $o->fee_breakdown_details;
                    $feePlatform += abs($details['platform_fee'] ?? $o->fee_platform_amount ?? 0);
                    $feeFreeShipping += abs($details['free_shipping'] ?? $o->fee_free_shipping_amount ?? 0);
                    $feeService += abs($details['service_fee'] ?? $o->fee_service_amount ?? 0);
                    $feePromo += abs($details['promo_fee'] ?? $o->fee_promo_amount ?? 0);
                    $feeOther += abs($details['other_fee'] ?? $o->fee_other_amount ?? 0);

                    $oFee = (float)$o->marketplace_fee;
                    if ($oFee <= 0 && !empty($details['total_fee'])) {
                        $oFee = abs((float)$details['total_fee']);
                    }
                    $totalFee += $oFee;

                    $netReleased += (float)$o->net_amount;

                    $omsetAmt = (float)$o->total_amount;
                    $isFullRefundOrReturn = in_array(strtoupper((string)$o->order_status), ['RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'CANCELLED', 'BATAL', 'IN_CANCEL'])
                        || ($omsetAmt > 0 && $refAmt >= $omsetAmt);
                    $oHpp = $isFullRefundOrReturn ? 0.0 : (float)$o->hpp_total;
                    $channelHpp += $oHpp;
                }

                $chMarginRp = $netReleased - $channelHpp;
                $chMarginPct = $netReleased > 0 ? round(($chMarginRp / $netReleased) * 100, 2) : 0.0;

                $channels[] = [
                    'name' => $storeName,
                    'type' => $channelType,
                    'orders' => $ordCount,
                    'qty' => $qty,
                    'omset' => $omset,
                    'refund' => $refundTotal,
                    'fee_platform' => $feePlatform,
                    'fee_free_shipping' => $feeFreeShipping,
                    'fee_service' => $feeService,
                    'fee_promo' => $feePromo,
                    'fee_other' => $feeOther,
                    'total_fee' => $totalFee,
                    'net_released' => $netReleased,
                    'hpp' => $channelHpp,
                    'margin_rp' => $chMarginRp,
                    'margin_pct' => $chMarginPct,
                    'aov' => $ordCount > 0 ? $omset / $ordCount : 0,
                ];

                $grandTotalOmset += $omset;
                $grandTotalQty += $qty;
                $grandTotalOrders += $ordCount;
                $grandTotalRefund += $refundTotal;
                $grandPlatformFee += $feePlatform;
                $grandFreeShippingFee += $feeFreeShipping;
                $grandServiceFee += $feeService;
                $grandPromoFee += $feePromo;
                $grandOtherFee += $feeOther;
                $grandMarketplaceFee += $totalFee;
                $grandNetReleased += $netReleased;
                $grandTotalHpp += $channelHpp;
                $grandTotalMarginRp += $chMarginRp;
            }
        }

        $grandTotalTotalFee = $grandMarketplaceFee;
        $grandOverallMarginPct = $grandNetReleased > 0 ? round(($grandTotalMarginRp / $grandNetReleased) * 100, 2) : 0.0;

        return compact(
            'channels', 'grandTotalOmset', 'grandTotalQty', 'grandTotalOrders', 'grandTotalRefund',
            'grandPlatformFee', 'grandFreeShippingFee', 'grandServiceFee',
            'grandPromoFee', 'grandOtherFee', 'grandMarketplaceFee', 'grandTotalTotalFee', 'grandNetReleased',
            'grandTotalHpp', 'grandTotalMarginRp', 'grandOverallMarginPct'
        );
    }

    public function getSalesReportDetailData($tenantId, $dateFrom, $dateTo, $categoryId = null, $brandId = null, $channelCode = 'all', $customerCat = 'all', $statusFilter = 'all', $search = null, $isBundle = null, $isPo = null, $storeId = null, $dateType = 'order_date')
    {
        $transactions = [];

        // 1. Offline Sales
        if ($channelCode === 'all' || $channelCode === 'offline') {
            $offQuery = OfflineSale::where('tenant_id', $tenantId)
                ->whereBetween('sold_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59'])
                ->with(['customer', 'items.masterProduct']);

            $this->applyOfflineStatusFilter($offQuery, $statusFilter);

            if ($customerCat === 'dropship') {
                $offQuery->where(function($q) {
                    $q->where('is_dropship', true)
                      ->orWhereHas('customer', fn($cq) => $cq->where('category', 'dropship'));
                });
            } elseif ($customerCat === 'umum') {
                $offQuery->where('is_dropship', false);
            }

            foreach ($offQuery->get() as $s) {
                $itemSummary = [];
                foreach ($s->items as $it) {
                    $itemSummary[] = ($it->masterProduct->sku ?? $it->sku) . ' x' . $it->quantity;
                }

                $txDate = $s->sold_at ? $s->sold_at->format('Y-m-d H:i') : '—';
                $marginRp = (float)$s->grand_total - (float)$s->hpp_total;
                $margin1PctRp = $marginRp * 0.01;
                $marginPct = (float)$s->grand_total > 0 ? round(($marginRp / (float)$s->grand_total) * 100, 2) : 0.0;

                $transactions[] = [
                    'order_date' => $txDate,
                    'released_date' => $txDate,
                    'date' => $txDate,
                    'ref' => $s->sale_number,
                    'channel' => $s->institution_name ? $s->institution_name . ' (POS Offline)' : 'Toko Fisik (POS Offline)',
                    'customer' => $s->is_dropship ? ($s->dropshipper_name . ' (Dropship)') : ($s->buyer_name ?: ($s->customer->name ?? 'Pelanggan Umum')),
                    'customer_cat' => $s->is_dropship ? 'Dropship' : ($s->customer->category ?? 'Umum'),
                    'items_summary' => implode(', ', $itemSummary) ?: '—',
                    'total_qty' => max(1, $s->items->sum('quantity')),
                    'omset' => (float)$s->grand_total,
                    'refund' => 0,
                    'platform_fee' => 0,
                    'free_shipping_fee' => 0,
                    'service_fee' => 0,
                    'promo_fee' => 0,
                    'other_fee' => 0,
                    'total_fee' => 0,
                    'net_released' => (float)$s->grand_total,
                    'hpp' => (float)$s->hpp_total,
                    'margin_rp' => $marginRp,
                    'margin_1_pct_rp' => $margin1PctRp,
                    'margin_pct' => $marginPct,
                    'status' => ucfirst($s->status),
                ];
            }
        }

        // 2. Online Orders
        if ($channelCode === 'all' || $channelCode !== 'offline') {
            $onQuery = Order::where('tenant_id', $tenantId)->with(['store.channel', 'customer', 'items']);

            if ($dateType === 'completed_at') {
                $onQuery->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            } else {
                $onQuery->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            }

            $this->applyOnlineStatusFilter($onQuery, $statusFilter);

            if (!empty($storeId)) {
                $onQuery->where('store_id', $storeId);
            } elseif ($channelCode !== 'all' && $channelCode !== 'online') {
                $onQuery->whereHas('store.channel', fn($cq) => $cq->where('code', strtolower($channelCode)));
            }

            $onlineOrders = $onQuery->get();
            foreach ($onlineOrders as $o) {
                $itemSummary = [];
                foreach ($o->items as $it) {
                    $itemSummary[] = ($it->sku) . ' x' . $it->quantity;
                }

                $orderDate = $o->order_date ? date('Y-m-d H:i', strtotime($o->order_date)) : '—';
                $releasedDate = $o->completed_at ? $o->completed_at->format('Y-m-d H:i') : ($o->order_date ? date('Y-m-d H:i', strtotime($o->order_date)) : '—');
                $refundAmt = $o->refund_amount;

                if ($o->recon_status === 'RECONCILED') {
                    $absFee = abs((float) $o->getRawOriginal('marketplace_fee'));
                    $fees = [
                        'platform_fee'  => -(float) $o->getRawOriginal('fee_platform_amount'),
                        'free_shipping' => -(float) $o->getRawOriginal('fee_free_shipping_amount'),
                        'service_fee'   => -(float) $o->getRawOriginal('fee_service_amount'),
                        'promo_fee'     => -(float) $o->getRawOriginal('fee_promo_amount'),
                        'other_fee'     => -(float) $o->getRawOriginal('fee_other_amount'),
                        'total_fee'     => -(float) $o->getRawOriginal('marketplace_fee'),
                    ];
                } else {
                    $fees = $o->fee_breakdown_details;
                    $absFee = (float)$o->marketplace_fee;
                    if ($absFee <= 0 && !empty($fees['total_fee'])) {
                        $absFee = abs((float)$fees['total_fee']);
                    }
                }

                $netAmt = (float)$o->net_amount;
                $refVal = $o->order_marketplace_id ?: ($o->order_number ?: $o->invoice_number);
                $chCode = strtolower($o->store->channel->code ?? '');
                if (str_contains($chCode, 'tiktok') || ($o->store->channel_id ?? 0) == 3) {
                    if (!str_starts_with((string)$refVal, "'")) {
                        $refVal = "'" . $refVal;
                    }
                }

                $omsetAmt = (float)$o->total_amount;
                $isFullRefundOrReturn = in_array(strtoupper((string)$o->order_status), ['RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'CANCELLED', 'BATAL', 'IN_CANCEL'])
                    || ($omsetAmt > 0 && (float)$refundAmt >= $omsetAmt);
                $hpp = $isFullRefundOrReturn ? 0.0 : (float)$o->hpp_total;
                $marginRp = $netAmt - $hpp;
                $margin1PctRp = $marginRp * 0.01;
                $marginPct = $netAmt > 0 ? round(($marginRp / $netAmt) * 100, 2) : 0.0;

                $transactions[] = [
                    'order_date' => $orderDate,
                    'released_date' => $releasedDate,
                    'date' => $releasedDate,
                    'ref' => $refVal,
                    'channel' => ($o->store->store_name ?? '—') . ' (' . ($o->store->channel->name ?? 'Marketplace') . ')',
                    'customer' => $o->buyer_name ?: ($o->customer->name ?? 'Pelanggan MP'),
                    'customer_cat' => 'Marketplace',
                    'items_summary' => implode(', ', $itemSummary) ?: '—',
                    'total_qty' => max(1, $o->items->sum('quantity')),
                    'omset' => (float)$o->total_amount,
                    'refund' => $refundAmt,
                    'platform_fee' => $fees['platform_fee'] ?? 0,
                    'free_shipping_fee' => $fees['free_shipping'] ?? 0,
                    'service_fee' => $fees['service_fee'] ?? 0,
                    'promo_fee' => $fees['promo_fee'] ?? 0,
                    'other_fee' => $fees['other_fee'] ?? 0,
                    'total_fee' => $absFee,
                    'net_released' => $netAmt,
                    'hpp' => $hpp,
                    'margin_rp' => $marginRp,
                    'margin_1_pct_rp' => $margin1PctRp,
                    'margin_pct' => $marginPct,
                    'status' => $o->order_status,
                ];
            }
        }

        usort($transactions, fn($a, $b) => strcmp($b['date'], $a['date']));

        $grandTotalOmset = array_sum(array_column($transactions, 'omset'));
        $grandTotalQty = array_sum(array_column($transactions, 'total_qty'));
        $grandTotalRefund = array_sum(array_column($transactions, 'refund'));
        $grandTotalPlatformFee = array_sum(array_column($transactions, 'platform_fee'));
        $grandTotalFreeShipping = array_sum(array_column($transactions, 'free_shipping_fee'));
        $grandTotalServiceFee = array_sum(array_column($transactions, 'service_fee'));
        $grandTotalPromoFee = array_sum(array_column($transactions, 'promo_fee'));
        $grandTotalOtherFee = array_sum(array_column($transactions, 'other_fee'));
        $grandTotalTotalFee = array_sum(array_column($transactions, 'total_fee'));
        $grandTotalNetReleased = array_sum(array_column($transactions, 'net_released'));
        $grandTotalHpp = array_sum(array_column($transactions, 'hpp'));
        $grandTotalMarginRp = array_sum(array_column($transactions, 'margin_rp'));
        $grandTotalMargin1PctRp = array_sum(array_column($transactions, 'margin_1_pct_rp'));
        $grandOverallMarginPct = $grandTotalNetReleased > 0 ? round(($grandTotalMarginRp / $grandTotalNetReleased) * 100, 2) : 0.0;

        return compact(
            'transactions', 'grandTotalOmset', 'grandTotalQty', 'grandTotalRefund',
            'grandTotalPlatformFee', 'grandTotalFreeShipping', 'grandTotalServiceFee',
            'grandTotalPromoFee', 'grandTotalOtherFee', 'grandTotalTotalFee', 'grandTotalNetReleased',
            'grandTotalHpp', 'grandTotalMarginRp', 'grandTotalMargin1PctRp', 'grandOverallMarginPct'
        );
    }

    public function getSalesReportPerDateData($tenantId, $dateFrom, $dateTo, $channelCode = 'all', $customerCat = 'all', $statusFilter = 'all', $storeId = null, $dateType = 'order_date')
    {
        $dates = [];
        $current = strtotime($dateFrom);
        $last = strtotime($dateTo);

        $grandTotalOmset = 0;
        $grandTotalQty = 0;
        $grandPlatformFee = 0;
        $grandFreeShippingFee = 0;
        $grandServiceFee = 0;
        $grandPromoFee = 0;
        $grandOtherFee = 0;
        $grandMarketplaceFee = 0;
        $grandNetReleased = 0;
        $grandTotalRefund = 0;
        $grandTotalHpp = 0;
        $grandTotalMarginRp = 0;

        while ($current <= $last) {
            $dt = date('Y-m-d', $current);

            // POS Offline
            $offQty = 0; $offOmset = 0.0; $offHpp = 0.0;
            if (($channelCode === 'all' || $channelCode === 'offline') && empty($storeId)) {
                $offQuery = OfflineSale::where('tenant_id', $tenantId)
                    ->whereDate('sold_at', $dt)
                    ->with('items');

                $this->applyOfflineStatusFilter($offQuery, $statusFilter);

                if ($customerCat === 'dropship') {
                    $offQuery->where(function($q) {
                        $q->where('is_dropship', true)
                          ->orWhereHas('customer', fn($cq) => $cq->where('category', 'dropship'));
                    });
                } elseif ($customerCat === 'umum') {
                    $offQuery->where('is_dropship', false);
                }

                $offSales = $offQuery->get();
                $offOmset = (float) $offSales->sum('grand_total');
                foreach ($offSales as $s) {
                    $iQty = $s->items->sum('quantity');
                    $offQty += ($iQty > 0 ? $iQty : 1);
                    $offHpp += (float) ($s->hpp_total ?? 0);
                }
            }

            // Online Orders
            $onQty = 0; $onOmset = 0.0; $onPlatformFee = 0; $onFreeShippingFee = 0;
            $onServiceFee = 0; $onPromoFee = 0; $onOtherFee = 0; $onTotalFee = 0;
            $onNetReleased = 0; $onRefund = 0; $onHpp = 0;

            if ($channelCode !== 'offline') {
                $onQuery = Order::where('tenant_id', $tenantId)->with('items');

                if ($dateType === 'completed_at') {
                    $onQuery->whereDate('completed_at', $dt);
                } else {
                    $onQuery->whereDate('order_date', $dt);
                }

                $this->applyOnlineStatusFilter($onQuery, $statusFilter);

                if (!empty($storeId)) {
                    $onQuery->where('store_id', $storeId);
                } elseif ($channelCode !== 'all' && $channelCode !== 'online') {
                    $onQuery->whereHas('store.channel', fn($cq) => $cq->where('code', strtolower($channelCode)));
                }

                $onOrders = $onQuery->get();
                $onOmset = (float) $onOrders->sum('total_amount');

                foreach ($onOrders as $o) {
                    $iQty = $o->items->sum('quantity');
                    $onQty += ($iQty > 0 ? $iQty : 1);

                    $refAmt = (float)$o->refund_amount;
                    $onRefund += $refAmt;

                    $details = $o->fee_breakdown_details;
                    $onPlatformFee += abs($details['platform_fee'] ?? $o->fee_platform_amount ?? 0);
                    $onFreeShippingFee += abs($details['free_shipping'] ?? $o->fee_free_shipping_amount ?? 0);
                    $onServiceFee += abs($details['service_fee'] ?? $o->fee_service_amount ?? 0);
                    $onPromoFee += abs($details['promo_fee'] ?? $o->fee_promo_amount ?? 0);
                    $onOtherFee += abs($details['other_fee'] ?? $o->fee_other_amount ?? 0);

                    $oFee = (float)$o->marketplace_fee;
                    if ($oFee <= 0 && !empty($details['total_fee'])) {
                        $oFee = abs((float)$details['total_fee']);
                    }
                    $onTotalFee += $oFee;

                    $onNetReleased += (float)$o->net_amount;

                    $omsetAmt = (float)$o->total_amount;
                    $isFullRefundOrReturn = in_array(strtoupper((string)$o->order_status), ['RETURNED', 'REFUNDED', 'RETURN', 'RETUR', 'CANCELLED', 'BATAL', 'IN_CANCEL'])
                        || ($omsetAmt > 0 && $refAmt >= $omsetAmt);
                    $oHpp = $isFullRefundOrReturn ? 0.0 : (float)$o->hpp_total;
                    $onHpp += $oHpp;
                }
            }

            $tQty = $offQty + $onQty;
            $tOmset = $offOmset + $onOmset;
            $tNetReleased = $offOmset + $onNetReleased;
            $tHpp = $offHpp + $onHpp;
            $dateMarginRp = $tNetReleased - $tHpp;
            $dateMarginPct = $tNetReleased > 0 ? round(($dateMarginRp / $tNetReleased) * 100, 2) : 0.0;

            if ($tQty > 0 || $tOmset > 0) {
                $dates[] = [
                    'date' => $dt,
                    'qty_offline' => $offQty,
                    'omset_offline' => $offOmset,
                    'qty_online' => $onQty,
                    'omset_online' => $onOmset,
                    'total_qty' => $tQty,
                    'total_omset' => $tOmset,
                    'refund' => $onRefund,
                    'fee_platform' => $onPlatformFee,
                    'fee_free_shipping' => $onFreeShippingFee,
                    'fee_service' => $onServiceFee,
                    'fee_promo' => $onPromoFee,
                    'fee_other' => $onOtherFee,
                    'total_fee' => $onTotalFee,
                    'net_released' => $tNetReleased,
                    'hpp' => $tHpp,
                    'margin_rp' => $dateMarginRp,
                    'margin_pct' => $dateMarginPct,
                ];
                $grandTotalQty += $tQty;
                $grandTotalOmset += $tOmset;
                $grandTotalRefund += $onRefund;
                $grandPlatformFee += $onPlatformFee;
                $grandFreeShippingFee += $onFreeShippingFee;
                $grandServiceFee += $onServiceFee;
                $grandPromoFee += $onPromoFee;
                $grandOtherFee += $onOtherFee;
                $grandMarketplaceFee += $onTotalFee;
                $grandNetReleased += $tNetReleased;
                $grandTotalHpp += $tHpp;
                $grandTotalMarginRp += $dateMarginRp;
            }

            $current = strtotime('+1 day', $current);
        }

        $grandTotalTotalFee = $grandMarketplaceFee;
        $grandOverallMarginPct = $grandNetReleased > 0 ? round(($grandTotalMarginRp / $grandNetReleased) * 100, 2) : 0.0;

        return compact(
            'dates', 'grandTotalQty', 'grandTotalOmset', 'grandTotalRefund',
            'grandPlatformFee', 'grandFreeShippingFee', 'grandServiceFee',
            'grandPromoFee', 'grandOtherFee', 'grandMarketplaceFee', 'grandTotalTotalFee', 'grandNetReleased',
            'grandTotalHpp', 'grandTotalMarginRp', 'grandOverallMarginPct'
        );
    }

    public function getSalesReportPerCustomerCategoryData($tenantId, $dateFrom, $dateTo, $channelCode = 'all', $statusFilter = 'all')
    {
        $categoriesData = [];
        $categoriesList = ['dropship' => 'Pelanggan Dropship', 'umum' => 'Pelanggan Umum', 'biasa' => 'Pelanggan Biasa', 'marketplace' => 'Pelanggan Marketplace'];

        $grandTotalOmset = 0;
        $grandTotalQty = 0;
        $grandTotalOrders = 0;

        foreach ($categoriesList as $catKey => $catLabel) {
            $qty = 0; $omset = 0.0; $ordersCount = 0;

            if ($catKey === 'marketplace') {
                $ordersQuery = Order::where('tenant_id', $tenantId)
                    ->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                $this->applyOnlineStatusFilter($ordersQuery, $statusFilter);
                if ($channelCode !== 'all' && $channelCode !== 'online') {
                    $ordersQuery->whereHas('store.channel', fn($cq) => $cq->where('code', strtolower($channelCode)));
                }

                $orders = $ordersQuery->get();
                $ordersCount = $orders->count();
                $omset = (float) $orders->sum('total_amount');
                foreach ($orders as $o) {
                    $qty += $o->items()->sum('quantity');
                }
            } else {
                $offSalesQuery = OfflineSale::where('tenant_id', $tenantId)
                    ->whereBetween('sold_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
                $this->applyOfflineStatusFilter($offSalesQuery, $statusFilter);

                if ($catKey === 'dropship') {
                    $offSalesQuery->where(function($q) {
                        $q->where('is_dropship', true)
                          ->orWhereHas('customer', fn($cq) => $cq->where('category', 'dropship'));
                    });
                } else {
                    $offSalesQuery->where('is_dropship', false)
                                 ->whereHas('customer', fn($cq) => $cq->where('category', $catKey));
                }

                $offGet = $offSalesQuery->get();
                $ordersCount = $offGet->count();
                $omset = (float) $offGet->sum('grand_total');
                foreach ($offGet as $s) {
                    $qty += $s->items()->sum('quantity');
                }
            }

            $categoriesData[] = [
                'category_key' => $catKey,
                'category_label' => $catLabel,
                'orders_count' => $ordersCount,
                'qty_sold' => $qty,
                'total_omset' => $omset,
            ];

            $grandTotalOmset += $omset;
            $grandTotalQty += $qty;
            $grandTotalOrders += $ordersCount;
        }

        return compact('categoriesData', 'grandTotalOmset', 'grandTotalQty', 'grandTotalOrders');
    }

    public function getIncomeStatementData($tenantId, $dateFrom, $dateTo, $channelCode = 'online', $customerCat = 'all', $statusFilter = 'completed', $storeId = null, $dateType = 'order_date')
    {
        $detailData = $this->getSalesReportDetailData($tenantId, $dateFrom, $dateTo, null, null, $channelCode, $customerCat, $statusFilter, null, null, null, $storeId, $dateType);

        $grossSales = (float) $detailData['grandTotalOmset'];
        
        $refundQuery = ReturnOrder::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

        if (!empty($storeId)) {
            $refundQuery->where('store_id', $storeId);
        }

        $refunds = (float) $refundQuery->sum('refund_amount');

        if ($refunds == 0) {
            $retQuery = Order::where('tenant_id', $tenantId)
                ->whereIn('order_status', ['RETURNED', 'REFUNDED', 'RETURN']);

            if ($dateType === 'completed_at') {
                $retQuery->whereBetween('completed_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            } else {
                $retQuery->whereBetween('order_date', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);
            }

            if (!empty($storeId)) {
                $retQuery->where('store_id', $storeId);
            }
            $refunds = (float) $retQuery->sum('total_amount');
        }

        $subtotalPesanan = max(0.0, $grossSales - $refunds);
        $vouchers = 0.0;

        $platformFee = (float) $detailData['grandTotalPlatformFee'];
        $freeShippingFee = (float) $detailData['grandTotalFreeShipping'];
        $serviceFee = (float) $detailData['grandTotalServiceFee'];
        $promoFee = (float) $detailData['grandTotalPromoFee'];
        $otherFee = (float) $detailData['grandTotalOtherFee'];
        $grandTotalFee = (float) ($detailData['grandTotalTotalFee'] ?? 0);
        $tax = 0.0;

        if ($grandTotalFee > 0) {
            $totalPengeluaran = $grandTotalFee;
        } else {
            $totalPengeluaran = abs($platformFee) + abs($freeShippingFee) + abs($serviceFee) + abs($promoFee) + abs($otherFee) + $tax;
        }
        $totalPendapatan = $subtotalPesanan + $vouchers;
        $grandTotalNetReleased = (float) ($detailData['grandTotalNetReleased'] ?? 0);
        if ($grandTotalNetReleased != 0.0) {
            $totalDilepas = $grandTotalNetReleased;
        } else {
            $totalDilepas = $totalPendapatan - abs($totalPengeluaran);
        }

        $grandTotalHpp = (float) ($detailData['grandTotalHpp'] ?? 0);
        $grandTotalMarginRp = (float) ($detailData['grandTotalMarginRp'] ?? 0);
        $grandOverallMarginPct = (float) ($detailData['grandOverallMarginPct'] ?? 0);

        return compact(
            'grossSales', 'refunds', 'subtotalPesanan', 'vouchers', 'totalPendapatan',
            'platformFee', 'freeShippingFee', 'serviceFee', 'promoFee', 'otherFee', 'tax',
            'totalPengeluaran', 'totalDilepas', 'grandTotalHpp', 'grandTotalMarginRp', 'grandOverallMarginPct'
        );
    }

    public function applyOfflineStatusFilter($query, $statusFilter)
    {
        if ($statusFilter === 'completed') {
            $query->where('status', 'completed');
        } elseif ($statusFilter === 'processing') {
            $query->where('status', 'pending_approval');
        } elseif ($statusFilter === 'pending') {
            $query->where('status', 'pending');
        } elseif ($statusFilter === 'cancelled') {
            $query->where('status', 'cancelled');
        } elseif ($statusFilter === 'shipped' || $statusFilter === 'returned') {
            $query->whereRaw('1 = 0');
        } else {
            $query->where('status', '!=', OfflineSale::STATUS_CANCELLED);
        }
    }

    public function applyOnlineStatusFilter($query, $statusFilter)
    {
        if ($statusFilter === 'completed') {
            $query->whereIn('order_status', ['COMPLETED', 'SELESAI', 'FINISHED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR']);
        } elseif ($statusFilter === 'shipped') {
            $query->whereIn('order_status', ['SHIPPED', 'IN_TRANSIT', 'DIKIRIM']);
        } elseif ($statusFilter === 'processing') {
            $query->whereIn('order_status', ['READY_TO_SHIP', 'PROCESSING', 'PROCESSED', 'PENDING_APPROVAL']);
        } elseif ($statusFilter === 'pending') {
            $query->whereIn('order_status', ['UNPAID', 'PENDING']);
        } elseif ($statusFilter === 'cancelled') {
            $query->whereIn('order_status', ['CANCELLED', 'BATAL']);
        } elseif ($statusFilter === 'returned') {
            $query->whereIn('order_status', ['RETURNED', 'REFUNDED', 'RETURN']);
        } else {
            $query->whereNotIn('order_status', ['CANCELLED', 'RETURNED', 'RETURN', 'BATAL']);
        }
    }
}
