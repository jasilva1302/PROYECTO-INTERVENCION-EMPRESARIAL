<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $table = 'empresas';
    protected $primaryKey = 'id';

    protected $fillable = [
        'razon_social',
        'nit',
        'direccion',
        'telefono',
        'email',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function sedes()
    {
        return $this->hasMany(Sede::class);
    }

    public function tarifas()
    {
        return $this->hasMany(Tarifa::class);
    }
} 