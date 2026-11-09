<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use App\Services\FacturaStorageService;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Factura extends Model
{
    use HasFactory;

    protected $table = 'facturas';

    protected $fillable = [
        'cargue_id',
        'empresa_id',
        'sede_id',
        'departamento',
        'tipo',
        'identificacion',
        'paciente',
        'tipo_cliente',
        'documento_aseguradora',
        'aseguradora',
        'plan',
        'numero_cuenta',
        'factura',
        'periodo_facturacion_inicio',
        'periodo_facturacion_final',
        'contabilizacion_documento',
        'interface_documento',
        'adquiriente',
        'total_documento',
        'saldo',
        'observacion_documento',
        'estado_documento_dian',
        'estado_transaccion_dian',
        'fecha_validacion_dian',
        'resultado_dian',
        'cufe',
        'fecha_radicacion',
        'numero_radicacion',
        'usuario'
    ];

    protected $dates = [
        'fecha_documento',
        'periodo_facturacion_inicio',
        'periodo_facturacion_final',
        'fecha_validacion_dian',
        'fecha_radicacion',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'valor_unitario_servicio' => 'decimal:2',
        'valor_total_servicio' => 'decimal:2',
        'total_documento' => 'decimal:2',
        'saldo' => 'decimal:2'
    ];

    /**
     * Atributos adicionales que deberían ser convertidos a tipos nativos.
     */
    protected $appends = [
        'pdfFiles'
    ];

    /**
     * Atributos que no deben ser enviados a la base de datos
     */
    protected $guarded = ['pdfFiles'];

    /**
     * Obtiene los servicios de la factura
     */
    public function servicios(): HasMany
    {
        return $this->hasMany(FacturaServicio::class);
    }

    /**
     * Obtiene el total de los servicios
     */
    public function getTotalServicios(): float
    {
        return $this->servicios->sum('valor_total_servicio');
    }

    /**
     * Obtiene las auditorías relacionadas con esta factura
     */
    public function audits(): BelongsToMany
    {
        return $this->belongsToMany(Audit::class, 'audit_facturas', 'factura_id', 'audit_id')
                    ->withTimestamps();
    }

    /**
     * Obtiene las relaciones pivot de auditoría-factura
     */
    public function auditFacturas(): HasMany
    {
        return $this->hasMany(AuditFactura::class, 'factura_id');
    }

    /**
     * Obtiene los archivos PDF asociados a la factura
     */
    public function getPdfFilesAttribute()
    {
        return [];
    }

    /**
     * Obtiene los archivos PDF de la factura usando el nuevo servicio
     */
    public function getPdfFiles($audit = null)
    {
        $numeroCompleto = $this->factura;
        
        // Si no se proporciona una auditoría, intentar obtenerla de la relación
        if (!$audit && $this->auditFacturas->isNotEmpty()) {
            $audit = $this->auditFacturas->first()->audit;
        }

        if (!$audit) {
            Log::warning('No se encontró auditoría para la factura', [
                'factura_id' => $this->id,
                'factura_numero' => $this->factura
            ]);
            return [];
        }

        return app(FacturaStorageService::class)
            ->findFacturaPdfs($numeroCompleto, $audit);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function cargue(): BelongsTo
    {
        return $this->belongsTo(CargueFacturacion::class, 'cargue_id');
    }

    /**
     * Obtiene los servicios adicionales de la factura
     */
    public function serviciosAdicionales(): HasMany
    {
        return $this->hasMany(FacturaServicioAdicional::class);
    }

    /**
     * Obtiene todos los servicios de la factura (principal y adicionales)
     */
    public function getAllServicios(): Collection
    {
        $servicios = collect();

        // Agregar el servicio principal
        if ($this->cups_servicio) {
            $servicios->push([
                'cups_servicio' => $this->cups_servicio,
                'cargo_servicio' => $this->cargo_servicio,
                'descripcion_servicio' => $this->descripcion_servicio,
                'cantidad' => $this->cantidad,
                'autorizacion_servicio' => $this->autorizacion_servicio,
                'valor_unitario_servicio' => $this->valor_unitario_servicio,
                'valor_total_servicio' => $this->valor_total_servicio,
                'es_principal' => true
            ]);
        }

        // Agregar servicios adicionales
        $this->serviciosAdicionales->each(function ($servicio) use ($servicios) {
            $servicios->push(array_merge($servicio->toArray(), ['es_principal' => false]));
        });

        return $servicios;
    }

    /**
     * Obtiene las observaciones de radicación de la factura
     */
    public function observacionesRadicacion()
    {
        return $this->hasMany(ObservacionRadicacion::class);
    }

    /**
     * Verifica si la factura está pendiente por radicar
     */
    public function isPendienteRadicar()
    {
        return is_null($this->fecha_radicacion) && is_null($this->numero_radicacion);
    }

    /**
     * Agrega una nueva observación de radicación
     */
    public function agregarObservacionRadicacion(string $observacion, int $userId): ObservacionRadicacion
    {
        return $this->observacionesRadicacion()->create([
            'user_id' => $userId,
            'observacion' => $observacion
        ]);
    }
}