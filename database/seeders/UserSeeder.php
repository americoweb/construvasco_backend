<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Utilizadores de demonstração Construvasco.
 *
 * admin@construvasco.co.mz / Admin@2026
 * gestor@construvasco.co.mz / Gestor@2026
 * tecnico@construvasco.co.mz / Tecnico@2026
 * cliente@construvasco.co.mz / Cliente@2026
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrador Construvasco',
                'identifier' => 'admin@construvasco.co.mz',
                'type' => 'email',
                'password' => Hash::make('Admin@2026'),
                'verified_at' => now(),
                'is_active' => true,
                'settings' => json_encode(['theme' => 'dark', 'notifications' => true]),
            ],
            [
                'name' => 'Gestor de Projectos',
                'identifier' => 'gestor@construvasco.co.mz',
                'type' => 'email',
                'password' => Hash::make('Gestor@2026'),
                'verified_at' => now(),
                'is_active' => true,
                'settings' => json_encode(['theme' => 'light', 'notifications' => true]),
            ],
            [
                'name' => 'Técnico Construvasco',
                'identifier' => 'tecnico@construvasco.co.mz',
                'type' => 'email',
                'password' => Hash::make('Tecnico@2026'),
                'verified_at' => now(),
                'is_active' => true,
                'settings' => json_encode(['theme' => 'light', 'notifications' => true]),
            ],
            [
                'name' => 'Cliente Construvasco',
                'identifier' => 'cliente@construvasco.co.mz',
                'type' => 'email',
                'password' => Hash::make('Cliente@2026'),
                'verified_at' => now(),
                'is_active' => true,
                'settings' => json_encode(['theme' => 'auto', 'notifications' => false]),
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