<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Get admin role
        $adminRole = Role::where('name', 'admin')->first();

        if (!$adminRole) {
            $this->command->error('Admin role not found. Please run RoleSeeder first.');
            return;
        }

        // Check if admin user already exists (support both old and new format)
        $existingAdmin = User::where('email', 'admin+seapedia@email.com')
            ->orWhere('email', 'admin@seapedia.com')
            ->first();

        if ($existingAdmin) {
            // Attach admin role if not already attached
            if (!$existingAdmin->roles()->where('role_id', $adminRole->id)->exists()) {
                $existingAdmin->roles()->attach($adminRole->id);
            }
            $this->command->info('Admin user already exists: admin+seapedia@email.com');
            return;
        }

        // Create admin user with new format
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin+seapedia@email.com',
            'password' => Hash::make('seapedia123'),
        ]);

        $admin->roles()->attach($adminRole->id);

        $this->command->info('Admin user created:');
        $this->command->info('  Email: admin+seapedia@email.com');
        $this->command->info('  Password: seapedia123');
    }
}
