<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionRoleSeeder::class,
            UserSeeder::class,
            TenantSeeder::class,
            TenantUserSeeder::class,
            
            // Amazing Print-on-Demand Module Seeders
            ProductModuleSeeder::class,  // Products must be seeded first
            DesignModuleSeeder::class,   // Designs depend on products
            CartModuleSeeder::class,     // Cart depends on products
            OrderModuleSeeder::class,     // Orders depend on products and carts
            TestimonialSeeder::class,    // Testimonials depend on products
        ]);
    }
}
