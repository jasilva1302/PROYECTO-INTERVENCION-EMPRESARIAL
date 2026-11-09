<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Primero, respaldamos los datos existentes
        $roles = DB::table('roles')->get();
        
        // Modificamos la tabla solo si las columnas no existen
        if (!Schema::hasColumn('roles', 'permissions')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->json('permissions')->nullable()->after('description');
            });
        }

        if (!Schema::hasColumn('roles', 'is_active')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('permissions');
            });
        }

        // Actualizamos los permisos para cada rol
        foreach ($roles as $role) {
            $permissions = [];
            
            // Si el rol es ADMINISTRADOR, le damos todos los permisos disponibles
            if (strtoupper($role->name) === 'ADMINISTRADOR') {
                $permissions = [
                    'usuarios' => ['ver', 'crear', 'editar', 'eliminar'],
                    'roles' => ['ver', 'crear', 'editar', 'eliminar'],
                    'auditorias' => ['ver', 'crear', 'editar', 'eliminar'],
                    'facturas' => ['ver', 'crear', 'editar', 'eliminar'],
                    'configuracion' => ['ver', 'editar'],
                    'evaluaciones' => ['ver', 'crear', 'editar', 'eliminar', 'responder']
                ];
            }
            // Para COORDINADOR y DIRECTOR
            elseif (in_array(strtoupper($role->name), ['COORDINADOR', 'DIRECTOR'])) {
                $permissions = [
                    'auditorias' => ['ver', 'crear', 'editar'],
                    'facturas' => ['ver', 'crear', 'editar'],
                    'evaluaciones' => ['ver', 'crear', 'editar', 'responder']
                ];
            }
            // Para ASISTENTE
            elseif (strtoupper($role->name) === 'ASISTENTE') {
                $permissions = [
                    'auditorias' => ['ver'],
                    'facturas' => ['ver'],
                    'evaluaciones' => ['ver', 'responder']
                ];
            }

            DB::table('roles')
                ->where('id', $role->id)
                ->update([
                    'permissions' => json_encode($permissions),
                    'is_active' => true
                ]);
        }
    }

    public function down()
    {
        // No hacemos nada en down() para evitar pérdida de datos
    }
}; 