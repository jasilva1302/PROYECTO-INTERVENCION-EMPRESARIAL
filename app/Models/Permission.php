<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'module_id'
    ];

    protected $visible = [
        'id',
        'name',
        'description'
    ];

    public function getNameAttribute($value)
    {
        return strtolower($value);
    }

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = strtolower($value);
    }

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }

    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
} 