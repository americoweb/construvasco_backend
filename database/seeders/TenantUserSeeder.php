<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Settings\Tenant;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class TenantUserSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('slug', 'construvasco')->firstOrFail();

        $admin = User::where('identifier', 'admin@construvasco.co.mz')->firstOrFail();
        $projectManager = User::where('identifier', 'gestor@construvasco.co.mz')->firstOrFail();
        $technician = User::where('identifier', 'tecnico@construvasco.co.mz')->firstOrFail();
        $customer = User::where('identifier', 'cliente@construvasco.co.mz')->firstOrFail();

        $adminRole = Role::where('name', 'admin')->firstOrFail();
        $projectManagerRole = Role::where('name', 'project_manager')->firstOrFail();
        $technicianRole = Role::where('name', 'technician')->firstOrFail();
        $customerRole = Role::where('name', 'customer')->firstOrFail();

        DB::table('tenant_users')->delete();
        DB::table('model_has_roles')->whereIn('model_id', [
            $admin->id,
            $projectManager->id,
            $technician->id,
            $customer->id,
        ])->delete();

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
                'user_id' => $technician->id,
                'role_id' => $technicianRole->id,
                'permissions' => json_encode(['projects.view', 'deliverables.*']),
                'current_tenant' => false,
                'status' => 'active',
            ],
            [
                'tenant_id' => $tenant->id,
                'user_id' => $customer->id,
                'role_id' => $customerRole->id,
                'permissions' => json_encode(['projects.view', 'payments.create', 'credits.purchase']),
                'current_tenant' => false,
                'status' => 'active',
            ],
        ];

        DB::table('tenant_users')->insert($tenantUsers);

        $roleMap = [
            $admin->id => $adminRole->id,
            $projectManager->id => $projectManagerRole->id,
            $technician->id => $technicianRole->id,
            $customer->id => $customerRole->id,
        ];

        foreach ($roleMap as $userId => $roleId) {
            DB::table('model_has_roles')->updateOrInsert(
                [
                    'role_id' => $roleId,
                    'model_type' => User::class,
                    'model_id' => $userId,
                    'tenant_id' => $tenant->id,
                ],
                []
            );
        }
    }
}
