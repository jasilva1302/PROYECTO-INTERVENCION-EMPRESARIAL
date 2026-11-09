<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        //
    ];

    public function boot()
    {
        $this->registerPolicies();

        // Gate para verificar si es administrador
        Gate::define('admin', function ($user) {
            return $user->isAdmin();
        });

        // Gate para verificar roles
        Gate::define('role', function ($user, $role) {
            if (!$user->role || !$user->is_active) {
                return false;
            }
            return strtoupper($user->role->name) === strtoupper($role);
        });

        // Gate para verificar múltiples roles
        Gate::define('any-role', function ($user, array $roles) {
            if (!$user->role || !$user->is_active) {
                return false;
            }
            foreach ($roles as $role) {
                if (strtoupper($user->role->name) === strtoupper($role)) {
                    return true;
                }
            }
            return false;
        });

        // Dar acceso total al administrador
        Gate::before(function (User $user, $ability) {
            if ($user->isAdmin()) {
                return true;
            }
            return null;
        });
    }
} 