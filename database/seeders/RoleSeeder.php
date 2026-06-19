<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = ['admin', 'seller', 'buyer', 'driver'];

        foreach ($roles as $role) {
            DB::table('roles')->insertOrIgnore(['name' => $role]);
        }
    }
}