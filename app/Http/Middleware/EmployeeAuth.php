<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EmployeeAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::guard('employee')->check()) {
            return redirect()->route('employee.login');
        }

        return $next($request);
    }
}
