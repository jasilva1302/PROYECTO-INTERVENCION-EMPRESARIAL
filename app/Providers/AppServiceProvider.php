<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Directiva para verificar si el usuario tiene un permiso específico
        Blade::if('hasPermission', function ($permission) {
            $user = Auth::user();
            if (!$user || !$user->role) {
                return false;
            }
            
            if (strtoupper($user->role->name) === 'ADMINISTRADOR') {
                return true;
            }
            
            return $user->hasPermission($permission);
        });

        // Directiva para verificar si el usuario tiene algún permiso en un módulo
        Blade::if('hasModuleAccess', function ($module) {
            $user = Auth::user();
            if (!$user || !$user->role) {
                return false;
            }
            
            if (strtoupper($user->role->name) === 'ADMINISTRADOR') {
                return true;
            }
            
            $modulePermissions = Config::get("permissions.{$module}", []);
            foreach ($modulePermissions as $permission) {
                if ($user->role->hasPermissionTo($permission)) {
                    return true;
                }
            }
            
            return false;
        });

        // Nueva directiva para verificar si el usuario tiene todos los permisos de un módulo
        Blade::if('hasAllModulePermissions', function ($module) {
            $user = Auth::user();
            if (!$user || !$user->role) {
                return false;
            }
            
            if (strtoupper($user->role->name) === 'ADMINISTRADOR') {
                return true;
            }
            
            $modulePermissions = Config::get("permissions.{$module}", []);
            foreach ($modulePermissions as $permission) {
                if (!$user->role->hasPermissionTo($permission)) {
                    return false;
                }
            }
            
            return true;
        });
    }
}