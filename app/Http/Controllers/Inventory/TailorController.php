<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Tailor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TailorController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $query = Tailor::where('tenant_id', $tenantId)->orderBy('name');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        $tailors = $query->paginate(15)->withQueryString();

        return view('inventory.tailors.index', compact('tailors'));
    }

    public function create()
    {
        return view('inventory.tailors.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'phone'     => 'nullable|string|max:100',
            'address'   => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        Tailor::create([
            'tenant_id' => Auth::user()->tenant_id,
            'name'      => $request->name,
            'phone'     => $request->phone,
            'address'   => $request->address,
            'is_active' => $request->is_active,
        ]);

        return redirect()->route('tailors.index')
            ->with('success', 'Tukang Jahit baru berhasil ditambahkan.');
    }

    public function edit(Tailor $tailor)
    {
        abort_unless($tailor->tenant_id === Auth::user()->tenant_id, 403);
        return view('inventory.tailors.edit', compact('tailor'));
    }

    public function update(Request $request, Tailor $tailor)
    {
        abort_unless($tailor->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'name'      => 'required|string|max:255',
            'phone'     => 'nullable|string|max:100',
            'address'   => 'nullable|string',
            'is_active' => 'required|boolean',
        ]);

        $tailor->update([
            'name'      => $request->name,
            'phone'     => $request->phone,
            'address'   => $request->address,
            'is_active' => $request->is_active,
        ]);

        return redirect()->route('tailors.index')
            ->with('success', 'Data Tukang Jahit berhasil diperbarui.');
    }

    public function destroy(Tailor $tailor)
    {
        abort_unless($tailor->tenant_id === Auth::user()->tenant_id, 403);
        $tailor->delete();

        return redirect()->route('tailors.index')
            ->with('success', 'Data Tukang Jahit berhasil dihapus.');
    }
}
