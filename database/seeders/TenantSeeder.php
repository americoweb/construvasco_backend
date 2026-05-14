<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Settings\Tenant;
use App\Models\User;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $admin = User::where('identifier', 'admin@construvasco.co.mz')->first();

        $tenants = [
            [
                'name' => 'Construvasco',
                'slug' => 'construvasco',
                'domain' => 'construvasco.local',
                'database' => 'constru_db',
                'settings' => json_encode([
                    'timezone' => 'Africa/Maputo',
                    'locale' => 'pt',
                    'currency' => 'MZN'
                ]),
                'is_active' => true,
                'created_by' => $admin?->id
            ]
        ];

        $allowedSlugs = array_column($tenants, 'slug');
        Tenant::whereNotIn('slug', $allowedSlugs)->delete();

        foreach ($tenants as $tenantData) {
            Tenant::updateOrCreate(
                ['slug' => $tenantData['slug']],
                $tenantData
            );
        }
    }
} 