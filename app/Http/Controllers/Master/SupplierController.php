<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $user     = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        // Super admin bisa lihat semua supplier lintas tenant
        $query = Supplier::with('tenant')->orderBy('name');

        if (! $isSuperAdmin) {
            $query->where('tenant_id', $user->tenant_id);
        }

        // Filter terpisah per field
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        if ($request->filled('contact_person')) {
            $query->where('contact_person', 'like', '%' . $request->contact_person . '%');
        }

        if ($request->filled('status') && in_array($request->status, ['1', '0'])) {
            $query->where('is_active', (bool) $request->status);
        }

        $suppliers = $query->paginate(20)->withQueryString();

        return view('master.suppliers.index', compact('suppliers', 'isSuperAdmin'));
    }

    public function create()
    {
        return view('master.suppliers.form');
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['tenant_id'] = $tenantId;
        $data['is_active'] = $request->has('is_active');

        Supplier::create($data);

        return redirect()->route('suppliers.index')->with('success', 'Data Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        abort_unless($supplier->tenant_id === Auth::user()->tenant_id, 403);

        return view('master.suppliers.form', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        abort_unless($supplier->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['is_active'] = $request->has('is_active');

        $supplier->update($data);

        return redirect()->route('suppliers.index')->with('success', 'Data Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        abort_unless($supplier->tenant_id === Auth::user()->tenant_id, 403);

        $supplier->delete();

        return redirect()->route('suppliers.index')->with('success', 'Data Supplier berhasil dihapus.');
    }
}
