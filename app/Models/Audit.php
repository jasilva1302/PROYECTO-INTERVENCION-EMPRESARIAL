<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Audit extends Model
{
    use HasFactory;

    protected $fillable = [
        'empresa_id',
        'sede_id',
        'year',
        'month',
        'mes',
        'link',
        'user_id',
        'created_by',
        'started_at',
        'status',
        'completed',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function facturas(): BelongsToMany
    {
        return $this->belongsToMany(Factura::class, 'audit_facturas')
                    ->withTimestamps();
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function sede(): BelongsTo
    {
        return $this->belongsTo(Sede::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Obtiene las relaciones pivot de auditoría-factura
     */
    public function auditFacturas(): HasMany
    {
        return $this->hasMany(AuditFactura::class);
    }

    /**
     * Obtiene los hallazgos a través de las relaciones pivot
     */
    public function hallazgos()
    {
        return $this->hasManyThrough(
            Hallazgo::class,
            AuditFactura::class,
            'audit_id',
            'audit_factura_id',
            'id',
            'id'
        );
    }

    /**
     * Actualiza el contador de facturas auditadas
     */
    public function actualizarContadorFacturas()
    {
        $this->total_facturas = $this->facturas()->count();
        $this->facturas_auditadas = $this->hallazgos()->count();
        $this->save();
    }

    public function getFullFileStructure()
    {
        $basePath = public_path('audits/'.$this->id);
        $structure = [];

        if (!is_dir($basePath)) {
            return $structure;
        }

        $aseguradoras = array_diff(scandir($basePath), ['.', '..']);

        foreach ($aseguradoras as $aseguradora) {
            $aseguradoraPath = $basePath.'/'.$aseguradora;
            
            if (!is_dir($aseguradoraPath)) continue;

            $facturas = array_diff(scandir($aseguradoraPath), ['.', '..']);
            $aseguradoraData = [
                'nombre' => $aseguradora,
                'facturas' => []
            ];

            foreach ($facturas as $factura) {
                $facturaPath = $aseguradoraPath.'/'.$factura;
                
                if (!is_dir($facturaPath)) continue;

                $files = array_diff(scandir($facturaPath), ['.', '..']);
                $pdfFiles = array_filter($files, function($file) {
                    return strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf';
                });

                $aseguradoraData['facturas'][] = [
                    'nombre' => $factura,
                    'pdfs' => array_values($pdfFiles),
                    'count' => count($pdfFiles)
                ];
            }

            if (!empty($aseguradoraData['facturas'])) {
                $structure[] = $aseguradoraData;
            }
        }

        return $structure;
    }

    public function getPdfFiles($aseguradora, $factura)
    {
        $path = "{$this->id}/{$aseguradora}/{$factura}";
        $fullPath = public_path("audits/{$path}");

        if (!is_dir($fullPath)) {
            return [];
        }

        $files = array_diff(scandir($fullPath), ['.', '..']);
        $pdfFiles = [];

        foreach ($files as $file) {
            if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'pdf') {
                // Extraer el tipo de archivo del nombre (primeros 3 caracteres)
                $tipoArchivo = substr($file, 0, 3);
                
                $pdfFiles[] = [
                    'name' => $file,
                    'url' => url("audits/{$path}/{$file}"),
                    'type' => $tipoArchivo // HEV, OPF, PDX, CRC, FEV
                ];
            }
        }

        return $pdfFiles;
    }

    public function getDocumentsBaseUrl()
    {
        return url('audits/'.$this->id);
    }

    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            if (isset($model->month)) {
                $model->month = ucfirst(strtolower($model->month));
            }
        });

        static::created(function ($model) {
            $model->update([
                'link' => url('audits/'.$model->id)
            ]);
        });

        static::retrieved(function ($audit) {
            // Asegurarse de que las relaciones estén inicializadas
            if (!isset($audit->facturas)) {
                $audit->facturas = collect();
            }
            if (!isset($audit->auditFacturas)) {
                $audit->auditFacturas = collect();
            }
        });
    }
}