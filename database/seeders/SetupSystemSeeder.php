<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Module;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SetupSystemSeeder extends Seeder
{
    public function run()
    {
        // 1. Crear módulos
        $modules = [
            'Usuarios' => 'Gestión de usuarios del sistema',
            'Roles' => 'Gestión de roles y permisos',
            'Auditorías' => 'Gestión de auditorías',
            'Facturación' => 'Gestión de facturación',
            'Empresas' => 'Gestión de empresas',
            'Sedes' => 'Gestión de sedes'
        ];

        $moduleIds = [];
        foreach ($modules as $name => $description) {
            $module = Module::create([
                'name' => $name,
                'description' => $description
            ]);
            $moduleIds[$name] = $module->id;
        }

        // 2. Crear permisos básicos para cada módulo
        $basicActions = ['ver', 'crear', 'editar', 'eliminar'];
        $permissions = [];

        foreach ($modules as $moduleName => $description) {
            foreach ($basicActions as $action) {
                $permissionName = "$action $moduleName";
                $permission = Permission::create([
                    'name' => $permissionName,
                    'description' => "Permiso para $action $moduleName",
                    'module_id' => $moduleIds[$moduleName]
                ]);
                $permissions[] = $permission->id;
            }
        }

        // Permisos especiales para Auditorías
        $specialAuditPermissions = ['asignar', 'cerrar'];
        foreach ($specialAuditPermissions as $action) {
            Permission::create([
                'name' => "$action Auditorías",
                'description' => "Permiso para $action auditorías",
                'module_id' => $moduleIds['Auditorías']
            ]);
        }

        // Permisos especiales para Facturación
        $specialBillingPermissions = ['cargar', 'exportar'];
        foreach ($specialBillingPermissions as $action) {
            Permission::create([
                'name' => "$action Facturación",
                'description' => "Permiso para $action facturación",
                'module_id' => $moduleIds['Facturación']
            ]);
        }

        // 3. Crear roles básicos
        $roles = [
            'Administrador' => 'Acceso completo al sistema',
            'Auditor' => 'Gestión de auditorías',
            'Asistente' => 'Asistente de auditoría',
            'Facturación' => 'Gestión de facturación',
            'Consulta' => 'Solo consulta'
        ];

        foreach ($roles as $name => $description) {
            Role::create([
                'name' => $name,
                'description' => $description
            ]);
        }

        // 4. Asignar permisos a roles
        $adminRole = Role::where('name', 'Administrador')->first();
        $adminRole->permissions()->sync(Permission::all()->pluck('id')->toArray());

        $auditorRole = Role::where('name', 'Auditor')->first();
        $auditorRole->permissions()->sync(
            Permission::whereIn('name', [
                'ver Auditorías', 'crear Auditorías', 'editar Auditorías',
                'asignar Auditorías', 'cerrar Auditorías',
                'ver Facturación',
                'ver Empresas',
                'ver Sedes'
            ])->pluck('id')->toArray()
        );

        $asistenteRole = Role::where('name', 'Asistente')->first();
        $asistenteRole->permissions()->sync(
            Permission::where('name', 'like', 'ver%')->pluck('id')->toArray()
        );

        $facturacionRole = Role::where('name', 'Facturación')->first();
        $facturacionRole->permissions()->sync(
            Permission::whereIn('name', [
                'ver Facturación', 'crear Facturación', 'editar Facturación',
                'cargar Facturación', 'exportar Facturación',
                'ver Empresas', 'ver Sedes'
            ])->pluck('id')->toArray()
        );

        $consultaRole = Role::where('name', 'Consulta')->first();
        $consultaRole->permissions()->sync(
            Permission::where('name', 'like', 'ver%')->pluck('id')->toArray()
        );

        // 5. Crear usuario administrador por defecto
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@example.com',
            'password' => Hash::make('admin123'),
            'role_id' => $adminRole->id
        ]);
    }
} 