<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:100|unique:users',
            'email'    => 'required|email|max:150|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:buyer,seller,driver',
        ]);

        $user = User::create([
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        $role = Role::where('name', $request->role)->first();
        $user->roles()->attach($role->id);

        $token = $user->createToken('auth_token', [$request->role])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data'    => [
                'user'         => $user,
                'active_role'  => $request->role,
                'token'        => $token,
            ],
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::with('roles')->where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah',
            ], 401);
        }

        $roles = $user->roles->pluck('name')->toArray();
        $activeRole = $roles[0];

        $token = $user->createToken('auth_token', [$activeRole])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'user'        => $user,
                'roles'       => $roles,
                'active_role' => $activeRole,
                'token'       => $token,
            ],
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    public function switchRole(Request $request)
    {
        $request->validate([
            'role' => 'required|in:buyer,seller,driver',
        ]);

        $user = $request->user();
        $roles = $user->roles->pluck('name')->toArray();

        if (!in_array($request->role, $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak ditemukan',
            ], 403);
        }

        // Invalidate token lama
        $user->currentAccessToken()->delete();

        // Issue token baru dengan role baru
        $token = $user->createToken('auth_token', [$request->role])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil diganti',
            'data'    => [
                'active_role' => $request->role,
                'token'       => $token,
            ],
        ]);
    }
}