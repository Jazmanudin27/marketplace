<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Channel;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PesananController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = Order::with(['store.channel', 'items.masterProduct', 'spks'])
            ->where('tenant_id', $tenantId);

        // Filter Nomor Pesanan / Invoice / Resi / Buyer Name
        if ($request->filled('order_number')) {
            $search = trim($request->order_number);
            $query->where(function ($q) use ($search) {
                $q->where('order_marketplace_id', 'like', '%' . $search . '%')
                  ->orWhere('invoice_number', 'like', '%' . $search . '%')
                  ->orWhere('tracking_number', 'like', '%' . $search . '%')
                  ->orWhere('buyer_name', 'like', '%' . $search . '%');
            });
        }

        // Filter Channel
        if ($request->filled('channel_id')) {
            $query->whereHas('store', function ($q) use ($request) {
                $q->where('channel_id', $request->channel_id);
            });
        }

        // Filter Toko
        if ($request->filled('store_id')) {
            $query->where('store_id', $request->store_id);
        }

        // Status tab map
        $tabStatusMap = [
            'UNPAID'        => ['UNPAID', 'PENDING'],
            'READY_TO_SHIP' => ['READY_TO_SHIP', 'TO_SHIP', 'PROCESSED', 'PROCESSING', 'PROSES', 'RETRY_SHIP', 'TO_RETRY_LOGISTICS'],
            'SHIPPED'       => ['SHIPPED', 'IN_TRANSIT', 'TO_RECEIVE', 'TO_CONFIRM_RECEIVE', 'DELIVERED'],
            'COMPLETED'     => ['COMPLETED', 'FINISHED', 'SELESAI'],
            'CANCELLED'     => ['CANCELLED', 'BATAL', 'IN_CANCEL'],
            'TO_RETURN'     => ['TO_RETURN', 'RETURN', 'RETURNED', 'REFUNDED', 'RETUR', 'RETURNING'],
        ];

        if ($request->filled('status')) {
            $reqStatus = strtoupper($request->status);
            $targetStatuses = $tabStatusMap[$reqStatus] ?? [$request->status];
            $query->whereIn(DB::raw('UPPER(order_status)'), array_map('strtoupper', $targetStatuses));
        } else {
            $query->whereNotIn(DB::raw('UPPER(order_status)'), ['CANCELLED', 'BATAL', 'CANCELED', 'IN_CANCEL']);
        }

        // Process status definitions
        $unprocessedStatuses = ['UNPAID', 'PENDING', 'READY_TO_SHIP', 'TO_SHIP', 'PROCESSED', 'PROCESSING', 'PROSES', 'RETRY_SHIP', 'TO_RETRY_LOGISTICS'];
        $processedStatuses   = ['SHIPPED', 'IN_TRANSIT', 'TO_RECEIVE', 'TO_CONFIRM_RECEIVE', 'COMPLETED', 'FINISHED', 'SELESAI', 'DELIVERED'];

        if ($request->filled('process_status')) {
            if ($request->process_status === 'to_process') {
                $query->where(function ($q) use ($unprocessedStatuses) {
                    $q->where(function ($sub) {
                        $sub->where('is_printed', false)->orWhereNull('is_printed');
                    })
                    ->whereIn(DB::raw('UPPER(order_status)'), $unprocessedStatuses)
                    ->where(function ($q2) {
                        $q2->whereNull('tracking_number')->orWhere('tracking_number', '');
                    });
                });
            } elseif ($request->process_status === 'processed') {
                $query->where(function ($q) use ($processedStatuses) {
                    $q->where('is_printed', true)
                      ->orWhereIn(DB::raw('UPPER(order_status)'), $processedStatuses)
                      ->orWhere(function ($q2) {
                          $q2->whereNotNull('tracking_number')->where('tracking_number', '!=', '');
                      });
                });
            }
        }

        // Filter Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('order_date', '<=', $request->end_date);
        }

        $orders = $query->orderByDesc('order_date')
            ->paginate(50)
            ->withQueryString();

        // Data pendukung UI filter & modal laporan
        $channels   = Channel::all();
        $stores     = Store::with('channel')->where('tenant_id', $tenantId)->get();
        $categories = \App\Models\Category::where('tenant_id', $tenantId)->orderBy('name')->get();
        $brands     = \App\Models\Brand::where('tenant_id', $tenantId)->orderBy('name')->get();

        // ── Tab counts ──
        $countBase = Order::where('tenant_id', $tenantId);

        if ($request->filled('order_number')) {
            $search = trim($request->order_number);
            $countBase->where(function ($q) use ($search) {
                $q->where('order_marketplace_id', 'like', '%' . $search . '%')
                  ->orWhere('invoice_number', 'like', '%' . $search . '%')
                  ->orWhere('tracking_number', 'like', '%' . $search . '%')
                  ->orWhere('buyer_name', 'like', '%' . $search . '%');
            });
        }
        if ($request->filled('channel_id')) {
            $countBase->whereHas('store', fn($q) => $q->where('channel_id', $request->channel_id));
        }
        if ($request->filled('store_id')) {
            $countBase->where('store_id', $request->store_id);
        }
        if ($request->filled('start_date')) {
            $countBase->whereDate('order_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $countBase->whereDate('order_date', '<=', $request->end_date);
        }

        $rawCounts = (clone $countBase)
            ->selectRaw('UPPER(order_status) as status_key, COUNT(*) as total')
            ->groupBy('status_key')
            ->pluck('total', 'status_key');

        $tabCounts = ['__all__' => $rawCounts->sum()];
        foreach ($tabStatusMap as $tabKey => $dbStatuses) {
            $tabCounts[$tabKey] = $rawCounts->only($dbStatuses)->sum();
        }

        // Process counts
        $processBase = clone $countBase;
        if ($request->filled('status')) {
            $reqStatus      = strtoupper($request->status);
            $targetStatuses = $tabStatusMap[$reqStatus] ?? [$request->status];
            $processBase->whereIn(DB::raw('UPPER(order_status)'), array_map('strtoupper', $targetStatuses));
        }

        $toProcessCount = (clone $processBase)->where(function ($q) use ($unprocessedStatuses) {
            $q->where(function ($sub) {
                $sub->where('is_printed', false)->orWhereNull('is_printed');
            })
            ->whereIn(DB::raw('UPPER(order_status)'), $unprocessedStatuses)
            ->where(function ($q2) {
                $q2->whereNull('tracking_number')->orWhere('tracking_number', '');
            });
        })->count();

        $processCounts = [
            '__all__'    => (clone $processBase)->count(),
            'to_process' => $toProcessCount,
            'processed'  => (clone $processBase)->where(function ($q) use ($processedStatuses) {
                $q->where('is_printed', true)
                  ->orWhereIn(DB::raw('UPPER(order_status)'), $processedStatuses)
                  ->orWhere(function ($q2) {
                      $q2->whereNotNull('tracking_number')->where('tracking_number', '!=', '');
                  });
            })->count(),
        ];

        return view('v2.pesanan.index', compact(
            'orders', 'channels', 'stores', 'categories', 'brands',
            'tabCounts', 'processCounts', 'toProcessCount'
        ));
    }
}

