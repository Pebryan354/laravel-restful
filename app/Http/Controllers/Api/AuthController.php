<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'message' => 'User created successfully',
            'user' => $user,
            'token' => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);

        $user = User::where('email', $credentials['email'])->first();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak terdaftar.',
            ], 404);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password salah.',
            ], 401);
        }

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat token. Silakan coba lagi.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil.',
            'token' => $token,
            'user' => $user
        ]);
    }

    public function logout()
    {
        auth('api')->logout();
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil.'
        ]);
    }

    public function refresh()
    {
        try {
            if (! $oldToken = auth('api')->getToken()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token tidak ditemukan dalam header Authorization'
                ], 401);
            }

            $newToken = auth('api')->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Token refresh berhasil',
                'token' => $newToken,
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid atau sudah expired, silakan login kembali.'
            ], 401);
        }
    }
}
