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
use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'SEAPEDIA API Documentation',
    version: '1.0.0',
    description: 'RESTful API untuk platform marketplace hasil laut segar SEAPEDIA'
)]
#[OA\Server(url: '/api', description: 'API Server')]
#[OA\SecurityScheme(
    securityScheme: 'sanctum',
    type: 'apiKey',
    name: 'Authorization',
    in: 'header'
)]
class AuthController extends Controller
{
    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Register new user',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['username', 'email', 'password', 'role'],
                properties: [
                    new OA\Property(property: 'username', type: 'string', example: 'johndoe'),
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'john@example.com'),
                    new OA\Property(property: 'password', type: 'string', minLength: 6, example: 'password123'),
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'seller', 'buyer', 'driver'], example: 'buyer')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Registration successful'),
            new OA\Response(response: 422, description: 'Validation error')
        ]
    )]
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

    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Login user',
        tags: ['Authentication'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email', example: 'admin+seapedia@email.com'),
                    new OA\Property(property: 'password', type: 'string', example: 'seapedia123')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login successful'),
            new OA\Response(response: 401, description: 'Invalid credentials')
        ]
    )]
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

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout user',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Logout successful')
        ]
    )]
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);
    }

    #[OA\Post(
        path: '/api/auth/switch-role',
        summary: 'Switch user role',
        tags: ['Authentication'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['role'],
                properties: [
                    new OA\Property(property: 'role', type: 'string', enum: ['admin', 'seller', 'buyer', 'driver'], example: 'seller')
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Role switched successfully'),
            new OA\Response(response: 403, description: 'Role not found')
        ]
    )]
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
