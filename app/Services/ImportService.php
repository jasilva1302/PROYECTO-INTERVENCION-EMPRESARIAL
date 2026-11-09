class ImportService
{
    public function processFile($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $content = file_get_contents($file->getRealPath());
        
        // Detectar el delimitador
        $delimiter = $this->detectDelimiter($content);
        
        // Convertir a UTF-8 si es necesario
        $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252']);
        if ($encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        
        // Dividir en líneas
        $lines = explode("\n", $content);
        $header = str_getcsv(array_shift($lines), $delimiter);
        $header = array_map('trim', $header);
        
        // Mapear columnas
        $columnIndexes = $this->mapColumns($header);
        
        $data = [];
        foreach ($lines as $index => $line) {
            if (empty(trim($line))) continue;
            
            $row = str_getcsv($line, $delimiter);
            $processedRow = $this->processRow($row, $columnIndexes);
            if (!empty($processedRow)) {
                $processedRow['fila'] = $index + 2; // +2 porque $index empieza en 0 y ya omitimos la fila de encabezados
                $data[] = $processedRow;
            }
        }
        
        return $data;
    }
    
    public function processHeader($worksheet)
    {
        $headerRow = $worksheet->getRowIterator()->current();
        $cellIterator = $headerRow->getCellIterator();
        $header = [];
        
        foreach ($cellIterator as $cell) {
            $header[] = trim($cell->getValue());
        }
        
        return $this->mapColumns($header);
    }
    
    public function processRow($row, $columnIndexes, $cargueId = null)
    {
        $data = [];
        $warnings = [];
        
        \Log::info("Procesando fila", [
            'indices_columnas' => $columnIndexes,
            'datos_fila' => $row
        ]);
        
        // Obtener empresa y sede por defecto
        $defaults = $this->getDefaultEmpresaAndSede();
        $data['empresa_id'] = $defaults['empresa_id'];
        $data['sede_id'] = $defaults['sede_id'];
        
        // Agregar el cargue_id si está presente
        if ($cargueId) {
            $data['cargue_id'] = $cargueId;
        }
        
        // Mapear campos básicos
        $camposBasicos = [
            'factura' => ['factura', 'numero_factura', 'nro_factura'],
            'aseguradora' => ['aseguradora', 'eps', 'empresa'],
            'departamento' => ['departamento', 'Departamento', 'DEPARTAMENTO', 'depto', 'dep'],
            'tipo' => ['tipo', 'tipo_documento', 'clase_documento'],
            'identificacion' => ['identificacion', 'id', 'documento', 'num_documento'],
            'paciente' => ['paciente', 'nombre_paciente', 'beneficiario'],
            'tipo_cliente' => ['tipo_cliente', 'tipo_beneficiario', 'clase_cliente'],
            'documento_aseguradora' => ['documento_aseguradora', 'nit_aseguradora', 'id_aseguradora'],
            'plan' => ['plan', 'plan_beneficios', 'tipo_plan'],
            'numero_cuenta' => ['numero_cuenta', 'cuenta', 'nro_cuenta'],
            'cups_servicio' => ['cups_servicio', 'cups', 'codigo_cups'],
            'cargo_servicio' => ['cargo_servicio', 'cargo', 'tipo_servicio'],
            'descripcion_servicio' => ['descripcion_servicio', 'descripcion', 'servicio'],
            'cantidad' => ['cantidad', 'cant', 'numero_servicios'],
            'autorizacion_servicio' => ['autorizacion_servicio', 'autorizacion', 'num_autorizacion'],
            'valor_unitario_servicio' => ['valor_unitario_servicio', 'valor_unitario', 'precio_unitario'],
            'valor_total_servicio' => ['valor_total_servicio', 'valor_total', 'total_servicio'],
            'fecha_documento' => ['fecha_documento', 'fecha', 'fecha_factura'],
            'prefijo_documento' => ['prefijo_documento', 'prefijo', 'pref'],
            'numero_documento' => ['numero_documento', 'num_documento', 'nro_documento'],
            'periodo_facturacion_inicio' => ['periodo_facturacion_inicio', 'fecha_inicio', 'inicio_periodo'],
            'periodo_facturacion_final' => ['periodo_facturacion_final', 'fecha_fin', 'fin_periodo'],
            'contabilizacion_documento' => ['contabilizacion_documento', 'fecha_contabilizacion'],
            'interface_documento' => ['interface_documento', 'interface', 'interfaz'],
            'adquiriente' => ['adquiriente', 'cliente', 'comprador'],
            'total_documento' => ['total_documento', 'total_factura', 'valor_total_factura'],
            'saldo' => ['saldo', 'saldo_pendiente', 'valor_pendiente'],
            'observacion_documento' => ['observacion_documento', 'observacion', 'notas'],
            'estado_documento_dian' => ['estado_documento_dian', 'estado_dian', 'status_dian'],
            'estado_transaccion_dian' => ['estado_transaccion_dian', 'transaccion_dian'],
            'fecha_validacion_dian' => ['fecha_validacion_dian', 'validacion_dian'],
            'resultado_dian' => ['resultado_dian', 'respuesta_dian'],
            'cufe' => ['cufe', 'codigo_cufe', 'id_cufe'],
            'fecha_radicacion' => ['fecha_radicacion', 'radicacion'],
            'numero_radicacion' => ['numero_radicacion', 'num_radicacion', 'radicado'],
            'usuario' => ['usuario', 'user', 'creado_por']
        ];
        
        foreach ($camposBasicos as $campo => $alternativas) {
            $valor = null;
            foreach ($alternativas as $alt) {
                if (isset($columnIndexes[$alt]) && isset($row[$columnIndexes[$alt]])) {
                    $valor = trim($row[$columnIndexes[$alt]]);
                    if ($campo === 'departamento') {
                        \Log::info("Encontrado valor de departamento", [
                            'alternativa' => $alt,
                            'valor' => $valor,
                            'indice' => $columnIndexes[$alt]
                        ]);
                    }
                    break;
                }
            }
            
            if ($valor !== null) {
                // Procesar según el tipo de campo
                if (in_array($campo, ['valor_unitario_servicio', 'valor_total_servicio', 'total_documento', 'saldo'])) {
                    $valor = $this->procesarValorMonetario($valor);
                } elseif (in_array($campo, [
                    'fecha_documento',
                    'periodo_facturacion_inicio',
                    'periodo_facturacion_final',
                    'contabilizacion_documento',
                    'fecha_validacion_dian',
                    'fecha_radicacion'
                ])) {
                    $valor = $this->procesarFecha($valor);
                } elseif ($campo === 'cantidad') {
                    $valor = intval($valor);
                } elseif ($campo === 'departamento') {
                    if (empty(trim($valor))) {
                        \Log::error("Valor de departamento vacío después de procesamiento");
                        throw new \Exception("El campo departamento no puede estar vacío");
                    }
                    \Log::info("Valor de departamento procesado", ['valor' => $valor]);
                }
                
                $data[$campo] = $valor;
            } elseif ($campo === 'departamento') {
                \Log::error("No se encontró valor para departamento", [
                    'alternativas_buscadas' => $alternativas,
                    'indices_disponibles' => $columnIndexes,
                    'datos_fila' => $row
                ]);
                throw new \Exception("No se encontró el campo departamento en el archivo");
            }
        }
        
        // Validar datos básicos
        if (empty($data['factura']) || empty($data['aseguradora'])) {
            return null;
        }
        
        // Establecer valores por defecto para campos requeridos
        $data['usuario'] = $data['usuario'] ?? auth()->user()->name ?? 'Sistema';
        
        // Agregar warnings si existen
        if (!empty($warnings)) {
            $data['_warnings'] = $warnings;
        }
        
        return $data;
    }
    
    public function validateRequiredFields($data)
    {
        $camposRequeridos = [
            'factura',
            'aseguradora',
            'valor'
        ];
        
        $faltantes = [];
        foreach ($camposRequeridos as $campo) {
            if (!isset($data[$campo]) || empty($data[$campo])) {
                $faltantes[] = $campo;
            }
        }
        
        return $faltantes;
    }
    
    private function detectDelimiter($content)
    {
        $delimiters = [',', ';', '\t'];
        $counts = [];
        
        foreach ($delimiters as $delimiter) {
            $counts[$delimiter] = substr_count($content, $delimiter);
        }
        
        return array_search(max($counts), $counts);
    }
    
    private function mapColumns($header)
    {
        $columnMap = [
            'factura' => ['factura', 'numero_factura', 'nro_factura', 'nro'],
            'aseguradora' => ['aseguradora', 'eps', 'empresa', 'entidad'],
            'valor' => ['valor', 'valor_total', 'monto', 'total'],
            'estado' => ['estado', 'status', 'estatus'],
            'fecha_documento' => ['fecha_documento', 'fecha_factura', 'fecha'],
            'periodo_facturacion_inicio' => ['periodo_inicio', 'fecha_inicio', 'inicio_periodo'],
            'periodo_facturacion_final' => ['periodo_fin', 'fecha_fin', 'fin_periodo'],
            'contabilizacion_documento' => ['fecha_contabilizacion', 'contabilizacion'],
            'fecha_validacion_dian' => ['fecha_dian', 'validacion_dian', 'dian'],
            'fecha_radicacion' => ['fecha_radicacion', 'radicacion']
        ];
        
        $indexes = [];
        foreach ($header as $index => $columnName) {
            $columnName = $this->normalizeString($columnName);
            foreach ($columnMap as $field => $alternatives) {
                if (in_array($columnName, array_map([$this, 'normalizeString'], $alternatives))) {
                    $indexes[$field] = $index;
                    break;
                }
            }
        }
        
        return $indexes;
    }
    
    private function normalizeString($string)
    {
        $string = mb_strtolower(trim($string));
        $string = preg_replace('/[^a-z0-9]/', '', $string);
        return $string;
    }
    
    private function procesarValorMonetario($valor)
    {
        // Eliminar caracteres no numéricos excepto punto y coma
        $valor = preg_replace('/[^0-9.,]/', '', $valor);
        
        // Reemplazar coma por punto si es el separador decimal
        if (strpos($valor, ',') !== false && strpos($valor, '.') === false) {
            $valor = str_replace(',', '.', $valor);
        }
        
        return (float) $valor;
    }
    
    private function procesarFecha($fecha)
    {
        if (empty($fecha)) return null;
        
        // Limpiar la fecha
        $fecha = trim($fecha);
        
        // Si es una fecha en formato Excel (número)
        if (is_numeric($fecha)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha)
                ->format('Y-m-d');
        }
        
        // Intentar varios formatos comunes
        $formatos = [
            'd/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d',
            'd/m/y', 'y-m-d', 'd-m-y', 'y/m/d'
        ];
        
        foreach ($formatos as $formato) {
            $date = \DateTime::createFromFormat($formato, $fecha);
            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }
        
        return null;
    }
    
    private function getDefaultEmpresaAndSede()
    {
        // Obtener la primera empresa y sede disponible como fallback
        $empresa = \App\Models\Empresa::first();
        $sede = $empresa ? $empresa->sedes()->first() : null;
        
        return [
            'empresa_id' => $empresa ? $empresa->id : null,
            'sede_id' => $sede ? $sede->id : null
        ];
    }
} 