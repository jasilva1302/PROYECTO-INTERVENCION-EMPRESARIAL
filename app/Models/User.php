<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'is_active'
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean'
    ];

    protected $with = ['role'];

    /**
     * Relación con el modelo Role
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relación con las auditorías
     */
    public function audits()
    {
        return $this->hasMany(Audit::class);
    }

    /**
     * Verifica si el usuario es administrador
     */
    public function isAdmin()
    {
        return $this->role && $this->role->name === 'Administrador';
    }

    /**
     * Verifica si el usuario tiene un permiso específico
     */
    public function hasPermission($permission)
    {
        if (!$this->role) {
            return false;
        }
        
        if (strtoupper($this->role->name) === 'ADMINISTRADOR') {
            return true;
        }

        $perms = $this->role->permissions ?? [];
        return in_array($permission, $perms);
    }

    /**
     * Verifica si el usuario tiene al menos uno de los permisos dados
     */
    public function hasAnyPermission(array $permissions)
    {
        if ($this->isAdmin()) {
            return true;
        }
        if (!$this->role || !$this->is_active) {
            return false;
        }
        $perms = $this->role->permissions ?? [];
        foreach ($permissions as $perm) {
            if (in_array($perm, $perms)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Verifica si el usuario tiene todos los permisos dados
     */
    public function hasAllPermissions(array $permissions)
    {
        if ($this->isAdmin()) {
            return true;
        }
        if (!$this->role || !$this->is_active) {
            return false;
        }
        $perms = $this->role->permissions ?? [];
        foreach ($permissions as $perm) {
            if (!in_array($perm, $perms)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Obtiene todos los permisos del usuario
     */
    public function getAllPermissions()
    {
        if ($this->isAdmin()) {
            return ['*'];
        }
        if (!$this->role || !$this->is_active) {
            return [];
        }
        return $this->role->permissions ?? [];
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public function hasRole($role)
    {
        if (!$this->role || !$this->is_active) {
            return false;
        }

        if (is_string($role)) {
            return strtoupper($this->role->name) === strtoupper($role);
        }
        
        return $this->role->id === $role->id;
    }

    public function hasAnyRole($roles)
    {
        if (!$this->role || !$this->is_active) {
            return false;
        }

        if (is_string($roles)) {
            return $this->hasRole($roles);
        }

        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        
        return false;
    }

    public function assignRole(...$roles)
    {
        $roles = collect($roles)
            ->flatten()
            ->map(function ($role) {
                if (is_string($role)) {
                    return Role::where('name', $role)->first()->id;
                }
                return $role->id;
            });

        $this->roles()->sync($roles, false);
        return $this;
    }

    public function removeRole($role)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->first();
        }

        $this->roles()->detach($role);
        return $this;
    }

    public function syncRoles(...$roles)
    {
        $this->roles()->detach();
        return $this->assignRole($roles);
    }

    public function getPermissionsViaRoles()
    {
        return $this->roles->map->permissions->flatten()->unique('id');
    }
}