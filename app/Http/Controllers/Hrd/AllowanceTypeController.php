<?php

namespace App\Http\Controllers\Hrd;

use App\Http\Controllers\Controller;
use App\Models\AllowanceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AllowanceTypeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $query = AllowanceType::with('tenant')->orderBy('name');

        if (!$isSuperAdmin) {
            $query->where('tenant_id', $user->tenant_id);
        } else {
            if ($request->filled('tenant_id')) {
                $query->where('tenant_id', $request->tenant_id);
            }
        }

        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        $allowanceTypes = $query->get();

        $tenants = [];
        if ($isSuperAdmin) {
            $tenants = \App\Models\Tenant::orderBy('name')->get();
        }

        return view('hrd.allowance_types.index', compact('allowanceTypes', 'isSuperAdmin', 'tenants'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        $request->validate([
            'name' => 'required|string|max:255',
            'tenant_id' => $isSuperAdmin ? 'required|exists:tenants,id' : 'nullable',
        ]);

        $tenantId = $isSuperAdmin ? $request->tenant_id : $user->tenant_id;

        AllowanceType::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
        ]);

        return back()->with('success', 'Jenis tunjangan berhasil ditambahkan.');
    }

    public function update(Request $request, AllowanceType $allowanceType)
    {
        $user = Auth::user();
        $isSuperAdmin = $user->isSuperAdmin();

        if (!$isSuperAdmin) {
            abort_unless($allowanceType->tenant_id === $user->tenant_id, 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'tenant_id' => $isSuperAdmin ? 'required|exists:tenants,id' : 'nullable',
        ]);

        $data = ['name' => $request->name];
        if ($isSuperAdmin) {
            $data['tenant_id'] = $request->tenant_id;
        }

        $allowanceType->update($data);

        return back()->with('success', 'Jenis tunjangan berhasil diperbarui.');
    }

    public function destroy(AllowanceType $allowanceType)
    {
        $user = Auth::user();
        if (!$user->isSuperAdmin()) {
            abort_unless($allowanceType->tenant_id === $user->tenant_id, 403);
        }
        $allowanceType->delete();
        return back()->with('success', 'Jenis tunjangan berhasil dihapus.');
    }
}
