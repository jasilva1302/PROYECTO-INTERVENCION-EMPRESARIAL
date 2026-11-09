<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Respuesta extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'pregunta_id',
        'respuesta',
        'correcta'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pregunta()
    {
        return $this->belongsTo(Pregunta::class);
    }
}