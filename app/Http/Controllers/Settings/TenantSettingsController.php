<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantSettingsController extends Controller
{
    public function edit(Request $request)
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            // Super Admin: bisa memilih tenant mana yang akan dikonfigurasi
            $tenants = Tenant::where('id', '!=', 1)->orderBy('name')->get();
            $defaultTenantId = ($user->tenant_id && $user->tenant_id > 1) ? $user->tenant_id : $tenants->first()?->id;
            $selectedTenantId = $request->get('tenant_id', $defaultTenantId);
            $tenant = Tenant::find($selectedTenantId);

            if (!$tenant) {
                return redirect()->route('settings.tenant.edit')->with('error', 'Perusahaan tidak ditemukan.');
            }

            return view('settings.tenant', compact('tenant', 'tenants', 'selectedTenantId'));
        }

        // Regular admin: hanya bisa akses tenant sendiri
        if (!$user->isAdmin()) {
            abort(403, 'Hanya Administrator yang dapat mengakses halaman ini.');
        }

        $tenant = $user->tenant;
        $tenants = null;
        $selectedTenantId = null;

        return view('settings.tenant', compact('tenant', 'tenants', 'selectedTenantId'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if ($user->isSuperAdmin()) {
            // Super Admin: update tenant yang dipilih
            $tenantId = $request->input('tenant_id');
            $tenant = Tenant::where('id', '!=', 1)->findOrFail($tenantId);
        } elseif ($user->isAdmin()) {
            $tenant = $user->tenant;
        } else {
            abort(403, 'Hanya Administrator yang dapat mengubah pengaturan.');
        }

        $request->validate([
            'name'             => 'required|string|max:255',
            'cutoff_start_day' => 'required|integer|min:1|max:28',
            'office_latitude'  => 'nullable|numeric|between:-90,90',
            'office_longitude' => 'nullable|numeric|between:-180,180',
            'office_radius'    => 'nullable|integer|min:5|max:1000',
        ]);

        $tenant->update([
            'name'             => $request->name,
            'cutoff_start_day' => $request->cutoff_start_day,
            'office_latitude'  => $request->office_latitude,
            'office_longitude' => $request->office_longitude,
            'office_radius'    => $request->office_radius ?? 20,
        ]);

        $redirectUrl = route('settings.tenant.edit');
        if ($user->isSuperAdmin()) {
            $redirectUrl .= '?tenant_id=' . $tenant->id;
        }

        return redirect($redirectUrl)->with('success', 'Pengaturan perusahaan "' . $tenant->name . '" berhasil diperbarui.');
    }
}
