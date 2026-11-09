<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TarifaHistorial extends Model
{
    use HasFactory;

    protected $table = 'tarifa_historial';
    protected $primaryKey = 'id';

    protected $fillable = [
        'tarifa_id',
        'valor_anterior',
        'valor_nuevo',
        'user_id'
    ];

    public function tarifa()
    {
        return $this->belongsTo(Tarifa::class, 'tarifa_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}