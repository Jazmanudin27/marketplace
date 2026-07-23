<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\OfflineSale;
use App\Models\BankAccount;
use App\Models\Income;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();
        $search = $request->query('search');
        $tag = $request->query('tag');
        $loyalty = $request->query('loyalty');
        $tenantIdQuery = $request->query('tenant_id', $user->tenant_id);
        $channelId = $request->query('channel_id');
        $storeId = $request->query('store_id');

        $query = Customer::withCount('orders')->with(['orders.store.channel', 'tenant']);

        // Tenant scope
        if (!$isSuperAdmin) {
            $query->where('tenant_id', $user->tenant_id);
        } else {
            if ($tenantIdQuery && $tenantIdQuery > 1) {
                $query->where('tenant_id', $tenantIdQuery);
            }
        }

        // Search name, username, or phone
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('marketplace_username', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by tags
        if ($tag) {
            $query->where('tags', 'like', "%{$tag}%");
        }

        // Filter by loyalty (Loyal Customer has >= 3 orders)
        if ($loyalty === 'loyal') {
            $query->has('orders', '>=', 3);
        } elseif ($loyalty === 'regular') {
            $query->has('orders', '<', 3);
        }

        // Filter by channel/marketplace
        if ($request->filled('channel_id')) {
            $query->whereHas('orders.store', function ($q) use ($channelId) {
                $q->where('channel_id', $channelId);
            });
        }

        // Filter by store
        if ($request->filled('store_id')) {
            $query->whereHas('orders', function ($q) use ($storeId) {
                $q->where('store_id', $storeId);
            });
        }

        $customers = $query->orderByDesc('orders_count')
            ->paginate(15)
            ->withQueryString();

        $tenants = [];
        if ($isSuperAdmin) {
            $tenants = \App\Models\Tenant::orderBy('name')->get();
        }

        // Get channels list
        $channels = \App\Models\Channel::orderBy('name')->get();

        // Get stores list
        $storesQuery = \App\Models\Store::orderBy('store_name');
        if (!$isSuperAdmin) {
            $storesQuery->where('tenant_id', $user->tenant_id);
        } else {
            if ($tenantIdQuery && $tenantIdQuery > 1) {
                $storesQuery->where('tenant_id', $tenantIdQuery);
            }
        }
        $stores = $storesQuery->get();

        // Get unique tags list
        $allTags = [];
        $tagsQuery = Customer::whereNotNull('tags');
        if (!$isSuperAdmin) {
            $tagsQuery->where('tenant_id', $user->tenant_id);
        } else {
            if ($tenantIdQuery && $tenantIdQuery > 1) {
                $tagsQuery->where('tenant_id', $tenantIdQuery);
            }
        }
        $rawTags = $tagsQuery->pluck('tags')->toArray();
            
        foreach ($rawTags as $rt) {
            foreach (explode(',', $rt) as $subt) {
                $trimmed = trim($subt);
                if ($trimmed && !in_array($trimmed, $allTags)) {
                    $allTags[] = $trimmed;
                }
            }
        }
        sort($allTags);

        return view('master.customers.index', compact(
            'customers', 'search', 'tag', 'loyalty', 'tenantIdQuery', 
            'channelId', 'storeId', 'isSuperAdmin', 'tenants', 'channels', 
            'stores', 'allTags'
        ));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'name'    => 'required|string|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'tags'    => 'nullable|string|max:255',
        ]);

        $customer = Customer::create([
            'tenant_id' => $user->tenant_id,
            'name'      => $request->name,
            'phone'     => $request->phone,
            'address'   => $request->address,
            'tags'      => $request->tags ?? 'Umum',
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success'  => true,
                'message'  => 'Pelanggan berhasil ditambahkan ke Data Master.',
                'customer' => $customer,
            ]);
        }

        return back()->with('success', 'Pelanggan baru berhasil ditambahkan.');
    }

    public function show(Customer $customer)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            abort_unless($customer->tenant_id === $user->tenant_id, 403);
        }

        $customer->load(['orders' => function($q) {
            $q->orderByDesc('order_date');
        }, 'orders.items']);

        $totalSpent = $customer->orders->sum('net_amount');
        $averageOrderValue = $customer->orders->count() > 0 ? $totalSpent / $customer->orders->count() : 0;

        $tenantId = $customer->tenant_id;
        $receivableSales = OfflineSale::where('tenant_id', $tenantId)
            ->where('customer_id', $customer->id)
            ->where('status', '!=', OfflineSale::STATUS_CANCELLED)
            ->where(function ($q) {
                $q->where('payment_method', 'piutang')
                  ->orWhereRaw('grand_total > paid_amount');
            })
            ->orderByDesc('sold_at')
            ->get();

        $totalReceivable = (float) $receivableSales->sum(fn($s) => max(0, $s->grand_total - $s->paid_amount));

        $bankAccounts = BankAccount::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return view('master.customers.show', compact('customer', 'totalSpent', 'averageOrderValue', 'receivableSales', 'totalReceivable', 'bankAccounts'));
    }

    public function update(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            abort_unless($customer->tenant_id === $user->tenant_id, 403);
        }

        $request->validate([
            'tags' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $customer->update($request->only(['tags', 'name', 'phone', 'address']));

        return back()->with('success', 'Profil pelanggan berhasil diperbarui.');
    }

    public function topup(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            abort_unless($customer->tenant_id === $user->tenant_id, 403);
        }

        $request->validate([
            'type' => 'required|in:in,out',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
        ]);

        $customer->adjustBalance(
            (float) $request->amount,
            $request->type,
            $request->description,
            Auth::id()
        );

        return back()->with('success', 'Saldo pelanggan ' . $customer->name . ' berhasil disesuaikan.');
    }

    public function payReceivable(Request $request, Customer $customer)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            abort_unless($customer->tenant_id === $user->tenant_id, 403);
        }

        $request->validate([
            'amount'              => 'required|numeric|min:1',
            'payment_destination' => 'required|string|max:100',
        ], [
            'amount.required'              => 'Nominal pelunasan wajib diisi.',
            'amount.min'                   => 'Nominal pelunasan minimal Rp 1.',
            'payment_destination.required' => 'Kas / Bank tujuan wajib dipilih.',
        ]);

        $tenantId    = $customer->tenant_id;
        $payAmount   = (float) $request->amount;
        $paymentDest = $request->payment_destination;

        // Ambil transaksi yang spesifik atau seluruh piutang pelanggan (FIFO)
        $query = OfflineSale::where('tenant_id', $tenantId)
            ->where('customer_id', $customer->id)
            ->where('status', '!=', OfflineSale::STATUS_CANCELLED)
            ->where(function ($q) {
                $q->where('payment_method', 'piutang')
                  ->orWhereRaw('grand_total > paid_amount');
            });

        if ($request->filled('offline_sale_id')) {
            $query->where('id', $request->offline_sale_id);
        }

        $unpaidSales = $query->orderBy('sold_at', 'asc')->get();
        $totalUnpaid = (float) $unpaidSales->sum(fn($s) => max(0, $s->grand_total - $s->paid_amount));

        if ($unpaidSales->isEmpty() || $totalUnpaid <= 0) {
            return back()->with('error', 'Pelanggan ini tidak memiliki tunggakan piutang untuk dilunasi.');
        }

        $remainingToPay = min($payAmount, $totalUnpaid);
        $totalPaidAllocated = 0;

        DB::transaction(function () use ($unpaidSales, $remainingToPay, $paymentDest, $tenantId, $customer, &$totalPaidAllocated) {
            $leftover = $remainingToPay;

            foreach ($unpaidSales as $sale) {
                if ($leftover <= 0) break;

                $saleUnpaid = max(0, $sale->grand_total - $sale->paid_amount);
                $allocated  = min($leftover, $saleUnpaid);

                $newPaidAmount = $sale->paid_amount + $allocated;
                $sale->update([
                    'paid_amount'         => $newPaidAmount,
                    'payment_destination' => $paymentDest,
                ]);

                $leftover -= $allocated;
                $totalPaidAllocated += $allocated;

                // Jika status transaksi sudah COMPLETED, catat pemasukan & update saldo bank
                if ($sale->status === OfflineSale::STATUS_COMPLETED && $allocated > 0) {
                    $bank = BankAccount::where('tenant_id', $tenantId)
                        ->where(function($q) use ($paymentDest) {
                            $q->where('bank_name', $paymentDest)
                              ->orWhere('id', $paymentDest);
                        })->first();

                    if ($bank) {
                        $bank->increment('current_balance', $allocated);
                    }

                    Income::create([
                        'tenant_id'           => $tenantId,
                        'title'               => "Pelunasan Piutang #{$sale->sale_number} ({$customer->name})",
                        'category'            => 'services',
                        'payment_destination' => $paymentDest,
                        'amount'              => $allocated,
                        'income_date'         => now(),
                        'description'         => "Pelunasan piutang pelanggan {$customer->name} untuk transaksi POS #{$sale->sale_number}",
                    ]);
                }
            }
        });

        return back()->with('success', '✅ Pelunasan piutang ' . $customer->name . ' sebesar Rp ' . number_format($totalPaidAllocated, 0, ',', '.') . ' berhasil dicatat!');
    }

    public function destroy(Customer $customer)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            abort_unless($customer->tenant_id === $user->tenant_id, 403);
        }

        if ($customer->orders()->count() > 0) {
            return back()->with('error', 'Pelanggan "' . $customer->name . '" tidak dapat dihapus karena sudah memiliki riwayat pesanan.');
        }

        $customer->delete();

        return back()->with('success', 'Pelanggan berhasil dihapus.');
    }
}
