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

        // Check if admin user already exists
        $existingAdmin = User::where('email', 'admin@seapedia.com')->first();

        if ($existingAdmin) {
            // Attach admin role if not already attached
            if (!$existingAdmin->roles()->where('role_id', $adminRole->id)->exists()) {
                $existingAdmin->roles()->attach($adminRole->id);
            }
            $this->command->info('Admin user already exists: admin@seapedia.com');
            return;
        }

        // Create admin user
        $admin = User::create([
            'username' => 'admin',
            'email' => 'admin@seapedia.com',
            'password' => Hash::make('admin123'),
        ]);

        $admin->roles()->attach($adminRole->id);

        $this->command->info('Admin user created:');
        $this->command->info('  Email: admin@seapedia.com');
        $this->command->info('  Password: admin123');
    }
}
