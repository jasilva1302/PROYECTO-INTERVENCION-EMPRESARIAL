<?php

namespace App\Http\Controllers;

use App\Models\CargueFacturacion;
use App\Models\Factura;
use App\Models\Sede;
use App\Services\ExcelImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Carbon\Carbon;

class CargueFacturacionController extends Controller
{
    protected $importService;

    public function __construct(ExcelImportService $importService)
    {
        $this->middleware('auth');
        $this->importService = $importService;
    }

    public function index()
    {
        // Verificar si es administrador o tiene permiso
        if (!auth()->user()->isAdmin() && !auth()->user()->hasPermission('ver Facturación')) {
            return redirect()->route('home')
                ->with('error', 'No tienes permiso para acceder a este módulo');
        }

        $cargues = CargueFacturacion::with(['sede', 'empresa'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('facturacion.cargue.index', compact('cargues'));
    }

    public function create()
    {
        // Verificar si es administrador o tiene permiso
        if (!auth()->user()->isAdmin() && !auth()->user()->hasPermission('crear Facturación')) {
            return redirect()->route('facturacion.cargue.index')
                ->with('error', 'No tienes permiso para crear cargues de facturación');
        }

        $sedes = Sede::orderBy('sede')->get();
        return view('facturacion.cargue.create', compact('sedes'));
    }

    public function store(Request $request)
    {
        // Verificar si es administrador o tiene permiso
        if (!auth()->user()->isAdmin() && !auth()->user()->hasPermission('crear Facturación')) {
            return redirect()->route('facturacion.cargue.index')
                ->with('error', 'No tienes permiso para crear cargues de facturación');
        }

        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv,txt',
            'mes' => 'required|date_format:Y-m',
        ]);

        try {
            DB::beginTransaction();

            // Obtener empresa y sede por defecto del usuario autenticado
            $empresaUsuario = auth()->user()->empresa;
            $sedeUsuario = auth()->user()->sede;

            // Guardar el archivo
            $archivo = $request->file('archivo');
            $path = $archivo->store('cargues_facturacion');
            $nombreOriginal = $archivo->getClientOriginalName();

            // Crear el registro de cargue inicial
            $cargue = CargueFacturacion::create([
                'nombre_archivo' => $nombreOriginal,
                'estado' => 'pendiente',
                'user_id' => auth()->id(),
                'empresa_id' => $empresaUsuario ? $empresaUsuario->id : null,
                'sede_id' => $sedeUsuario ? $sedeUsuario->id : null,
                'tipo_archivo' => $archivo->getClientOriginalExtension(),
                'total_registros' => 0,
                'registros_procesados' => 0,
                'registros_error' => 0,
                'errores' => [],
                'fecha_inicio' => now(),
                'mes' => $request->mes
            ]);

            // Variables para estadísticas
            $totalRegistros = 0;
            $registrosProcesados = 0;
            $registrosError = 0;
            $errores = [];
            $data = [];

            // Procesar archivo Excel
            $spreadsheet = IOFactory::load(Storage::path($path));
            $worksheet = $spreadsheet->getActiveSheet();
            
            // Procesar encabezados
            $columnIndexes = $this->importService->processHeader($worksheet);
            
            // Verificar que se encontraron las columnas necesarias
            $columnasRequeridas = ['factura', 'aseguradora', 'empresa', 'sede', 'departamento'];
            $columnasFaltantes = [];
            
            foreach ($columnasRequeridas as $columna) {
                if (!isset($columnIndexes[$columna])) {
                    $columnasFaltantes[] = $columna;
                }
            }
            
            if (!empty($columnasFaltantes)) {
                throw new \Exception('El archivo no contiene las columnas requeridas: ' . implode(', ', $columnasFaltantes));
            }

            $rows = $worksheet->toArray();
            array_shift($rows); // Omitir la fila de encabezados
            
            foreach ($rows as $index => $row) {
                try {
                    $processedRow = $this->importService->processRow($row, $columnIndexes, $cargue->id);
                    if (!empty($processedRow)) {
                        $processedRow['fila'] = $index + 2;
                        $data[] = $processedRow;
                    }
                } catch (\Exception $e) {
                    $registrosError++;
                    $errores[] = [
                        'tipo' => 'error',
                        'fila' => $index + 2,
                        'mensaje' => $e->getMessage()
                    ];
                    continue;
                }
            }

            $totalRegistros = count($data);

            // Procesar cada factura
            foreach ($data as $row) {
                try {
                    // Validar campos requeridos
                    $camposFaltantes = [];
                    
                    if (empty($row['aseguradora'])) {
                        $camposFaltantes[] = 'Aseguradora';
                    }
                    
                    if (!empty($camposFaltantes)) {
                        $registrosError++;
                        $errores[] = [
                            'tipo' => 'error',
                            'fila' => $row['fila'],
                            'mensaje' => 'Campos obligatorios faltantes: ' . implode(', ', $camposFaltantes)
                        ];
                        continue;
                    }

                    // Crear la factura si todos los campos requeridos están presentes
                    $factura = Factura::create([
                        'cargue_id' => $cargue->id,
                        'empresa_id' => $row['empresa_id'],
                        'sede_id' => $row['sede_id'],
                        'departamento' => $row['departamento'],
                        'tipo' => $row['tipo'] ?? 'CC',
                        'identificacion' => $row['identificacion'],
                        'paciente' => $row['paciente'] ?? null,
                        'tipo_cliente' => $row['tipo_cliente'] ?? null,
                        'documento_aseguradora' => $row['documento_aseguradora'] ?? null,
                        'aseguradora' => $row['aseguradora'],
                        'plan' => $row['plan'] ?? null,
                        'numero_cuenta' => $row['numero_cuenta'] ?? null,
                        'factura' => $row['factura'],
                        'periodo_facturacion_inicio' => $row['periodo_facturacion_inicio'] ?? null,
                        'periodo_facturacion_final' => $row['periodo_facturacion_final'] ?? null,
                        'contabilizacion_documento' => $row['contabilizacion_documento'] ?? null,
                        'interface_documento' => $row['interface_documento'] ?? null,
                        'adquiriente' => $row['adquiriente'] ?? null,
                        'total_documento' => $row['total_documento'] ?? 0,
                        'saldo' => $row['saldo'] ?? 0,
                        'observacion_documento' => $row['observacion_documento'] ?? null,
                        'estado_documento_dian' => $row['estado_documento_dian'] ?? null,
                        'estado_transaccion_dian' => $row['estado_transaccion_dian'] ?? null,
                        'fecha_validacion_dian' => $row['fecha_validacion_dian'] ?? null,
                        'resultado_dian' => $row['resultado_dian'] ?? null,
                        'cufe' => $row['cufe'] ?? null,
                        'fecha_radicacion' => $row['fecha_radicacion'] ?? null,
                        'numero_radicacion' => $row['numero_radicacion'] ?? null,
                        'usuario' => $row['usuario'] ?? auth()->user()->name
                    ]);

                    // Guardar los servicios de la factura
                    if (isset($row['cups_servicio']) && isset($row['cargo_servicio'])) {
                        DB::table('factura_servicios')->insert([
                            'factura_id' => $factura->id,
                            'cups_servicio' => $row['cups_servicio'],
                            'cargo_servicio' => $row['cargo_servicio'],
                            'descripcion_servicio' => $row['descripcion_servicio'] ?? null,
                            'cantidad' => $row['cantidad'] ?? 1,
                            'autorizacion_servicio' => $row['autorizacion_servicio'] ?? null,
                            'valor_unitario_servicio' => $row['valor_unitario_servicio'] ?? 0,
                            'valor_total_servicio' => $row['valor_total_servicio'] ?? 0,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                    }

                    $registrosProcesados++;

                } catch (\Exception $e) {
                    $registrosError++;
                    $errores[] = [
                        'tipo' => 'error',
                        'fila' => $row['fila'],
                        'mensaje' => $e->getMessage()
                    ];
                    continue;
                }
            }

            // Actualizar estadísticas del cargue
            $cargue->update([
                'total_registros' => $totalRegistros,
                'registros_procesados' => $registrosProcesados,
                'registros_error' => $registrosError,
                'errores' => $errores,
                'estado' => 'completado',
                'fecha_fin' => now()
            ]);

            DB::commit();

            return redirect()->route('facturacion.cargue.show', $cargue)
                ->with('success', "Archivo procesado: {$registrosProcesados} registros exitosos, {$registrosError} con errores");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error procesando archivo: ' . $e->getMessage());
            return back()->with('error', 'Error procesando el archivo: ' . $e->getMessage());
        }
    }

    public function show(CargueFacturacion $cargue)
    {
        // Verificar si es administrador o tiene permiso
        if (!auth()->user()->isAdmin() && !auth()->user()->hasPermission('ver Facturación')) {
            return redirect()->route('facturacion.cargue.index')
                ->with('error', 'No tienes permiso para ver este cargue');
        }

        $facturas = $cargue->facturas()
            ->orderBy('factura')
            ->paginate(15);

        return view('facturacion.cargue.show', compact('cargue', 'facturas'));
    }

    private function convertirFecha($fecha)
    {
        // Si ya es un objeto DateTime o Carbon, retornarlo formateado
        if ($fecha instanceof \DateTime || $fecha instanceof \Carbon\Carbon) {
            return $fecha->format('Y-m-d');
        }

        if (empty($fecha)) return null;
        
        // Si es una fecha en formato Excel (número)
        if (is_numeric($fecha)) {
            return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($fecha)
                ->format('Y-m-d');
        }
        
        // Si es string, intentar varios formatos
        if (is_string($fecha)) {
            $fecha = trim($fecha);
            
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
        }
        
        // Si no se pudo convertir, retornar null
        return null;
    }

    public function downloadTemplate()
    {
        $headers = [
            // Datos de la empresa y sede
            'Empresa',
            'Sede',
            'Departamento',
            
            // Datos del paciente
            'Tipo Documento',
            'Identificación',
            'Paciente',
            'Tipo Cliente',
            
            // Datos de la aseguradora
            'Documento Aseguradora',
            'Aseguradora',
            'Plan',
            'Número de Cuenta',
            
            // Datos del servicio
            'CUPS',
            'Cargo',
            'Descripción',
            'Cantidad',
            'Autorización',
            'Valor Unitario',
            'Valor Total',
            
            // Datos de la factura
            'Fecha Documento',
            'Prefijo Documento',
            'Número Documento',
            'Factura',
            'Periodo Facturación Inicio',
            'Periodo Facturación Final',
            'Contabilización Documento',
            'Interface Documento',
            'Adquiriente',
            'Total Documento',
            'Saldo',
            'Observación',
            
            // Datos DIAN
            'Estado Documento DIAN',
            'Estado Transacción DIAN',
            'Fecha Validación DIAN',
            'Resultado DIAN',
            'CUFE',
            
            // Datos de radicación
            'Fecha Radicación',
            'Número Radicación',
            'Usuario'
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Establecer los encabezados
        foreach ($headers as $index => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
        }

        // Dar formato a los encabezados
        $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers));
        $headerRange = 'A1:' . $lastColumn . '1';
        
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'FF6B00'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
        ]);

