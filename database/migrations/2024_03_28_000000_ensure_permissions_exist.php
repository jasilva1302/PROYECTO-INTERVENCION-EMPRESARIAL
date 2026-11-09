<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Module;
use App\Models\Permission;

class EnsurePermissionsExist extends Migration
{
    public function up()
    {
        // Primero, verificar si el módulo ya existe
        $auditoriasModule = Module::where('name', 'auditorias')->first();
        if (!$auditoriasModule) {
            $auditoriasModule = Module::create([
                'name' => 'auditorias',
                'description' => 'Módulo de Auditorías',
                'is_active' => true
            ]);
        }

        // Lista de permisos a crear
        $permissions = [
            'ver_auditoria' => 'Ver auditorías',
            'crear_auditoria' => 'Crear auditorías',
            'editar_auditoria' => 'Editar auditorías',
            'eliminar_auditoria' => 'Eliminar auditorías',
            'ver_evaluaciones' => 'Ver evaluaciones',
            'crear_evaluaciones' => 'Crear evaluaciones',
            'editar_evaluaciones' => 'Editar evaluaciones',
            'eliminar_evaluaciones' => 'Eliminar evaluaciones',
            'realizar_evaluaciones' => 'Realizar evaluaciones',
            'calificar_evaluaciones' => 'Calificar evaluaciones'
        ];

        // Crear los permisos si no existen
        foreach ($permissions as $name => $description) {
            Permission::firstOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'module_id' => $auditoriasModule->id
                ]
            );
        }
    }

    public function down()
    {
        // No es necesario eliminar los permisos en el rollback
        // ya que son datos esenciales para el sistema
    }
} 