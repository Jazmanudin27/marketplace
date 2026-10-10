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
            if ($request->status === 'completed') {
                $query->where('status', OfflineSale::STATUS_COMPLETED);
            } elseif ($request->status === 'pending_spk') {
                $query->whereIn('status', [OfflineSale::STATUS_PENDING_SPK, 'pending_spk', 'belum_spk']);
            } elseif ($request->status === 'spk_diproses') {
                $query->where(function($q) {
                    $q->where('status', OfflineSale::STATUS_SPK_PROCESSING)
                      ->orWhere(function($sub) {
                          $sub->where('status', OfflineSale::STATUS_PENDING_APPROVAL)
                              ->where('is_po', true);
                      });
                });
            } elseif ($request->status === 'waiting_dp') {
                $query->whereIn('status', [OfflineSale::STATUS_WAITING_DP, 'waiting_dp', 'menunggu_dp']);
            } elseif ($request->status === 'cancelled') {
                $query->whereIn('status', [OfflineSale::STATUS_CANCELLED, 'cancelled', 'batal']);
            } elseif ($request->status === 'perlu_follow_up') {
                $query->where('status', OfflineSale::STATUS_WAITING_DP)
                      ->where('paid_amount', '<=', 0)
                      ->whereNotNull('follow_up_date')
                      ->whereDate('follow_up_date', '<', now()->toDateString());
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

        if ($request->filled('is_po')) {
            if ($request->is_po === '1' || $request->is_po === 'po') {
                $query->where('is_po', true);
            } elseif ($request->is_po === '0' || $request->is_po === 'walk_in') {
                $query->where('is_po', false);
            }
        }

        if ($request->filled('date_from')) {
            $query->whereDate('sold_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('sold_at', '<=', $request->date_to);
        }

        $sales = $query->paginate(20)->withQueryString();

        // Calculate Tab Counts (Status Tabs)
        $tabCountBase = OfflineSale::where('tenant_id', $tenantId);
        if ($request->filled('date_from')) $tabCountBase->whereDate('sold_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $tabCountBase->whereDate('sold_at', '<=', $request->date_to);
        if ($request->filled('search')) {
            $s = trim($request->search);
            $tabCountBase->where(function ($q) use ($s) {
                $q->where('sale_number', 'like', "%{$s}%")
                  ->orWhere('buyer_name', 'like', "%{$s}%")
                  ->orWhere('buyer_phone', 'like', "%{$s}%");
            });
        }
        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'lunas') {
                $tabCountBase->where('status', '!=', OfflineSale::STATUS_CANCELLED)->whereRaw('paid_amount >= grand_total');
            } elseif ($request->payment_status === 'belum_lunas') {
                $tabCountBase->where('status', '!=', OfflineSale::STATUS_CANCELLED)->whereRaw('paid_amount < grand_total');
            }
        }

        $tabCounts = [
            '__all__'      => (clone $tabCountBase)->count(),
            'completed'    => (clone $tabCountBase)->where('status', OfflineSale::STATUS_COMPLETED)->count(),
            'pending_spk'  => (clone $tabCountBase)->whereIn('status', [OfflineSale::STATUS_PENDING_SPK, 'pending_spk', 'belum_spk'])->count(),
            'spk_diproses' => (clone $tabCountBase)->where(function($q) {
                                $q->where('status', OfflineSale::STATUS_SPK_PROCESSING)
                                  ->orWhere(function($sub) {
                                      $sub->where('status', OfflineSale::STATUS_PENDING_APPROVAL)->where('is_po', true);
                                  });
                            })->count(),
            'waiting_dp'   => (clone $tabCountBase)->whereIn('status', [OfflineSale::STATUS_WAITING_DP, 'waiting_dp', 'menunggu_dp'])->count(),
            'cancelled'    => (clone $tabCountBase)->whereIn('status', [OfflineSale::STATUS_CANCELLED, 'cancelled', 'batal'])->count(),
        ];

        // Calculate Sub-tab Counts (Payment Status)
        $payCountBase = OfflineSale::where('tenant_id', $tenantId);
        if ($request->filled('date_from')) $payCountBase->whereDate('sold_at', '>=', $request->date_from);
        if ($request->filled('date_to'))   $payCountBase->whereDate('sold_at', '<=', $request->date_to);
        if ($request->filled('status')) {
            if ($request->status === 'completed') {
                $payCountBase->where('status', OfflineSale::STATUS_COMPLETED);
            } elseif ($request->status === 'pending_spk') {
                $payCountBase->whereIn('status', [OfflineSale::STATUS_PENDING_SPK, 'pending_spk', 'belum_spk']);
            } elseif ($request->status === 'spk_diproses') {
                $payCountBase->where(function($q) {
                    $q->where('status', OfflineSale::STATUS_SPK_PROCESSING)
                      ->orWhere(function($sub) {
                          $sub->where('status', OfflineSale::STATUS_PENDING_APPROVAL)->where('is_po', true);
                      });
                });
            } elseif ($request->status === 'waiting_dp') {
                $payCountBase->whereIn('status', [OfflineSale::STATUS_WAITING_DP, 'waiting_dp', 'menunggu_dp']);
            } elseif ($request->status === 'cancelled') {
                $payCountBase->whereIn('status', [OfflineSale::STATUS_CANCELLED, 'cancelled', 'batal']);
            }
        }

        $paymentCounts = [
            '__all__'     => (clone $payCountBase)->count(),
            'lunas'       => (clone $payCountBase)->where('status', '!=', OfflineSale::STATUS_CANCELLED)->whereRaw('paid_amount >= grand_total')->count(),
            'belum_lunas' => (clone $payCountBase)->where('status', '!=', OfflineSale::STATUS_CANCELLED)->whereRaw('paid_amount < grand_total')->count(),
        ];

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
            'tabCounts',
            'paymentCounts',
            'totalSalesCount',
            'totalSalesOmset',
            'totalSalesPaid',
            'totalPiutang'
        ));
    }

    /**
     * Show form to create new offline POS sale (V2).
     */
    public function create()
    {
        $tenantId = Auth::user()->tenant_id;
        $products = MasterProduct::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->nonBundle()
            ->orderBy('name')
            ->get();

        $customers = \App\Models\Customer::where('tenant_id', $tenantId)
            ->offline()
            ->orderBy('name')
            ->get();

        $bankAccounts = \App\Models\BankAccount::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return view('v2.penjualan_offline.create', compact('products', 'customers', 'bankAccounts'));
    }

    /**
     * Store new offline POS sale.
     */
    public function store(Request $request)
    {
        // Bersihkan pemisah ribuan (titik/koma) agar validasi numeric di backend sukses
        $input = $request->all();
        if (isset($input['discount_amount'])) {
            $input['discount_amount'] = (float) preg_replace('/[^0-9]/', '', (string) $input['discount_amount']);
        }
        if (isset($input['paid_amount'])) {
            $input['paid_amount'] = (float) preg_replace('/[^0-9]/', '', (string) $input['paid_amount']);
        }
        if (isset($input['items']) && is_array($input['items'])) {
            foreach ($input['items'] as $k => $item) {
                if (isset($item['unit_price'])) {
                    $input['items'][$k]['unit_price'] = (float) preg_replace('/[^0-9]/', '', (string) $item['unit_price']);
                }
            }
        }
        $request->merge($input);

        $controller = new \App\Http\Controllers\OfflineSaleController();
        $controller->store($request);
        return redirect()->route('v2.penjualan_offline.index')->with('success', 'Transaksi Penjualan Offline (POS) berhasil disimpan!');
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
