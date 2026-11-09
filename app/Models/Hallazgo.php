<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hallazgo extends Model
{
    use HasFactory;

    protected $fillable = [
        'audit_id',
        'audit_factura_id',
        'observacion',
        'soporte',
        'documento_referencia',
        'is_ok'
    ];

    protected $casts = [
        'is_ok' => 'boolean'
    ];

    public function auditFactura()
    {
        return $this->belongsTo(AuditFactura::class);
    }

    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }
}