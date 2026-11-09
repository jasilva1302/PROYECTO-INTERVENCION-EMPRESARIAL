<?php

namespace App\Traits;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

trait HasPermissionChecks
{
    protected function checkPermission($permission)
    {
        $user = Auth::user();
        
        if (!$user || !$user->role) {
            return false;
        }

        // Si el usuario es administrador, tiene acceso a todo
        if (strtoupper($user->role->name) === 'ADMINISTRADOR') {
            return true;
        }

        // Verificar el permiso específico
        return $user->hasPermission($permission);
    }

    protected function checkPermissionAndRespond($permission)
    {
        if (!$this->checkPermission($permission)) {
            abort(403, 'No tienes permiso para realizar esta acción.');
        }
    }

    protected function getModulePermissions($module)
    {
        return Config::get("permissions.{$module}", []);
    }

    protected function userHasAnyPermissionInModule($module)
    {
        $user = Auth::user();
        
        if (!$user || !$user->role) {
            return false;
        }

        // Si el usuario es administrador, tiene acceso a todo
        if (strtoupper($user->role->name) === 'ADMINISTRADOR') {
            return true;
        }

        // Obtener todos los permisos del módulo
        $modulePermissions = $this->getModulePermissions($module);
        
        // Verificar si el usuario tiene al menos uno de los permisos del módulo
        foreach ($modulePermissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }
        
        return false;
    }

    protected function userHasAllPermissionsInModule($module)
    {
        $user = Auth::user();
        
        if (!$user || !$user->role) {
            return false;
        }

        // Si el usuario es administrador, tiene acceso a todo
        if (strtoupper($user->role->name) === 'ADMINISTRADOR') {
            return true;
        }

        // Obtener todos los permisos del módulo
        $modulePermissions = $this->getModulePermissions($module);
        
        // Verificar si el usuario tiene todos los permisos del módulo
        foreach ($modulePermissions as $permission) {
            if (!$user->hasPermission($permission)) {
                return false;
            }
        }
        
        return true;
    }
} 