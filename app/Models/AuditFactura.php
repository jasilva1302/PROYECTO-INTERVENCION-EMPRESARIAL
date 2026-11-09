<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuditFactura extends Pivot
{
    use HasFactory;

    protected $table = 'audit_facturas';

    public $incrementing = true;

    protected $fillable = [
        'audit_id',
        'factura_id'
    ];

    /**
     * Obtiene la auditoría relacionada
     */
    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }

    /**
     * Obtiene la factura relacionada
     */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    /**
     * Obtiene los hallazgos relacionados
     */
    public function hallazgos(): HasMany
    {
        return $this->hasMany(Hallazgo::class, 'audit_factura_id');
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::retrieved(function ($auditFactura) {
            // Asegurarse de que hallazgos sea siempre una colección
            if (!isset($auditFactura->hallazgos)) {
                $auditFactura->hallazgos = collect();
            }
        });
    }
}