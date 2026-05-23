<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Services\Credits\CreditService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionRoleSeeder::class,
            CreditPackageSeeder::class,
            ProjectTemplateSeeder::class,
            UserSeeder::class,
            TenantSeeder::class,
            TenantUserSeeder::class,
            ConstructionProjectSeeder::class,
            DemoFlowSeeder::class,
        ]);

        $cliente = User::where('identifier', 'cliente@construvasco.co.mz')->first();
        if ($cliente) {
            app(CreditService::class)->grantInitialCredits($cliente);
        }
    }
}
