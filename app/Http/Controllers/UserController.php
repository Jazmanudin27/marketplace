<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $users = User::where('tenant_id', $tenantId)->get();
        $roles = \Spatie\Permission\Models\Role::where('tenant_id', $tenantId)->get();
        return view('users.index', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->where(function ($query) use ($tenantId) {
                    return $query->where('tenant_id', $tenantId);
                }),
            ],
            'password' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = \Spatie\Permission\Models\Role::where('tenant_id', $tenantId)->findOrFail($request->role_id);

        $user = User::create([
            'tenant_id' => $tenantId,
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $role->name,
        ]);

        setPermissionsTeamId($tenantId);
        $user->assignRole($role);

        return back()->with('success', 'Karyawan berhasil ditambahkan.');
    }

    public function update(Request $request, User $user)
    {
        abort_unless($user->tenant_id === Auth::user()->tenant_id, 403);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users')->ignore($user->id)->where(function ($query) use ($user) {
                    return $query->where('tenant_id', $user->tenant_id);
                }),
            ],
            'role_id' => 'required|exists:roles,id',
        ]);

        $role = \Spatie\Permission\Models\Role::where('tenant_id', $user->tenant_id)->findOrFail($request->role_id);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $role->name;
        
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        setPermissionsTeamId($user->tenant_id);
        $user->syncRoles([$role]);

        return back()->with('success', 'Data Karyawan berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        abort_unless($user->tenant_id === Auth::user()->tenant_id, 403);
        
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();
        return back()->with('success', 'Karyawan berhasil dihapus.');
    }
}
