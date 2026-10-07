<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\MasterProduct;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\OfflineSalePayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PenjualanOfflineController extends Controller
{
    /**
     * Display V2 Penjualan Offline (POS / Kasir Store) index.
     */
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        // Auto-heal orphaned PO sales where SPK deleted
        $orphanedSales = OfflineSale::where('tenant_id', $tenantId)
            ->where('is_po', true)
            ->whereIn('status', [OfflineSale::STATUS_SPK_PROCESSING, OfflineSale::STATUS_PENDING_APPROVAL])
            ->whereDoesntHave('spks')
            ->get();

        foreach ($orphanedSales as $orphanedSale) {
            $reverted = ((float) $orphanedSale->paid_amount > 0)
                ? OfflineSale::STATUS_PENDING_SPK
                : OfflineSale::STATUS_WAITING_DP;
            $orphanedSale->update(['status' => $reverted]);
        }

        $query = OfflineSale::with(['user', 'items', 'spks'])
            ->where('tenant_id', $tenantId)
            ->orderByDesc('created_at');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('sale_number', 'like', "%{$search}%")
                  ->orWhere('buyer_name', 'like', "%{$search}%")
                  ->orWhere('buyer_phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            if ($request->status === 'perlu_follow_up') {
                $query->where('status', OfflineSale::STATUS_WAITING_DP)
                      ->where('paid_amount', '<=', 0)
                      ->whereNotNull('follow_up_date')
                      ->whereDate('follow_up_date', '<', now()->toDateString());
            } elseif ($request->status === 'spk_diproses') {
                $query->where(function($q) {
                    $q->where('status', OfflineSale::STATUS_SPK_PROCESSING)
                      ->orWhere(function($sub) {
                          $sub->where('status', OfflineSale::STATUS_PENDING_APPROVAL)
                              ->where('is_po', true);
                      });
                });
            } elseif ($request->status === 'pending_approval') {
                $query->where('status', OfflineSale::STATUS_PENDING_APPROVAL)
                      ->where('is_po', false);
            } else {
                $query->where('status', $request->status);
            }
        }

        if ($request->filled('payment_method')) {
            if ($request->payment_method === 'piutang' || $request->payment_method === 'kredit') {
                $query->where('payment_method', 'piutang');
            } elseif ($request->payment_method === 'tunai') {
                $query->where('payment_method', '!=', 'piutang');
            } else {
                $query->where('payment_method', $request->payment_method);
            }
        }

        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'lunas') {
                $query->where('status', '!=', OfflineSale::STATUS_CANCELLED)
                      ->whereRaw('paid_amount >= grand_total');
            } elseif ($request->payment_status === 'belum_lunas') {
                $query->where('status', '!=', OfflineSale::STATUS_CANCELLED)
                      ->whereRaw('paid_amount < grand_total');
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sold_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sold_at', '<=', $request->date_to);
        }

        $sales = $query->paginate(20)->withQueryString();

        // Calculate KPI Statistics
        $kpiQuery = OfflineSale::where('tenant_id', $tenantId)
            ->where('status', '!=', OfflineSale::STATUS_CANCELLED);

        if ($request->filled('date_from')) {
            $kpiQuery->whereDate('sold_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $kpiQuery->whereDate('sold_at', '<=', $request->date_to);
        }

        $totalSalesCount = (clone $kpiQuery)->count();
        $totalSalesOmset = (float) (clone $kpiQuery)->sum('grand_total');
        $totalSalesPaid  = (float) (clone $kpiQuery)->sum('paid_amount');
        $totalPiutang    = max(0, $totalSalesOmset - $totalSalesPaid);

        return view('v2.penjualan_offline.index', compact(
            'sales',
            'totalSalesCount',
            'totalSalesOmset',
            'totalSalesPaid',
            'totalPiutang'
        ));
    }

    /**
     * Show detail of offline sale.
     */
    public function show($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $sale = OfflineSale::with(['user', 'items.masterProduct', 'payments', 'spks'])
            ->where('tenant_id', $tenantId)
            ->findOrFail($id);

        return view('v2.penjualan_offline.show', compact('sale'));
    }
}
