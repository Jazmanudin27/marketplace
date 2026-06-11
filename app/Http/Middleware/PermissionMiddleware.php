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

        // Admin (Owner) memiliki akses penuh tanpa terkecuali
        if ($user->role === 'admin' || $user->hasRole('admin')) {
            return $next($request);
        }

        // Periksa apakah user memiliki permission yang dibutuhkan
        if (!$user->hasPermissionTo($permission)) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki izin (' . $permission . ') untuk mengakses halaman ini.');
        }

        return $next($request);
    }
}
