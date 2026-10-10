<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Models\MarketingTeam;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MarketingTeamController extends Controller
{
    /**
     * Tampilkan daftar Tim Marketing & Targetnya
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Ambil daftar tahun unik dari data tim & orders milik tenant untuk dropdown option
        $currentYear = (int) date('Y');
        $teamYears = MarketingTeam::forTenant($tenantId)
            ->whereNotNull('period_year')
            ->pluck('period_year')
            ->toArray();
        $orderYears = DB::table('orders')
            ->where('tenant_id', $tenantId)
            ->whereNotNull('order_date')
            ->selectRaw('YEAR(order_date) as yr')
            ->pluck('yr')
            ->toArray();

        $availableYears = array_values(array_unique(array_filter(array_merge([$currentYear], $teamYears, $orderYears))));
        rsort($availableYears);

        // Deteksi filter Bulan & Tahun (opsional untuk menyaring tim berdasarkan periode targetnya)
        $hasExplicitMonthYear = $request->filled('month') || $request->filled('year');
        $reqMonth = $request->filled('month') ? (int) $request->month : null;
        $reqYear  = $request->filled('year') ? (int) $request->year : null;

        // Query teams — jika filter bulan/tahun dipilih, filter tim yang period_month & period_year sesuai
        $teams = MarketingTeam::forTenant($tenantId)
            ->with(['stores.channel'])
            ->when($request->filled('search'), function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('description', 'like', '%' . $request->search . '%');
            })
            ->when($reqMonth, function ($q) use ($reqMonth) {
                $q->where('period_month', $reqMonth);
            })
            ->when($reqYear, function ($q) use ($reqYear) {
                $q->where('period_year', $reqYear);
            })
            ->orderBy('id', 'desc')
            ->get();

        // Hitung nilai aktual per tim berdasarkan tanggal dana cair terkunci yang tersimpan di DB
        foreach ($teams as $team) {
            $metrics = $team->calculateActualMetrics();
            $team->custom_actual_qty    = $metrics['qty'];
            $team->custom_actual_value  = $metrics['value'];
            $team->custom_actual_omset  = $metrics['omset'];
            $team->custom_actual_hpp    = $metrics['hpp'];
            $team->custom_actual_margin = $metrics['margin'];
            $team->custom_total_reward  = $team->total_reward;
            $team->custom_progress_percent = $team->value_progress_percent;
        }

        // Ambil daftar seluruh Toko milik tenant
        $stores = Store::where('tenant_id', $tenantId)
            ->with('channel')
            ->orderBy('store_name')
            ->get();

        // Ambil daftar seluruh Master Product untuk modal pengecualian komisi
        $masterProducts = \App\Models\MasterProduct::where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'exclude_commission']);

        // Summary KPI Metrics
        $totalTeams = $teams->count();
        $activeTeams = $teams->where('is_active', true)->count();
        $totalStoresLinked = $teams->pluck('stores')->flatten()->unique('id')->count();
        $totalTargetQty = $teams->where('is_active', true)->sum('target_qty');
        $totalTargetMargin = $teams->where('is_active', true)->sum('target_omset');
        $totalTargetValue = $totalTargetMargin;
        $totalActualQty = $teams->where('is_active', true)->sum('custom_actual_qty');
        $totalActualMargin = $teams->where('is_active', true)->sum('custom_actual_margin');
        $totalActualValue = $teams->where('is_active', true)->sum('custom_actual_value');
        $totalActualHpp = $teams->where('is_active', true)->sum('custom_actual_hpp');
        $totalEarnedReward = $teams->where('is_active', true)->sum('custom_total_reward');

        return view('marketing.teams.index', compact(
            'teams',
            'stores',
            'totalTeams',
            'activeTeams',
            'totalStoresLinked',
            'totalTargetQty',
            'totalTargetMargin',
            'totalTargetValue',
            'totalActualQty',
            'totalActualMargin',
            'totalActualValue',
            'totalActualHpp',
            'totalEarnedReward',
            'reqMonth',
            'reqYear',
            'availableYears',
            'masterProducts',
            'hasExplicitMonthYear'
        ));
    }

    /**
     * Simpan Tim Marketing baru & Targetnya
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'target_omset' => 'required|numeric|min:0',
            'commission_type' => 'nullable|string|in:percentage,nominal,qty',
            'commission_rate' => 'nullable|numeric|min:0',
            'reward_fixed_nominal' => 'nullable|numeric|min:0',
            'target_qty' => 'nullable|numeric|min:0',
            'reward_per_qty' => 'nullable|numeric|min:0',
            'period_month' => 'nullable|integer|between:1,12',
            'period_year' => 'nullable|integer|min:2020|max:2099',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
            'description' => 'nullable|string',
        ]);

        $tenantId = Auth::user()->tenant_id;

        $pMonth = $request->period_month ?? (int) date('n');
        $pYear  = $request->period_year ?? (int) date('Y');

        $dateFrom = $request->date_from;
        $dateTo   = $request->date_to;

        // Auto-generate date_from & date_to jika user tidak mengisi manual
        if (empty($dateFrom) || empty($dateTo)) {
            $dateFrom = sprintf('%04d-%02d-01', $pYear, $pMonth);
            $dateTo   = date('Y-m-t', strtotime($dateFrom));
        }

        $team = MarketingTeam::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'code' => $request->code ?? null,
            'description' => $request->description,
            'target_omset' => $request->target_omset ?? 0,
            'commission_type' => $request->commission_type ?: 'percentage',
            'commission_rate' => $request->commission_rate ?? 0,
            'reward_fixed_nominal' => $request->reward_fixed_nominal ?? 0,
            'target_qty' => $request->target_qty ?? 0,
            'reward_per_qty' => $request->reward_per_qty ?? 0,
            'period_month' => $pMonth,
            'period_year' => $pYear,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : true,
        ]);

        if ($request->has('store_ids')) {
            $team->stores()->sync($request->store_ids);
        }

        return redirect()->route('marketing.teams.index')
            ->with('success', "Tim Marketing '{$team->name}' & Target Nilai Rp " . number_format($team->target_omset, 0, ',', '.') . " berhasil dibuat!");
    }

    /**
     * Update data Tim Marketing & Targetnya
     */
    public function update(Request $request, MarketingTeam $marketingTeam)
    {
        abort_unless($marketingTeam->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'target_omset' => 'required|numeric|min:0',
            'commission_type' => 'nullable|string|in:percentage,nominal,qty',
            'commission_rate' => 'nullable|numeric|min:0',
            'reward_fixed_nominal' => 'nullable|numeric|min:0',
            'target_qty' => 'nullable|numeric|min:0',
            'reward_per_qty' => 'nullable|numeric|min:0',
            'period_month' => 'nullable|integer|between:1,12',
            'period_year' => 'nullable|integer|min:2020|max:2099',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'store_ids' => 'nullable|array',
            'store_ids.*' => 'exists:stores,id',
            'description' => 'nullable|string',
        ]);

        $pMonth = $request->period_month ?? $marketingTeam->period_month ?? (int) date('n');
        $pYear  = $request->period_year ?? $marketingTeam->period_year ?? (int) date('Y');

        $dateFrom = $request->date_from;
        $dateTo   = $request->date_to;

        if (empty($dateFrom) || empty($dateTo)) {
            $dateFrom = sprintf('%04d-%02d-01', $pYear, $pMonth);
            $dateTo   = date('Y-m-t', strtotime($dateFrom));
        }

        $marketingTeam->update([
            'name' => $request->name,
            'code' => $request->code ?? $marketingTeam->code,
            'description' => $request->description,
            'target_omset' => $request->target_omset ?? 0,
            'commission_type' => $request->commission_type ?: 'percentage',
            'commission_rate' => $request->commission_rate ?? 0,
            'reward_fixed_nominal' => $request->reward_fixed_nominal ?? 0,
            'target_qty' => $request->target_qty ?? 0,
            'reward_per_qty' => $request->reward_per_qty ?? 0,
            'period_month' => $pMonth,
            'period_year' => $pYear,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'is_active' => $request->has('is_active') ? (bool) $request->is_active : false,
        ]);

        $marketingTeam->stores()->sync($request->input('store_ids', []));

        return redirect()->route('marketing.teams.index')
            ->with('success', "Data Tim Marketing '{$marketingTeam->name}' berhasil diperbarui!");
    }

    /**
     * Hapus Tim Marketing
     */
    public function destroy(MarketingTeam $marketingTeam)
    {
        abort_unless($marketingTeam->tenant_id === Auth::user()->tenant_id, 403);

        $name = $marketingTeam->name;
        $marketingTeam->delete();

        return redirect()->route('marketing.teams.index')
            ->with('success', "Tim Marketing '{$name}' berhasil dihapus.");
    }

    /**
     * Toggle Status Aktif Tim Marketing
     */
    public function toggleStatus(MarketingTeam $marketingTeam)
    {
        abort_unless($marketingTeam->tenant_id === Auth::user()->tenant_id, 403);

        $marketingTeam->is_active = !$marketingTeam->is_active;
        $marketingTeam->save();

        $statusStr = $marketingTeam->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return redirect()->route('marketing.teams.index')
            ->with('success', "Status Tim '{$marketingTeam->name}' berhasil {$statusStr}.");
    }

    /**
     * Tampilkan detail transaksi (order) untuk tim marketing berdasarkan filter yang aktif
     */
    public function transactions(Request $request, MarketingTeam $marketingTeam)
    {
        abort_unless($marketingTeam->tenant_id === Auth::user()->tenant_id, 403);

        $storeIds = $marketingTeam->stores->pluck('id')->toArray();
        $rewardPerQty = $marketingTeam->reward_per_qty;

        if (empty($storeIds)) {
            $orders = collect();
            $totalQty = 0;
            $totalOmset = 0.0;
            $totalEarnedReward = 0.0;
            $reqMonth = null;
            $reqYear  = null;
            $dateFrom = null;
            $dateTo   = null;
        } else {
            $validStatuses = [
                'COMPLETED', 'RELEASED', 'COMPLETED_ESCROW', 'SELESAI', 'DELIVERED', 'FINISHED',
                'completed', 'released', 'selesai', 'delivered', 'finished'
            ];
            $invalidStatuses = [
                'CANCELLED', 'CANCELED', 'BATAL', 'RETURNED', 'REFUNDED', 'RETUR', 'IN_CANCEL', 'FAILED',
                'cancelled', 'canceled', 'batal', 'returned', 'refunded'
            ];

            // Resolve filters:
            // Prioritas:
            // 1. Parameter eksplisit di request (date_from & date_to)
            // 2. Parameter eksplisit di request (month & year)
            // 3. Tanggal acuan tersimpan di data tim ($marketingTeam->date_from & date_to)
            // 4. Bulan & Tahun tim / default bulan ini
            $hasReqDateRange = $request->filled('date_from') && $request->filled('date_to');
            $hasReqMonthYear = $request->filled('month') || $request->filled('year');

            if ($hasReqDateRange) {
                $dateFrom = $request->date_from;
                $dateTo   = $request->date_to;
                $reqMonth = null;
                $reqYear  = null;
                $useMonthYear = false;
            } elseif ($hasReqMonthYear) {
                $reqMonth = $request->filled('month') ? (int) $request->month : null;
                $reqYear  = $request->filled('year') ? (int) $request->year : null;
                $dateFrom = null;
                $dateTo   = null;
                $useMonthYear = true;
            } elseif ($marketingTeam->date_from && $marketingTeam->date_to) {
                $dateFrom = $marketingTeam->date_from instanceof \Carbon\Carbon ? $marketingTeam->date_from->format('Y-m-d') : (string) $marketingTeam->date_from;
                $dateTo   = $marketingTeam->date_to instanceof \Carbon\Carbon ? $marketingTeam->date_to->format('Y-m-d') : (string) $marketingTeam->date_to;
                $reqMonth = null;
                $reqYear  = null;
                $useMonthYear = false;
            } else {
                $dateFrom = date('Y-m-01');
                $dateTo   = date('Y-m-d');
                $reqMonth = null;
                $reqYear  = null;
                $useMonthYear = false;
            }

            // Base query for orders
            $query = \App\Models\Order::whereIn('store_id', $storeIds)
                ->whereIn('order_status', $validStatuses)
                ->whereNotIn('order_status', $invalidStatuses)
                ->with(['store.channel', 'items.masterProduct', 'items.marketplaceProduct.masterProduct', 'returnOrder.items']);

            if ($useMonthYear) {
                if ($reqYear) {
                    $query->whereYear(DB::raw('COALESCE(completed_at, updated_at, order_date)'), $reqYear);
                }
                if ($reqMonth) {
                    $query->whereMonth(DB::raw('COALESCE(completed_at, updated_at, order_date)'), $reqMonth);
                }
            } else {
                $from = $dateFrom . ' 00:00:00';
                $to = $dateTo . ' 23:59:59';
                $query->whereBetween(DB::raw('COALESCE(completed_at, updated_at, order_date)'), [$from, $to]);
            }

            // Get all records without pagination
            $orders = $query->orderBy(DB::raw('COALESCE(completed_at, order_date)'), 'desc')
                            ->get();

            // Filter out only fully returned/refunded orders
            $orders = $orders->filter(function ($order) {
                if ($order->refund_amount >= $order->total_amount && $order->total_amount > 0) {
                    return false;
                }
                return true;
            });

            // Summary metrics
            $totalQty = 0;
            $totalOmset = 0.0;
            $totalValue = 0.0;
            $totalHpp = 0.0;
            $totalMargin = 0.0;
            $totalEarnedReward = 0.0;

            $commType = $marketingTeam->commission_type ?: 'percentage';
            $commRate = (float) ($marketingTeam->commission_rate ?? 0);

            foreach ($orders as $order) {
                $orderQty = 0;
                $totalOrderItemsQty = $order->items->sum('quantity');
                $orderEligibleHpp = 0.0;

                foreach ($order->items as $item) {
                    $isExcluded = false;
                    if ($item->masterProduct && $item->masterProduct->exclude_commission) {
                        $isExcluded = true;
                    } elseif ($item->marketplaceProduct && $item->marketplaceProduct->masterProduct && $item->marketplaceProduct->masterProduct->exclude_commission) {
                        $isExcluded = true;
                    }

                    if (!$isExcluded) {
                        $returnedQty = 0;
                        if ($order->returnOrder) {
                            $returnedQty = $order->returnOrder->items
                                ->where('order_item_id', $item->id)
                                ->sum('quantity');
                        }
                        
                        if ($returnedQty == 0 && $order->refund_amount > 0 && $order->total_amount > 0) {
                            if ($order->refund_amount >= $order->total_amount) {
                                $returnedQty = $item->quantity;
                            } else {
                                $ratio = (float)$order->refund_amount / (float)$order->total_amount;
                                $returnedQty = min($item->quantity, (int) round($item->quantity * $ratio));
                            }
                        }
                        
                        $itemNetQty = max(0, $item->quantity - $returnedQty);
                        $orderQty += $itemNetQty;

                        // HPP unit
                        $unitHpp = 0.0;
                        if ($item->hpp_subtotal > 0 && $item->quantity > 0) {
                            $unitHpp = (float)$item->hpp_subtotal / (float)$item->quantity;
                        } elseif ($item->cost_price > 0) {
                            $unitHpp = (float)$item->cost_price;
                        } elseif ($item->masterProduct && $item->masterProduct->cost_price > 0) {
                            $unitHpp = (float)$item->masterProduct->cost_price;
                        } elseif ($item->marketplaceProduct && $item->marketplaceProduct->masterProduct && $item->marketplaceProduct->masterProduct->cost_price > 0) {
                            $unitHpp = (float)$item->marketplaceProduct->masterProduct->cost_price;
                        }

                        $orderEligibleHpp += ($unitHpp * $itemNetQty);
                    }
                }
                
                $totalQty += $orderQty;
                $effectiveOmset = (float) $order->total_amount - (float) $order->refund_amount;
                $totalOmset += max(0.0, $effectiveOmset);

                // Hitung Value Penjualan yang Dilepas (Net Released / Omset Bersih)
                $orderReleased = (float) $order->net_amount;
                if ($orderReleased <= 0) {
                    $orderReleased = max(0.0, (float) $order->total_amount - (float) $order->refund_amount - (float) $order->marketplace_fee);
                    if ($orderReleased <= 0) {
                        $orderReleased = max(0.0, (float) $order->total_amount - (float) $order->refund_amount);
                    }
                }

                // Proporsionalitas jika ada item yang di-exclude komisi
                if ($totalOrderItemsQty > 0 && $orderQty < $totalOrderItemsQty) {
                    $ratio = max(0.0, min(1.0, $orderQty / $totalOrderItemsQty));
                    $orderReleased = $orderReleased * $ratio;
                }

                $orderReleased = max(0.0, round($orderReleased, 2));
                $orderHpp = round($orderEligibleHpp, 2);
                $orderMargin = round($orderReleased - $orderHpp, 2);

                $order->calculated_released_value = $orderReleased;
                $order->calculated_hpp = $orderHpp;
                $order->calculated_margin = $orderMargin;

                // Hitung komisi pesanan ini berdasarkan Margin (Rp)
                if ($commType === 'percentage') {
                    $orderComm = round(max(0.0, $orderMargin) * ($commRate / 100), 2);
                } elseif ($commType === 'nominal') {
                    $orderComm = 0.0;
                } else {
                    $orderComm = $orderQty * $rewardPerQty;
                }

                $order->calculated_commission = $orderComm;

                $totalValue += $orderReleased;
                $totalHpp += $orderHpp;
                $totalMargin += $orderMargin;
                $totalEarnedReward += $orderComm;
            }

            // Jika tipe nominal flat bonus saat mencapai target margin
            if ($commType === 'nominal') {
                if ($marketingTeam->target_omset > 0 && $totalMargin >= $marketingTeam->target_omset) {
                    $totalEarnedReward = (float) $marketingTeam->reward_fixed_nominal;
                } else {
                    $totalEarnedReward = 0.0;
                }
            } elseif ($commType === 'percentage') {
                if ($marketingTeam->target_omset > 0 && $totalMargin >= $marketingTeam->target_omset && $marketingTeam->reward_fixed_nominal > 0) {
                    $totalEarnedReward += (float) $marketingTeam->reward_fixed_nominal;
                }
            }
        }

        return view('marketing.teams.transactions', compact(
            'marketingTeam',
            'orders',
            'totalQty',
            'totalOmset',
            'totalValue',
            'totalHpp',
            'totalMargin',
            'totalEarnedReward',
            'rewardPerQty',
            'reqMonth',
            'reqYear',
            'dateFrom',
            'dateTo'
        ));
    }

    public function updateExcludedProducts(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        \Illuminate\Support\Facades\Log::info('[updateExcludedProducts] Input received:', [
            'tenant_id' => $tenantId,
            'input' => $request->all(),
        ]);

        $request->validate([
            'excluded_product_ids' => 'nullable|array',
            'excluded_product_ids.*' => 'exists:master_products,id',
        ]);

        $excludedIds = $request->input('excluded_product_ids', []);

        // 1. Reset semua produk milik tenant ini agar exclude_commission = false
        $resetCount = \App\Models\MasterProduct::where('tenant_id', $tenantId)
            ->update(['exclude_commission' => false]);

        // 2. Set produk yang dipilih menjadi exclude_commission = true
        $updateCount = 0;
        if (!empty($excludedIds)) {
            $updateCount = \App\Models\MasterProduct::where('tenant_id', $tenantId)
                ->whereIn('id', $excludedIds)
                ->update(['exclude_commission' => true]);
        }

        \Illuminate\Support\Facades\Log::info('[updateExcludedProducts] Database updated:', [
            'reset_count' => $resetCount,
            'update_count' => $updateCount,
        ]);

        return redirect()->back()
            ->with('success', 'Pengaturan pengecualian komisi produk berhasil diperbarui!');
    }
}

