<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        try {
            DB::beginTransaction();

            // ID del rol ASISTENTE
            $asistenteId = DB::table('roles')->where('name', 'ASISTENTE')->value('id');

            if ($asistenteId) {
                // Primero eliminamos los permisos existentes del rol ASISTENTE
                DB::table('permission_role')->where('role_id', $asistenteId)->delete();

                // Obtener todos los permisos necesarios
                $permisos = [
                    'ver Auditorías',
                    'ver Facturación',
                    'ver Estadísticas',
                    'ver Tarifas',
                    'ver Evaluaciones',
                    'cargar Facturación',
                    'realizar Evaluaciones'
                ];

                $permissionIds = DB::table('permissions')
                    ->whereIn('name', $permisos)
                    ->pluck('id');

                // Crear los registros en permission_role
                $now = now();
                $permissions = $permissionIds->map(function ($permissionId) use ($asistenteId, $now) {
                    return [
                        'permission_id' => $permissionId,
                        'role_id' => $asistenteId,
                        'created_at' => $now,
                        'updated_at' => $now
                    ];
                })->toArray();

                DB::table('permission_role')->insert($permissions);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function down()
    {
        // Eliminar todos los permisos del rol ASISTENTE
        $asistenteId = DB::table('roles')->where('name', 'ASISTENTE')->value('id');
        
        if ($asistenteId) {
            DB::table('permission_role')->where('role_id', $asistenteId)->delete();
        }
    }
}; 