<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CartModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CartSeeder::class,
        ]);
    }
}
