<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Module;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear módulos
        $modules = [
            'auditoria' => 'Módulo de Auditoría',
            'estadisticas' => 'Módulo de Estadísticas',
            'tarifas' => 'Módulo de Tarifas',
            'evaluaciones' => 'Módulo de Evaluaciones',
            'facturacion' => 'Módulo de Facturación',
            'configuracion' => 'Módulo de Configuración'
        ];

        foreach ($modules as $name => $description) {
            Module::updateOrCreate(
                ['name' => $name],
                ['description' => $description]
            );
        }

        // Crear permisos
        $permissions = [
            'auditoria' => [
                'ver' => 'Ver auditorías',
                'crear' => 'Crear auditorías',
                'editar' => 'Editar auditorías',
                'eliminar' => 'Eliminar auditorías'
            ],
            'estadisticas' => [
                'ver' => 'Ver estadísticas'
            ],
            'tarifas' => [
                'ver' => 'Ver tarifas',
                'crear' => 'Crear tarifas',
                'editar' => 'Editar tarifas',
                'eliminar' => 'Eliminar tarifas'
            ],
            'evaluaciones' => [
                'ver' => 'Ver evaluaciones',
                'crear' => 'Crear evaluaciones',
                'editar' => 'Editar evaluaciones',
                'eliminar' => 'Eliminar evaluaciones'
            ],
            'facturacion' => [
                'ver' => 'Ver facturación',
                'crear' => 'Crear facturación',
                'editar' => 'Editar facturación',
                'eliminar' => 'Eliminar facturación',
                'cargar' => 'Cargar facturación'
            ],
            'configuracion' => [
                'ver' => 'Ver configuración',
                'crear' => 'Crear configuración',
                'editar' => 'Editar configuración',
                'eliminar' => 'Eliminar configuración'
            ]
        ];

        foreach ($permissions as $moduleName => $actions) {
            $module = Module::where('name', $moduleName)->first();
            
            foreach ($actions as $action => $description) {
                Permission::updateOrCreate(
                    ['name' => "$action $moduleName"],
                    [
                        'description' => $description,
                        'module_id' => $module->id
                    ]
                );
            }
        }

        // Asignar todos los permisos al rol Administrador
        $adminRole = Role::where('name', 'Administrador')->first();
        if ($adminRole) {
            $allPermissions = Permission::all();
            $adminRole->permissions()->sync($allPermissions->pluck('id'));
        }
    }
}
