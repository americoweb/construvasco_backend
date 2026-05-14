<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Seed default users (Construvasco demo).
 *
 * Login (API auth/login, field "identifier"):
 *  - admin@construvasco.co.mz / 12345678
 *  - gestor@construvasco.co.mz / 12345678
 *  - cliente@construvasco.co.mz / 12345678
 *
 * After changing users, run full seed or at least TenantUserSeeder so tenant_users
 * rows exist (otherwise roles/context after login may be empty).
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrador Construvasco',
                'identifier' => 'admin@construvasco.co.mz',
                'type' => 'email',
                'password' => Hash::make('12345678'),
                'verified_at' => now(),
                'is_active' => true,
                'settings' => json_encode(['theme' => 'dark', 'notifications' => true])
            ],
            [
                'name' => 'Gestor de Projectos',
                'identifier' => 'gestor@construvasco.co.mz',
                'type' => 'email',
                'password' => Hash::make('12345678'),
                'verified_at' => now(),
                'is_active' => true,
                'settings' => json_encode(['theme' => 'light', 'notifications' => true])
            ],
            [
                'name' => 'Cliente Construvasco',
                'identifier' => 'cliente@construvasco.co.mz',
                'type' => 'email',
                'password' => Hash::make('12345678'),
                'verified_at' => now(),
                'is_active' => true,
                'settings' => json_encode(['theme' => 'auto', 'notifications' => false])
            ],
        ];

        $allowedIdentifiers = array_column($users, 'identifier');
        User::whereNotIn('identifier', $allowedIdentifiers)->delete();

        foreach ($users as $userData) {
            User::updateOrCreate(
                ['identifier' => $userData['identifier']],
                $userData
            );
        }
    }
} 