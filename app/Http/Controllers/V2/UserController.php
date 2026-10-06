<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PermissionRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private function getPermissionGroups()
    {
        return PermissionRegistry::getPermissionGroups();
    }

    public function index(Request $request)
    {
        $authUser = Auth::user();

        if ($authUser->isSuperAdmin()) {
            $tenants = Tenant::where('id', '!=', 1)->orderBy('name')->get();
            $selectedTenantId = $request->get('tenant_id', $authUser->tenant_id);

            $query = User::with(['roles', 'permissions', 'tenant'])->where('tenant_id', '!=', 1);

            if ($selectedTenantId && $selectedTenantId > 1) {
                $query->where('tenant_id', $selectedTenantId);
                $tenantId = $selectedTenantId;
            } else {
                $tenantId = null;
                $selectedTenantId = null;
            }
        } else {
            $tenants = null;
            $selectedTenantId = null;
            $tenantId = $authUser->tenant_id;
            $query = User::with(['roles', 'permissions', 'tenant'])->where('tenant_id', $tenantId);
        }

        if ($tenantId) {
            setPermissionsTeamId($tenantId);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $roleName = $request->role;
            $query->whereHas('roles', fn($q) => $q->where('name', $roleName));
        }

        $users = $query->orderBy('name')->get();

        if ($tenantId) {
            $roles = Role::where('tenant_id', $tenantId)->get();
        } else {
            $roles = collect();
        }

        $roleNames = Role::whereNotNull('tenant_id')
            ->where('tenant_id', '!=', 1)
            ->select('name')->distinct()->pluck('name');

        $permissionGroups = $this->getPermissionGroups();

        // Calculate role breakdown stats
        $totalUsers = $users->count();
        $adminCount = $users->filter(fn($u) => $u->roles->contains(fn($r) => in_array($r->name, ['admin', 'owner'])))->count();
        $warehouseCount = $users->filter(fn($u) => $u->roles->contains('name', 'warehouse'))->count();
        $financeCount = $users->filter(fn($u) => $u->roles->contains('name', 'finance'))->count();

        return view('v2.users.index', compact(
            'users',
            'roles',
            'tenants',
            'selectedTenantId',
            'roleNames',
            'permissionGroups',
            'totalUsers',
            'adminCount',
            'warehouseCount',
            'financeCount'
        ));
    }

    public function store(Request $request)
    {
        $authUser = Auth::user();
        $tenantId = $authUser->isSuperAdmin()
            ? $request->input('tenant_id', $authUser->tenant_id)
            : $authUser->tenant_id;

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                }),
            ],
            'password' => 'required|string|min:8',
            'role_id'  => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
        ]);

        $role = Role::where('tenant_id', $tenantId)->findOrFail($request->role_id);

        $user = User::create([
            'tenant_id' => $tenantId,
            'name'      => $request->name,
            'email'     => $request->email,
            'password'  => Hash::make($request->password),
            'role'      => $role->name,
        ]);

        setPermissionsTeamId($tenantId);
        $user->assignRole($role);

        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions);
        } else {
            $user->syncPermissions([]);
        }

        return redirect()->route('v2.users.index')->with('success', "Pengguna {$user->name} berhasil ditambahkan.");
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
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id)->where(function ($query) use ($user) {
                    return $query->where('tenant_id', $user->tenant_id);
                }),
            ],
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = Role::where('tenant_id', $user->tenant_id)->findOrFail($request->role_id);

        $user->name  = $request->name;
        $user->email = $request->email;
        $user->role  = $role->name;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        setPermissionsTeamId($user->tenant_id);
        $user->syncRoles([$role]);

        return redirect()->route('v2.users.index')->with('success', "Data pengguna {$user->name} berhasil diperbarui.");
    }

    public function editPermissions(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isSuperAdmin()) {
            abort_unless($user->tenant_id === $authUser->tenant_id, 403);
        }

        setPermissionsTeamId($user->tenant_id);

        $userPermissions = $user->permissions->pluck('name')->toArray();
        $permissionGroups = $this->getPermissionGroups();

        return view('v2.users.permissions', compact('user', 'userPermissions', 'permissionGroups'));
    }

    public function updatePermissions(Request $request, User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isSuperAdmin()) {
            abort_unless($user->tenant_id === $authUser->tenant_id, 403);
        }

        $request->validate([
            'permissions' => 'nullable|array',
        ]);

        setPermissionsTeamId($user->tenant_id);
        $user->syncPermissions($request->permissions ?? []);

        return redirect()->route('v2.users.index')->with('success', "Hak akses khusus untuk {$user->name} berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        $authUser = Auth::user();

        if (!$authUser->isSuperAdmin()) {
            abort_unless($user->tenant_id === $authUser->tenant_id, 403);
        }

        if ($user->id === Auth::id()) {
            return redirect()->route('v2.users.index')->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $userName = $user->name;
        $user->delete();

        return redirect()->route('v2.users.index')->with('success', "Pengguna {$userName} berhasil dihapus.");
    }

    public function impersonate(Request $request, User $user)
    {
        $currentUser = Auth::user();

        $actualAdmin = session()->has('impersonator_id')
            ? User::find(session('impersonator_id'))
            : $currentUser;

        if (!$actualAdmin || (!$actualAdmin->isSuperAdmin() && $actualAdmin->role !== 'admin' && !$actualAdmin->can('manage-users'))) {
            abort(403, 'Anda tidak memiliki hak untuk melakukan simulasi user.');
        }

        if ($currentUser->id === $user->id) {
            return redirect()->back()->with('error', 'Anda sudah berada di akun pengguna ini.');
        }

        if (!session()->has('impersonator_id')) {
            session(['impersonator_id' => $actualAdmin->id]);
        }

        Auth::login($user);

        $roleLabel = $user->roles->first()?->name ?? $user->role ?? 'User';
        return redirect()->route('v2.dashboard')->with('success', "Simulasi Mode Aktif: Masuk sebagai {$user->name} (" . ucfirst($roleLabel) . ").");
    }
}