        // Agregar una fila de ejemplo
        $exampleData = [
            'NEFROLIFE S.A.S', // Empresa
            'MEDELLIN - CONSULTA EXTERNA', // Sede
            'ANTIOQUIA', // Departamento
            'CC', // Tipo Documento
            '32460488', // Identificación
            'JUAN PEREZ', // Paciente
            'CONTRIBUTIVO', // Tipo Cliente
            '800251440', // Documento Aseguradora
            'NUEVA EPS', // Aseguradora
            'PLAN OBLIGATORIO', // Plan
            'CTA-2023-001', // Número de Cuenta
            '890201', // CUPS
            'CONSULTA EXTERNA', // Cargo
            'CONSULTA DE CONTROL', // Descripción
            '1', // Cantidad
            'AUTH-2023-001', // Autorización
            '50000', // Valor Unitario
            '50000', // Valor Total
            '2024-03-01', // Fecha Documento
            'FE', // Prefijo Documento
            '12345', // Número Documento
            'FE12345', // Factura
            '2024-03-01', // Periodo Facturación Inicio
            '2024-03-31', // Periodo Facturación Final
            '2024-03-01', // Contabilización Documento
            'SI', // Interface Documento
            'NUEVA EPS', // Adquiriente
            '50000', // Total Documento
            '0', // Saldo
            'FACTURA PAGADA', // Observación
            'VALIDADA', // Estado Documento DIAN
            'EXITOSA', // Estado Transacción DIAN
            '2024-03-01', // Fecha Validación DIAN
            'ACEPTADA', // Resultado DIAN
            '3565ec7e2fbb95a0a49efc90fddb441849400472', // CUFE
            '2024-03-02', // Fecha Radicación
            'RAD-2024-001', // Número Radicación
            'USUARIO SISTEMA' // Usuario
        ];

