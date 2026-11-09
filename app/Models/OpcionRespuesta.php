<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpcionRespuesta extends Model
{
    protected $table = 'opciones_respuesta';

    protected $fillable = [
        'pregunta_id',
        'opcion',
        'es_correcta',
        'orden'
    ];

    protected $casts = [
        'es_correcta' => 'boolean',
        'orden' => 'integer'
    ];

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class);
    }

    public function respuestasUsuarios(): HasMany
    {
        return $this->hasMany(RespuestaUsuario::class, 'opcion_seleccionada_id');
    }
} 