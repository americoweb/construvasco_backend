<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PermissionRoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->createPermissions();
        $this->cleanupLegacyRoles();
        $this->createRoles();
        $this->assignPermissionsToRoles();
    }

    private function cleanupLegacyRoles(): void
    {
        $allowedRoles = ['admin', 'project_manager', 'technician', 'customer'];
        $rolesToDelete = Role::query()
            ->where('guard_name', 'api')
            ->whereNotIn('name', $allowedRoles)
            ->pluck('id');

        if ($rolesToDelete->isNotEmpty()) {
            DB::table('tenant_users')->whereIn('role_id', $rolesToDelete)->delete();
            DB::table('model_has_roles')->whereIn('role_id', $rolesToDelete)->delete();
            DB::table('role_has_permissions')->whereIn('role_id', $rolesToDelete)->delete();
            Role::query()->whereIn('id', $rolesToDelete)->delete();
        }
    }

    private function createPermissions(): void
    {
        $names = [
            'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage_roles', 'users.manage_permissions', 'users.*',
            'team.view', 'team.create', 'team.edit', 'team.delete', 'team.*',
            'clients.view', 'clients.create', 'clients.edit', 'clients.*',
            'projects.view', 'projects.create', 'projects.edit', 'projects.delete', 'projects.assign', 'projects.review', 'projects.manage_team', 'projects.*',
            'project_requests.view', 'project_requests.create', 'project_requests.edit', 'project_requests.approve', 'project_requests.reject', 'project_requests.*',
            'quotes.create', 'quotes.send', 'quotes.view', 'quotes.accept', 'quotes.reject', 'quotes.*',
            'project_phases.update', 'project_phases.*',
            'deliverables.upload', 'deliverables.delete', 'deliverables.download', 'deliverables.*',
            'projects.submit_for_review',
            'templates.view', 'templates.create', 'templates.edit', 'templates.delete', 'templates.*',
            'credit_packages.view', 'credit_packages.create', 'credit_packages.edit', 'credit_packages.*',
            'credits.purchase', 'credits.view',
            'payments.view', 'payments.create', 'payments.edit', 'payments.delete', 'payments.*',
            'finance.view', 'finance.create', 'finance.edit', 'finance.approve_budget', 'finance.*',
            'reports.view', 'reports.*',
            'ai.generate', 'ai.config.view', 'ai.config.edit', 'ai.config.*',
            'system.settings.view', 'system.settings.edit', 'system.settings.*',
            'notifications.send', 'notifications.view',
            'tenants.view', 'tenants.edit', 'tenants.manage_users', 'tenants.manage_settings',
            'assignments.view', 'assignments.create', 'assignments.edit', 'assignments.delete', 'assignments.*',
            'portfolio.view', 'portfolio.create', 'portfolio.edit', 'portfolio.delete', 'portfolio.*',
            'admin.*',
        ];

        foreach ($names as $name) {
            $category = explode('.', $name)[0];
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'api'],
                ['category' => $category, 'description' => $name]
            );
        }
    }

    private function createRoles(): void
    {
        foreach ([
            ['name' => 'admin', 'display_name' => 'Administrator', 'is_system' => true],
            ['name' => 'project_manager', 'display_name' => 'Gestor de Projectos', 'is_system' => false],
            ['name' => 'technician', 'display_name' => 'Técnico / Desenhista', 'is_system' => false],
            ['name' => 'customer', 'display_name' => 'Cliente', 'is_system' => true],
        ] as $role) {
            Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => 'api'],
                array_merge($role, ['description' => $role['display_name']])
            );
        }
    }

    private function assignPermissionsToRoles(): void
    {
        Role::findByName('admin', 'api')?->syncPermissions(Permission::where('guard_name', 'api')->pluck('name'));

        Role::findByName('project_manager', 'api')?->syncPermissions([
            'project_requests.view', 'project_requests.edit', 'project_requests.approve', 'project_requests.reject',
            'quotes.create', 'quotes.send', 'quotes.view',
            'projects.view', 'projects.assign', 'projects.review',
            'team.view', 'clients.view', 'notifications.send',
                'assignments.view', 'assignments.create', 'assignments.edit',
                'payments.view', 'payments.create',
            'portfolio.view',
        ]);

        Role::findByName('technician', 'api')?->syncPermissions([
            'projects.view', 'project_phases.update',
            'deliverables.upload', 'deliverables.delete',
            'projects.submit_for_review',
        ]);

        Role::findByName('customer', 'api')?->syncPermissions([
            'project_requests.create', 'project_requests.view',
            'ai.generate', 'credits.purchase', 'credits.view',
            'quotes.accept', 'quotes.reject', 'quotes.view',
            'projects.view', 'payments.create', 'payments.view',
            'deliverables.download',
        ]);
    }
}
