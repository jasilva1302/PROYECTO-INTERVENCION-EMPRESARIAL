<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    public function run()
    {
        $roles = [
            [
                'name' => 'ADMINISTRADOR',
                'permissions' => [
                    // Auditorías
                    'ver Auditorías',
                    'crear Auditorías',
                    'editar Auditorías',
                    'eliminar Auditorías',
                    'Asignar auditorías',
                    'Cerrar auditorías',
                    // Facturación
                    'Ver facturación',
                    'Crear facturación',
                    'Editar facturación',
                    'Eliminar facturación',
                    'Cargar facturación',
                    'Exportar facturación',
                    // Estadísticas
                    'Ver estadísticas',
                    // Tarifas
                    'Ver tarifas',
                    'Crear tarifas',
                    'Editar tarifas',
                    'Eliminar tarifas',
                    // Evaluaciones
                    'Ver evaluaciones',
                    'Crear evaluaciones',
                    'Editar evaluaciones',
                    'Eliminar evaluaciones',
                    'Realizar evaluaciones',
                    'Calificar evaluaciones',
                    // Configuración
                    'Ver configuración',
                    'Editar configuración'
                ]
            ],
            [
                'name' => 'COORDINADOR',
                'permissions' => [
                    // Auditorías
                    'ver Auditorías',
                    'crear Auditorías',
                    'editar Auditorías',
                    'Asignar auditorías',
                    // Facturación
                    'Ver facturación',
                    'Crear facturación',
                    'Editar facturación',
                    'Cargar facturación',
                    // Estadísticas
                    'Ver estadísticas',
                    // Tarifas
                    'Ver tarifas',
                    'Crear tarifas',
                    'Editar tarifas',
                    // Evaluaciones
                    'Ver evaluaciones',
                    'Crear evaluaciones',
                    'Editar evaluaciones',
                    'Realizar evaluaciones',
                    'Calificar evaluaciones'
                ]
            ],
            [
                'name' => 'ASISTENTE',
                'permissions' => [
                    // Auditorías - Solo ver y crear
                    'ver Auditorías',
                    'crear Auditorías',
                    // Estadísticas - Acceso completo
                    'Ver estadísticas',
                    // Tarifas - Acceso completo
                    'Ver tarifas',
                    'Crear tarifas',
                    'Editar tarifas',
                    'Eliminar tarifas',
                    // Evaluaciones - Acceso completo
                    'Ver evaluaciones',
                    'Crear evaluaciones',
                    'Editar evaluaciones',
                    'Eliminar evaluaciones',
                    'Realizar evaluaciones',
                    'Calificar evaluaciones'
                ]
            ]
        ];

        foreach ($roles as $roleData) {
            $role = Role::where('name', $roleData['name'])->first();
            
            if ($role) {
                // Actualizar permisos del rol existente
                $role->update([
                    'permissions' => $roleData['permissions']
                ]);
            } else {
                // Crear nuevo rol si no existe
                Role::create([
                    'name' => $roleData['name'],
                    'permissions' => $roleData['permissions']
                ]);
            }
        }
    }
}