        // Agregar la fila de ejemplo
        $row = 2;
        foreach ($exampleData as $index => $value) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . $row, $value);
        }

        // Dar formato a la fila de ejemplo
        $exampleRange = 'A2:' . $lastColumn . '2';
        $sheet->getStyle($exampleRange)->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA'],
            ],
            'font' => [
                'italic' => true,
            ],
        ]);

        // Ajustar el ancho de las columnas
        foreach (range('A', $lastColumn) as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Agregar validaciones
        // Tipo de documento
        $validationTipoDoc = $sheet->getCell('D2')->getDataValidation();
        $validationTipoDoc->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validationTipoDoc->setFormula1('"CC,TI,CE,PA,RC"');
        $validationTipoDoc->setShowDropDown(true);

        // Tipo cliente
        $validationTipoCliente = $sheet->getCell('G2')->getDataValidation();
        $validationTipoCliente->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validationTipoCliente->setFormula1('"CONTRIBUTIVO,SUBSIDIADO,PARTICULAR"');
        $validationTipoCliente->setShowDropDown(true);

        // Interface documento
        $validationInterface = $sheet->getCell('Z2')->getDataValidation();
        $validationInterface->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validationInterface->setFormula1('"SI,NO"');
        $validationInterface->setShowDropDown(true);

        // Estado DIAN
        $validationEstadoDian = $sheet->getCell('AE2')->getDataValidation();
        $validationEstadoDian->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validationEstadoDian->setFormula1('"VALIDADA,PENDIENTE,RECHAZADA"');
        $validationEstadoDian->setShowDropDown(true);

        // Crear el archivo Excel
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $filename = 'plantilla_cargue_facturacion.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    public function destroy(CargueFacturacion $cargue)
    {
        try {
            DB::beginTransaction();

            // Si el cargue está en estado de error y no tiene facturas, eliminar directamente
            if ($cargue->estado === 'error' && $cargue->facturas()->count() === 0) {
                // Eliminar el archivo físico si existe y si tenemos una ruta válida
                $rutaArchivo = $cargue->nombre_archivo;
                if ($rutaArchivo && Storage::exists('cargues_facturacion/' . $rutaArchivo)) {
                    Storage::delete('cargues_facturacion/' . $rutaArchivo);
                }
                
                $cargue->delete();
                DB::commit();
                
                return redirect()->route('facturacion.cargue.index')
                    ->with('success', 'Cargue eliminado correctamente.');
            }

            // Verificar si hay facturas que están en auditorías
            $facturasEnAuditoria = $cargue->facturas()
                ->whereHas('auditFacturas')
                ->count();

            if ($facturasEnAuditoria > 0) {
                return redirect()->route('facturacion.cargue.index')
                    ->with('error', 'No se puede eliminar el cargue porque hay facturas que están en auditorías.');
            }

            // Eliminar todas las facturas asociadas a este cargue que no están en auditorías
            $cargue->facturas()->delete();

            // Eliminar el archivo físico si existe y si tenemos una ruta válida
            $rutaArchivo = $cargue->nombre_archivo;
            if ($rutaArchivo && Storage::exists('cargues_facturacion/' . $rutaArchivo)) {
                Storage::delete('cargues_facturacion/' . $rutaArchivo);
            }

            // Eliminar el registro del cargue
            $cargue->delete();

            DB::commit();

            return redirect()->route('facturacion.cargue.index')
                ->with('success', 'Cargue y sus facturas asociadas eliminados correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al eliminar cargue: ' . $e->getMessage(), [
                'cargue_id' => $cargue->id,
                'nombre_archivo' => $cargue->nombre_archivo
            ]);
            return back()->with('error', 'Error al eliminar el cargue: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene las facturas para una sede y mes específicos
     */
    public function getFacturas(Request $request)
    {
        try {
            $request->validate([
                'sede_id' => 'required|exists:sedes,id',
                'month' => 'required|date_format:Y-m'
            ]);

            $date = Carbon::createFromFormat('Y-m', $request->month);
            
            $facturas = Factura::where('sede_id', $request->sede_id)
                ->whereYear('fecha_documento', $date->year)
                ->whereMonth('fecha_documento', $date->month)
                ->select([
                    'id',
                    'factura',
                    'aseguradora',
                    'fecha_documento as fecha',
                    'saldo as valor',
                    'estado'
                ])
                ->get()
                ->map(function($factura) {
                    return [
                        'id' => $factura->id,
                        'factura' => $factura->factura,
                        'sede' => $factura->sede->sede ?? 'N/A',
                        'aseguradora' => $factura->aseguradora,
                        'fecha' => $factura->fecha ? Carbon::parse($factura->fecha)->format('Y-m-d') : null,
                        'valor' => number_format($factura->valor, 2, '.', ','),
                        'estado' => $factura->estado ?? 'pendiente'
                    ];
                });

            return response()->json([
                'success' => true,
                'facturas' => $facturas
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las facturas: ' . $e->getMessage()
            ], 500);
        }
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

    private function limpiarValorNumerico($valor)
    {
        if (empty($valor)) {
            return 0;
        }

        // Si es numérico, retornarlo directamente
        if (is_numeric($valor)) {
            return floatval($valor);
        }

        // Si es string, limpiar el formato
        if (is_string($valor)) {
            // Remover cualquier caracter que no sea número, punto o coma
            $valor = preg_replace('/[^0-9.,]/', '', $valor);
            
            // Reemplazar coma por punto si hay coma y no hay punto
            if (strpos($valor, ',') !== false && strpos($valor, '.') === false) {
                $valor = str_replace(',', '.', $valor);
            }
            
            // Si hay tanto coma como punto, asumir que la coma es el separador de miles
            if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
                $valor = str_replace(',', '', $valor);
            }
            
            return floatval($valor);
        }

        return 0;
    }
} 