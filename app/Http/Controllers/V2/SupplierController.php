<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    /**
     * Display V2 Supplier List.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $tenantId = $user->tenant_id;

        $query = Supplier::where('tenant_id', $tenantId)->orderBy('name');

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('contact_person', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status') && in_array($request->status, ['1', '0'])) {
            $query->where('is_active', (bool) $request->status);
        }

        $suppliers = $query->paginate(20)->withQueryString();

        $totalSuppliers  = Supplier::where('tenant_id', $tenantId)->count();
        $activeSuppliers = Supplier::where('tenant_id', $tenantId)->where('is_active', true)->count();

        return view('v2.supplier.index', compact('suppliers', 'totalSuppliers', 'activeSuppliers'));
    }

    /**
     * Store new Supplier V2.
     */
    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:255',
            'address'        => 'nullable|string',
        ]);

        Supplier::create([
            'tenant_id'      => $tenantId,
            'name'           => $request->name,
            'contact_person' => $request->contact_person,
            'phone'          => $request->phone,
            'address'        => $request->address,
            'is_active'      => $request->has('is_active') ? true : (bool)$request->get('is_active', true),
        ]);

        return redirect()->route('v2.supplier.index')->with('success', 'Data Supplier berhasil ditambahkan!');
    }

    /**
     * Update Supplier V2.
     */
    public function update(Request $request, $id)
    {
        $tenantId = Auth::user()->tenant_id;
        $supplier = Supplier::where('tenant_id', $tenantId)->findOrFail($id);

        $request->validate([
            'name'           => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone'          => 'nullable|string|max:255',
            'address'        => 'nullable|string',
        ]);

        $supplier->update([
            'name'           => $request->name,
            'contact_person' => $request->contact_person,
            'phone'          => $request->phone,
            'address'        => $request->address,
            'is_active'      => $request->has('is_active') ? (bool)$request->is_active : $supplier->is_active,
        ]);

        return redirect()->route('v2.supplier.index')->with('success', 'Data Supplier berhasil diperbarui!');
    }

    /**
     * Remove Supplier V2.
     */
    public function destroy($id)
    {
        $tenantId = Auth::user()->tenant_id;
        $supplier = Supplier::where('tenant_id', $tenantId)->findOrFail($id);
        $supplier->delete();

        return redirect()->route('v2.supplier.index')->with('success', 'Data Supplier berhasil dihapus!');
    }
}
