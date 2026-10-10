<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OwnerAuthController extends Controller
{
    /**
     * API Login khusus Mobile Owner
     * Mengambil dan mencocokkan data langsung dari tabel `users`
     */
    public function login(Request $request)
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = trim($request->input('login'));
        $password   = $request->input('password');

        // Cari user berdasarkan email atau username/name di tabel users
        $user = User::with('tenant')
            ->where('email', $loginInput)
            ->orWhere('name', $loginInput)
            ->first();

        if (!$user || !Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau kata sandi tidak cocok dengan data pengguna.',
            ], 401);
        }

        // Generate token sesi API ringan
        $apiToken = Str::random(60);

        // Ambil inisial nama untuk avatar mobile
        $nameParts = explode(' ', trim($user->name));
        $initials = '';
        if (count($nameParts) >= 2) {
            $initials = strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1));
        } else {
            $initials = strtoupper(substr($user->name, 0, 2));
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil. Selamat datang kembali, ' . $user->name,
            'data'    => [
                'token' => $apiToken,
                'user'  => [
                    'id'              => $user->id,
                    'name'            => $user->name,
                    'email'           => $user->email,
                    'role'            => $user->role ?? 'owner',
                    'tenant_id'       => $user->tenant_id,
                    'tenant_name'     => $user->tenant ? $user->tenant->name : 'Ruang Seragam',
                    'avatar_initials' => $initials,
                ],
            ],
        ]);
    }

    /**
     * Cek status auth / profil user
     */
    public function me(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Belum terautentikasi.',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data'    => $user,
        ]);
    }
}
