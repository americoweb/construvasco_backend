<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Settings\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class TenantUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'construvasco')->firstOrFail();

        $admin = User::where('identifier', 'admin@construvasco.co.mz')->firstOrFail();
        $projectManager = User::where('identifier', 'gestor@construvasco.co.mz')->firstOrFail();
        $customer = User::where('identifier', 'cliente@construvasco.co.mz')->firstOrFail();

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $projectManagerRole = Role::where('name', 'project_manager')->firstOrFail();
        $customerRole = Role::where('name', 'customer')->firstOrFail();

        DB::table('tenant_users')->delete();

        $tenantUsers = [
            [
                'tenant_id' => $tenant->id,
                'user_id' => $admin->id,
                'role_id' => $adminRole->id,
                'permissions' => json_encode(['admin.*']),
                'current_tenant' => true,
                'status' => 'active',
            ],
            [
                'tenant_id' => $tenant->id,
                'user_id' => $projectManager->id,
                'role_id' => $projectManagerRole->id,
                'permissions' => json_encode(['projects.*', 'assignments.*']),
                'current_tenant' => false,
                'status' => 'active',
            ],
            [
                'tenant_id' => $tenant->id,
                'user_id' => $customer->id,
                'role_id' => $customerRole->id,
                'permissions' => json_encode(['projects.view', 'payments.create']),
                'current_tenant' => false,
                'status' => 'active',
            ],
        ];

        DB::table('tenant_users')->insert($tenantUsers);
    }
} 