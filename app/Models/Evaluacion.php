<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Evaluacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones';

    protected $fillable = [
        'titulo',
        'descripcion',
        'creador_id',
        'fecha_inicio',
        'fecha_limite',
        'tiempo_limite',
        'is_active'
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_limite' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creador_id');
    }

    public function preguntas(): HasMany
    {
        return $this->hasMany(Pregunta::class)->orderBy('orden');
    }

    public function usuarios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'evaluaciones_usuarios')
            ->withPivot(['fecha_inicio', 'fecha_fin', 'calificacion', 'estado', 'retroalimentacion'])
            ->withTimestamps();
    }

    public function evaluacionesUsuarios(): HasMany
    {
        return $this->hasMany(EvaluacionUsuario::class);
    }

    public function isDisponibleParaUsuario(User $user): bool
    {
        return $this->is_active &&
            now()->between($this->fecha_inicio, $this->fecha_limite) &&
            !$this->evaluacionesUsuarios()
                ->where('user_id', $user->id)
                ->whereIn('estado', ['completada', 'calificada'])
                ->exists();
    }

    public function getPuntajeTotal(): int
    {
        return $this->preguntas->sum('puntaje');
    }

    public function getEstadoAttribute()
    {
        if (!$this->is_active) {
            return 'inactiva';
        }

        if ($this->fecha_inicio->isFuture()) {
            return 'proxima';
        }

        if ($this->fecha_limite->isPast()) {
            return 'finalizada';
        }

        // Verificar si hay evaluaciones de usuarios y su estado
        $ultimaEvaluacion = $this->evaluacionesUsuarios()
            ->orderBy('updated_at', 'desc')
            ->first();

        if ($ultimaEvaluacion) {
            return $ultimaEvaluacion->estado;
        }

        return 'en_curso';
    }
}