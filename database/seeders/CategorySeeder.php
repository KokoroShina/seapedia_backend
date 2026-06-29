<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Makanan', 'icon' => '🍔'],
            ['name' => 'Minuman', 'icon' => '🥤'],
            ['name' => 'Elektronik', 'icon' => '📱'],
            ['name' => 'Fashion', 'icon' => '👕'],
            ['name' => 'Kesehatan', 'icon' => '💊'],
            ['name' => 'Olahraga', 'icon' => '⚽'],
            ['name' => 'Rumah Tangga', 'icon' => '🏠'],
            ['name' => 'Kecantikan', 'icon' => '💄'],
            ['name' => 'Buku', 'icon' => '📚'],
            ['name' => 'Mainan', 'icon' => '🧸'],
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category['name'],
                'slug' => Str::slug($category['name']),
                'icon' => $category['icon'],
                'is_active' => true,
            ]);
        }

        $this->command->info('Berhasil membuat ' . count($categories) . ' kategori default!');
    }
}
