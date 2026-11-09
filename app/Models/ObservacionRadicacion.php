<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ObservacionRadicacion extends Model
{
    use HasFactory;

    protected $table = 'observaciones_radicacion';

    protected $fillable = [
        'factura_id',
        'user_id',
        'observacion'
    ];

    /**
     * Obtiene la factura asociada a la observación
     */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class);
    }

    /**
     * Obtiene el usuario que creó la observación
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
} 