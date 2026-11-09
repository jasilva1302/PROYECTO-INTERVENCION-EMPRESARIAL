<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacturaServicioAdicional extends Model
{
    protected $table = 'factura_servicios_adicionales';

    protected $fillable = [
        'factura_id',
        'cups_servicio',
        'cargo_servicio',
        'descripcion_servicio',
        'cantidad',
        'autorizacion_servicio',
        'valor_unitario_servicio',
        'valor_total_servicio'
    ];

    protected $casts = [
        'valor_unitario_servicio' => 'decimal:2',
        'valor_total_servicio' => 'decimal:2',
        'cantidad' => 'integer'
    ];

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }
} 