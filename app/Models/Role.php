<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Log;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'permissions',
        'is_active'
    ];

    protected $casts = [
        'permissions' => 'array',
        'is_active' => 'boolean'
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function getNameAttribute($value)
    {
        return strtoupper($value);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtoupper($value);
    }

    public function hasPermissionTo($permission): bool
    {
        // Si es administrador, tiene todos los permisos
        if (strtoupper($this->name) === 'ADMINISTRADOR') {
            return true;
        }

        $perms = $this->permissions ?? [];
        return in_array($permission, $perms);
    }

    public function getPermissionsAttribute($value)
    {
        if (!$value) return [];
        return json_decode($value, true) ?? [];
    }

    public function setPermissionsAttribute($value)
    {
        $this->attributes['permissions'] = is_string($value) ? $value : json_encode($value);
    }
}