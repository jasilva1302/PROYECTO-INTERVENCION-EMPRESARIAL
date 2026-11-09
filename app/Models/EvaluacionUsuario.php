<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EvaluacionUsuario extends Model
{
    protected $table = 'evaluaciones_usuarios';

    const ESTADO_PENDIENTE = 'pendiente';
    const ESTADO_EN_PROGRESO = 'en_progreso';
    const ESTADO_COMPLETADA = 'completada';
    const ESTADO_CALIFICADA = 'calificada';

    protected $fillable = [
        'evaluacion_id',
        'user_id',
        'fecha_inicio',
        'fecha_fin',
        'calificacion',
        'calificacion_total',
        'estado',
        'retroalimentacion'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'calificacion' => 'decimal:2',
        'calificacion_total' => 'decimal:2'
    ];

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaUsuario::class);
    }

    public function calcularCalificacion(): float
    {
        $puntajeTotal = $this->evaluacion->getPuntajeTotal();
        $puntajeObtenido = $this->respuestas->sum('puntaje_obtenido');

        return $puntajeTotal > 0 ? ($puntajeObtenido * 5) / $puntajeTotal : 0;
    }

    public function getTiempoTranscurrido()
    {
        if (!$this->fecha_inicio) {
            return null;
        }

        $fin = $this->fecha_fin ?? now();
        return $this->fecha_inicio->diffInMinutes($fin);
    }

    public function isEnTiempo(): bool
    {
        if (!$this->evaluacion->tiempo_limite) {
            return true;
        }

        return $this->getTiempoTranscurrido() <= $this->evaluacion->tiempo_limite;
    }
} 