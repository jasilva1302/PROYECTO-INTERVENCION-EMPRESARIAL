<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RespuestaUsuario extends Model
{
    protected $table = 'respuestas_usuarios';

    protected $fillable = [
        'evaluacion_usuario_id',
        'pregunta_id',
        'respuesta_texto',
        'opcion_seleccionada_id',
        'puntaje_obtenido',
        'retroalimentacion'
    ];

    protected $casts = [
        'puntaje_obtenido' => 'decimal:2'
    ];

    public function evaluacionUsuario(): BelongsTo
    {
        return $this->belongsTo(EvaluacionUsuario::class);
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }

    public function opcionSeleccionada(): BelongsTo
    {
        return $this->belongsTo(OpcionRespuesta::class, 'opcion_seleccionada_id');
    }

    public function calificarAutomaticamente()
    {
        if ($this->pregunta->tipo === 'opcion_multiple') {
            $opcionCorrecta = $this->pregunta->getRespuestaCorrecta();
            if ($opcionCorrecta && $this->opcion_seleccionada_id === $opcionCorrecta->id) {
                $this->puntaje_obtenido = $this->pregunta->puntaje;
            } else {
                $this->puntaje_obtenido = 0;
            }
            $this->save();
        }
    }
} 