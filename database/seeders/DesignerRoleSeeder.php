<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class DesignerRoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Job-card permissions
        $permissions = [
            ['name' => 'job_cards.view',   'guard_name' => 'api', 'category' => 'job_cards'],
            ['name' => 'job_cards.create', 'guard_name' => 'api', 'category' => 'job_cards'],
            ['name' => 'job_cards.edit',   'guard_name' => 'api', 'category' => 'job_cards'],
            ['name' => 'job_cards.delete', 'guard_name' => 'api', 'category' => 'job_cards'],
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                ['name' => $perm['name'], 'guard_name' => $perm['guard_name']],
                ['category' => $perm['category'], 'description' => '']
            );
        }

        // Designer role
        $designer = Role::firstOrCreate(
            ['name' => 'designer', 'guard_name' => 'api'],
            [
                'display_name' => 'Designer',
                'description'  => 'Design team member responsible for producing visual work',
                'is_system'    => false,
            ]
        );

        $designer->syncPermissions(
            Permission::where('guard_name', 'api')
                ->whereIn('name', ['job_cards.view', 'job_cards.edit'])
                ->get()
        );

        $this->command->info('Designer role and job_cards permissions seeded.');
    }
}
