<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SwitchRoleRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'username' => $validated['username'],
            'email'    => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        $role = Role::where('name', $validated['role'])->first();
        
        if (!$role) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak ditemukan. Pastikan data role sudah ada di database.',
            ], 422);
        }
        
        $user->roles()->attach($role->id);

        $token = $user->createToken('auth_token', [$validated['role']])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Registrasi berhasil',
            'data'    => [
                'user'         => $user,
                'active_role'  => $validated['role'],
                'token'        => $token,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::with('roles')->where('email', $validated['email'])->first();

        if (!$user || !Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau password salah',
            ], 401);
        }

        $roles = $user->roles->pluck('name')->toArray();
        $activeRole = $roles[0] ?? 'buyer';

        $token = $user->createToken('auth_token', [$activeRole])->plainTextToken;

        // Format user data dengan username
        $userData = [
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'roles' => $user->roles->map(function($role) {
                return ['id' => $role->id, 'name' => $role->name];
            })->toArray(),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data'    => [
                'user'        => $userData,
                'roles'       => $roles,
                'active_role' => $activeRole,
                'token'       => $token,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    public function switchRole(SwitchRoleRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $roles = $user->roles->pluck('name')->toArray();

        if (!in_array($validated['role'], $roles)) {
            return response()->json([
                'success' => false,
                'message' => 'Role tidak ditemukan',
            ], 403);
        }

        $user->currentAccessToken()->delete();

        $token = $user->createToken('auth_token', [$validated['role']])->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Role berhasil diganti',
            'data'    => [
                'active_role' => $validated['role'],
                'token'       => $token,
            ],
        ]);
    }
}