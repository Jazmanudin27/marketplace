<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        
        $query = Supplier::where('tenant_id', $tenantId)->orderBy('name');
        
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('contact_person', 'like', '%' . $request->search . '%');
        }
        
        $suppliers = $query->paginate(20)->withQueryString();
        
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.form');
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
        
        return view('suppliers.form', compact('supplier'));
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
