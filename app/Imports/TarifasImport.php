<?php

namespace App\Imports;

use App\Models\Tarifa;
use App\Models\Empresa;
use App\Models\Sede;
use App\Services\ImportProgressService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\TarifaHistorial;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TarifasImport implements ToCollection, WithHeadingRow, WithValidation, WithChunkReading, WithBatchInserts
{
    protected $inicio_vigencia;
    protected $fin_vigencia;
    protected $importId;
    protected $progressService;
    protected $errores = [];
    protected $batch = [];
    protected $batchSize = 1000;
    protected $reportFile;
    protected $columnMapping = [];

    public function __construct($inicio_vigencia, $fin_vigencia)
    {
        ini_set('max_execution_time', 600); // 10 minutos
        ini_set('memory_limit', '1G'); // 1GB de memoria

        $this->inicio_vigencia = Carbon::parse($inicio_vigencia);
        $this->fin_vigencia = Carbon::parse($fin_vigencia);
        $this->importId = Str::random(10);
        $this->progressService = new ImportProgressService();
        $this->errores = [];
        
        // Definir mapeo de columnas según los nombres exactos del Excel
        $this->columnMapping = [
            'Razón Social Empresa' => [
                'Razón Social Empresa', 'Razon Social Empresa', 'razon_social_empresa',
                'Empresa', 'empresa', 'Razón Social', 'Razon Social', 'razon_social'
            ],
            'Nombre Sede' => [
                'Nombre Sede', 'nombre_sede', 'Sede', 'sede', 'Nombre de Sede',
                'Nombre de la Sede', 'NombreSede'
            ],
            'Régimen' => [
                'Régimen', 'Regimen', 'regimen', 'REGIMEN', 'RÉGIMEN',
                'Tipo de Régimen', 'Tipo de Regimen'
            ],
            'Tercero' => [
                'Tercero', 'tercero', 'TERCERO', 'Nombre Tercero',
                'EPS', 'eps', 'Aseguradora', 'aseguradora'
            ],
            'Descripción del Servicio' => [
                'Descripción del Servicio', 'Descripcion del Servicio',
                'descripcion_del_servicio', 'Descripción Servicio',
                'Descripcion Servicio', 'Servicio'
            ],
            'CUPS' => [
                'CUPS', 'cups', 'Código CUPS', 'Codigo CUPS',
                'cups_servicio', 'CUPS Servicio', 'codigo_cups'
            ],
            'CUPS Facturados' => [
                'CUPS Facturados', 'cups_facturados', 'CUPS IPS',
                'cups_ips', 'CUPS con Sufijo', 'CUPS_Facturados'
            ],
            'Tarifas de Manual' => [
                'Tarifas de Manual', 'tarifas_de_manual', 'Tarifa Manual',
                'Manual', 'Valor Manual', 'valor_manual'
            ],
            'Valor' => [
                'Valor', 'valor', 'Valor Tarifa', 'valor_tarifa',
                'Precio', 'precio', 'Valor Total', 'valor_total'
            ]
        ];
    }

    public function getImportId()
    {
        return $this->importId;
    }

    public function getReportFile()
    {
        return $this->reportFile;
    }

    protected function logError($fila, $mensaje, $datos = [])
    {
        $error = [
            'fila' => $fila,
            'mensaje' => $mensaje,
            'datos' => $datos
        ];
        $this->errores[] = $error;
        
        Log::error('Error en importación de tarifas', $error);
    }

    protected function guardarReporteErrores()
    {
        if (empty($this->errores)) {
            return;
        }

        $filename = storage_path('logs/errores_importacion_' . $this->importId . '.json');
        file_put_contents($filename, json_encode($this->errores, JSON_PRETTY_PRINT));
    }

    protected function generarReporteExcel()
    {
        if (empty($this->errores)) {
            return null;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Establecer encabezados
        $sheet->setCellValue('A1', 'Fila');
        $sheet->setCellValue('B1', 'Error');
        $sheet->setCellValue('C1', 'Razón Social');
        $sheet->setCellValue('D1', 'Sede');
        $sheet->setCellValue('E1', 'CUPS');
        $sheet->setCellValue('F1', 'CUPS IPS/Facturados');
        $sheet->setCellValue('G1', 'Valor');
        $sheet->setCellValue('H1', 'Detalles');

        // Dar formato a los encabezados
        $sheet->getStyle('A1:H1')->getFont()->setBold(true);
        $sheet->getStyle('A1:H1')->getFill()
            ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
            ->getStartColor()->setRGB('CCCCCC');

        // Llenar datos
        $row = 2;
        foreach ($this->errores as $error) {
            $datos = $error['datos'];
            $sheet->setCellValue('A' . $row, $error['fila']);
            $sheet->setCellValue('B' . $row, $error['mensaje']);
            $sheet->setCellValue('C' . $row, $datos['razon_social_empresa'] ?? '');
            $sheet->setCellValue('D' . $row, $datos['nombre_sede'] ?? '');
            $sheet->setCellValue('E' . $row, $datos['cups'] ?? '');
            $sheet->setCellValue('F' . $row, $datos['cups_ips_facturados'] ?? '');
            $sheet->setCellValue('G' . $row, $datos['valor'] ?? '');
            $sheet->setCellValue('H' . $row, is_array($datos) ? json_encode($datos) : '');
            $row++;
        }

        // Ajustar ancho de columnas
        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Guardar archivo
        $filename = 'reporte_errores_importacion_' . $this->importId . '.xlsx';
        $filepath = storage_path('app/public/reportes/' . $filename);
        
        // Asegurar que el directorio existe
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0777, true);
        }

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        $this->reportFile = $filename;
        return $filename;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function batchSize(): int
    {
        return $this->batchSize;
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            '*.razon_social_empresa' => 'required',
            '*.nombre_sede' => 'required',
            '*.regimen' => 'required',
            '*.tercero' => 'required',
            '*.descripcion_del_servicio' => 'required',
            '*.cups' => 'required',
            '*.cups_ips_facturados' => 'nullable',
            '*.cups facturados' => 'nullable',
            '*.tarifas_de_manual' => 'nullable',
            '*.valor' => 'required|numeric'
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages(): array
    {
        return [
            'razon_social_empresa.required' => 'La columna "Razón Social Empresa" es obligatoria',
            'nombre_sede.required' => 'La columna "Nombre Sede" es obligatoria',
            'regimen.required' => 'La columna "Régimen" es obligatoria',
            'tercero.required' => 'La columna "Tercero" es obligatoria',
            'descripcion_del_servicio.required' => 'La columna "Descripción del Servicio" es obligatoria',
            'cups.required' => 'La columna "CUPS" es obligatoria',
            'cups_ips_facturados.required' => 'La columna "CUPS IPS/Facturados" es obligatoria',
            'valor.required' => 'La columna "Valor" es obligatoria',
            'valor.numeric' => 'El campo "Valor" debe ser numérico'
        ];
    }

    public function collection(Collection $rows)
    {
        $processedCount = 0;
        $totalRows = count($rows);
        
        // Actualizar progreso inicial
        $this->progressService->updateProgress($this->importId, 0, $totalRows);

        foreach ($rows->chunk($this->batchSize) as $index => $chunk) {
            try {
                DB::beginTransaction();
                
                $processedData = [];
                foreach ($chunk as $index => $row) {
                    $data = $this->processRow($row, $index);
                    if ($data) {
                        $processedData[] = $data;
                    }
                    $processedCount++;
                    
                    // Actualizar progreso
                    $percentage = ($processedCount / $totalRows) * 100;
                    $this->progressService->updateProgress($this->importId, $processedCount, $totalRows, $percentage);
                }

                // Procesar el lote
                if (!empty($processedData)) {
                    foreach ($processedData as $data) {
                        Tarifa::updateOrCreate(
                            [
                                'empresa_id' => $data['empresa_id'],
                                'sede_id' => $data['sede_id'],
                                'cups' => $data['cups'],
                                'cups_facturados' => $data['cups_facturados'],
                                'regimen' => $data['regimen']
                            ],
                            [
                                'tercero' => $data['tercero'],
                                'descripcion_del_servicio' => $data['descripcion_del_servicio'],
                                'tarifas_de_manual' => $data['tarifas_de_manual'],
                                'valor' => $data['valor'],
                                'year' => $this->inicio_vigencia->year,
                                'inicio_vigencia' => $this->inicio_vigencia,
                                'fin_vigencia' => $this->fin_vigencia,
                                'activo' => true
                            ]
                        );
                    }
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                \Log::error('Error procesando lote', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                    'filas_afectadas' => count($chunk)
                ]);
                foreach ($chunk as $index => $row) {
                    $this->logError($processedCount + $index + 2, $e->getMessage(), $row->toArray());
                }
            }
        }

        // Log fin de importación
        \Log::info('Finalización de importación', [
            'total_procesados' => $processedCount,
            'total_errores' => count($this->errores),
            'fecha_fin' => now()->toDateTimeString()
        ]);

        // Al final de la importación, guardar el reporte si hay errores
        if (!empty($this->errores)) {
            $this->guardarReporteErrores();
            $this->generarReporteExcel();
            throw new \Exception("Se encontraron " . count($this->errores) . " errores durante la importación. Descargue el reporte para más detalles.");
        }
    }

    protected function convertToString($value)
    {
        if ($value === null) {
            return '';
        }
        
        if ($value instanceof \DateTime || $value instanceof \Carbon\Carbon) {
            return $value->format('Y-m-d H:i:s');
        }
        
        if (is_object($value)) {
            return method_exists($value, '__toString') ? (string)$value : get_class($value);
        }
        
        return (string)$value;
    }

    protected function findColumnName($row, $field)
    {
        // Si el campo ya existe exactamente como está, retornarlo
        if (isset($row[$field])) {
            return $field;
        }

        // Convertir el campo a minúsculas para comparación
        $fieldLower = strtolower($field);

        // Buscar en el mapeo de columnas
        if (isset($this->columnMapping[$fieldLower])) {
            foreach ($this->columnMapping[$fieldLower] as $possibleName) {
                if (isset($row[$possibleName])) {
                    return $possibleName;
                }
                // Intentar con variaciones del nombre
                $variations = [
                    $possibleName,
                    strtolower($possibleName),
                    str_replace(' ', '_', $possibleName),
                    str_replace('_', ' ', $possibleName)
                ];
                foreach ($variations as $variant) {
                    if (isset($row[$variant])) {
                        return $variant;
                    }
                }
            }
        }

        // Buscar de forma más flexible
        foreach ($row as $key => $value) {
            if (strpos(strtolower($key), $fieldLower) !== false) {
                return $key;
            }
        }

        return null;
    }

    protected function getColumnValue($row, $field)
    {
        try {
            $columnName = $this->findColumnName($row, $field);
            if ($columnName === null) {
                return null;
            }
            
            return $row instanceof \Illuminate\Support\Collection ? $row->get($columnName) : $row[$columnName];
            
        } catch (\Exception $e) {
            \Log::error('Error en getColumnValue', [
                'field' => $field,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    protected function processRow($row, $index)
    {
        try {
            // Validar campos requeridos mínimos
            $requiredColumns = [
                'Razón Social Empresa',
                'Nombre Sede',
                'Régimen',
                'Tercero',
                'Descripción del Servicio',
                'CUPS',
                'Valor'
            ];

            foreach ($requiredColumns as $column) {
                if (!$this->findColumnName($row, $column)) {
                    throw new \Exception("La columna '{$column}' es obligatoria");
                }
            }

            // Procesar empresa y sede
            $empresa = $this->processEmpresa($this->getColumnValue($row, 'Razón Social Empresa'));
            $sede = $this->processSede($this->getColumnValue($row, 'Nombre Sede'), $empresa->id);

            // Construir array de datos procesados
            return [
                'empresa_id' => $empresa->id,
                'sede_id' => $sede->id,
                'regimen' => trim($this->convertToString($this->getColumnValue($row, 'Régimen'))),
                'tercero' => trim($this->convertToString($this->getColumnValue($row, 'Tercero'))),
                'descripcion_del_servicio' => trim($this->convertToString($this->getColumnValue($row, 'Descripción del Servicio'))),
                'cups' => trim($this->convertToString($this->getColumnValue($row, 'CUPS'))),
                'cups_facturados' => trim($this->convertToString($this->getColumnValue($row, 'CUPS Facturados'))),
                'tarifas_de_manual' => $this->processValue($this->getColumnValue($row, 'Tarifas de Manual')),
                'valor' => $this->processValue($this->getColumnValue($row, 'Valor')),
                'year' => $this->inicio_vigencia->year,
                'inicio_vigencia' => $this->inicio_vigencia,
                'fin_vigencia' => $this->fin_vigencia,
                'activo' => true
            ];

        } catch (\Exception $e) {
            $this->logError($index + 2, $e->getMessage(), [
                'razon_social_empresa' => $this->getColumnValue($row, 'Razón Social Empresa'),
                'nombre_sede' => $this->getColumnValue($row, 'Nombre Sede'),
                'cups' => $this->getColumnValue($row, 'CUPS'),
                'cups_facturados' => $this->getColumnValue($row, 'CUPS Facturados'),
                'valor' => $this->getColumnValue($row, 'Valor')
            ]);
            throw $e;
        }
    }

    protected function processEmpresa($razonSocial)
    {
        if (empty($razonSocial)) {
            throw new \Exception("La columna 'Razón Social Empresa' es obligatoria");
        }

        // Limpiamos el texto de espacios extras y caracteres especiales
        $razonSocial = trim($razonSocial);
        
        // Intentamos encontrar la empresa por razón social exacta primero
        $empresa = Empresa::where('razon_social', $razonSocial)->first();
        
        if (!$empresa) {
            // Si no encontramos coincidencia exacta, buscamos ignorando mayúsculas/minúsculas
            $empresa = Empresa::whereRaw('LOWER(razon_social) = ?', [strtolower($razonSocial)])->first();
        }
        
        if (!$empresa) {
            // Si aún no encontramos, buscamos con LIKE para ser más flexibles
            $empresa = Empresa::where('razon_social', 'LIKE', '%' . $razonSocial . '%')->first();
        }

        if (!$empresa) {
            throw new \Exception("No se encontró la empresa con razón social: " . $razonSocial . ". Por favor, verifique que el nombre coincida exactamente con el registrado en el sistema.");
        }

        return $empresa;
    }

    protected function processSede($nombreSede, $empresaId)
    {
        if (empty($nombreSede)) {
            throw new \Exception("La columna 'Nombre Sede' es obligatoria");
        }

        // Limpiamos el texto de espacios extras y caracteres especiales
        $nombreSede = trim($nombreSede);
        
        // Intentamos encontrar la sede por nombre exacto primero
        $sede = Sede::where('sede', $nombreSede)
                   ->where('empresa_id', $empresaId)
                   ->first();
        
        if (!$sede) {
            // Si no encontramos coincidencia exacta, buscamos ignorando mayúsculas/minúsculas
            $sede = Sede::whereRaw('LOWER(sede) = ?', [strtolower($nombreSede)])
                       ->where('empresa_id', $empresaId)
                       ->first();
        }
        
        if (!$sede) {
            // Si aún no encontramos, buscamos con LIKE para ser más flexibles
            $sede = Sede::where('sede', 'LIKE', '%' . $nombreSede . '%')
                       ->where('empresa_id', $empresaId)
                       ->first();
        }

        if (!$sede) {
            // Obtenemos el nombre de la empresa para el mensaje de error
            $empresa = Empresa::find($empresaId);
            $empresaNombre = $empresa ? $empresa->razon_social : 'desconocida';
            
            throw new \Exception("No se encontró la sede '{$nombreSede}' para la empresa '{$empresaNombre}'. " .
                               "Por favor, verifique que el nombre de la sede coincida exactamente con el registrado en el sistema.");
        }

        return $sede;
    }

    protected function processDate($date)
    {
        if (empty($date)) {
            return null;
        }

        try {
            // Si ya es un objeto DateTime, retornarlo directamente
            if ($date instanceof \DateTime || $date instanceof \Carbon\Carbon) {
                return $date;
            }

            // Si es una fecha en formato Excel (número), convertirla
            if (is_numeric($date)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date);
            }

            // Si es string, intentar parsear
            if (is_string($date)) {
                return Carbon::parse($date);
            }

            \Log::warning('Tipo de fecha no soportado', [
                'tipo' => is_object($date) ? get_class($date) : gettype($date),
                'valor' => is_object($date) ? 'objeto' : var_export($date, true)
            ]);

            return null;

        } catch (\Exception $e) {
            \Log::error('Error procesando fecha', [
                'tipo' => is_object($date) ? get_class($date) : gettype($date),
                'valor' => is_object($date) ? 'objeto' : var_export($date, true),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    protected function processValue($value, $allowNull = false)
    {
        if ($allowNull && ($value === null || $value === '')) {
            return null;
        }

        if (is_string($value)) {
            $value = str_replace(',', '.', $value);
            $value = preg_replace('/[^0-9.]/', '', $value);
        }
        
        $value = floatval($value);
        
        if (!$allowNull && $value <= 0) {
            throw new \Exception("El valor debe ser mayor a 0");
        }
        
        return $value;
    }
} 