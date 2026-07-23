<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

        return view('master.customers.show', compact('customer', 'totalSpent', 'averageOrderValue'));
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
}
