<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $search = $request->query('search');

        $customers = Customer::where('tenant_id', $tenantId)
            ->withCount('orders')
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('marketplace_username', 'like', "%{$search}%")
                      ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('orders_count')
            ->paginate(15);

        return view('customers.index', compact('customers', 'search'));
    }

    public function show(Customer $customer)
    {
        abort_unless($customer->tenant_id === Auth::user()->tenant_id, 403);
        
        $customer->load(['orders' => function($q) {
            $q->orderByDesc('order_date');
        }, 'orders.items']);

        // Calculate analytics for this specific customer
        $totalSpent = $customer->orders->sum('net_amount');
        $averageOrderValue = $customer->orders->count() > 0 ? $totalSpent / $customer->orders->count() : 0;
        
        return view('customers.show', compact('customer', 'totalSpent', 'averageOrderValue'));
    }

    public function update(Request $request, Customer $customer)
    {
        abort_unless($customer->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'tags' => 'nullable|string|max:255',
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
        ]);

        $customer->update($request->only(['tags', 'name', 'phone', 'address']));

        return back()->with('success', 'Profil pelanggan berhasil diperbarui.');
    }
}
