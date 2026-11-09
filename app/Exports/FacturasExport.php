<?php

namespace App\Exports;

use App\Models\Factura;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FacturasExport implements FromCollection, WithHeadings, WithMapping, WithStyles, ShouldAutoSize
{
    private $estado;
    private $fechaInicio;
    private $fechaFin;
    private $empresa_id;
    private $sede_id;
    private $mes;
    private $anio;

    public function __construct($estado = 'pendiente', $fechaInicio = null, $fechaFin = null, $empresa_id = null, $sede_id = null, $mes = null, $anio = null)
    {
        $this->estado = $estado;
        $this->fechaInicio = $fechaInicio;
        $this->fechaFin = $fechaFin;
        $this->empresa_id = $empresa_id;
        $this->sede_id = $sede_id;
        $this->mes = $mes;
        $this->anio = $anio;
    }

    public function collection()
    {
        $query = Factura::query()
            ->with([
                'observacionesRadicacion.user',
                'empresa:id,razon_social',
                'sede:id,sede'
            ])
            ->select([
                'id',
                'factura',
                'empresa_id',
                'sede_id',
                'aseguradora',
                'periodo_facturacion_inicio',
                'periodo_facturacion_final',
                'total_documento',
                'saldo',
                'fecha_radicacion',
                'numero_radicacion',
                'created_at',
                'updated_at'
            ]);
        
        // Filtro por estado (pendiente/radicado)
        if ($this->estado === 'pendiente') {
            $query->whereNull('fecha_radicacion')
                  ->whereNull('numero_radicacion');

            // Aplicar filtros adicionales solo si se proporcionan
            if ($this->empresa_id) {
                $query->where('empresa_id', $this->empresa_id);
            }
            if ($this->sede_id) {
                $query->where('sede_id', $this->sede_id);
            }
            if ($this->mes && $this->anio) {
                $query->whereYear('periodo_facturacion_inicio', $this->anio)
                      ->whereMonth('periodo_facturacion_inicio', $this->mes);
            }
        } else {
            $query->whereNotNull('fecha_radicacion')
                  ->whereNotNull('numero_radicacion');

            if ($this->fechaInicio && $this->fechaFin) {
                $query->whereBetween('fecha_radicacion', [$this->fechaInicio, $this->fechaFin]);
            }
        }

        $facturas = $query->orderBy('fecha_radicacion', 'desc')
                         ->orderBy('factura', 'asc')
                         ->get();

        // Log para debugging
        \Log::info('Facturas a exportar:', [
            'cantidad' => $facturas->count(),
            'filtros' => [
                'estado' => $this->estado,
                'empresa_id' => $this->empresa_id,
                'sede_id' => $this->sede_id,
                'mes' => $this->mes,
                'anio' => $this->anio,
                'fecha_inicio' => $this->fechaInicio,
                'fecha_fin' => $this->fechaFin
            ],
            'muestra' => $facturas->take(2)->map(function($f) {
                return [
                    'id' => $f->id,
                    'factura' => $f->factura,
                    'empresa_id' => $f->empresa_id,
                    'empresa_nombre' => $f->empresa ? $f->empresa->razon_social : null,
                    'sede_id' => $f->sede_id,
                    'sede_nombre' => $f->sede ? $f->sede->sede : null,
                    'aseguradora' => $f->aseguradora,
                    'periodo_inicio' => $f->periodo_facturacion_inicio,
                    'periodo_fin' => $f->periodo_facturacion_final,
                    'total' => $f->total_documento,
                    'saldo' => $f->saldo
                ];
            })
        ]);

        return $facturas;
    }

    public function headings(): array
    {
        return [
            'Factura',
            'Empresa',
            'Sede',
            'Aseguradora',
            'Periodo Inicio',
            'Periodo Fin',
            'Valor Factura',
            'Saldo',
            'Observaciones',
            'Estado',
            'Fecha Creación',
            'Fecha Actualización'
        ];
    }

    protected function formatDate($date)
    {
        if (!$date) return '';
        return $date instanceof Carbon ? $date->format('d/m/Y') : Carbon::parse($date)->format('d/m/Y');
    }

    protected function formatDateTime($date)
    {
        if (!$date) return '';
        return $date instanceof Carbon ? $date->format('d/m/Y H:i') : Carbon::parse($date)->format('d/m/Y H:i');
    }

    protected function formatNumber($number)
    {
        if (!is_numeric($number)) return '0,00';
        return number_format(floatval($number), 2, ',', '.');
    }

    public function map($factura): array
    {
        $observaciones = $factura->observacionesRadicacion->map(function($obs) {
            return sprintf(
                "[%s] %s - %s",
                $this->formatDateTime($obs->created_at),
                $obs->user->name,
                $obs->observacion
            );
        })->join("\n");

        // Obtener los nombres de empresa y sede a través de las relaciones
        $empresa = $factura->empresa;
        $sede = $factura->sede;

        return [
            $factura->factura,
            $empresa ? $empresa->razon_social : '',
            $sede ? $sede->sede : '',
            $factura->aseguradora ?? '',
            $this->formatDate($factura->periodo_facturacion_inicio),
            $this->formatDate($factura->periodo_facturacion_final),
            $this->formatNumber($factura->total_documento ?? 0),
            $this->formatNumber($factura->saldo ?? 0),
            $observaciones,
            $factura->fecha_radicacion ? 'RADICADO' : 'PENDIENTE POR RADICAR',
            $this->formatDateTime($factura->created_at),
            $this->formatDateTime($factura->updated_at)
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // Estilo para los encabezados
        $sheet->getStyle('A1:R1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50'],
            ],
        ]);

        // Ajustar el alto de las filas para las observaciones
        foreach ($sheet->getRowDimensions() as $rd) {
            $rd->setRowHeight(-1);
        }

        // Habilitar ajuste de texto para la columna de observaciones
        $sheet->getStyle('O:O')->getAlignment()->setWrapText(true);

        // Formato para columnas numéricas
        $lastRow = $sheet->getHighestRow();
        $columnsWithNumbers = ['H', 'K', 'L', 'M', 'N']; // Columnas con valores monetarios
        foreach ($columnsWithNumbers as $col) {
            $sheet->getStyle($col . '2:' . $col . $lastRow)->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        // Centrar fechas y números
        $sheet->getStyle('D2:F' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('I2:I' . $lastRow)->getAlignment()->setHorizontal('center');
        $sheet->getStyle('Q2:R' . $lastRow)->getAlignment()->setHorizontal('center');

        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
} 