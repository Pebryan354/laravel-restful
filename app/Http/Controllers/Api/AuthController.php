<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;

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

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string'
        ], [
            'email.required' => 'Email tidak boleh kosong',
            'email.email' => 'Email harus format email',
            'password.required' => 'Password tidak boleh kosong',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Silahkan periksa kembali inputan anda',
                'error' => 'validation',
                'data' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $credentials['email'])->first();
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Email tidak terdaftar.',
                'error' => 'process'
            ], 422);
        }

        if (! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password salah.',
                'error' => 'process'
            ], 422);
        }

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat token. Silakan coba lagi.',
                'error' => 'process'
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
            if (! auth('api')->getToken()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token tidak ditemukan dalam header Authorization',
                    'error' => 'process'
                ], 422);
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
                'message' => 'Token tidak valid atau sudah expired, silakan login kembali.',
                'error' => 'process'
            ], 500);
        }
    }
}
