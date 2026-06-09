<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin — tiene rol global super-admin
        $admin = User::firstOrCreate(
            ['email' => 'superadmin@test.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('super-admin');

        // Project Manager — tiene rol global project-manager (puede crear proyectos)
        $pm = User::firstOrCreate(
            ['email' => 'pm@test.com'],
            [
                'name'     => 'Project Manager',
                'password' => Hash::make('password'),
            ]
        );
        $pm->assignRole('project-manager');

        // Usuarios regulares SIN rol global — los roles se asignan por proyecto
        // Estos usuarios solo obtienen permisos cuando un PM los agrega como miembros
        $regularUsers = [
            ['name' => 'Developer',   'email' => 'dev@test.com'],
            ['name' => 'QA Engineer', 'email' => 'qa@test.com'],
            ['name' => 'Support',     'email' => 'support@test.com'],
            ['name' => 'Client',      'email' => 'client@test.com'],
        ];

        foreach ($regularUsers as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['password' => Hash::make('password')])
            );
        }

        $this->command->info('Usuarios de prueba creados correctamente.');
    }
}