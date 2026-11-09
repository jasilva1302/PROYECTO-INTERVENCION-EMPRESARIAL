<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CargueFacturacion extends Model
{
    protected $table = 'cargue_facturacion';

    protected $fillable = [
        'user_id',
        'empresa_id',
        'sede_id',
        'nombre_archivo',
        'archivo',
        'tipo_archivo',
        'total_registros',
        'registros_procesados',
        'registros_error',
        'errores',
        'estado',
        'fecha_inicio',
        'fecha_fin',
        'mes'
    ];

    protected $casts = [
        'errores' => 'array'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'cargue_id');
    }
} 