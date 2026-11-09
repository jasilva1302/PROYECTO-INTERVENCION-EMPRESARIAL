<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    protected $signature = 'user:create-admin {name} {email} {password}';
    protected $description = 'Create a new user with administrator role';

    public function handle()
    {
        // Obtener el rol administrador
        $adminRole = Role::where('name', 'Administrador')->first();

        if (!$adminRole) {
            $this->error('El rol Administrador no existe en la base de datos.');
            return 1;
        }

        // Verificar si el usuario ya existe
        $existingUser = User::where('email', $this->argument('email'))->first();

        if ($existingUser) {
            // Actualizar el usuario existente
            $existingUser->update([
                'name' => $this->argument('name'),
                'password' => Hash::make($this->argument('password')),
                'role_id' => $adminRole->id,
                'is_active' => true
            ]);

            $this->info('Usuario administrador actualizado correctamente.');
            $this->table(
                ['Nombre', 'Email', 'Rol'],
                [[$existingUser->name, $existingUser->email, 'Administrador']]
            );
            return 0;
        }

        // Crear nuevo usuario
        $user = User::create([
            'name' => $this->argument('name'),
            'email' => $this->argument('email'),
            'password' => Hash::make($this->argument('password')),
            'role_id' => $adminRole->id,
            'is_active' => true
        ]);

        $this->info('Usuario administrador creado correctamente.');
        $this->table(
            ['Nombre', 'Email', 'Rol'],
            [[$user->name, $user->email, 'Administrador']]
        );

        return 0;
    }
} 