<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\DB;

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
        $allowedRoles = ['admin', 'project_manager', 'customer'];
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
        $permissions = [
            // Tenant Management
            ['name' => 'tenants.view', 'category' => 'tenants', 'description' => 'View tenant information'],
            ['name' => 'tenants.create', 'category' => 'tenants', 'description' => 'Create new tenants'],
            ['name' => 'tenants.edit', 'category' => 'tenants', 'description' => 'Edit tenant information'],
            ['name' => 'tenants.delete', 'category' => 'tenants', 'description' => 'Delete tenants'],
            ['name' => 'tenants.manage_users', 'category' => 'tenants', 'description' => 'Manage tenant users'],
            ['name' => 'tenants.manage_settings', 'category' => 'tenants', 'description' => 'Manage tenant settings'],
            
            // User Management
            ['name' => 'users.view', 'category' => 'users', 'description' => 'View users'],
            ['name' => 'users.create', 'category' => 'users', 'description' => 'Create new users'],
            ['name' => 'users.edit', 'category' => 'users', 'description' => 'Edit user information'],
            ['name' => 'users.delete', 'category' => 'users', 'description' => 'Delete users'],
            ['name' => 'users.manage_roles', 'category' => 'users', 'description' => 'Manage user roles'],
            ['name' => 'users.manage_permissions', 'category' => 'users', 'description' => 'Manage user permissions'],
            
            // Form Engine
            ['name' => 'forms.view', 'category' => 'forms', 'description' => 'View forms'],
            ['name' => 'forms.create', 'category' => 'forms', 'description' => 'Create new forms'],
            ['name' => 'forms.edit', 'category' => 'forms', 'description' => 'Edit forms'],
            ['name' => 'forms.delete', 'category' => 'forms', 'description' => 'Delete forms'],
            ['name' => 'forms.submit', 'category' => 'forms', 'description' => 'Submit forms'],
            ['name' => 'forms.approve', 'category' => 'forms', 'description' => 'Approve form submissions'],
            
            // Project Management
            ['name' => 'projects.view', 'category' => 'projects', 'description' => 'View projects'],
            ['name' => 'projects.create', 'category' => 'projects', 'description' => 'Create new projects'],
            ['name' => 'projects.edit', 'category' => 'projects', 'description' => 'Edit projects'],
            ['name' => 'projects.delete', 'category' => 'projects', 'description' => 'Delete projects'],
            ['name' => 'projects.manage_team', 'category' => 'projects', 'description' => 'Manage project team'],

            // Assignment Management
            ['name' => 'assignments.view', 'category' => 'assignments', 'description' => 'View project assignments'],
            ['name' => 'assignments.create', 'category' => 'assignments', 'description' => 'Create project assignments'],
            ['name' => 'assignments.edit', 'category' => 'assignments', 'description' => 'Edit project assignments'],
            ['name' => 'assignments.delete', 'category' => 'assignments', 'description' => 'Delete project assignments'],

            // Portfolio Management
            ['name' => 'portfolio.view', 'category' => 'portfolio', 'description' => 'View portfolio items'],
            ['name' => 'portfolio.create', 'category' => 'portfolio', 'description' => 'Create portfolio items'],
            ['name' => 'portfolio.edit', 'category' => 'portfolio', 'description' => 'Edit portfolio items'],
            ['name' => 'portfolio.delete', 'category' => 'portfolio', 'description' => 'Delete portfolio items'],

            // Payment Management
            ['name' => 'payments.view', 'category' => 'payments', 'description' => 'View project payments'],
            ['name' => 'payments.create', 'category' => 'payments', 'description' => 'Create project payments'],
            ['name' => 'payments.edit', 'category' => 'payments', 'description' => 'Edit project payments'],
            ['name' => 'payments.delete', 'category' => 'payments', 'description' => 'Delete project payments'],
            
            // Finance
            ['name' => 'finance.view', 'category' => 'finance', 'description' => 'View financial data'],
            ['name' => 'finance.create', 'category' => 'finance', 'description' => 'Create financial records'],
            ['name' => 'finance.edit', 'category' => 'finance', 'description' => 'Edit financial records'],
            ['name' => 'finance.approve_budget', 'category' => 'finance', 'description' => 'Approve budgets'],
            
            // Wildcard permissions
            ['name' => 'projects.*', 'category' => 'projects', 'description' => 'All project permissions'],
            ['name' => 'assignments.*', 'category' => 'assignments', 'description' => 'All assignment permissions'],
            ['name' => 'portfolio.*', 'category' => 'portfolio', 'description' => 'All portfolio permissions'],
            ['name' => 'payments.*', 'category' => 'payments', 'description' => 'All payment permissions'],
            ['name' => 'finance.*', 'category' => 'finance', 'description' => 'All finance permissions'],
            ['name' => 'admin.*', 'category' => 'admin', 'description' => 'All admin permissions'],
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission['name'], 'guard_name' => 'api'],
                $permission
            );
        }
    }

    private function createRoles(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Administrative access to most features',
                'is_system' => true,
            ],
            [
                'name' => 'project_manager',
                'display_name' => 'Gestor de Projectos',
                'description' => 'Gerencia projetos e execução operacional',
                'is_system' => false,
            ],
            [
                'name' => 'customer',
                'display_name' => 'Cliente',
                'description' => 'Cliente com acesso ao acompanhamento do projeto',
                'is_system' => true,
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name'], 'guard_name' => 'api'],
                $role
            );
        }
    }

    private function assignPermissionsToRoles(): void
    {
        // Administrator
        $admin = Role::where('name', 'admin')->first();
        if ($admin) {
            $admin->syncPermissions([
                'tenants.view', 'tenants.edit', 'tenants.manage_users', 'tenants.manage_settings',
                'users.view', 'users.create', 'users.edit', 'users.delete', 'users.manage_roles', 'users.manage_permissions',
                'forms.view', 'forms.create', 'forms.edit', 'forms.delete', 'forms.submit', 'forms.approve',
                'projects.*', 'assignments.*', 'payments.*', 'portfolio.*', 'finance.*', 'admin.*'
            ]);
        }

        // Gestor de Projectos
        $projectManager = Role::where('name', 'project_manager')->first();
        if ($projectManager) {
            $projectManager->syncPermissions([
                'projects.view', 'projects.edit', 'projects.manage_team',
                'assignments.view', 'assignments.create', 'assignments.edit',
                'payments.view', 'payments.create',
                'portfolio.view', 'portfolio.create', 'portfolio.edit',
                'forms.view', 'forms.submit'
            ]);
        }

        // Cliente
        $customer = Role::where('name', 'customer')->first();
        if ($customer) {
            $customer->syncPermissions([
                'projects.view', 'payments.create', 'portfolio.view', 'forms.submit'
            ]);
        }
    }
}