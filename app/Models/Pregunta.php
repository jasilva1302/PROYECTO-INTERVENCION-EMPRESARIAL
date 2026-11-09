<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pregunta extends Model
{
    use HasFactory;

    protected $fillable = [
        'evaluacion_id',
        'pregunta',
        'tipo',
        'puntaje',
        'orden'
    ];

    protected $casts = [
        'puntaje' => 'integer',
        'orden' => 'integer'
    ];

    public function evaluacion(): BelongsTo
    {
        return $this->belongsTo(Evaluacion::class);
    }

    public function opciones(): HasMany
    {
        return $this->hasMany(OpcionRespuesta::class)->orderBy('orden');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(RespuestaUsuario::class);
    }

    public function getRespuestaCorrecta()
    {
        if ($this->tipo === 'opcion_multiple') {
            return $this->opciones()->where('es_correcta', true)->first();
        }
        return null;
    }
}