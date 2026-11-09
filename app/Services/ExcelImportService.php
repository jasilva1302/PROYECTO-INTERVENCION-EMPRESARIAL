<?php

namespace App\Services;

use App\Models\Sede;
use App\Models\Empresa;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class ExcelImportService
{
    protected $headerRow;
    protected $columnMap;
    protected $delimiter = ','; // Delimitador por defecto para CSV

    public function __construct()
    {
        // Mapeo exacto de las columnas del Excel con los campos de la base de datos
        $this->columnMap = [
            'empresa' => ['Empresa'],
            'sede' => ['Sede'],
            'departamento' => ['Departamento', 'departamento', 'DEPARTAMENTO'],
            'tipo' => ['Tipo Documento'],
            'identificacion' => ['Identificación'],
            'paciente' => ['Paciente'],
            'tipo_cliente' => ['Tipo Cliente'],
            'documento_aseguradora' => ['Documento Aseguradora'],
            'aseguradora' => ['Aseguradora', 'ASEGURADORA', 'aseguradora', 'EPS', 'eps', 'ENTIDAD', 'Entidad', 'entidad', 'EMPRESA ASEGURADORA', 'Empresa Aseguradora'],
            'plan' => ['Plan'],
            'numero_cuenta' => ['Número de Cuenta'],
            'cups_servicio' => ['CUPS'],
            'cargo_servicio' => ['Cargo'],
            'descripcion_servicio' => ['Descripción'],
            'cantidad' => ['Cantidad'],
            'autorizacion_servicio' => ['Autorización'],
            'valor_unitario_servicio' => ['Valor Unitario'],
            'valor_total_servicio' => ['Valor Total'],
            'fecha_documento' => ['Fecha Documento'],
            'prefijo_documento' => ['Prefijo Documento'],
            'numero_documento' => ['Número Documento', 'Numero Documento', 'NUMERO DOCUMENTO'],
            'factura' => ['Factura'],
            'periodo_facturacion_inicio' => ['Periodo Facturación Inicio'],
            'periodo_facturacion_final' => ['Periodo Facturación Final'],
            'contabilizacion_documento' => ['Contabilización Documento', 'Contabilizacion Documento', 'CONTABILIZACION DOCUMENTO'],
            'interface_documento' => ['Interface Documento'],
            'adquiriente' => ['Adquiriente'],
            'total_documento' => ['Total Documento'],
            'saldo' => ['Saldo'],
            'observacion_documento' => ['Observación'],
            'estado_documento_dian' => ['Estado Documento DIAN'],
            'estado_transaccion_dian' => ['Estado Transacción DIAN'],
            'fecha_validacion_dian' => ['Fecha Validación DIAN'],
            'resultado_dian' => ['Resultado DIAN'],
            'cufe' => ['CUFE'],
            'fecha_radicacion' => ['Fecha Radicación'],
            'numero_radicacion' => ['Número Radicación'],
            'usuario' => ['Usuario']
        ];
    }

    /**
     * Procesa un archivo de importación (Excel o TXT/CSV)
     */
    public function processFile($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        
        // Si es TXT o CSV, primero limpiamos el archivo
        if (in_array($extension, ['txt', 'csv'])) {
            $cleanedFile = $this->cleanAndPreprocessFile($file);
            return $this->processCsvFile($cleanedFile);
        } else if (in_array($extension, ['xlsx', 'xls'])) {
            return $this->processExcelFile($file);
        }
        
        throw new \Exception("Formato de archivo no soportado: {$extension}");
    }

    /**
     * Limpia y preprocesa el archivo TXT/CSV
     */
    protected function cleanAndPreprocessFile($file)
    {
        $content = file_get_contents($file->getRealPath());
        
        // Detectar y establecer el delimitador
        $this->delimiter = (strpos($content, ';') !== false) ? ';' : ',';
        
        // Convertir a UTF-8 si es necesario
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'auto');
        }
        
        // Dividir en líneas
        $lines = explode("\n", $content);
        
        // Procesar encabezados
        $headers = str_getcsv(array_shift($lines), $this->delimiter);
        $headers = $this->cleanHeaders($headers);
        
        // Procesar y limpiar cada línea
        $cleanedLines = [];
        $cleanedLines[] = implode($this->delimiter, $headers);
        
        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            
            $row = str_getcsv($line, $this->delimiter);
            $cleanedRow = $this->cleanRow($row, $headers);
            $cleanedLines[] = implode($this->delimiter, $cleanedRow);
        }
        
        // Guardar archivo limpio temporalmente
        $tempFile = tempnam(sys_get_temp_dir(), 'clean_');
        file_put_contents($tempFile, implode("\n", $cleanedLines));
        
        return new \Illuminate\Http\UploadedFile(
            $tempFile,
            $file->getClientOriginalName(),
            'text/plain',
            null,
            true
        );
    }

    /**
     * Limpia los encabezados
     */
    protected function cleanHeaders($headers)
    {
        return array_map(function($header) {
            return trim($header);
        }, $headers);
    }

    /**
     * Limpia una fila de datos
     */
    protected function cleanRow($row, $headers)
    {
        $cleanedRow = [];
        foreach ($row as $index => $value) {
            $header = $headers[$index] ?? '';
            $value = trim($value);
            
            // Limpieza según tipo de campo
            if ($this->isMonetaryField($header)) {
                $value = $this->cleanMonetaryValue($value);
            } else if ($this->isDateField($header)) {
                $value = $this->cleanDateValue($value);
            } else {
                $value = $this->cleanGeneralValue($value);
            }
            
            $cleanedRow[] = $value;
        }
        return $cleanedRow;
    }

    /**
     * Determina si un campo es monetario
     */
    protected function isMonetaryField($header)
    {
        $monetaryFields = [
            'valor_unitario_servicio',
            'valor_total_servicio',
            'total_documento',
            'saldo'
        ];
        return in_array($header, $monetaryFields);
    }

    /**
     * Determina si un campo es de fecha
     */
    protected function isDateField($header)
    {
        $dateFields = [
            'fecha_documento',
            'periodo_facturacion_inicio',
            'periodo_facturacion_final',
            'fecha_validacion_dian',
            'fecha_radicacion'
        ];
        return in_array($header, $dateFields);
    }

    /**
     * Limpia valores monetarios
     */
    protected function cleanMonetaryValue($value)
    {
        // Eliminar todo excepto números, punto y coma
        $value = preg_replace('/[^0-9,\.]/', '', $value);
        
        // Manejar diferentes formatos numéricos
        if (strpos($value, ',') !== false) {
            if (strpos($value, '.') === false) {
                // Formato europeo (1.234,56)
                $value = str_replace(',', '.', $value);
            } else {
                // Formato con separadores de miles (1,234.56)
                $value = str_replace(',', '', $value);
            }
        }
        
        return $value ?: '0';
    }

    /**
     * Limpia valores de fecha
     */
    protected function cleanDateValue($value)
    {
        if (empty($value)) return '';
        
        try {
            $date = \Carbon\Carbon::parse($value);
            return $date->format('d/m/Y');
        } catch (\Exception $e) {
            return $value; // Mantener el valor original si no se puede parsear
        }
    }

    /**
     * Limpia valores generales
     */
    protected function cleanGeneralValue($value)
    {
        // Eliminar espacios múltiples
        $value = preg_replace('/\s+/', ' ', $value);
        // Eliminar caracteres especiales pero mantener acentos y ñ
        $value = preg_replace('/[^\p{L}\p{N}\s\-_.,]/u', '', $value);
        return trim($value);
    }

    /**
     * Procesa un archivo CSV/TXT limpio
     */
    protected function processCsvFile($file)
    {
        $handle = fopen($file->getRealPath(), 'r');
        if ($handle === false) {
            throw new \Exception("No se pudo abrir el archivo");
        }

        $headers = fgetcsv($handle, 0, $this->delimiter);
        if (!$headers) {
            fclose($handle);
            throw new \Exception("No se encontraron encabezados en el archivo");
        }

        $data = [];
        $row = 1;

        while (($rowData = fgetcsv($handle, 0, $this->delimiter)) !== false) {
            $row++;
            
            // Crear array asociativo con los datos
            $record = [];
            foreach ($headers as $index => $header) {
                $value = $rowData[$index] ?? null;
                $record[trim($header)] = $value;
            }
            
            try {
                $processedRow = $this->processRow($record, $this->mapHeadersToCols($headers), $row);
                $data[] = $processedRow;
            } catch (\Exception $e) {
                \Log::warning("Error procesando fila {$row}: " . $e->getMessage());
                continue;
            }
        }

        fclose($handle);
        @unlink($file->getRealPath()); // Eliminar archivo temporal
        return $data;
    }

    /**
     * Procesa un archivo Excel
     */
    protected function processExcelFile($file)
    {
        // ... existing Excel processing code ...
    }

    /**
     * Mapea los encabezados del archivo a las columnas del sistema
     */
    protected function mapHeadersToCols(array $headers): array
    {
        $columnIndexes = [];
        \Log::info("=== Iniciando mapeo de encabezados ===");
        \Log::info("Encabezados recibidos:", $headers);

        // Normalizar encabezados del archivo
        $normalizedHeaders = [];
        foreach ($headers as $index => $header) {
            $normalizedHeaders[$index] = $this->normalizeHeader($header);
            \Log::info("Header normalizado [{$index}]: {$header} -> {$normalizedHeaders[$index]}");
        }
            
        // Normalizar los posibles encabezados del sistema y mapear
            foreach ($this->columnMap as $dbField => $possibleHeaders) {
            foreach ($possibleHeaders as $possibleHeader) {
                $normalizedPossible = $this->normalizeHeader($possibleHeader);
                foreach ($normalizedHeaders as $index => $normalizedHeader) {
                    if ($normalizedHeader === $normalizedPossible) {
                        $columnIndexes[$dbField] = $headers[$index]; // Guarda el nombre real del archivo
                        \Log::info("Mapeo encontrado para {$dbField}: {$headers[$index]}");
                        break 2;
                    }
                }
            }
        }

        \Log::info("=== Mapeo final de columnas ===", $columnIndexes);
        
        // Validar campos requeridos
        $requiredFields = ['factura', 'aseguradora', 'empresa', 'sede', 'departamento'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($columnIndexes[$field])) {
                $missingFields[] = $field;
                \Log::error("Campo requerido no encontrado en mapeo: {$field}");
            }
        }
        if (!empty($missingFields)) {
            $message = "Campos obligatorios faltantes: " . implode(", ", $missingFields);
            \Log::error($message);
            throw new \Exception($message);
        }
        
        return $columnIndexes;
    }

    public function processHeader($worksheet)
    {
        $headerRow = $worksheet->getRowIterator()->current();
        $cellIterator = $headerRow->getCellIterator();
        $cellIterator->setIterateOnlyExistingCells(false);
        
        $columnIndexes = [];
        
        \Log::info("=== Procesando encabezados del Excel ===");
        
        foreach ($cellIterator as $cell) {
            $value = trim($cell->getValue());
            if (!empty($value)) {
                $columnIndex = $cell->getColumn();
                $normalizedValue = $this->normalizeHeader($value);
                
                \Log::info("Procesando encabezado:", [
                    'columna' => $columnIndex,
                    'valor_original' => $value,
                    'valor_normalizado' => $normalizedValue
                ]);
                
                // Buscar coincidencia en el mapeo de columnas
                foreach ($this->columnMap as $dbField => $possibleHeaders) {
                    $normalizedHeaders = array_map([$this, 'normalizeHeader'], $possibleHeaders);
                    if (in_array($normalizedValue, $normalizedHeaders)) {
                        $columnIndexes[$dbField] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($columnIndex) - 1;
                        \Log::info("Mapeo encontrado:", [
                            'campo_db' => $dbField,
                            'indice' => $columnIndexes[$dbField]
                        ]);
                        break;
                    }
                }
            }
        }
        
        \Log::info("=== Mapeo final de columnas ===", $columnIndexes);
        
        // Validar campos requeridos
        $requiredFields = ['factura', 'aseguradora', 'empresa', 'sede', 'departamento'];
        $missingFields = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($columnIndexes[$field])) {
                $missingFields[] = ucfirst($field);
                \Log::error("Campo requerido no encontrado en mapeo: {$field}");
            }
        }
        
        if (!empty($missingFields)) {
            throw new \Exception("Campos obligatorios faltantes en el archivo: " . implode(", ", $missingFields));
        }
        
        return $columnIndexes;
    }

    private function normalizeHeader($header): string
    {
        if (empty($header)) return '';
        
        // Handle DateTime objects
        if ($header instanceof \DateTime || $header instanceof \Carbon\Carbon) {
            $header = $header->format('Y-m-d H:i:s');
        }
        
        // Convert to string if it's an object
        if (is_object($header)) {
            $header = method_exists($header, '__toString') ? (string)$header : get_class($header);
        }
        
        // Convertir a minúsculas y remover acentos
        $normalized = strtolower((string)$header);
        $normalized = preg_replace('/[áàãâä]/u', 'a', $normalized);
        $normalized = preg_replace('/[éèêë]/u', 'e', $normalized);
        $normalized = preg_replace('/[íìîï]/u', 'i', $normalized);
        $normalized = preg_replace('/[óòõôö]/u', 'o', $normalized);
        $normalized = preg_replace('/[úùûü]/u', 'u', $normalized);
        $normalized = preg_replace('/[ñ]/u', 'n', $normalized);
        
        // Remover caracteres especiales y espacios múltiples
        $normalized = preg_replace('/[^a-z0-9\s]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        
        return trim($normalized);
    }

    private function normalizeHeaderValue($value)
    {
        // Eliminar espacios extras y normalizar caracteres especiales
        $value = trim($value);
        
        // Mapeo de nombres de columnas
        $columnMap = [
            'FECHA DOCUMENTO' => 'Fecha Documento',
            'PREFIJO DOCUMENTO' => 'Prefijo Documento',
            'NUMERO DOCUMENTO' => 'Número Documento',
            'CONTABILIZACION DOCUMENTO' => 'Contabilización Documento',
            'PERIODO FACTURACION INICIO' => 'Periodo Facturación Inicio',
            'PERIODO FACTURACION FINAL' => 'Periodo Facturación Final',
            'INTERFACE DOCUMENTO' => 'Interface Documento',
            'TOTAL DOCUMENTO' => 'Total Documento',
            'OBSERVACION DOCUMENTO' => 'Observación',
            'ESTADO DOCUMENTO DIAN' => 'Estado Documento DIAN',
            'ESTADO TRANSACCION DIAN' => 'Estado Transacción DIAN',
            'FECHA VALIDACION DIAN' => 'Fecha Validación DIAN',
            'RESULTADO DIAN' => 'Resultado DIAN',
            'FECHA RADICACION' => 'Fecha Radicación',
            'NUMERO RADICACION' => 'Número Radicación'
        ];
        
        // Si existe un mapeo para este valor, usarlo
        if (isset($columnMap[strtoupper($value)])) {
            return $columnMap[strtoupper($value)];
        }
        
        // Si no hay mapeo, devolver el valor original
        return $value;
    }

    public function processRow(array $row, array $columnIndexes, int $cargueId): array
    {
        \Log::info("=== Iniciando procesamiento de fila ===");
        \Log::info("Datos de entrada:", $row);
        \Log::info("Mapeo de columnas:", $columnIndexes);
        
        $processedData = [
            'cargue_id' => $cargueId
        ];

        // Procesar empresa primero
        if (!isset($columnIndexes['empresa']) || !isset($row[$columnIndexes['empresa']]) || empty(trim($row[$columnIndexes['empresa']]))) {
            throw new \Exception("Campo obligatorio faltante: Empresa");
        }
        
        $empresaName = trim($row[$columnIndexes['empresa']]);
        \Log::info("Procesando empresa:", ['nombre' => $empresaName]);
        
        $empresaId = $this->getEmpresaId($empresaName);
        if (!$empresaId) {
            throw new \Exception("No se encontró la empresa: " . $empresaName);
        }
        $processedData['empresa_id'] = $empresaId;

        // Procesar sede después
        if (!isset($columnIndexes['sede']) || !isset($row[$columnIndexes['sede']]) || empty(trim($row[$columnIndexes['sede']]))) {
            throw new \Exception("Campo obligatorio faltante: Sede");
        }
        
        $sedeName = trim($row[$columnIndexes['sede']]);
        \Log::info("Procesando sede:", ['nombre' => $sedeName, 'empresa_id' => $empresaId]);
        
        $sedeId = $this->getSedeId($sedeName, $empresaId);
        if (!$sedeId) {
            throw new \Exception("No se encontró la sede: " . $sedeName);
        }
        $processedData['sede_id'] = $sedeId;

        // Validar el resto de campos requeridos
        $requiredFields = [
            'aseguradora',
            'departamento',
            'identificacion',
            'tipo',
            'factura'
        ];

        $missingFields = [];
        foreach ($requiredFields as $field) {
            $columnName = $columnIndexes[$field] ?? null;
            if (!$columnName || !isset($row[$columnName]) || empty(trim($row[$columnName]))) {
                $missingFields[] = ucfirst($field);
                \Log::error("Campo requerido vacío o no encontrado", [
                    'campo' => $field,
                    'columna' => $columnName,
                    'valor' => $row[$columnName] ?? 'null'
                ]);
            }
        }

        if (!empty($missingFields)) {
            throw new \Exception("Campos obligatorios faltantes: " . implode(', ', $missingFields));
        }

        // Procesar campos básicos requeridos
        $processedData['departamento'] = trim($row[$columnIndexes['departamento']]);
        $processedData['tipo'] = $this->normalizarTipoDocumento(trim($row[$columnIndexes['tipo']]));
        $processedData['identificacion'] = preg_replace('/[^0-9-]/', '', trim($row[$columnIndexes['identificacion']]));
        $processedData['aseguradora'] = trim($row[$columnIndexes['aseguradora']]);
        $processedData['factura'] = trim($row[$columnIndexes['factura']]);

        // Procesar el resto de los campos usando el mapeo de columnas
        $camposOpcionales = [
            'paciente',
            'tipo_cliente',
            'documento_aseguradora',
            'plan',
            'numero_cuenta',
            'cups_servicio',
            'cargo_servicio',
            'descripcion_servicio',
            'cantidad',
            'autorizacion_servicio',
            'valor_unitario_servicio',
            'valor_total_servicio',
            'fecha_documento',
            'prefijo_documento',
            'numero_documento',
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

        foreach ($camposOpcionales as $campo) {
            if (isset($columnIndexes[$campo]) && isset($row[$columnIndexes[$campo]])) {
                $valor = trim($row[$columnIndexes[$campo]]);
                if (!empty($valor)) {
                    $processedData[$campo] = $this->normalizeValue($valor, $campo);
                }
            }
        }

        return $processedData;
    }

    private function normalizarTipoDocumento($tipo)
    {
        if (empty($tipo)) {
            return 'CC';
        }

        // Convertir a mayúsculas y eliminar espacios
        $tipo = trim(strtoupper($tipo));

        // Lista de tipos válidos
        $tiposValidos = [
            'CC' => 'CC',  // Cédula de ciudadanía
            'CE' => 'CE',  // Cédula de extranjería
            'TI' => 'TI',  // Tarjeta de identidad
            'RC' => 'RC',  // Registro civil
            'PA' => 'PA',  // Pasaporte
            'MS' => 'MS',  // Menor sin identificación
            'AS' => 'AS',  // Adulto sin identificación
            'NIT' => 'NIT' // NIT
        ];

        // Normalizar variaciones comunes
        $variaciones = [
            'CEDULA' => 'CC',
            'CÉDULA' => 'CC',
            'CEDULA DE CIUDADANIA' => 'CC',
            'CÉDULA DE CIUDADANÍA' => 'CC',
            'C.C.' => 'CC',
            'C.C' => 'CC',
            'CEDULAEXTRANJERIA' => 'CE',
            'CÉDULA DE EXTRANJERÍA' => 'CE',
            'C.E.' => 'CE',
            'C.E' => 'CE',
            'TARJETAIDENTIDAD' => 'TI',
            'TARJETA DE IDENTIDAD' => 'TI',
            'T.I.' => 'TI',
            'T.I' => 'TI',
            'REGISTROCIVIL' => 'RC',
            'REGISTRO CIVIL' => 'RC',
            'R.C.' => 'RC',
            'R.C' => 'RC',
            'PASAPORTE' => 'PA',
            'PAS' => 'PA',
            'MENORSINIDENTIFICACION' => 'MS',
            'MENOR SIN IDENTIFICACION' => 'MS',
            'ADULTOSINIDENTIFICACION' => 'AS',
            'ADULTO SIN IDENTIFICACION' => 'AS',
            'N.I.T.' => 'NIT',
            'N.I.T' => 'NIT'
        ];

        // Primero intentar con el tipo exacto
        if (isset($tiposValidos[$tipo])) {
            return $tiposValidos[$tipo];
        }

        // Luego intentar con las variaciones
        if (isset($variaciones[$tipo])) {
            return $variaciones[$tipo];
        }

        // Si no se encuentra, devolver CC por defecto
        return 'CC';
    }

    private function validateProcessedData($data)
    {
        $requiredFields = [
            'empresa_id',
            'sede_id',
            'departamento',
            'tipo',
            'identificacion',
            'factura'
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '')) {
                \Log::error("Campo requerido faltante o vacío", ['campo' => $field]);
                throw new \Exception("El campo {$field} es requerido y no puede estar vacío");
            }
        }

        // Validaciones específicas
        if (!is_string($data['departamento'])) {
            throw new \Exception("El departamento debe ser una cadena de texto");
        }

        if (!preg_match('/^[0-9-]+$/', $data['identificacion'])) {
            throw new \Exception("La identificación solo debe contener números y guiones");
        }
    }

    private function normalizeValue($value, $field)
    {
        \Log::info("=== Normalizando valor para {$field} ===", [
            'valor_original' => var_export($value, true),
            'tipo_dato' => gettype($value)
        ]);
        
        if (is_object($value)) {
            $result = method_exists($value, '__toString') ? (string)$value : get_class($value);
            \Log::info("Objeto convertido a string", ['resultado' => $result]);
            return $result;
        }

        // Si la celda está completamente vacía, retornar null
        if ($value === null || $value === '') {
            \Log::info("Valor vacío detectado para {$field}");
            return null;
        }

        // Convertir a string y limpiar espacios al inicio y final
        $value = trim((string)$value);

        // Procesar campos específicos
        switch ($field) {
            case 'contabilizacion_documento':
                \Log::info("Procesando contabilización como string", [
                    'valor_original' => $value
                ]);
                return $value;

            case 'fecha_documento':
            case 'periodo_facturacion_inicio':
            case 'periodo_facturacion_final':
            case 'fecha_validacion_dian':
            case 'fecha_radicacion':
                try {
                    $fecha = \Carbon\Carbon::createFromFormat('d/m/Y', $value);
                    \Log::info("Fecha procesada para {$field}", [
                        'valor_original' => $value,
                        'fecha_procesada' => $fecha->format('Y-m-d')
                    ]);
                    return $fecha;
                } catch (\Exception $e) {
                    \Log::warning("Error procesando fecha para {$field}", [
                        'valor' => $value,
                        'error' => $e->getMessage()
                    ]);
                    return null;
                }

            case 'prefijo_documento':
            case 'numero_documento':
                \Log::info("Documento procesado para {$field}", [
                    'valor_original' => $value,
                    'resultado' => $value
                ]);
                return $value;

            case 'observacion_documento':
                \Log::info("Observación procesada", [
                    'valor_original' => $value,
                    'resultado' => $value
                ]);
                return $value;

            case 'valor_unitario_servicio':
            case 'valor_total_servicio':
            case 'total_documento':
            case 'saldo':
                return is_numeric($value) ? $value : null;

            case 'cantidad':
                return intval($value);

            default:
                return $value;
        }
    }

    private function normalizeNumericValue($value)
    {
        // Eliminar espacios y caracteres no numéricos
        $value = preg_replace('/[^0-9,\.]/', '', $value);
        
        // Manejar diferentes formatos numéricos
        if (strpos($value, ',') !== false) {
            if (strpos($value, '.') === false) {
                // Formato europeo (1.234,56)
                $value = str_replace(',', '.', $value);
            } else {
                // Formato con separadores de miles (1,234.56)
                $value = str_replace(',', '', $value);
            }
        }
        
        return $value ?: '0';
    }

    protected function getSedeId(string $sedeName, int $empresaId): ?int
    {
        if (empty($sedeName)) {
            \Log::warning("Nombre de sede vacío");
            return null;
        }

        $sedeName = trim($sedeName);
        \Log::info("Buscando sede:", [
            'nombre' => $sedeName, 
            'empresa_id' => $empresaId,
            'nombre_normalizado' => strtoupper($sedeName)
        ]);

        // Primero buscar por nombre exacto y empresa_id
        $sede = Sede::where(function($query) use ($sedeName) {
                    $query->where('sede', $sedeName)
                          ->orWhere('sede', strtoupper($sedeName))
                          ->orWhere('sede', strtolower($sedeName));
                })
                ->where('empresa_id', $empresaId)
                ->first();
        \Log::info("Resultado búsqueda exacta:", ['encontrado' => !is_null($sede)]);

        if (!$sede) {
            // Si no se encuentra, intentar con el nombre sin la parte después del guion
            $baseName = explode(' - ', $sedeName)[0];
            $sede = Sede::where(function($query) use ($baseName) {
                        $query->where('sede', 'like', $baseName . '%')
                              ->orWhere('sede', 'like', strtoupper($baseName) . '%')
                              ->orWhere('sede', 'like', strtolower($baseName) . '%');
                    })
                    ->where('empresa_id', $empresaId)
                    ->first();
            \Log::info("Búsqueda con baseName:", [
                'baseName' => $baseName, 
                'encontrado' => !is_null($sede),
                'sede_encontrada' => $sede->sede ?? null
            ]);
        }

        if (!$sede) {
            // Si aún no se encuentra, intentar una búsqueda más flexible
            $searchTerms = [
                $sedeName,
                str_replace(' - ', ' ', $sedeName),
                str_replace(' - ', '-', $sedeName),
                str_replace('MEDELLIN', 'MED', $sedeName),
                str_replace('MEDELLIN', 'MEDELLÍN', $sedeName)
            ];

            foreach ($searchTerms as $term) {
                $sede = Sede::where(function($query) use ($term) {
                            $query->where('sede', 'like', '%' . $term . '%')
                                  ->orWhere('sede', 'like', '%' . strtoupper($term) . '%')
                                  ->orWhere('sede', 'like', '%' . strtolower($term) . '%');
                        })
                        ->where('empresa_id', $empresaId)
                        ->first();
                
                \Log::info("Búsqueda con término alternativo:", [
                    'termino' => $term,
                    'encontrado' => !is_null($sede),
                    'sede_encontrada' => $sede->sede ?? null
                ]);
                
                if ($sede) break;
            }
        }

        if (!$sede) {
            // Última opción: buscar por palabras clave
            $palabrasClave = array_filter(explode(' ', str_replace(['-', '_'], ' ', $sedeName)), function($palabra) {
                return strlen($palabra) > 3;
            });
            
            $sede = Sede::where('empresa_id', $empresaId)
                       ->where(function($query) use ($palabrasClave) {
                           foreach ($palabrasClave as $palabra) {
                               $query->orWhere('sede', 'like', '%' . $palabra . '%');
                           }
                       })
                       ->first();
                       
            \Log::info("Búsqueda por palabras clave:", [
                'palabras' => $palabrasClave,
                'encontrado' => !is_null($sede),
                'sede_encontrada' => $sede->sede ?? null
            ]);
        }

        if (!$sede) {
            \Log::warning("No se encontró la sede:", [
                'nombre' => $sedeName,
                'empresa_id' => $empresaId,
                'variaciones_probadas' => $searchTerms ?? [],
                'palabras_clave' => $palabrasClave ?? []
            ]);
            return null;
        }

        \Log::info("Sede encontrada:", [
            'id' => $sede->id,
            'sede' => $sede->sede,
            'empresa_id' => $sede->empresa_id
        ]);
        
        return $sede->id;
    }

    protected function getEmpresaId(string $empresaName): ?int
    {
        if (empty($empresaName)) {
            \Log::warning("Nombre de empresa vacío");
            return null;
        }

        $empresaName = trim($empresaName);
        \Log::info("Buscando empresa en BD:", ['nombre' => $empresaName]);

        // Primero intentar una coincidencia exacta
        $empresa = Empresa::where('razon_social', '=', $empresaName)->first();
        \Log::info("Resultado búsqueda exacta:", [
            'nombre_buscado' => $empresaName,
            'encontrado' => !is_null($empresa),
            'razon_social_encontrada' => $empresa->razon_social ?? null
        ]);

        if (!$empresa) {
            // Si no se encuentra, intentar con el nombre normalizado
            $normalizedName = strtoupper(trim($empresaName));
            $empresa = Empresa::whereRaw('UPPER(TRIM(razon_social)) = ?', [$normalizedName])->first();
            \Log::info("Resultado búsqueda normalizada:", [
                'nombre_normalizado' => $normalizedName,
                'encontrado' => !is_null($empresa)
            ]);
        }

        if (!$empresa) {
            // Buscar por variaciones comunes del nombre
            $searchTerms = [
                str_replace(' SAS', '', $empresaName),
                str_replace(' S.A.S', '', $empresaName),
                str_replace(' S.A.S.', '', $empresaName),
                str_replace(' SA', '', $empresaName),
                str_replace(' S.A', '', $empresaName),
                str_replace(' S.A.', '', $empresaName)
            ];

            foreach ($searchTerms as $term) {
                $normalizedTerm = strtoupper(trim($term));
                $empresa = Empresa::whereRaw('UPPER(TRIM(razon_social)) LIKE ?', ['%' . $normalizedTerm . '%'])->first();
                
                \Log::info("Búsqueda con término:", [
                    'termino_original' => $term,
                    'termino_normalizado' => $normalizedTerm,
                    'encontrado' => !is_null($empresa),
                    'razon_social' => $empresa->razon_social ?? null
                ]);
                
                if ($empresa) break;
            }
        }

        if (!$empresa) {
            \Log::warning("No se encontró la empresa:", [
                'nombre_original' => $empresaName,
                'nombre_normalizado' => $normalizedName ?? null,
                'variaciones_probadas' => $searchTerms ?? []
            ]);
            return null;
        }

        \Log::info("Empresa encontrada:", [
            'id' => $empresa->id,
            'razon_social' => $empresa->razon_social,
            'nombre_buscado' => $empresaName
        ]);
        
        return $empresa->id;
    }

    public function validateRequiredFields(array $data): void
    {
        $missingFields = [];
        
        // Lista de campos requeridos con sus nombres de visualización
        $requiredFields = [
            'empresa_id' => 'Empresa',
            'sede_id' => 'Sede',
            'departamento' => 'Departamento',
            'aseguradora' => 'Aseguradora',
            'factura' => 'Factura',
            'identificacion' => 'Identificación',
            'tipo' => 'Tipo Documento'
        ];
        
        foreach ($requiredFields as $field => $displayName) {
            if (!isset($data[$field]) || (is_string($data[$field]) && trim($data[$field]) === '') || $data[$field] === null) {
                $missingFields[] = $displayName;
                \Log::error("Campo requerido faltante: {$displayName}", [
                    'campo' => $field,
                    'valor' => $data[$field] ?? 'null'
                ]);
            }
        }
        
        if (!empty($missingFields)) {
            throw new \Exception('Campos obligatorios faltantes: ' . implode(', ', $missingFields));
        }
    }
} 
