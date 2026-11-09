<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use App\Models\Factura;
use App\Models\AuditFactura;
use App\Models\Hallazgo;
use App\Services\FacturaStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;
use App\Models\Sede;
use App\Traits\HasPermissionChecks;

class AuditoriaController extends Controller
{
    use HasPermissionChecks;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $this->checkPermissionAndRespond('Ver Auditorías');

        $audits = Audit::with(['sede', 'empresa', 'createdBy'])
                     ->select('audits.*')
                     ->orderBy('created_at', 'desc')
                     ->paginate(10);

        $sedes = Sede::orderBy('sede')->get();

        $monthNames = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];

        return view('auditoria.index', compact('audits', 'sedes', 'monthNames'));
    }

    public function create()
    {
        $this->checkPermissionAndRespond('Crear Auditorías');

        $sedes = Sede::orderBy('sede')->get();
        return view('auditoria.create', compact('sedes'));
    }

    public function store(Request $request)
    {
        $this->checkPermissionAndRespond('Crear Auditorías');

        try {
            // Validar la solicitud
            $validated = $request->validate([
                'sede_id' => 'required|exists:sedes,id',
                'month' => 'required|date_format:Y-m',
                'link' => 'required|string',
                'facturas' => 'required|array',
                'facturas.*' => 'required|exists:facturas,id'
            ]);

            DB::beginTransaction();

            // Extraer año y mes del input month
            $date = Carbon::createFromFormat('Y-m', $validated['month']);
            
            // Obtener la sede y su empresa
            $sede = Sede::findOrFail($validated['sede_id']);
            
            // Crear la auditoría
            $audit = new Audit();
            $audit->sede_id = $validated['sede_id'];
            $audit->empresa_id = $sede->empresa_id;
            $audit->year = $date->year;
            $audit->month = $date->month;
            $audit->mes = $validated['month']; // Formato YYYY-MM
            $audit->link = $validated['link'];
            $audit->user_id = auth()->id();
            $audit->created_by = auth()->id();
            $audit->status = 'pendiente';
            $audit->completed = false;
            $audit->save();

            // Asociar las facturas seleccionadas con la auditoría
            $audit->facturas()->attach($validated['facturas']);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Auditoría creada exitosamente',
                'redirect' => route('auditoria.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la auditoría: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Audit $audit)
    {
        $this->checkPermissionAndRespond('Ver Auditorías');

        $audit->load(['sede', 'empresa', 'createdBy', 'facturas']);
        return view('auditoria.show', compact('audit'));
    }

    public function edit(Audit $audit)
    {
        $this->checkPermissionAndRespond('Editar Auditorías');

        // ... resto del código del método edit
    }

    public function update(Request $request, Audit $audit)
    {
        $this->checkPermissionAndRespond('Editar Auditorías');

        // ... resto del código del método update
    }

    public function destroy(Audit $audit)
    {
        $this->checkPermissionAndRespond('Eliminar Auditorías');

        try {
            // Verificar que el usuario sea el creador de la auditoría
            if ($audit->created_by !== auth()->id()) {
                return back()->with('error', 'Solo el creador de la auditoría puede eliminarla');
            }

            DB::beginTransaction();

            // Verificar si hay hallazgos
            if ($audit->hallazgos()->exists()) {
                return back()->with('error', 'No se puede eliminar la auditoría porque tiene hallazgos registrados');
            }

            // Eliminar las relaciones con facturas
            $audit->facturas()->detach();

            // Eliminar la auditoría
            $audit->delete();

            DB::commit();

            return redirect()->route('auditoria.index')
                ->with('success', 'Auditoría eliminada correctamente');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al eliminar la auditoría: ' . $e->getMessage());
        }
    }

    /**
     * Obtiene los datos de una factura específica para el modo pantalla completa
     */
    public function getFacturaData(Request $request, Audit $audit, $facturaId)
    {
        try {
            \Log::info('Buscando factura', [
                'audit_id' => $audit->id,
                'factura_id' => $facturaId
            ]);

            // Cargar la factura con sus relaciones
            $factura = $audit->facturas()
                ->with(['auditFacturas' => function($query) use ($audit) {
                    $query->where('audit_id', $audit->id)
                        ->with('hallazgos');
                }])
                ->find($facturaId);

            if (!$factura) {
                \Log::warning('Factura no encontrada', ['factura_id' => $facturaId]);
                return response()->json([
                    'success' => false,
                    'message' => 'La factura no existe'
                ], 404);
            }

            // Obtener la relación audit_factura específica
            $auditFactura = $factura->auditFacturas->first();
            if (!$auditFactura) {
                \Log::warning('Relación audit_factura no encontrada', [
                    'factura_id' => $facturaId,
                    'audit_id' => $audit->id
                ]);
                return response()->json([
                    'success' => false,
                    'message' => 'La factura no está asociada a esta auditoría'
                ], 404);
            }

            // Obtener los archivos PDF usando el nuevo servicio
            $pdfFiles = $factura->getPdfFiles($audit);
            
            // Obtener los hallazgos
            $hallazgos = $auditFactura->hallazgos ?? collect();

            $response = [
                'success' => true,
                'factura' => [
                    'id' => $factura->id,
                    'factura' => $factura->factura,
                    'aseguradora' => $factura->aseguradora,
                    'saldo' => $factura->saldo,
                    'auditFacturas' => [
                        [
                            'id' => $auditFactura->id,
                            'hallazgos' => $hallazgos->toArray()
                        ]
                    ]
                ],
                'pdfFiles' => $pdfFiles,
                'currentPdf' => count($pdfFiles) > 0 ? $pdfFiles[0]['url'] : null,
                'hallazgos' => $hallazgos
            ];

            \Log::info('Datos de factura preparados', [
                'factura_id' => $factura->id,
                'total_pdfs' => count($pdfFiles),
                'total_hallazgos' => $hallazgos->count()
            ]);

            return response()->json($response);

        } catch (\Exception $e) {
            \Log::error('Error al obtener datos de la factura', [
                'audit_id' => $audit->id,
                'factura_id' => $facturaId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los datos de la factura: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Muestra la vista de auditoría en curso
     */
    public function start(Audit $audit, Request $request)
    {
        try {
            // Verificar que el usuario sea el creador de la auditoría
            if ($audit->created_by !== auth()->id()) {
                return back()->with('error', 'Solo el creador de la auditoría puede realizarla.');
            }

            // Si la auditoría no tiene started_at, establecerlo ahora y cambiar el estado
            if (!$audit->started_at) {
                $audit->started_at = now();
                $audit->status = 'en_progreso';
                $audit->save();
            }

            // Si la auditoría está pausada, actualizar el estado
            if ($audit->status === 'pendiente') {
                $audit->status = 'en_progreso';
                $audit->save();
            }

            $currentFacturaId = $request->query('factura_id');
            $currentPdfIndex = (int) $request->query('pdf_index', 0);
            
            // Cargar la relación con la sede
            $audit->load('sede');

            // Obtener el nombre limpio de la sede
            $sedeName = '';
            if ($audit->sede) {
                $sedeData = is_string($audit->sede->sede) ? json_decode($audit->sede->sede, true) : null;
                $sedeName = $sedeData ? $sedeData['sede'] : ($audit->sede->sede ?? 'N/A');
            }

            // Obtener todas las facturas de la base de datos para esta auditoría
            $facturas = $audit->facturas()
                ->with(['auditFacturas' => function($query) use ($audit) {
                    $query->where('audit_id', $audit->id)
                        ->with('hallazgos');
                }])
                ->get();

            \Log::info('Facturas en base de datos', [
                'total_facturas' => $facturas->count(),
                'facturas_numeros' => $facturas->pluck('factura')->toArray()
            ]);

            // Obtener los PDFs para cada factura usando el nuevo servicio
            foreach ($facturas as $factura) {
                \Log::info('Buscando PDFs para factura', [
                    'factura_numero' => $factura->factura,
                    'aseguradora' => $factura->aseguradora
                ]);
                
                $factura->pdfFiles = $factura->getPdfFiles($audit);
                
                \Log::info('PDFs encontrados', [
                    'factura_numero' => $factura->factura,
                    'total_pdfs' => count($factura->pdfFiles)
                ]);
            }

            // Determinar la factura actual
            $currentFactura = null;
            $currentPdfIndex = 0;

            if ($currentFacturaId) {
                $currentFactura = $facturas->firstWhere('id', $currentFacturaId);
                \Log::info('Factura actual seleccionada por ID', [
                    'factura_id' => $currentFacturaId,
                    'encontrada' => !is_null($currentFactura)
                ]);
            }

            if (!$currentFactura && $facturas->isNotEmpty()) {
                $currentFactura = $facturas->first();
                \Log::info('Usando primera factura disponible', [
                    'factura_numero' => $currentFactura->factura
                ]);
            }

            if ($currentPdfIndex < 0 || $currentPdfIndex >= count($currentFactura->pdfFiles)) {
                $currentPdfIndex = 0;
            }

            return view('auditoria.audit', [
                'audit' => $audit,
                'facturas' => $facturas,
                'currentFactura' => $currentFactura,
                'currentPdfIndex' => $currentPdfIndex,
                'auditId' => $audit->id,
                'auditMonth' => $this->getMonthName($audit->month),
                'auditYear' => $audit->year,
                'auditLink' => $audit->link,
                'sedeName' => $sedeName
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en start: ' . $e->getMessage());
            return back()->with('error', 'Error al iniciar la auditoría: ' . $e->getMessage());
        }
    }

    private function getMonthName($monthNumber)
    {
        $months = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre'
        ];
        return $months[$monthNumber] ?? '';
    }

    /**
     * Obtiene los archivos PDF de una factura desde una URL externa
     */
    private function getPdfFiles($audit, $factura)
    {
        try {
            if (!$audit || !$factura) {
                \Log::warning('Parámetros inválidos en getPdfFiles', [
                    'audit' => $audit ? $audit->id : null,
                    'factura' => $factura ? $factura->id : null
                ]);
                return [];
            }

            // Construir la URL base usando el link de la auditoría
            $baseUrl = rtrim($audit->link, '/');
            
            \Log::info('Iniciando búsqueda de PDFs para factura', [
                'base_url' => $baseUrl,
                'factura_numero' => $factura->factura
            ]);

            // Obtener el contenido del directorio raíz
            $pdfFiles = $this->searchPdfsInDirectory($baseUrl, $factura->factura);

            \Log::info('PDFs encontrados', [
                'factura' => $factura->factura,
                'total_pdfs' => count($pdfFiles),
                'pdfs' => $pdfFiles
            ]);

            return $pdfFiles;

        } catch (\Exception $e) {
            \Log::error('Error al obtener PDFs', [
                'factura' => $factura->factura,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Busca PDFs recursivamente en un directorio y sus subdirectorios
     */
    private function searchPdfsInDirectory($url, $facturaNumero, $depth = 0)
    {
        // Limitar la profundidad de búsqueda para evitar bucles infinitos
        if ($depth > 10) {
            return [
                'error' => 'Máxima profundidad alcanzada',
                'url' => $url,
                'depth' => $depth
            ];
        }

        try {
            // Configurar cURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($response, 0, $headerSize);
            $html = substr($response, $headerSize);
            
            if (curl_errno($ch)) {
                return [
                    'error' => 'Error cURL',
                    'url' => $url,
                    'curl_error' => curl_error($ch),
                    'http_code' => $httpCode
                ];
            }
            
            curl_close($ch);

            if ($httpCode !== 200) {
                return [
                    'error' => 'Error HTTP',
                    'url' => $url,
                    'http_code' => $httpCode,
                    'headers' => $headers
                ];
            }

            // Buscar enlaces a directorios y archivos
            $pattern = '/<a[^>]*href=["\']([^"\']+)["\'][^>]*>/i';
            preg_match_all($pattern, $html, $matches);

            $debug_info = [
                'url' => $url,
                'total_enlaces' => count($matches[1] ?? []),
                'html_length' => strlen($html),
                'http_code' => $httpCode,
                'headers' => $headers,
                'enlaces_encontrados' => $matches[1] ?? []
            ];

            $pdfFiles = [];

            if (!empty($matches[1])) {
                foreach ($matches[1] as $path) {
                    // Ignorar enlaces relativos especiales
                    if ($path === './' || $path === '../' || $path === '/') {
                        continue;
                    }

                    // Construir URL completa
                    $fullPath = $this->buildFullUrl($url, $path);
                    
                    // Si es un PDF y contiene el número de factura
                    if (stripos($path, '.pdf') !== false && stripos($path, $facturaNumero) !== false) {
                        // Verificar si el archivo existe
                        if ($this->verifyFileExists($fullPath)) {
                            $pdfFiles[] = [
                                'name' => basename($path),
                                'url' => $fullPath,
                                'type' => $this->getPdfType(basename($path))
                            ];
                        }
                    }
                    // Si es un directorio, buscar recursivamente
                    elseif (substr($path, -1) === '/' || !pathinfo($path, PATHINFO_EXTENSION)) {
                        $subPdfs = $this->searchPdfsInDirectory($fullPath, $facturaNumero, $depth + 1);
                        if (is_array($subPdfs) && !isset($subPdfs['error'])) {
                            $pdfFiles = array_merge($pdfFiles, $subPdfs);
                        } else {
                            $debug_info['subdirectory_error'][$fullPath] = $subPdfs;
                        }
                    }
                }
            }

            if (empty($pdfFiles)) {
                return array_merge(['error' => 'No se encontraron PDFs'], $debug_info);
            }

            return $pdfFiles;

        } catch (\Exception $e) {
            return [
                'error' => 'Excepción',
                'url' => $url,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ];
        }
    }

    /**
     * Verifica si un archivo existe y es accesible
     */
    private function verifyFileExists($url)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $result !== false && $httpCode === 200;
    }

    /**
     * Construye una URL completa a partir de una URL base y una ruta relativa
     */
    private function buildFullUrl($baseUrl, $path)
    {
        // Si la ruta ya es una URL completa
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }
        
        // Si la ruta comienza con /
        if (strpos($path, '/') === 0) {
            $urlParts = parse_url($baseUrl);
            return $urlParts['scheme'] . '://' . $urlParts['host'] . $path;
        }
        
        // Remover el nombre del archivo de la URL base si existe
        $baseUrl = preg_replace('/\/[^\/]*$/', '/', $baseUrl);
        
        // Unir la URL base con la ruta relativa
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }

    /**
     * Determina el tipo de PDF basado en el nombre del archivo
     */
    private function getPdfType($filename)
    {
        $types = [
            'factura' => ['factura', 'fac', 'fv', 'fe'],
            'soporte' => ['soporte', 'sop', 'sp'],
            'autorizacion' => ['autorizacion', 'aut', 'auth'],
            'historia' => ['historia', 'hc', 'hist']
        ];

        $filename = strtolower($filename);
        foreach ($types as $type => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($filename, $keyword)) {
                    return ucfirst($type);
                }
            }
        }

        return 'Otro';
    }

    /**
     * Verifica si una factura ya ha sido auditada
     */
    private function isFacturaAuditada($auditFactura)
    {
        if (!$auditFactura) return false;
        return $auditFactura->hallazgos()->count() > 0;
    }

    /**
     * Verifica si todas las facturas tienen observaciones
     */
    public function verificarObservaciones(Audit $audit)
    {
        try {
            $facturasSinObservaciones = [];
            $totalFacturas = 0;
            $facturasAuditadas = 0;
            
            // Obtener todas las facturas con sus relaciones
            $facturas = $audit->facturas()
                ->with(['auditFacturas' => function($query) use ($audit) {
                    $query->where('audit_id', $audit->id)
                        ->with('hallazgos');
                }])
                ->get();
            
            foreach ($facturas as $factura) {
                $totalFacturas++;
                $auditFactura = $factura->auditFacturas->first();
                
                if (!$this->isFacturaAuditada($auditFactura)) {
                    $facturasSinObservaciones[] = $factura->factura;
                } else {
                    $facturasAuditadas++;
                }
            }
            
            return response()->json([
                'success' => true,
                'todasTienenObservaciones' => count($facturasSinObservaciones) === 0,
                'facturasSinObservaciones' => count($facturasSinObservaciones),
                'facturasPendientes' => $facturasSinObservaciones,
                'progreso' => [
                    'total' => $totalFacturas,
                    'auditadas' => $facturasAuditadas,
                    'porcentaje' => $totalFacturas > 0 ? round(($facturasAuditadas / $totalFacturas) * 100) : 0
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al verificar observaciones', [
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al verificar las observaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Guarda una nueva observación para una factura
     */
    public function saveObservation(Request $request, Audit $audit, $facturaId)
    {
        \Log::info('Iniciando guardado de observación', [
            'audit_id' => $audit->id,
            'factura_id' => $facturaId,
            'request_data' => $request->all()
        ]);

        try {
            // Validar la solicitud
            $validated = $request->validate([
                'observacion' => 'required|string|max:1000',
                'current_pdf' => 'nullable|string|max:255',
                'is_ok' => 'required|boolean'
            ]);

            // Convertir explícitamente is_ok a booleano
            $isOk = filter_var($validated['is_ok'], FILTER_VALIDATE_BOOLEAN);

            // Verificar que la factura existe y pertenece a la auditoría
            $auditFactura = AuditFactura::where('audit_id', $audit->id)
                ->where('factura_id', $facturaId)
                ->first();

            if (!$auditFactura) {
                return response()->json([
                    'success' => false,
                    'message' => 'La factura no está asociada a esta auditoría'
                ], 404);
            }

            // Crear el hallazgo
            $hallazgo = $auditFactura->hallazgos()->create([
                'observacion' => $validated['observacion'],
                'documento_referencia' => $validated['current_pdf'],
                'is_ok' => $isOk
            ]);

            return response()->json([
                'success' => true,
                'message' => $isOk ? 'Factura marcada como correcta' : 'Observación guardada exitosamente',
                'hallazgo' => $hallazgo,
                'is_ok' => $isOk
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al guardar observación', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar la observación: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Termina la auditoría
     */
    public function terminarAuditoria(Audit $audit)
    {
        try {
            // Verificar que el usuario sea el creador de la auditoría
            if ($audit->created_by !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo el creador de la auditoría puede terminarla'
                ], 403);
            }

            DB::beginTransaction();

            // Guardar el tiempo final
            if (request()->has('tiempo_acumulado')) {
                $audit->tiempo_acumulado = request()->tiempo_acumulado;
            }

            $audit->completed = true;
            $audit->completed_at = now();
            $audit->status = 'completada';
            $audit->save();

            // Registrar en el log para debugging
            \Log::info('Auditoría completada', [
                'audit_id' => $audit->id,
                'completed' => $audit->completed,
                'status' => $audit->status
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Auditoría completada exitosamente',
                'redirect' => route('auditoria.index')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al terminar auditoría: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al terminar la auditoría: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Genera el reporte de hallazgos de la auditoría
     */
    public function generarReporte(Audit $audit)
    {
        try {
            // Obtener todos los hallazgos de la auditoría con sus relaciones
            $hallazgos = DB::table('hallazgos')
                ->join('audit_facturas', 'hallazgos.audit_factura_id', '=', 'audit_facturas.id')
                ->join('facturas', 'audit_facturas.factura_id', '=', 'facturas.id')
                ->join('audits', 'audit_facturas.audit_id', '=', 'audits.id')
                ->where('hallazgos.audit_id', $audit->id)
                ->select([
                    'audits.sede',
                    'facturas.factura',
                    DB::raw("CONCAT(audits.month, ' ', audits.year) as periodo"),
                    'facturas.aseguradora',
                    'facturas.saldo as valor',
                    'hallazgos.observacion as hallazgo',
                    'hallazgos.is_ok',
                    'hallazgos.created_at'
                ])
                ->orderBy('facturas.aseguradora')
                ->orderBy('facturas.factura')
                ->get();

            if ($hallazgos->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No hay hallazgos para generar el reporte'
                ], 404);
            }

            // Generar el reporte en Excel
            $excel = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $excel->getActiveSheet();

            // Establecer encabezados
            $sheet->setCellValue('A1', 'SEDE');
            $sheet->setCellValue('B1', 'FACTURA');
            $sheet->setCellValue('C1', 'PERIODO');
            $sheet->setCellValue('D1', 'ASEGURADORA');
            $sheet->setCellValue('E1', 'VALOR');
            $sheet->setCellValue('F1', 'HALLAZGO');
            $sheet->setCellValue('G1', 'ESTADO');

            // Estilo para encabezados
            $headerStyle = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E67E22']
                ],
                'alignment' => [
                    'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                ]
            ];
            $sheet->getStyle('A1:G1')->applyFromArray($headerStyle);

            // Llenar datos
            $row = 2;
            foreach ($hallazgos as $hallazgo) {
                $sheet->setCellValue('A'.$row, $hallazgo->sede);
                $sheet->setCellValue('B'.$row, $hallazgo->factura);
                $sheet->setCellValue('C'.$row, $hallazgo->periodo);
                $sheet->setCellValue('D'.$row, $hallazgo->aseguradora);
                $sheet->setCellValue('E'.$row, number_format($hallazgo->valor, 2));
                $sheet->setCellValue('F'.$row, $hallazgo->hallazgo);
                $sheet->setCellValue('G'.$row, $hallazgo->is_ok ? 'CORRECTO' : 'OBSERVACIÓN');
                $row++;
            }

            // Autoajustar columnas
            foreach (range('A', 'G') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Crear el archivo Excel
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($excel);
            $filename = 'Reporte_Auditoria_' . $audit->sede . '_' . $audit->month . '_' . $audit->year . '.xlsx';
            $path = storage_path('app/public/reportes/' . $filename);

            // Asegurar que el directorio existe
            if (!file_exists(storage_path('app/public/reportes'))) {
                mkdir(storage_path('app/public/reportes'), 0755, true);
            }

            $writer->save($path);

            return response()->download($path, $filename)->deleteFileAfterSend();

        } catch (\Exception $e) {
            \Log::error('Error al generar reporte', [
                'audit_id' => $audit->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al generar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descarga el formato de Excel para cargar facturas
     */
    public function descargarFormato()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        
        // Establecer los encabezados
        $headers = [
            'A1' => 'FACTURA',
            'B1' => 'ASEGURADORA',
            'C1' => 'VALOR',
            'D1' => 'FECHA',
            'E1' => 'PERIODO',
            'F1' => 'SEDE'
        ];
        
        // Aplicar los encabezados
        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }
        
        // Dar formato a los encabezados
        $sheet->getStyle('A1:F1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '718096']
            ]
        ]);
        
        // Ajustar el ancho de las columnas
        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
        
        // Agregar filtros
        $sheet->setAutoFilter('A1:F1');
        
        // Crear el archivo Excel
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Configurar headers para la descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="formato_carga_facturas.xlsx"');
        header('Cache-Control: max-age=0');
        
        // Guardar el archivo
        $writer->save('php://output');
        exit;
    }

    public function pausarAuditoria(Audit $audit)
    {
        try {
            // Verificar que el usuario sea el creador de la auditoría
            if ($audit->created_by !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solo el creador de la auditoría puede pausarla'
                ], 403);
            }

            // Actualizar el tiempo acumulado si se proporciona
            if (request()->has('tiempo_acumulado')) {
                $audit->tiempo_acumulado = request()->tiempo_acumulado;
            }

            // Cambiar el estado a pausado
            $audit->status = 'en_progreso';
            $audit->save();

            return response()->json([
                'success' => true,
                'message' => 'Auditoría pausada correctamente',
                'redirect' => route('auditoria.index')
            ]);
        } catch (\Exception $e) {
            \Log::error('Error al pausar auditoría: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al pausar la auditoría: ' . $e->getMessage()
            ], 500);
        }
    }

    public function generarReporteHallazgos(Audit $audit)
    {
        try {
            // Cargar la auditoría con sus relaciones
            $audit->load(['sede', 'facturas.auditFacturas.hallazgos']);
            
            // Crear un nuevo archivo Excel
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Establecer el título
            $sheet->setCellValue('A1', 'REPORTE DE HALLAZGOS - ' . $audit->sede->sede);
            $sheet->setCellValue('A2', 'Período: ' . $audit->month_name . ' ' . $audit->year);
            
            // Establecer los encabezados
            $sheet->setCellValue('A4', 'Factura');
            $sheet->setCellValue('B4', 'Aseguradora');
            $sheet->setCellValue('C4', 'Documento Referencia');
            $sheet->setCellValue('D4', 'Hallazgo');
            $sheet->setCellValue('E4', 'Fecha');
            
            // Iniciar desde la fila 5
            $row = 5;
            
            foreach ($audit->facturas as $factura) {
                foreach ($factura->auditFacturas as $auditFactura) {
                    foreach ($auditFactura->hallazgos as $hallazgo) {
                        $sheet->setCellValue('A' . $row, $factura->factura);
                        $sheet->setCellValue('B' . $row, $factura->aseguradora);
                        $sheet->setCellValue('C' . $row, $hallazgo->documento_referencia);
                        $sheet->setCellValue('D' . $row, $hallazgo->observacion);
                        $sheet->setCellValue('E' . $row, $hallazgo->created_at->format('d/m/Y H:i'));
                        $row++;
                    }
                }
            }
            
            // Aplicar estilos
            $sheet->getStyle('A1:E1')->getFont()->setBold(true)->setSize(14);
            $sheet->getStyle('A2:E2')->getFont()->setSize(12);
            $sheet->getStyle('A4:E4')->getFont()->setBold(true);
            $sheet->getStyle('A4:E' . ($row-1))->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN);
            
            // Ajustar el ancho de las columnas
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(20);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(50);
            $sheet->getColumnDimension('E')->setWidth(20);
            
            // Crear el archivo Excel
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $filename = 'Hallazgos_' . $audit->sede->sede . '_' . $audit->month_name . $audit->year . '.xlsx';
            $tempPath = storage_path('app/public/temp/' . $filename);
            
            // Asegurarse de que el directorio existe
            if (!file_exists(storage_path('app/public/temp'))) {
                mkdir(storage_path('app/public/temp'), 0755, true);
            }
            
            $writer->save($tempPath);
            
            return response()->download($tempPath, $filename)->deleteFileAfterSend(true);
            
        } catch (\Exception $e) {
            \Log::error('Error al generar reporte de hallazgos: ' . $e->getMessage());
            return back()->with('error', 'Error al generar el reporte de hallazgos: ' . $e->getMessage());
        }
    }

    public function getFacturas(Request $request)
    {
        $this->checkPermissionAndRespond('Crear Auditorías');

        try {
            $validated = $request->validate([
                'sede_id' => 'required|exists:sedes,id',
                'month' => 'required|date_format:Y-m'
            ]);

            // Crear el rango de fechas para el mes seleccionado
            $date = Carbon::createFromFormat('Y-m', $validated['month']);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            // Obtener facturas que no están en ninguna auditoría para la sede y mes seleccionados
            $facturas = Factura::where('sede_id', $validated['sede_id'])
                ->whereBetween('periodo_facturacion_inicio', [$startOfMonth, $endOfMonth])
                ->whereDoesntHave('audits')
                ->with('sede')
                ->get();

            if ($facturas->isEmpty()) {
                return response()->json([
                    'message' => 'No hay facturas disponibles para auditar en el período seleccionado.',
                    'facturas' => []
                ]);
            }

            return response()->json([
                'message' => 'Facturas encontradas exitosamente',
                'facturas' => $facturas
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener facturas: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener las facturas',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 