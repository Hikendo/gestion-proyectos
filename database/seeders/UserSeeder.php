<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Super Admin',
                'email'    => 'superadmin@test.com',
                'password' => Hash::make('password'),
                'role'     => 'super-admin',
            ],
            [
                'name'     => 'Project Manager',
                'email'    => 'pm@test.com',
                'password' => Hash::make('password'),
                'role'     => 'project-manager',
            ],
            [
                'name'     => 'Developer',
                'email'    => 'dev@test.com',
                'password' => Hash::make('password'),
                'role'     => 'developer',
            ],
            [
                'name'     => 'QA Engineer',
                'email'    => 'qa@test.com',
                'password' => Hash::make('password'),
                'role'     => 'qa',
            ],
            [
                'name'     => 'Support',
                'email'    => 'support@test.com',
                'password' => Hash::make('password'),
                'role'     => 'support',
            ],
            [
                'name'     => 'Client',
                'email'    => 'client@test.com',
                'password' => Hash::make('password'),
                'role'     => 'client',
            ],
        ];

        foreach ($users as $data) {
            $role = $data['role'];
            unset($data['role']);

            $user = User::firstOrCreate(
                ['email' => $data['email']],
                $data
            );

            $user->assignRole($role);
        }

        $this->command->info('Usuarios de prueba creados correctamente.');
    }
}
