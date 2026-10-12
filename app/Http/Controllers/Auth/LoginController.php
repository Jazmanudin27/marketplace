<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('employee')->check()) {
            return redirect()->route('employee.dashboard');
        }
        if (Auth::check()) {
            return redirect()->route('v2.dashboard');
        }
        return view('auth.login');
    }

    public function showMobileLogin()
    {
        if (Auth::check()) {
            return redirect()->route('mobile.index');
        }
        return view('auth.mobile_login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($request->login);
        $password = $request->password;
        $remember = $request->boolean('remember');
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

        // 1. Coba login sebagai User / Owner / Admin (Guard: web -> tabel users)
        if ($isEmail) {
            if (Auth::guard('web')->attempt(['email' => $login, 'password' => $password], $remember)) {
                $request->session()->regenerate();
                return redirect()->intended(route('v2.dashboard'));
            }
        } else {
            // Cek di tabel users via nama atau email
            if (Auth::guard('web')->attempt(['name' => $login, 'password' => $password], $remember) ||
                Auth::guard('web')->attempt(['email' => $login, 'password' => $password], $remember)) {
                $request->session()->regenerate();
                return redirect()->intended(route('v2.dashboard'));
            }
        }

        // 2. Coba login sebagai Karyawan (Guard: employee -> tabel employees)
        $employeeField = $isEmail ? 'email' : 'username';
        if (Auth::guard('employee')->attempt([$employeeField => $login, 'password' => $password], $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('employee.dashboard'));
        }

        return back()->withErrors([
            'login' => 'Email/Username atau password yang Anda masukkan salah.',
        ])->onlyInput('login');
    }

    public function mobileLogin(Request $request)
    {
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = trim($request->login);
        $password = $request->password;
        $remember = $request->boolean('remember');
        $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);

        // 1. Coba login sebagai User / Owner / Admin (Guard: web -> tabel users)
        if ($isEmail) {
            if (Auth::guard('web')->attempt(['email' => $login, 'password' => $password], $remember)) {
                $request->session()->regenerate();
                return redirect()->intended(route('mobile.index'));
            }
        } else {
            if (Auth::guard('web')->attempt(['name' => $login, 'password' => $password], $remember) ||
                Auth::guard('web')->attempt(['email' => $login, 'password' => $password], $remember)) {
                $request->session()->regenerate();
                return redirect()->intended(route('mobile.index'));
            }
        }

        // 2. Coba login sebagai Karyawan (Guard: employee -> tabel employees)
        $employeeField = $isEmail ? 'email' : 'username';
        if (Auth::guard('employee')->attempt([$employeeField => $login, 'password' => $password], $remember)) {
            $request->session()->regenerate();
            return redirect()->intended(route('employee.dashboard'));
        }

        return back()->withErrors([
            'login' => 'Email/Username atau password yang Anda masukkan salah.',
        ])->onlyInput('login');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function mobileLogout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('mobile.login');
    }
}
