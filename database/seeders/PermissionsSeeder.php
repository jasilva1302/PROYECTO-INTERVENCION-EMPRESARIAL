<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class PermissionsSeeder extends Seeder
{
    public function run()
    {
        // Crear permisos
        $permissions = [
            // Permisos de evaluaciones
            'evaluaciones.view' => 'Ver evaluaciones',
            'evaluaciones.create' => 'Crear evaluaciones',
            'evaluaciones.edit' => 'Editar evaluaciones',
            'evaluaciones.delete' => 'Eliminar evaluaciones',
            'evaluaciones.take' => 'Realizar evaluaciones',
            'evaluaciones.grade' => 'Calificar evaluaciones',
            'Ver Radicación',
            'Gestionar Radicación',

            // Otros permisos existentes...
        ];

        foreach ($permissions as $name => $description) {
            Permission::create(['name' => $name, 'description' => $description]);
        }

        // Crear roles
        $roles = [
            'ADMINISTRADOR' => 'Administrador del sistema con acceso total',
            'DIRECTOR' => 'Director con acceso a todos los módulos',
            'COORDINADOR' => 'Coordinador con acceso a todos los módulos',
            'ASISTENTE' => 'Asistente con acceso limitado'
        ];

        foreach ($roles as $name => $description) {
            Role::create(['name' => $name, 'description' => $description]);
        }

        // Asignar permisos a roles
        $roleAdmin = Role::findByName('ADMINISTRADOR');
        $roleDirector = Role::findByName('DIRECTOR');
        $roleCoordinador = Role::findByName('COORDINADOR');
        $roleAsistente = Role::findByName('ASISTENTE');

        // Administrador tiene todos los permisos
        $roleAdmin->givePermissionTo(Permission::all());

        // Director y Coordinador tienen los mismos permisos
        $directorPermisos = [
            'evaluaciones.view',
            'evaluaciones.create',
            'evaluaciones.edit',
            'evaluaciones.delete',
            'evaluaciones.grade'
        ];
        $roleDirector->givePermissionTo($directorPermisos);
        $roleCoordinador->givePermissionTo($directorPermisos);

        // Asistente solo puede ver y realizar evaluaciones
        $roleAsistente->givePermissionTo([
            'evaluaciones.view',
            'evaluaciones.take'
        ]);

        // Insertar permisos en la tabla de permisos
        foreach ($permissions as $name => $description) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
} 