<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DesignModuleSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            DesignSeeder::class,
        ]);
    }
}
