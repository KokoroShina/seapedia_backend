<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountsSeeder extends Seeder
{
    /**
     * Seed demo accounts untuk semua role.
     * Email format: role+seapedia@email.com
     * Password: seapedia123
     */
    public function run(): void
    {
        $roles = ['admin', 'seller', 'buyer', 'driver'];

        foreach ($roles as $roleName) {
            $this->createUserForRole($roleName);
        }
    }

    private function createUserForRole(string $roleName): void
    {
        $email = "{$roleName}+seapedia@email.com";

        // Get role
        $role = Role::where('name', $roleName)->first();

        if (!$role) {
            $this->command->error("Role '{$roleName}' not found. Please run RoleSeeder first.");
            return;
        }

        // Check if user already exists
        $existingUser = User::where('email', $email)->first();

        if ($existingUser) {
            // Attach role if not already attached
            if (!$existingUser->roles()->where('role_id', $role->id)->exists()) {
                $existingUser->roles()->attach($role->id);
            }
            $this->command->info("User already exists: {$email}");
            return;
        }

        // Create user (use unique username to avoid conflicts)
        $uniqueUsername = $roleName . '_user';
        $counter = 1;
        while (User::where('username', $uniqueUsername)->exists()) {
            $uniqueUsername = $roleName . '_user_' . $counter;
            $counter++;
        }

        $user = User::create([
            'username' => $uniqueUsername,
            'email' => $email,
            'password' => Hash::make('seapedia123'),
        ]);

        $user->roles()->attach($role->id);

        $this->command->info("Created: {$email} / seapedia123 (username: {$uniqueUsername})");
    }
}
