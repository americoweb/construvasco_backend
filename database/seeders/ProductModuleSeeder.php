<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class ProductModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CategorySeeder::class,  // Categories must be seeded first
            TagSeeder::class,        // Tags must be seeded before products
            ProductSeeder::class,    // Products depend on categories and tags
        ]);
    }
}
