<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    use HasFactory;

    protected $table = 'sedes';
    protected $primaryKey = 'id';

    protected $fillable = [
        'sede',
        'empresa_id',
        'direccion',
        'telefono',
        'email',
        'is_active',
        'codigo_habilitacion'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function tarifas()
    {
        return $this->hasMany(Tarifa::class);
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }

    public function audits()
    {
        return $this->hasMany(Audit::class);
    }
} 