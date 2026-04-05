<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OrderModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            OrderSeeder::class,
        ]);
    }
}
