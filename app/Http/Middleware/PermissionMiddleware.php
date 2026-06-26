<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Super Admin and Owner have full access to everything
        if ($user->role === 'super-admin' || $user->role === 'owner' || $user->hasRole('owner')) {
            return $next($request);
        }

        // Admin has full access EXCEPT for company settings
        if ($permission !== 'settings.tenant.edit') {
            if ($user->role === 'admin' || $user->hasRole('admin')) {
                return $next($request);
            }
        } else {
            if ($user->role === 'admin' || $user->hasRole('admin')) {
                abort(403, 'Akses Ditolak: Administrator tidak diizinkan mengakses halaman ini.');
            }
        }

        // Periksa apakah user memiliki permission yang dibutuhkan
        if (!$user->hasPermissionTo($permission)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin (' . $permission . ') untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
