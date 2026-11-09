<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Role;

class VerifyAdminUser extends Command
{
    protected $signature = 'admin:verify-user';
    protected $description = 'Verify and fix administrator user role';

    public function handle()
    {
        $this->info('Verificando usuario administrador...');

        // Obtener el rol administrador
        $adminRole = Role::where('name', 'Administrador')->first();

        if (!$adminRole) {
            $this->error('¡Error! No se encontró el rol Administrador');
            return 1;
        }

        // Buscar usuarios administradores
        $adminUsers = User::where('role_id', $adminRole->id)->get();

        if ($adminUsers->isEmpty()) {
            $this->warn('No se encontraron usuarios con rol Administrador');
            
            // Buscar el primer usuario
            $firstUser = User::first();
            
            if (!$firstUser) {
                $this->error('No hay usuarios en el sistema');
                return 1;
            }

            // Asignar rol administrador al primer usuario
            $firstUser->role_id = $adminRole->id;
            $firstUser->is_active = true;
            $firstUser->save();

            $this->info("Se asignó el rol Administrador al usuario: {$firstUser->name}");
        } else {
            $this->info('Usuarios administradores encontrados:');
            foreach ($adminUsers as $user) {
                $this->line("- {$user->name} ({$user->email})");
                
                // Asegurarse de que el usuario esté activo
                if (!$user->is_active) {
                    $user->is_active = true;
                    $user->save();
                    $this->info("  * Se activó el usuario {$user->name}");
                }
            }
        }

        return 0;
    }
} 