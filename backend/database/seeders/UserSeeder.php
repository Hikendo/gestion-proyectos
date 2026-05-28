<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Super Admin — único rol global
        $admin = User::firstOrCreate(
            ['email' => 'superadmin@test.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        $admin->assignRole('super-admin');

        // Usuarios regulares sin rol global (los roles se asignan por proyecto)
        $regularUsers = [
            ['name' => 'Project Manager', 'email' => 'pm@test.com'],
            ['name' => 'Developer',        'email' => 'dev@test.com'],
            ['name' => 'QA Engineer',      'email' => 'qa@test.com'],
            ['name' => 'Support',          'email' => 'support@test.com'],
            ['name' => 'Client',           'email' => 'client@test.com'],
        ];

        foreach ($regularUsers as $data) {
            User::firstOrCreate(
                ['email' => $data['email']],
                array_merge($data, ['password' => Hash::make('password')])
            );
        }

        // Asignar rol global project-manager al PM de prueba
        $pm = User::where('email', 'pm@test.com')->first();
        if ($pm) {
            $pm->syncRoles(['project-manager']);
        }

        $this->command->info('Usuarios de prueba creados correctamente.');
    }
}
