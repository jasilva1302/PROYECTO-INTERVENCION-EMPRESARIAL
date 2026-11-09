<?php

namespace App\Http\Traits;

use Illuminate\Support\Facades\Auth;

trait ChecksUserPermissions
{
    protected function checkUserAccess($module = null)
    {
        $user = Auth::user();
        
        if (!$user || !$user->role) {
            return false;
        }

        $roleName = strtoupper($user->role->name);

        // Administrador tiene acceso a todo
        if ($roleName === 'ADMINISTRADOR') {
            return true;
        }

        // COORDINADOR y DIRECTOR tienen acceso a todo
        if (in_array($roleName, ['COORDINADOR', 'DIRECTOR'])) {
            return true;
        }

        // Si no se especifica módulo, verificar si el usuario tiene algún permiso
        if ($module === null) {
            return !empty($user->role->permissions);
        }

        // Convertir el nombre del módulo a formato estándar
        $module = strtolower(str_replace(' ', '_', $module));
        $permission = "ver_{$module}";

        // Verificar el permiso en la base de datos
        return $user->role->hasPermissionTo($permission);
    }

    protected function checkRole($user, $roles)
    {
        if (!$user || !$user->role) {
            return false;
        }

        if (is_string($roles)) {
            $roles = [$roles];
        }

        $roleName = strtoupper($user->role->name);
        $roles = array_map('strtoupper', $roles);

        return in_array($roleName, $roles);
    }

    protected function isAdministrador()
    {
        $user = Auth::user();
        return $this->checkRole($user, 'ADMINISTRADOR');
    }

    protected function isAsistente()
    {
        $user = Auth::user();
        return $this->checkRole($user, 'ASISTENTE');
    }

    protected function isCoordinadorOrDirector()
    {
        $user = Auth::user();
        return $this->checkRole($user, ['COORDINADOR', 'DIRECTOR']);
    }

    protected function hasPermission($permission)
    {
        $user = Auth::user();
        
        if (!$user || !$user->role) {
            return false;
        }

        $roleName = strtoupper($user->role->name);

        // Administrador tiene todos los permisos
        if ($roleName === 'ADMINISTRADOR') {
            return true;
        }

        // COORDINADOR y DIRECTOR tienen todos los permisos
        if (in_array($roleName, ['COORDINADOR', 'DIRECTOR'])) {
            return true;
        }

        // Para ASISTENTE y otros roles, verificar los permisos almacenados
        // Convertir el permiso a formato estándar
        $permission = strtolower(str_replace(' ', '_', $permission));

        // Verificar el permiso en la base de datos
        return $user->role->hasPermissionTo($permission);
    }
} 