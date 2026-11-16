<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            ['name' => 'Maya Enora Studio', 'slug' => 'maya-enora-studio'],
            ['name' => 'Baseus', 'slug' => 'baseus'],
            ['name' => 'Xiaomi Ecosystem', 'slug' => 'xiaomi'],
            ['name' => 'Joyroom', 'slug' => 'joyroom'],
            ['name' => 'Remax', 'slug' => 'remax'],
            ['name' => 'Ugreen', 'slug' => 'ugreen'],
        ];

        foreach ($brands as $b) {
            Brand::create($b);
        }
    }
}

