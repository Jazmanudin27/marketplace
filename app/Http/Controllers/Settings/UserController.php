<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::user();

        if ($authUser->isSuperAdmin()) {
            // Super Admin: bisa lihat semua tenant
            $tenants = Tenant::where('id', '!=', 1)->orderBy('name')->get();
            $selectedTenantId = $request->get('tenant_id');

            $query = User::with('roles')->where('tenant_id', '!=', 1);

            if ($selectedTenantId) {
                $query->where('tenant_id', $selectedTenantId);
                $tenantId = $selectedTenantId;
            } else {
                $tenantId = null;
            }
        } else {
            // Admin biasa: hanya lihat tenant sendiri
            $tenants = null;
            $selectedTenantId = null;
            $tenantId = $authUser->tenant_id;
            $query = User::with('roles')->where('tenant_id', $tenantId);
        }

        // Filter: pencarian nama/email
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter: role
        if ($request->filled('role')) {
            $roleName = $request->role;
            $query->whereHas('roles', fn($q) => $q->where('name', $roleName));
        }

        $users = $query->orderBy('name')->get();

        // Roles for the add/edit modal dropdown (scoped to selected tenant)
        if ($tenantId) {
            $roles = \Spatie\Permission\Models\Role::where('tenant_id', $tenantId)->get();
        } else {
            // Super Admin without specific tenant: empty for now
            $roles = collect();
        }

        // All distinct role names for filter dropdown bar
        $roleNames = \Spatie\Permission\Models\Role::whereNotNull('tenant_id')
            ->where('tenant_id', '!=', 1)
            ->select('name')->distinct()->pluck('name');

        return view('settings.users.index', compact(
            'users', 'roles', 'tenants', 'selectedTenantId', 'roleNames'
        ));
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();
        $tenantId = $authUser->isSuperAdmin()
            ? $request->input('tenant_id', null)
            : $authUser->tenant_id;

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                }),
            ],
            'password' => 'required|string|min:8',
            'role_id'  => 'required|exists:roles,id',
        ]);

        $role = \Spatie\Permission\Models\Role::where('tenant_id', $tenantId)->findOrFail($request->role_id);

        $user = User::create([
            'tenant_id' => $tenantId,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $role->name,
        ]);

        setPermissionsTeamId($tenantId);
        $user->assignRole($role);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isSuperAdmin()) {
            abort_unless($user->tenant_id === $authUser->tenant_id, 403);
        }

        $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($user->id)->where(function ($query) use ($user) {
                    return $query->where('tenant_id', $user->tenant_id);
                }),
            ],
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = \Spatie\Permission\Models\Role::where('tenant_id', $user->tenant_id)->findOrFail($request->role_id);

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->role  = $role->name;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        setPermissionsTeamId($user->tenant_id);
        $user->syncRoles([$role]);

        return back()->with('success', 'Data Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isSuperAdmin()) {
            abort_unless($user->tenant_id === $authUser->tenant_id, 403);
        }

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();
        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
