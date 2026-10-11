<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\MasterProduct;
use App\Models\Store;
use App\Models\MarketingTeam;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class OwnerApiController extends Controller
{
    /**
     * Resolve active tenant ID from request or fallback to first tenant
     */
    protected function getTenantId(Request $request)
    {
        $tenantId = $request->header('X-Tenant-Id') ?: $request->input('tenant_id');
        if ($tenantId) {
            return (int) $tenantId;
        }

        // Check token or default tenant
        $token = $request->bearerToken();
        if ($token) {
            // Find user if token matches or get first super-admin tenant
            $user = User::first();
            if ($user && $user->tenant_id) {
                return (int) $user->tenant_id;
            }
        }

        $firstTenant = \App\Models\Tenant::first();
        return $firstTenant ? (int) $firstTenant->id : 1;
    }

    /**
     * GET /api/v2/owner/metrics/overview
     * Mengambil seluruh metrik live langsung dari database ERP
     */
    public function overview(Request $request)
    {
        $tenantId = $this->getTenantId($request);
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();

        // 1. Status Counter Pesanan
        $ordersNew = Order::where('tenant_id', $tenantId)
            ->whereIn(DB::raw('UPPER(order_status)'), ['NEW', 'UNPAID', 'PENDING'])
            ->count();

        $ordersToShip = Order::where('tenant_id', $tenantId)
            ->whereIn(DB::raw('UPPER(order_status)'), ['READY_TO_SHIP', 'IN_PROCESS', 'PROCESSED', 'SIAP_KIRIM'])
            ->count();

        $ordersReturned = Order::where('tenant_id', $tenantId)
            ->whereIn(DB::raw('UPPER(order_status)'), ['RETURNED', 'REFUNDED', 'RETUR', 'IN_CANCEL', 'CANCELLED', 'CANCELED'])
            ->whereDate('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        $ordersCompletedToday = Order::where('tenant_id', $tenantId)
            ->whereIn(DB::raw('UPPER(order_status)'), ['COMPLETED', 'RELEASED', 'SELESAI', 'DELIVERED', 'FINISHED'])
            ->where(function ($q) use ($today) {
                $q->whereDate('completed_at', $today)
                  ->orWhere(function ($sq) use ($today) {
                      $sq->whereNull('completed_at')->whereDate('created_at', $today);
                  });
            })
            ->count();

        // 2. Online Sales Dilepas Bulan Ini (Diselaraskan persis dengan Laporan Penjualan Dilepas & Laba Rugi V2)
        $completedMonthOrders = Order::where('tenant_id', $tenantId)
            ->whereIn(DB::raw('UPPER(order_status)'), ['COMPLETED', 'SELESAI', 'FINISHED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR'])
            ->where(function ($q) use ($startOfMonth) {
                $q->where('completed_at', '>=', $startOfMonth)
                  ->orWhere(function ($sq) use ($startOfMonth) {
                      $sq->whereNull('completed_at')->where('order_date', '>=', $startOfMonth);
                  });
            })
            ->with(['items.masterProduct'])
            ->get();

        $monthGrossSales = 0.0;
        $monthReleased = 0.0;
        $monthHpp = 0.0;
        $monthMarketplaceFee = 0.0;
        $monthRefund = 0.0;

        foreach ($completedMonthOrders as $order) {
            $monthGrossSales += (float) $order->total_amount;
            $monthRefund += (float) $order->refund_amount;

            // Marketplace Fee (Reconciled vs Unreconciled fee breakdown)
            if ($order->recon_status === 'RECONCILED') {
                $monthMarketplaceFee += abs((float) $order->getRawOriginal('marketplace_fee'));
            } else {
                $absFee = (float) $order->marketplace_fee;
                if ($absFee <= 0 && !empty($order->fee_breakdown_details['total_fee'])) {
                    $absFee = abs((float) $order->fee_breakdown_details['total_fee']);
                }
                $monthMarketplaceFee += $absFee;
            }

            // Dana Bersih Dilepas
            $released = (float) $order->net_amount;
            if ($released <= 0) {
                $released = max(0.0, (float) $order->total_amount - (float) $order->refund_amount - (float) $order->marketplace_fee);
                if ($released <= 0) {
                    $released = max(0.0, (float) $order->total_amount - (float) $order->refund_amount);
                }
            }
            $monthReleased += $released;

            // HPP total (menggunakan hpp_total jika ada, fallback per-item)
            if ($order->hpp_total && (float) $order->hpp_total > 0) {
                $monthHpp += (float) $order->hpp_total;
            } else {
                $orderHpp = 0.0;
                foreach ($order->items as $item) {
                    $unitHpp = (float) ($item->cost_price ?: ($item->masterProduct ? $item->masterProduct->cost_price : 0));
                    $orderHpp += ($unitHpp * $item->quantity);
                }
                $monthHpp += $orderHpp;
            }
        }

        // Penjualan Bulan Ini = Dana Penjualan Dilepas (Net Released) / Omset Bersih
        $monthOmset = $monthGrossSales > 0 ? $monthGrossSales : $monthReleased;
        $monthMargin = max(0.0, $monthReleased - $monthHpp);

        // 3. Target Komisi Tim Marketing (Di-cache 60 detik per tenant agar respon API di bawah 50ms)
        $teams = MarketingTeam::where('tenant_id', $tenantId)->where('is_active', true)->get();
        $targetMonthlyMargin = (float) $teams->sum('target_omset');
        
        $teamCacheKey = "owner_team_metrics_{$tenantId}_" . date('Ymd_H');
        $cachedTeamData = \Illuminate\Support\Facades\Cache::remember($teamCacheKey, 60, function () use ($teams) {
            $actMargin = 0.0;
            $totReward = 0.0;
            foreach ($teams as $team) {
                $metrics = $team->calculateActualMetrics();
                $actMargin += (float) ($metrics['margin'] ?? 0);
                $totReward += (float) ($team->total_reward ?? 0);
            }
            return ['margin' => $actMargin, 'reward' => $totReward];
        });

        $actualMonthlyMargin = (float) ($cachedTeamData['margin'] ?? 0);
        $totalEarnedCommission = (float) ($cachedTeamData['reward'] ?? 0);

        $targetProgressPercent = $targetMonthlyMargin > 0
            ? round(($actualMonthlyMargin / $targetMonthlyMargin) * 100, 1)
            : 0.0;

        // 4. Toko Terhubung
        $stores = Store::where('tenant_id', $tenantId)->with('channel')->get();
        $connectedMarketplaces = $stores->pluck('channel.name')->filter()->unique()->values()->toArray();

        // 5. Saldo Kas & Escrow
        $cashBalance = 0.0;
        if (\Illuminate\Support\Facades\Schema::hasTable('bank_accounts')) {
            $cashBalance = (float) (DB::table('bank_accounts')
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->sum('current_balance') ?? 0);
        }

        $receivableEscrow = (float) Order::where('tenant_id', $tenantId)
            ->whereIn(DB::raw('UPPER(order_status)'), ['SHIPPED', 'READY_TO_SHIP'])
            ->sum('total_amount');

        // 6. Data Profil Owner
        $ownerUser = User::where('tenant_id', $tenantId)->first() ?: User::first();
        $tenantModel = \App\Models\Tenant::find($tenantId);

        return response()->json([
            'success' => true,
            'data'    => [
                'ordersNew'             => $ordersNew,
                'ordersToShip'          => $ordersToShip,
                'ordersReturned'        => $ordersReturned,
                'ordersCompletedToday'  => $completedMonthOrders->count(),
                'ordersCompletedMonth'  => $completedMonthOrders->count(),
                'todayOmset'            => round($monthOmset, 0),
                'todayMargin'           => round($monthMargin, 0),
                'todayHpp'              => round($monthHpp, 0),
                'todayMarketplaceFee'   => round($monthMarketplaceFee, 0),
                'todayRefund'           => round($monthRefund, 0),
                'monthOmset'            => round($monthOmset, 0),
                'monthMargin'           => round($monthMargin, 0),
                'monthHpp'              => round($monthHpp, 0),
                'monthMarketplaceFee'   => round($monthMarketplaceFee, 0),
                'monthRefund'           => round($monthRefund, 0),
                'targetMonthlyMargin'   => round($targetMonthlyMargin, 0),
                'actualMonthlyMargin'   => round($actualMonthlyMargin, 0),
                'targetProgressPercent' => $targetProgressPercent,
                'totalEarnedCommission' => round($totalEarnedCommission, 0),
                'cashBalance'           => round($cashBalance, 0),
                'receivableEscrow'      => round($receivableEscrow, 0),
                'storesCount'           => $stores->count(),
                'connectedMarketplaces' => $connectedMarketplaces,
            ],
            'profile' => [
                'name'                  => $ownerUser ? $ownerUser->name : 'Owner',
                'role'                  => $ownerUser ? ($ownerUser->role ?: 'Business Owner') : 'Owner',
                'tenantName'            => $tenantModel ? $tenantModel->name : '',
                'avatarInitials'        => strtoupper(substr($ownerUser ? $ownerUser->name : 'OW', 0, 2)),
                'email'                 => $ownerUser ? $ownerUser->email : '',
                'storesCount'           => $stores->count(),
                'connectedMarketplaces' => $connectedMarketplaces,
            ],
        ]);
    }

    /**
     * GET /api/v2/owner/orders
     * Mengambil daftar order live dari tabel orders
     */
    public function orders(Request $request)
    {
        $tenantId = $this->getTenantId($request);
        $status = strtoupper($request->input('status', 'ALL'));
        $search = trim($request->input('search', ''));
        $limit  = min(50, max(1, (int) $request->input('limit', 20)));

        $query = Order::where('tenant_id', $tenantId)
            ->with(['store.channel', 'items.masterProduct']);

        if ($status !== 'ALL') {
            if ($status === 'READY_TO_SHIP') {
                $query->whereIn(DB::raw('UPPER(order_status)'), ['READY_TO_SHIP', 'IN_PROCESS', 'PROCESSED', 'SIAP_KIRIM']);
            } elseif ($status === 'SHIPPED') {
                $query->whereIn(DB::raw('UPPER(order_status)'), ['SHIPPED', 'DIKIRIM']);
            } elseif ($status === 'COMPLETED') {
                $query->whereIn(DB::raw('UPPER(order_status)'), ['COMPLETED', 'RELEASED', 'SELESAI', 'DELIVERED', 'FINISHED']);
            } elseif ($status === 'RETURNED') {
                $query->whereIn(DB::raw('UPPER(order_status)'), ['RETURNED', 'REFUNDED', 'RETUR', 'IN_CANCEL', 'CANCELLED', 'CANCELED']);
            }
        }

        // Sub-filter Process Status (to_process / processed)
        $processStatus = $request->input('process_status');
        if ($processStatus === 'to_process') {
            $query->where(function ($sub) {
                $sub->where('is_printed', false)->orWhereNull('is_printed');
            })
            ->where(function ($q2) {
                $q2->whereNull('tracking_number')->orWhere('tracking_number', '');
            });
        } elseif ($processStatus === 'processed') {
            $query->where(function ($sub) {
                $sub->where('is_printed', true)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('tracking_number')->where('tracking_number', '!=', '');
                    });
            });
        }

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhere('order_marketplace_id', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%");
            });
        }

        $monthOnly = $request->boolean('month_only') || $request->boolean('today_only');
        if ($monthOnly) {
            $startOfMonth = Carbon::now()->startOfMonth();
            $query->where(function ($q) use ($startOfMonth) {
                $q->where('completed_at', '>=', $startOfMonth)
                  ->orWhere(function ($sq) use ($startOfMonth) {
                      $sq->whereNull('completed_at')->where('order_date', '>=', $startOfMonth);
                  });
            });
        }

        $orders = $query->orderBy('created_at', 'desc')->paginate($limit);

        $formatted = $orders->getCollection()->map(function ($order) {
            $totalQty = $order->items->sum('quantity');

            // Nilai dilepas / omset bersih
            $orderReleased = (float) $order->net_amount;
            if ($orderReleased <= 0) {
                $orderReleased = max(0.0, (float) $order->total_amount - (float) $order->refund_amount - (float) $order->marketplace_fee);
                if ($orderReleased <= 0) {
                    $orderReleased = max(0.0, (float) $order->total_amount - (float) $order->refund_amount);
                }
            }

            // HPP modal
            $orderHpp = 0.0;
            $itemsList = [];
            foreach ($order->items as $item) {
                $unitHpp = (float) ($item->cost_price ?: ($item->masterProduct ? $item->masterProduct->cost_price : 0));
                $orderHpp += ($unitHpp * $item->quantity);

                $itemPrice = (float) ($item->price ?: ($item->total_price ?: ($item->unit_price ?: 0)));
                if ($itemPrice <= 0 && $item->quantity > 0 && (float) $order->total_amount > 0) {
                    $itemPrice = (float) $order->total_amount / $item->quantity;
                }

                $itemsList[] = [
                    'id'             => $item->id,
                    'productName'    => $item->product_name ?: ($item->masterProduct ? $item->masterProduct->name : 'Produk'),
                    'sku'            => $item->sku ?: ($item->masterProduct ? $item->masterProduct->sku : '—'),
                    'size'           => $item->variation_name ?: ($item->variation ?: 'Standar'),
                    'quantity'       => (int) $item->quantity,
                    'price'          => $itemPrice,
                    'costPrice'      => $unitHpp,
                    'marketplaceFee' => (float) ($item->marketplace_fee ?? 0),
                ];
            }

            $orderMargin = max(0.0, round($orderReleased - $orderHpp, 2));
            $orderComm   = round($orderMargin * 0.07, 0); // 7% estimasi komisi tim

            $chName = strtolower($order->store->channel->code ?? ($order->store->channel->name ?? 'pos'));

            return [
                'id'             => $order->id,
                'invoiceNumber'  => $order->invoice_number ?: ($order->order_marketplace_id ?: 'ORD-' . $order->id),
                'marketplaceId'  => $order->order_marketplace_id,
                'storeName'      => $order->store ? $order->store->store_name : 'Toko #' . $order->store_id,
                'channel'        => $chName,
                'channelCode'    => $chName,
                'buyerName'      => $order->buyer_name ?: 'Pembeli Marketplace',
                'buyerPhone'     => $order->buyer_phone ?: '—',
                'buyerCity'      => $order->buyer_city ?: 'Indonesia',
                'courier'        => $order->courier ?: 'Ekspedisi',
                'trackingNumber' => $order->tracking_number,
                'completedAt'    => $order->completed_at ? Carbon::parse($order->completed_at)->format('d M Y H:i') : ($order->created_at ? Carbon::parse($order->created_at)->format('d M Y H:i') : '—'),
                'status'         => strtoupper($order->order_status ?: 'COMPLETED'),
                'isPrinted'      => (bool) $order->is_printed,
                'processStatus'  => ((bool) $order->is_printed || (!empty($order->tracking_number))) ? 'processed' : 'to_process',
                'quantity'       => max(1, $totalQty),
                'totalAmount'    => round((float) $order->total_amount, 0),
                'marketplaceFee' => round((float) $order->marketplace_fee, 0),
                'refundAmount'   => round((float) $order->refund_amount, 0),
                'releasedValue'  => round($orderReleased, 0),
                'hppModal'       => round($orderHpp, 0),
                'margin'         => round($orderMargin, 0),
                'commission'     => round($orderComm, 0),
                'commissionRate' => 7.0,
                'items'          => $itemsList,
            ];
        });

        $unprocessedStatuses = ['UNPAID', 'PENDING', 'READY_TO_SHIP', 'TO_SHIP', 'PROCESSED', 'PROCESSING', 'PROSES', 'RETRY_SHIP', 'TO_RETRY_LOGISTICS'];
        $processedStatuses   = ['SHIPPED', 'IN_TRANSIT', 'TO_RECEIVE', 'TO_CONFIRM_RECEIVE', 'COMPLETED', 'FINISHED', 'SELESAI', 'DELIVERED'];

        // Exact ERP Tab counts
        $countBase = Order::where('tenant_id', $tenantId);
        $rawCounts = (clone $countBase)
            ->selectRaw('UPPER(order_status) as status_key, COUNT(*) as total')
            ->groupBy('status_key')
            ->pluck('total', 'status_key');

        $tabStatusMap = [
            'READY_TO_SHIP' => ['READY_TO_SHIP', 'TO_SHIP', 'PROCESSED', 'PROCESSING', 'PROSES', 'RETRY_SHIP', 'TO_RETRY_LOGISTICS'],
            'SHIPPED'       => ['SHIPPED', 'IN_TRANSIT', 'TO_RECEIVE', 'TO_CONFIRM_RECEIVE', 'DELIVERED'],
            'COMPLETED'     => ['COMPLETED', 'FINISHED', 'SELESAI'],
            'RETURNED'      => ['TO_RETURN', 'RETURN', 'RETURNED', 'REFUNDED', 'RETUR', 'RETURNING'],
            'CANCELLED'     => ['CANCELLED', 'BATAL', 'IN_CANCEL', 'CANCELED'],
        ];

        $tabCounts = [
            'ALL'           => (clone $countBase)->count(),
            'READY_TO_SHIP' => $rawCounts->only($tabStatusMap['READY_TO_SHIP'])->sum(),
            'SHIPPED'       => $rawCounts->only($tabStatusMap['SHIPPED'])->sum(),
            'COMPLETED'     => $rawCounts->only($tabStatusMap['COMPLETED'])->sum(),
            'RETURNED'      => $rawCounts->only($tabStatusMap['RETURNED'])->sum(),
            'CANCELLED'     => $rawCounts->only($tabStatusMap['CANCELLED'])->sum(),
        ];

        // Process Counts for READY_TO_SHIP: Belum diproses vs Telah diproses
        $readyBase = (clone $countBase)->whereIn(DB::raw('UPPER(order_status)'), $tabStatusMap['READY_TO_SHIP']);
        $toProcessCount = (clone $readyBase)->where(function($q) use ($unprocessedStatuses) {
            $q->where(function ($sub) {
                $sub->where('is_printed', false)->orWhereNull('is_printed');
            })
            ->where(function($q2) {
                $q2->whereNull('tracking_number')->orWhere('tracking_number', '');
            });
        })->count();

        $processedCount = (clone $readyBase)->where(function($q) use ($processedStatuses) {
            $q->where('is_printed', true)
              ->orWhere(function($q2) {
                  $q2->whereNotNull('tracking_number')->where('tracking_number', '!=', '');
              });
        })->count();

        return response()->json([
            'success'       => true,
            'data'          => $formatted,
            'total'         => $orders->total(),
            'page'          => $orders->currentPage(),
            'limit'         => $orders->perPage(),
            'tabCounts'     => $tabCounts,
            'processCounts' => [
                'to_process' => $toProcessCount,
                'processed'  => $processedCount,
            ]
        ]);
    }

    /**
     * GET /api/v2/owner/orders/{order}
     */
    public function showOrder(Request $request, $id)
    {
        $tenantId = $this->getTenantId($request);
        $order = Order::where('tenant_id', $tenantId)
            ->where('id', $id)
            ->with(['store.channel', 'items.masterProduct'])
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    /**
     * GET /api/v2/owner/target-komisi
     * Data real target marketing dan reward tim dari ERP (Selaras 100% dengan V2 TargetKomisiController)
     */
    public function targetKomisi(Request $request)
    {
        $tenantId = $this->getTenantId($request);

        // Ambil daftar tahun unik dari data tim & orders milik tenant
        $currentYear = (int) date('Y');
        $teamYears = MarketingTeam::where('tenant_id', $tenantId)
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

        // Filter Bulan & Tahun (opsional)
        $reqMonth = $request->filled('month') ? (int) $request->month : null;
        $reqYear  = $request->filled('year') ? (int) $request->year : null;

        // Query teams
        $teams = MarketingTeam::where('tenant_id', $tenantId)
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

        $formattedTeams = [];
        $totalTargetMargin = 0.0;
        $totalTargetQty = 0;
        $totalActualMargin = 0.0;
        $totalActualQty = 0;
        $totalReward = 0.0;

        foreach ($teams as $team) {
            $metrics = $team->calculateActualMetrics($reqMonth, $reqYear);
            $tTarget = (float) ($team->target_omset ?: 0);
            $tTargetQty = (int) ($team->target_qty ?: 0);
            $actMargin = (float) ($metrics['margin'] ?? 0);
            $actQty = (int) ($metrics['qty'] ?? 0);

            // Perhitungan komisi yang konsisten dengan V2 TargetKomisiController
            $cType = $team->commission_type ?: 'percentage';
            $tReward = 0.0;
            if ($cType === 'percentage') {
                $tReward = max(0.0, $actMargin) * ((float)$team->commission_rate / 100);
                if ($tTarget > 0 && $actMargin >= $tTarget && (float)$team->reward_fixed_nominal > 0) {
                    $tReward += (float)$team->reward_fixed_nominal;
                }
            } elseif ($cType === 'nominal') {
                if ($tTarget > 0 && $actMargin >= $tTarget) {
                    $tReward = (float)$team->reward_fixed_nominal;
                }
            } else {
                $tReward = (float) ($actQty * (float)$team->reward_per_qty);
            }
            $tReward = round($tReward, 2);

            $progressPct = $tTarget > 0 ? min(100.0, round(($actMargin / $tTarget) * 100, 1)) : 0.0;

            if ($team->is_active) {
                $totalTargetMargin += $tTarget;
                $totalTargetQty += $tTargetQty;
                $totalActualMargin += $actMargin;
                $totalActualQty += $actQty;
                $totalReward += $tReward;
            }

            $storesLinked = $team->stores->map(function ($s) {
                return [
                    'id'        => $s->id,
                    'name'      => $s->store_name,
                    'channel'   => $s->channel ? $s->channel->name : 'POS',
                ];
            });

            $formattedTeams[] = [
                'id'                 => $team->id,
                'name'               => $team->name,
                'description'        => $team->description,
                'periodLabel'        => $team->period_label,
                'periodMonth'        => $team->period_month,
                'periodYear'         => $team->period_year,
                'isActive'           => (bool) $team->is_active,
                'targetMargin'       => round($tTarget, 0),
                'targetQty'          => $tTargetQty,
                'actualMargin'       => round($actMargin, 0),
                'actualQty'          => $actQty,
                'actualOmset'        => round((float) ($metrics['omset'] ?? 0), 0),
                'actualHpp'          => round((float) ($metrics['hpp'] ?? 0), 0),
                'progressPercent'    => $progressPct,
                'rewardEarned'       => round($tReward, 0),
                'commissionType'     => $cType,
                'commissionRate'     => (float) ($team->commission_rate ?: 0),
                'rewardFixedNominal' => (float) ($team->reward_fixed_nominal ?: 0),
                'rewardPerQty'       => (float) ($team->reward_per_qty ?: 0),
                'stores'             => $storesLinked,
            ];
        }

        $overallProgress = $totalTargetMargin > 0
            ? round(($totalActualMargin / $totalTargetMargin) * 100, 1)
            : 0.0;

        $totalStoresCount = Store::where('tenant_id', $tenantId)->count();
        $totalStoresLinked = $teams->pluck('stores')->flatten()->unique('id')->count();

        return response()->json([
            'success' => true,
            'summary' => [
                'totalTargetMargin'  => round($totalTargetMargin, 0),
                'totalTargetQty'     => $totalTargetQty,
                'totalActualMargin'  => round($totalActualMargin, 0),
                'totalActualQty'     => $totalActualQty,
                'totalReward'        => round($totalReward, 0),
                'progressPercent'    => $overallProgress,
                'totalTeams'         => $teams->count(),
                'activeTeamsCount'   => $teams->where('is_active', true)->count(),
                'totalStoresLinked'  => $totalStoresLinked,
                'totalStoresCount'   => $totalStoresCount,
                'availableYears'     => $availableYears,
                'selectedMonth'      => $reqMonth,
                'selectedYear'       => $reqYear,
            ],
            'teams'   => $formattedTeams,
        ]);
    }

    /**
     * GET /api/v2/owner/laba-rugi
     * Laporan Laba Rugi real-time ERP
     */
    public function labaRugi(Request $request)
    {
        $tenantId = $this->getTenantId($request);
        $period = $request->input('period', 'month'); // 'today', 'month', 'year'

        $now = Carbon::now();
        if ($period === 'today') {
            $dateFrom = $now->copy()->startOfDay();
            $dateTo   = $now->copy()->endOfDay();
            $periodLabel = $now->translatedFormat('d F Y');
        } elseif ($period === 'year') {
            $dateFrom = $now->copy()->startOfYear();
            $dateTo   = $now->copy()->endOfYear();
            $periodLabel = 'Tahun ' . $now->year;
        } else {
            $dateFrom = $now->copy()->startOfMonth();
            $dateTo   = $now->copy()->endOfMonth();
            $periodLabel = $now->translatedFormat('F Y');
        }

        // 1. Online revenue, HPP, & fee (diselaraskan dengan ERP V2 Laba Rugi)
        $onlineOrders = Order::where('tenant_id', $tenantId)
            ->whereIn('order_status', ['COMPLETED', 'SELESAI', 'FINISHED', 'RETURNED', 'REFUNDED', 'RETURN', 'RETUR'])
            ->where(function ($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('completed_at', [$dateFrom, $dateTo])
                  ->orWhere(function ($sub) use ($dateFrom, $dateTo) {
                      $sub->whereNull('completed_at')->whereBetween('order_date', [$dateFrom, $dateTo]);
                  });
            })
            ->with(['items.masterProduct'])
            ->get();

        $onlineGrossSales = (float) $onlineOrders->sum('total_amount');
        $onlineRevenue = (float) $onlineOrders->sum('net_amount');
        if ($onlineRevenue <= 0 && $onlineGrossSales > 0) {
            $onlineRevenue = $onlineGrossSales;
        }

        $onlineFee = 0.0;
        $onlineHpp = 0.0;
        foreach ($onlineOrders as $ord) {
            if ($ord->recon_status === 'RECONCILED') {
                $onlineFee += abs((float) $ord->getRawOriginal('marketplace_fee'));
            } else {
                $absFee = (float) $ord->marketplace_fee;
                if ($absFee <= 0 && !empty($ord->fee_breakdown_details['total_fee'])) {
                    $absFee = abs((float) $ord->fee_breakdown_details['total_fee']);
                }
                $onlineFee += $absFee;
            }
            $onlineHpp += (float) ($ord->hpp_total ?: 0);
        }

        // 2. Offline / POS Sales jika ada tabel
        $offlineRevenue = 0.0;
        $offlineHpp = 0.0;
        if (\Illuminate\Support\Facades\Schema::hasTable('offline_sales')) {
            $offlineSales = \App\Models\OfflineSale::with('items.masterProduct')
                ->where('tenant_id', $tenantId)
                ->where('status', defined('\App\Models\OfflineSale::STATUS_COMPLETED') ? \App\Models\OfflineSale::STATUS_COMPLETED : 'completed')
                ->where(function ($q) use ($dateFrom, $dateTo) {
                    $q->whereBetween('sold_at', [$dateFrom, $dateTo])
                      ->orWhere(function ($sub) use ($dateFrom, $dateTo) {
                          $sub->whereNull('sold_at')->whereBetween('created_at', [$dateFrom, $dateTo]);
                      });
                })
                ->get();

            $offlineRevenue = (float) $offlineSales->sum('grand_total');
            foreach ($offlineSales as $sale) {
                $offlineHpp += (float) ($sale->hpp_total ?: 0);
            }
        }

        $totalOmset = $onlineRevenue + $offlineRevenue;
        $totalHpp   = $onlineHpp + $offlineHpp;
        $labaKotor  = $totalOmset - $totalHpp;
        $grossMarginPct = $totalOmset > 0 ? round(($labaKotor / $totalOmset) * 100, 1) : 0.0;

        // 3. Beban Operasional / Expenses
        $totalOperasional = 0.0;
        $expenseCategories = [];
        if (\Illuminate\Support\Facades\Schema::hasTable('expenses')) {
            $expenses = DB::table('expenses')
                ->where('tenant_id', $tenantId)
                ->whereBetween('expense_date', [$dateFrom->toDateString(), $dateTo->toDateString()])
                ->get();

            // Abaikan beban terkait modal pembelian / produksi barang agar tidak double counting
            $excludedCodes = ['pembelian_supplier', 'bahan_baku', 'biaya_produksi', 'supplier_payable'];
            $operatingExpenses = $expenses->filter(function ($exp) use ($excludedCodes) {
                return !in_array(strtolower($exp->category), $excludedCodes);
            });

            $totalOperasional = (float) $operatingExpenses->sum('amount');
            $grouped = $operatingExpenses->groupBy('category');

            $defaultLabels = [
                'salary'    => 'Gaji Karyawan',
                'rent'      => 'Sewa Tempat',
                'utilities' => 'Utilitas & Operasional',
                'other'     => 'Lain-lain',
            ];

            foreach ($grouped as $catName => $items) {
                $label = $defaultLabels[strtolower($catName)] ?? ucwords(str_replace('_', ' ', $catName));
                $expenseCategories[] = [
                    'name'   => $label,
                    'amount' => round((float) $items->sum('amount'), 0),
                ];
            }
        }

        $labaBersih = $labaKotor - $totalOperasional;
        $netMarginPct = $totalOmset > 0 ? round(($labaBersih / $totalOmset) * 100, 1) : 0.0;

        return response()->json([
            'success' => true,
            'period'  => [
                'type'  => $period,
                'label' => $periodLabel,
            ],
            'data'    => [
                'totalOmset'        => round($totalOmset, 0),
                'onlineRevenue'     => round($onlineRevenue, 0),
                'offlineRevenue'    => round($offlineRevenue, 0),
                'totalHpp'          => round($totalHpp, 0),
                'marketplaceFee'    => round($onlineFee, 0),
                'labaKotor'         => round($labaKotor, 0),
                'grossMarginPct'    => $grossMarginPct,
                'totalOperasional'  => round($totalOperasional, 0),
                'expenseCategories' => $expenseCategories,
                'labaBersih'        => round($labaBersih, 0),
                'netMarginPct'      => $netMarginPct,
                'ordersCount'       => $onlineOrders->count(),
            ]
        ]);
    }
}
