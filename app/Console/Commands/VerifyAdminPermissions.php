<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use App\Models\Permission;

class VerifyAdminPermissions extends Command
{
    protected $signature = 'admin:verify-permissions';
    protected $description = 'Verify and fix administrator role permissions';

    public function handle()
    {
        $this->info('Verificando permisos del rol Administrador...');

        // Obtener el rol administrador
        $adminRole = Role::where('name', 'Administrador')->first();

        if (!$adminRole) {
            $this->error('¡Error! No se encontró el rol Administrador');
            return 1;
        }

        // Obtener todos los permisos
        $allPermissions = Permission::all();
        
        // Obtener los permisos actuales del administrador
        $currentPermissions = $adminRole->permissions()->pluck('id')->toArray();
        
        // Verificar permisos faltantes
        $missingPermissions = $allPermissions->whereNotIn('id', $currentPermissions);
        
        if ($missingPermissions->isEmpty()) {
            $this->info('El rol Administrador ya tiene todos los permisos asignados.');
            return 0;
        }

        // Mostrar permisos faltantes
        $this->warn('Permisos faltantes encontrados:');
        foreach ($missingPermissions as $permission) {
            $this->line("- {$permission->name}");
        }

        // Asignar todos los permisos
        $adminRole->permissions()->sync($allPermissions->pluck('id')->toArray());

        $this->info('¡Permisos actualizados correctamente!');
        $this->info('Total de permisos asignados: ' . $allPermissions->count());

        return 0;
    }
} 