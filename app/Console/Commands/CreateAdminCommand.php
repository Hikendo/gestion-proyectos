<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminCommand extends Command
{
    protected $signature = 'admin:create
                            {--name= : Nombre del administrador}
                            {--email= : Email del administrador}
                            {--password= : Contraseña del administrador}';

    protected $description = 'Crea el primer super-admin del sistema';

    public function handle(): int
    {
        $this->info('================================================');
        $this->info(' Creando Super Administrador');
        $this->info('================================================');

        // Si se pasan como opciones (modo no interactivo para Docker)
        $name     = $this->option('name')     ?? $this->ask('Nombre');
        $email    = $this->option('email')    ?? $this->ask('Email');
        $password = $this->option('password') ?? $this->secret('Contraseña');

        // Validaciones básicas
        if (empty($name) || empty($email) || empty($password)) {
            $this->error('Nombre, email y contraseña son obligatorios.');
            return self::FAILURE;
        }

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error("Email inválido: {$email}");
            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->warn("Ya existe un usuario con el email: {$email}");
            return self::FAILURE;
        }

        $user = User::create([
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($password),
        ]);

        $user->assignRole('super-admin');

        $this->info('');
        $this->info("✓ Super-admin creado correctamente.");
        $this->table(
            ['Campo', 'Valor'],
            [
                ['Nombre', $user->name],
                ['Email',  $user->email],
                ['Rol',    'super-admin'],
            ]
        );

        return self::SUCCESS;
    }
}
