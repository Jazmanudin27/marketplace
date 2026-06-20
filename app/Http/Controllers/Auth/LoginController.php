<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $request->login;
        $password = $request->password;
        $remember = $request->boolean('remember');

        // Check if the input looks like an email (contains '@')
        if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
            $credentials = [
                'email'    => $login,
                'password' => $password,
            ];

            if (Auth::guard('web')->attempt($credentials, $remember)) {
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'));
            }

            return back()->withErrors([
                'login' => 'Email atau password yang Anda masukkan salah.',
            ])->onlyInput('login');
        } else {
            $credentials = [
                'username' => $login,
                'password' => $password,
            ];

            if (Auth::guard('employee')->attempt($credentials, $remember)) {
                $request->session()->regenerate();
                return redirect()->intended(route('employee.dashboard'));
            }

            return back()->withErrors([
                'login' => 'Username atau password karyawan salah.',
            ])->onlyInput('login');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}
