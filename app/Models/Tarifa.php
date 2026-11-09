<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tarifa extends Model
{
    use HasFactory;

    protected $table = 'tarifas';
    protected $primaryKey = 'id';

    protected $fillable = [
        'empresa_id',
        'sede_id',
        'regimen',
        'tercero',
        'descripcion_del_servicio',
        'cups',
        'cups_facturados',
        'tarifas_de_manual',
        'valor',
        'year',
        'activo',
        'inicio_vigencia',
        'fin_vigencia'
    ];

    protected $casts = [
        'valor' => 'decimal:2',
        'tarifas_de_manual' => 'decimal:2',
        'is_active' => 'boolean',
        'year' => 'integer',
        'inicio_vigencia' => 'date',
        'fin_vigencia' => 'date'
    ];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function historial()
    {
        return $this->hasMany(TarifaHistorial::class);
    }

    // Accessor for formatted value
    public function getValorFormateadoAttribute()
    {
        return '$' . number_format($this->valor, 2);
    }

    // Accessor for formatted manual tariff
    public function getTarifaManualFormateadaAttribute()
    {
        return $this->tarifas_de_manual ? '$' . number_format($this->tarifas_de_manual, 2) : 'N/A';
    }

    // Asegurarnos de que cups_facturados se maneje como string sin modificaciones
    public function getCupsFacturadosAttribute($value)
    {
        return $value;
    }

    public function setCupsFacturadosAttribute($value)
    {
        $this->attributes['cups_facturados'] = $value;
    }
}