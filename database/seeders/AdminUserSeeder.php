<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Role;
use App\Models\Module;
use App\Models\Permission;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        // Create roles
        $roles = [
            [
                'name' => 'Administrador',
                'description' => 'Rol con acceso completo al sistema'
            ],
            [
                'name' => 'Auditor',
                'description' => 'Rol para realizar auditorías'
            ],
            [
                'name' => 'Asistente',
                'description' => 'Rol para asistentes de auditoría'
            ],
            [
                'name' => 'Facturación',
                'description' => 'Rol para gestión de facturación'
            ],
            [
                'name' => 'Consulta',
                'description' => 'Rol solo para consulta'
            ]
        ];

        $createdRoles = [];
        foreach ($roles as $roleData) {
            $createdRoles[$roleData['name']] = Role::create($roleData);
        }

        // Create basic modules
        $modules = [
            [
                'name' => 'Usuarios',
                'description' => 'Gestión de usuarios',
                'route' => 'users',
                'icon' => 'users',
                'permissions' => ['ver', 'crear', 'editar', 'eliminar']
            ],
            [
                'name' => 'Roles',
                'description' => 'Gestión de roles y permisos',
                'route' => 'roles',
                'icon' => 'shield',
                'permissions' => ['ver', 'crear', 'editar', 'eliminar']
            ],
            [
                'name' => 'Auditorías',
                'description' => 'Gestión de auditorías',
                'route' => 'audits',
                'icon' => 'clipboard-check',
                'permissions' => ['ver', 'crear', 'editar', 'eliminar', 'asignar', 'cerrar']
            ],
            [
                'name' => 'Facturación',
                'description' => 'Gestión de facturación',
                'route' => 'billing',
                'icon' => 'receipt',
                'permissions' => ['ver', 'crear', 'editar', 'eliminar', 'cargar', 'exportar']
            ],
            [
                'name' => 'Empresas',
                'description' => 'Gestión de empresas',
                'route' => 'companies',
                'icon' => 'building',
                'permissions' => ['ver', 'crear', 'editar', 'eliminar']
            ],
            [
                'name' => 'Sedes',
                'description' => 'Gestión de sedes',
                'route' => 'locations',
                'icon' => 'map-marker',
                'permissions' => ['ver', 'crear', 'editar', 'eliminar']
            ],
            [
                'name' => 'Evaluaciones',
                'description' => 'Gestión de evaluaciones',
                'route' => 'evaluations',
                'icon' => 'graduation-cap',
                'permissions' => ['ver', 'crear', 'editar', 'eliminar', 'asignar', 'calificar']
            ],
            [
                'name' => 'Configuración',
                'description' => 'Configuración del sistema',
                'route' => 'settings',
                'icon' => 'cog',
                'permissions' => ['ver', 'editar']
            ]
        ];

        // Create modules and their permissions
        foreach ($modules as $moduleData) {
            $module = Module::create([
                'name' => $moduleData['name'],
                'description' => $moduleData['description'],
                'route' => $moduleData['route'],
                'icon' => $moduleData['icon']
            ]);

            // Create permissions for this module
            foreach ($moduleData['permissions'] as $permissionName) {
                $permission = Permission::create([
                    'name' => $permissionName . ' ' . $moduleData['name'],
                    'description' => 'Permiso para ' . $permissionName . ' ' . $moduleData['name'],
                    'module_id' => $module->id
                ]);

                // Assign permissions based on roles
                switch ($permissionName) {
                    case 'ver':
                        // Todos los roles pueden ver
                        foreach ($createdRoles as $role) {
                            $role->permissions()->attach($permission->id);
                        }
                        break;
                    
                    case 'crear':
                    case 'editar':
                        // Administrador, Auditor y Facturación pueden crear y editar
                        if (in_array($module->name, ['Auditorías', 'Facturación', 'Evaluaciones'])) {
                            $createdRoles['Auditor']->permissions()->attach($permission->id);
                            $createdRoles['Facturación']->permissions()->attach($permission->id);
                        }
                        $createdRoles['Administrador']->permissions()->attach($permission->id);
                        break;

                    case 'eliminar':
                        // Solo administrador puede eliminar
                        $createdRoles['Administrador']->permissions()->attach($permission->id);
                        break;

                    case 'asignar':
                    case 'cerrar':
                    case 'calificar':
                        // Administrador y Auditor pueden realizar estas acciones
                        $createdRoles['Administrador']->permissions()->attach($permission->id);
                        $createdRoles['Auditor']->permissions()->attach($permission->id);
                        break;

                    case 'cargar':
                    case 'exportar':
                        // Administrador y Facturación pueden realizar estas acciones
                        $createdRoles['Administrador']->permissions()->attach($permission->id);
                        $createdRoles['Facturación']->permissions()->attach($permission->id);
                        break;
                }
            }
        }

        // Create admin user
        $admin = User::create([
            'name' => 'JHONATHAN SILVA',
            'email' => 'jasilva1302@itm.net',
            'password' => Hash::make('1302'),
        ]);

        // Assign admin role to user
        $admin->roles()->attach($createdRoles['Administrador']->id);
    }
} 