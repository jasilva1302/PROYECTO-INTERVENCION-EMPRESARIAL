<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Module;
use App\Models\Permission;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Crear el rol de Administrador si no existe
        $adminRole = Role::firstOrCreate(
            ['name' => 'Administrador'],
            [
                'description' => 'Acceso total al sistema',
                'permissions' => ['*']
            ]
        );

        // Crear módulos si no existen
        $modules = [
            [
                'name' => 'Auditorías',
                'description' => 'Gestión de auditorías',
                'route' => 'auditoria.index',
                'icon' => 'bi-clipboard-check',
                'order' => 1
            ],
            [
                'name' => 'Facturación',
                'description' => 'Gestión de facturación',
                'route' => 'facturacion.cargue.index',
                'icon' => 'bi-receipt',
                'order' => 2
            ],
            [
                'name' => 'Estadísticas',
                'description' => 'Reportes y estadísticas',
                'route' => 'estadisticas.index',
                'icon' => 'bi-graph-up',
                'order' => 3
            ],
            [
                'name' => 'Tarifas',
                'description' => 'Gestión de tarifas',
                'route' => 'tarifas.index',
                'icon' => 'bi-tags',
                'order' => 4
            ],
            [
                'name' => 'Evaluaciones',
                'description' => 'Gestión de evaluaciones',
                'route' => 'evaluaciones.index',
                'icon' => 'bi-list-check',
                'order' => 5
            ],
            [
                'name' => 'Configuración',
                'description' => 'Configuración del sistema',
                'route' => 'configuracion.index',
                'icon' => 'bi-gear',
                'order' => 6
            ]
        ];

        // Crear los módulos y guardar sus IDs
        $moduleIds = [];
        foreach ($modules as $moduleData) {
            $module = Module::updateOrCreate(
                ['name' => $moduleData['name']],
                $moduleData
            );
            $moduleIds[$module->name] = $module->id;
        }

        // Crear permisos básicos para cada módulo
        $permissions = [
            'Auditorías' => ['ver', 'crear', 'editar', 'eliminar'],
            'Facturación' => ['ver', 'crear', 'editar', 'eliminar', 'cargar'],
            'Estadísticas' => ['ver'],
            'Tarifas' => ['ver', 'crear', 'editar', 'eliminar'],
            'Evaluaciones' => ['ver', 'crear', 'editar', 'eliminar'],
            'Configuración' => ['ver', 'crear', 'editar', 'eliminar']
        ];

        foreach ($permissions as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(
                    ['name' => "$action $module"],
                    [
                        'name' => "$action $module",
                        'description' => "Permiso para $action $module",
                        'module_id' => $moduleIds[$module]
                    ]
                );
            }
        }

        // Crear usuario administrador si no existe
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('password'),
                'role_id' => $adminRole->id,
                'email_verified_at' => now()
            ]
        );

        $this->call([
            UpdateRolePermissionsSeeder::class
        ]);
    }
}