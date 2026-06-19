<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Test registration with allowed roles (buyer, seller, driver).
     */
    public function test_user_can_register_with_valid_roles()
    {
        $rolesToTest = ['buyer', 'seller', 'driver'];

        foreach ($rolesToTest as $roleName) {
            $username = 'testuser_' . $roleName;
            $email = $roleName . '@example.com';

            $response = $this->postJson('/api/auth/register', [
                'username' => $username,
                'email' => $email,
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => $roleName,
            ]);

            $response->assertStatus(201);
            $response->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user' => ['id', 'username', 'email'],
                    'active_role',
                    'token',
                ]
            ]);

            $response->assertJsonPath('success', true);
            $response->assertJsonPath('data.active_role', $roleName);

            // Assert database records
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user);
            $this->assertTrue($user->roles->contains('name', $roleName));
        }
    }

    /**
     * Test admin registration is not allowed through the public endpoint.
     */
    public function test_admin_role_cannot_be_registered_publicly()
    {
        $response = $this->postJson('/api/auth/register', [
            'username' => 'adminuser',
            'email' => 'admin@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
    }

    /**
     * Test registration validation.
     */
    public function test_registration_validation()
    {
        $response = $this->postJson('/api/auth/register', [
            'username' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
            'role' => 'invalid-role',
        ]);

        $response->assertStatus(422);
        $response->assertJsonStructure(['message', 'errors']);
        $response->assertJsonValidationErrors(['username', 'email', 'password', 'role']);
    }

    /**
     * Test login with valid credentials.
     */
    public function test_user_can_login()
    {
        $user = User::create([
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $role = Role::where('name', 'buyer')->first();
        $user->roles()->attach($role->id);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'username', 'email'],
                'roles',
                'active_role',
                'token',
            ]
        ]);

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.active_role', 'buyer');
        $response->assertJsonPath('data.roles', ['buyer']);
    }

    /**
     * Test login failure with invalid credentials.
     */
    public function test_login_with_invalid_credentials()
    {
        $user = User::create([
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'john@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        $response->assertJson([
            'success' => false,
            'message' => 'Email atau password salah',
        ]);
    }

    /**
     * Test authenticated logout.
     */
    public function test_user_can_logout()
    {
        $user = User::create([
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $token = $user->createToken('test_token', ['buyer'])->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/logout');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Logout berhasil',
        ]);

        $this->assertEquals(0, $user->tokens()->count());
    }

    /**
     * Test role switching.
     */
    public function test_user_can_switch_role()
    {
        $user = User::create([
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        // Attach both buyer and seller roles
        $buyerRole = Role::where('name', 'buyer')->first();
        $sellerRole = Role::where('name', 'seller')->first();
        $user->roles()->attach([$buyerRole->id, $sellerRole->id]);

        // Create token with buyer role
        $token = $user->createToken('test_token', ['buyer'])->plainTextToken;

        // Switch to seller
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/switch-role', [
            'role' => 'seller',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'active_role',
                'token',
            ]
        ]);

        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.active_role', 'seller');

        // Verify old token was deleted
        $this->assertEquals(1, $user->tokens()->count());

        // Verify new token has seller ability
        $newTokenObj = $user->tokens()->first();
        $this->assertTrue($newTokenObj->can('seller'));
        $this->assertFalse($newTokenObj->can('buyer'));
    }

    /**
     * Test switching to role that the user doesn't own.
     */
    public function test_user_cannot_switch_to_unowned_role()
    {
        $user = User::create([
            'username' => 'john_doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password123'),
        ]);

        $buyerRole = Role::where('name', 'buyer')->first();
        $user->roles()->attach($buyerRole->id);

        $token = $user->createToken('test_token', ['buyer'])->plainTextToken;

        // Attempt to switch to driver (which they do not own)
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->postJson('/api/auth/switch-role', [
            'role' => 'driver',
        ]);

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Role tidak ditemukan',
        ]);
    }
}
