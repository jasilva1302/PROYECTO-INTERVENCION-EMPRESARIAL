<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Role;
use App\Models\Module;
use App\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Crear roles usando updateOrCreate
        $roles = [
            [
                'name' => 'COORDINADOR',
                'description' => 'Coordinador con acceso a todos los módulos y gestión de evaluaciones'
            ],
            [
                'name' => 'DIRECTOR',
                'description' => 'Director con acceso a todos los módulos y gestión de evaluaciones'
            ],
            [
                'name' => 'ASISTENTE',
                'description' => 'Asistente con acceso limitado y participación en evaluaciones'
            ]
        ];

        $roleIds = [];
        foreach ($roles as $role) {
            $roleModel = Role::updateOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
            $roleIds[$role['name']] = $roleModel->id;
        }

        // Crear módulo de evaluaciones usando updateOrCreate
        $module = Module::updateOrCreate(
            ['name' => 'Evaluaciones'],
            [
                'description' => 'Gestión de evaluaciones y exámenes',
                'route' => 'evaluaciones.index',
                'icon' => 'fas fa-graduation-cap',
                'order' => 5
            ]
        );

        // Crear permisos para el módulo
        $permissions = [
            'ver_evaluaciones' => 'Ver listado de evaluaciones',
            'crear_evaluaciones' => 'Crear nuevas evaluaciones',
            'editar_evaluaciones' => 'Editar evaluaciones existentes',
            'eliminar_evaluaciones' => 'Eliminar evaluaciones',
            'calificar_evaluaciones' => 'Calificar evaluaciones completadas',
            'realizar_evaluaciones' => 'Realizar evaluaciones asignadas'
        ];

        $permissionIds = [];
        foreach ($permissions as $name => $description) {
            $permission = Permission::updateOrCreate(
                [
                    'name' => $name,
                    'module_id' => $module->id
                ],
                ['description' => $description]
            );
            $permissionIds[] = $permission->id;
        }

        // Asignar todos los permisos a COORDINADOR y DIRECTOR
        foreach (['COORDINADOR', 'DIRECTOR'] as $roleName) {
            $role = Role::find($roleIds[$roleName]);
            $role->permissions()->syncWithoutDetaching($permissionIds);
        }

        // Asignar solo permiso de realizar evaluaciones al ASISTENTE
        $role = Role::find($roleIds['ASISTENTE']);
        $verPermission = Permission::where('name', 'ver_evaluaciones')
            ->where('module_id', $module->id)
            ->first();
        $realizarPermission = Permission::where('name', 'realizar_evaluaciones')
            ->where('module_id', $module->id)
            ->first();
        
        if ($verPermission && $realizarPermission) {
            $role->permissions()->syncWithoutDetaching([$verPermission->id, $realizarPermission->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No eliminamos los roles ni módulos en el rollback para mantener la integridad de los datos
    }
}; 