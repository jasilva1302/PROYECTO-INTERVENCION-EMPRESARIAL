<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            DB::beginTransaction();

            // Primero obtenemos el ID del módulo de evaluaciones
            $moduleId = DB::table('modules')
                ->where('name', 'Evaluaciones')
                ->value('id');

            if (!$moduleId) {
                // Si no existe el módulo, lo creamos
                $moduleId = DB::table('modules')->insertGetId([
                    'name' => 'Evaluaciones',
                    'description' => 'Gestión de evaluaciones y exámenes',
                    'route' => 'evaluaciones.index',
                    'icon' => 'fas fa-graduation-cap',
                    'order' => 5,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // Agregamos los permisos de evaluaciones
            $permisos = [
                ['name' => 'evaluaciones.view', 'description' => 'Ver evaluaciones', 'module_id' => $moduleId],
                ['name' => 'evaluaciones.create', 'description' => 'Crear evaluaciones', 'module_id' => $moduleId],
                ['name' => 'evaluaciones.edit', 'description' => 'Editar evaluaciones', 'module_id' => $moduleId],
                ['name' => 'evaluaciones.delete', 'description' => 'Eliminar evaluaciones', 'module_id' => $moduleId],
                ['name' => 'evaluaciones.take', 'description' => 'Realizar evaluaciones', 'module_id' => $moduleId],
                ['name' => 'evaluaciones.grade', 'description' => 'Calificar evaluaciones', 'module_id' => $moduleId]
            ];

            // Insertamos los permisos y guardamos sus IDs
            $permissionIds = [];
            foreach ($permisos as $permiso) {
                $existingPermission = DB::table('permissions')
                    ->where('name', $permiso['name'])
                    ->first();

                if ($existingPermission) {
                    $permissionIds[$permiso['name']] = $existingPermission->id;
                } else {
                    $permissionIds[$permiso['name']] = DB::table('permissions')->insertGetId($permiso);
                }
            }

            // Obtenemos los IDs de los roles
            $coordinadorId = DB::table('roles')->where('name', 'COORDINADOR')->value('id');
            $directorId = DB::table('roles')->where('name', 'DIRECTOR')->value('id');
            $asistenteId = DB::table('roles')->where('name', 'ASISTENTE')->value('id');

            // Definimos las asignaciones de permisos a roles
            $permisosRoles = [];

            // COORDINADOR - Todos los permisos
            foreach ($permissionIds as $permissionId) {
                $permisosRoles[] = ['permission_id' => $permissionId, 'role_id' => $coordinadorId];
            }

            // DIRECTOR - Todos los permisos
            foreach ($permissionIds as $permissionId) {
                $permisosRoles[] = ['permission_id' => $permissionId, 'role_id' => $directorId];
            }

            // ASISTENTE - Solo ver y realizar evaluaciones
            $permisosRoles[] = ['permission_id' => $permissionIds['evaluaciones.view'], 'role_id' => $asistenteId];
            $permisosRoles[] = ['permission_id' => $permissionIds['evaluaciones.take'], 'role_id' => $asistenteId];

            // Insertamos las asignaciones de permisos a roles
            foreach ($permisosRoles as $permisoRol) {
                if (!DB::table('permission_role')
                    ->where('permission_id', $permisoRol['permission_id'])
                    ->where('role_id', $permisoRol['role_id'])
                    ->exists()) {
                    DB::table('permission_role')->insert($permisoRol);
                }
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Eliminamos los permisos de evaluaciones
        DB::table('permissions')
            ->where('name', 'like', 'evaluaciones.%')
            ->delete();
        
        // Las asignaciones de permisos a roles se eliminarán automáticamente por la restricción de clave foránea
    }
};
