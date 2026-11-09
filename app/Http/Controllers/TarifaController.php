<?php

namespace App\Http\Controllers;

use App\Models\Tarifa;
use App\Models\TarifaHistorial;
use App\Imports\TarifasImport;
use App\Imports\TarifasUpdateImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Models\Empresa;
use App\Models\Sede;
use App\Services\ImportProgressService;
use App\Traits\HasPermissionChecks;
use Illuminate\Support\Facades\Config;

class TarifaController extends Controller
{
    use HasPermissionChecks;

    public function index()
    {
        $this->checkPermissionAndRespond(Config::get('permissions.tarifas.ver'));

        try {
            $query = Tarifa::query()
                ->with(['empresa', 'sede'])
                ->withoutGlobalScopes();

            // Filtros de búsqueda
            if (request()->filled('empresa')) {
                $empresaSearch = request('empresa');
                $query->whereHas('empresa', function($q) use ($empresaSearch) {
                    $q->where('razon_social', 'like', '%' . $empresaSearch . '%');
                });
            }

            if (request()->filled('sede')) {
                $sedeSearch = request('sede');
                $query->whereHas('sede', function($q) use ($sedeSearch) {
                    $q->where('sede', 'like', '%' . $sedeSearch . '%');
                });
            }

            if (request()->filled('tercero')) {
                $query->where('tercero', 'like', '%' . request('tercero') . '%');
            }

            if (request()->filled('regimen')) {
                $query->where('regimen', request('regimen'));
            }

            if (request()->filled('cups')) {
                $query->where('cups', 'like', '%' . request('cups') . '%');
            }

            // Ordenar por ID descendente
            $query->orderBy('id', 'desc');

            // Obtener el total de registros antes de la paginación
            $totalRecords = $query->count();

            // Paginación
            $perPage = request('per_page', 10);
            if ($perPage === 'all') {
                $tarifas = $query->get();
                $initialTarifas = new \Illuminate\Pagination\LengthAwarePaginator(
                    $tarifas,
                    $totalRecords,
                    $totalRecords,
                    1
                );
            } else {
                $initialTarifas = $query->paginate($perPage);
            }

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $initialTarifas->items(),
                    'pagination' => [
                        'total' => $initialTarifas->total(),
                        'per_page' => $initialTarifas->perPage(),
                        'current_page' => $initialTarifas->currentPage(),
                        'last_page' => $initialTarifas->lastPage(),
                        'from' => $initialTarifas->firstItem(),
                        'to' => $initialTarifas->lastItem()
                    ]
                ]);
            }

            return view('tarifas.index', compact('initialTarifas', 'totalRecords'));
        } catch (\Exception $e) {
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al cargar las tarifas: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'Error al cargar las tarifas: ' . $e->getMessage());
        }
    }

    public function show(Tarifa $tarifa)
    {
        return view('tarifas.show', compact('tarifa'));
    }

    public function create()
    {
        $this->checkPermissionAndRespond(Config::get('permissions.tarifas.crear'));

        $empresas = Empresa::orderBy('razon_social')->get();
        $sedes = Sede::orderBy('sede')->get();
        return view('tarifas.create', compact('empresas', 'sedes'));
    }

    public function store(Request $request)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.tarifas.crear'));

        $request->validate([
            'empresa' => 'required|string',
            'sede' => 'required|string',
            'regimen' => 'required|string',
            'tercero' => 'required|string',
            'descripcion_servicio' => 'required|string',
            'cups' => 'required|string',
            'cups_ips_facturados' => 'nullable|string',
            'tarifas_de_manual' => 'nullable|numeric',
            'valor' => 'required|numeric'
        ]);

        DB::beginTransaction();
        try {
            // Buscar o crear empresa
            $empresa = Empresa::firstOrCreate(['razon_social' => $request->empresa]);
            
            // Buscar o crear sede
            $sede = Sede::firstOrCreate([
                'sede' => $request->sede,
                'empresa_id' => $empresa->id
            ]);

            // Crear tarifa
            $tarifa = Tarifa::create([
                'empresa_id' => $empresa->id,
                'sede_id' => $sede->id,
                'regimen' => $request->regimen,
                'tercero' => $request->tercero,
                'descripcion_del_servicio' => $request->descripcion_servicio,
                'cups' => $request->cups,
                'cups_facturados' => $request->cups_ips_facturados,
                'tarifas_de_manual' => $request->tarifas_de_manual,
                'valor' => $request->valor,
                'year' => date('Y'),
                'activo' => true
            ]);

            TarifaHistorial::create([
                'tarifa_id' => $tarifa->id,
                'valor_anterior' => 0,
                'valor_nuevo' => $request->valor,
                'user_id' => Auth::id()
            ]);

            DB::commit();
            return redirect()->route('tarifas.index')->with('success', 'Tarifa creada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al crear la tarifa: ' . $e->getMessage());
        }
    }

    public function edit(Tarifa $tarifa)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.tarifas.editar'));

        $empresas = Empresa::orderBy('razon_social')->get();
        $sedes = Sede::orderBy('sede')->get();
        return view('tarifas.edit', compact('tarifa', 'empresas', 'sedes'));
    }

    public function update(Request $request, Tarifa $tarifa)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.tarifas.editar'));

        $request->validate([
            'empresa' => 'required|string',
            'sede' => 'required|string',
            'regimen' => 'required|string',
            'tercero' => 'required|string',
            'descripcion_servicio' => 'required|string',
            'cups' => 'required|string',
            'cups_ips_facturados' => 'nullable|string',
            'tarifas_de_manual' => 'nullable|numeric',
            'valor' => 'required|numeric'
        ]);

        DB::beginTransaction();
        try {
            $valorAnterior = $tarifa->valor;

            // Buscar o crear empresa
            $empresa = Empresa::firstOrCreate(['razon_social' => $request->empresa]);
            
            // Buscar o crear sede
            $sede = Sede::firstOrCreate([
                'sede' => $request->sede,
                'empresa_id' => $empresa->id
            ]);

            // Actualizar tarifa
            $tarifa->update([
                'empresa_id' => $empresa->id,
                'sede_id' => $sede->id,
                'regimen' => $request->regimen,
                'tercero' => $request->tercero,
                'descripcion_del_servicio' => $request->descripcion_servicio,
                'cups' => $request->cups,
                'cups_facturados' => $request->cups_ips_facturados,
                'tarifas_de_manual' => $request->tarifas_de_manual,
                'valor' => $request->valor
            ]);

            if ($valorAnterior != $request->valor) {
                TarifaHistorial::create([
                    'tarifa_id' => $tarifa->id,
                    'valor_anterior' => $valorAnterior,
                    'valor_nuevo' => $request->valor,
                    'user_id' => Auth::id()
                ]);
            }

            DB::commit();
            return redirect()->route('tarifas.index')->with('success', 'Tarifa actualizada exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar la tarifa: ' . $e->getMessage());
        }
    }

    public function destroy(Tarifa $tarifa)
    {
        $this->checkPermissionAndRespond(Config::get('permissions.tarifas.eliminar'));

        try {
            $tarifa->delete();
            return response()->json([
                'success' => true,
                'message' => 'Tarifa eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la tarifa: ' . $e->getMessage()
            ], 500);
        }
    }

    public function template()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Establecer encabezados
        $headers = [
            'Empresa',
            'Sede',
            'Régimen',
            'Tercero',
            'Descripción del Servicio',
            'CUPS',
            'CUPS IPS/Facturados',
            'Tarifas de Manual',
            'Valor'
        ];

        foreach ($headers as $index => $header) {
            $column = chr(65 + $index);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
        }

        // Ejemplo de datos
        $sheet->setCellValue('A2', 'EJEMPLO S.A.');
        $sheet->setCellValue('B2', 'SEDE PRINCIPAL');
        $sheet->setCellValue('C2', 'Contributivo');
        $sheet->setCellValue('D2', 'EPS EJEMPLO');
        $sheet->setCellValue('E2', 'CONSULTA MEDICINA GENERAL');
        $sheet->setCellValue('F2', '890201');
        $sheet->setCellValue('G2', '890201');
        $sheet->setCellValue('H2', '30000');
        $sheet->setCellValue('I2', '35000');

        // Ajustar ancho de columnas
        foreach(range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Crear el archivo
        $writer = new Xlsx($spreadsheet);
        
        // Configurar headers para la descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="plantilla_tarifas.xlsx"');
        header('Cache-Control: max-age=0');

        // Guardar el archivo al output
        $writer->save('php://output');
        exit;
    }

    public function import(Request $request)
    {
        if (!$this->hasPermission('crear_tarifas')) {
            abort(403, 'No tienes permiso para importar tarifas.');
        }

        try {
            $request->validate([
                'file' => 'required|file|mimes:xlsx,xls',
                'inicio_vigencia' => 'required|date',
                'fin_vigencia' => 'required|date|after:inicio_vigencia'
            ]);

            if (!$request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La solicitud debe ser AJAX'
                ], 400);
            }

            $file = $request->file('file');
            $inicio_vigencia = $request->input('inicio_vigencia');
            $fin_vigencia = $request->input('fin_vigencia');

            if (!$file || !$inicio_vigencia || !$fin_vigencia) {
                return response()->json([
                    'success' => false,
                    'message' => 'Todos los campos son requeridos.'
                ], 422);
            }

            // Configurar el importador
            $import = new TarifasImport($inicio_vigencia, $fin_vigencia);
            
            try {
                // Importar
                Excel::import($import, $file);

                return response()->json([
                    'success' => true,
                    'message' => 'Importación completada exitosamente',
                    'importId' => $import->getImportId()
                ]);
            } catch (\Exception $importError) {
                // Obtener el reporte si está disponible
                $reportFile = null;
                if (method_exists($import, 'getReportFile')) {
                    $reportFile = $import->getReportFile();
                }

                // Si hay un reporte, devolver la información
                if ($reportFile) {
                    return response()->json([
                        'success' => false,
                        'message' => $importError->getMessage(),
                        'report_file' => $reportFile
                    ], 422);
                }

                // Si no hay reporte, relanzar la excepción
                throw $importError;
            }

        } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
            $failures = $e->failures();
            $errors = [];
            
            foreach ($failures as $failure) {
                $errors[] = "Fila {$failure->row()}: {$failure->errors()[0]}";
            }
            
            return response()->json([
                'success' => false,
                'message' => implode("\n", $errors)
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Error en importación de tarifas', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error en la importación: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkImportProgress($importId)
    {
        try {
            $progressService = new ImportProgressService();
            $progress = $progressService->getProgress($importId);

            return response()->json([
                'success' => true,
                'current' => $progress['current'] ?? 0,
                'total' => $progress['total'] ?? 0,
                'percentage' => $progress['percentage'] ?? 0
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error al verificar el progreso: ' . $e->getMessage()
            ], 500);
        }
    }

    public function bulkUpdate(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            DB::beginTransaction();

            Excel::import(new TarifasUpdateImport, $request->file('archivo'));

            DB::commit();
            return back()->with('success', 'Tarifas actualizadas correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar tarifas: ' . $e->getMessage());
        }
    }

    public function quickUpdate(Request $request, Tarifa $tarifa)
    {
        $request->validate([
            'new_tarifa' => 'required|numeric|min:0',
            'year' => 'required|digits:4'
        ]);

        try {
            DB::transaction(function () use ($request, $tarifa) {
                TarifaHistorial::create([
                    'tarifa_id' => $tarifa->id,
                    'valor_anterior' => $tarifa->valor,
                    'valor_nuevo' => $request->new_tarifa,
                    'user_id' => Auth::id()
                ]);

                $tarifa->update(['valor' => $request->new_tarifa]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Tarifa actualizada correctamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la tarifa: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar plantilla para importación de tarifas
     */
    public function downloadTemplate()
    {
        $headers = [
            'razon_social_empresa',
            'nombre_sede',
            'regimen',
            'tercero',
            'descripcion_del_servicio',
            'cups',
            'cups facturados',
            'tarifas_de_manual',
            'valor'
        ];

        $friendlyHeaders = [
            'Razón Social Empresa',
            'Nombre Sede',
            'Régimen',
            'Tercero',
            'Descripción del Servicio',
            'CUPS',
            'CUPS Facturados',
            'Tarifas de Manual',
            'Valor'
        ];

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Establecer los encabezados
        foreach ($friendlyHeaders as $index => $header) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
            $sheet->setCellValue($column . '1', $header);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
            $sheet->getStyle($column . '1')->getFill()
                ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                ->getStartColor()->setRGB('CCCCCC');
        }

        // Agregar validación para el campo régimen
        $validation = $sheet->getCell('C2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_INFORMATION);
        $validation->setAllowBlank(false);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1('"CONTRIBUTIVO,SUBSIDIADO,PARTICULAR"');
        
        // Copiar la validación a las demás celdas de la columna (hasta la fila 1000)
        for ($i = 3; $i <= 1000; $i++) {
            $validation = $sheet->getCell('C' . $i)->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setFormula1('"CONTRIBUTIVO,SUBSIDIADO,PARTICULAR"');
            $validation->setShowDropDown(true);
        }

        // Hoja de referencia de empresas
        $empresasSheet = $spreadsheet->createSheet();
        $empresasSheet->setTitle('Empresas Ref');
        $empresas = \App\Models\Empresa::select('razon_social', 'nit')->orderBy('razon_social')->get();
        $empresasSheet->setCellValue('A1', 'Razón Social');
        $empresasSheet->setCellValue('B1', 'NIT');
        $empresasSheet->getStyle('A1:B1')->getFont()->setBold(true);

        $row = 2;
        foreach ($empresas as $empresa) {
            $empresasSheet->setCellValue('A' . $row, $empresa->razon_social);
            $empresasSheet->setCellValue('B' . $row, $empresa->nit);
            $row++;
        }

        // Hoja de referencia de sedes
        $sedesSheet = $spreadsheet->createSheet();
        $sedesSheet->setTitle('Sedes Ref');
        $sedes = \App\Models\Sede::with('empresa')
            ->select('sedes.*')
            ->orderBy('sede')
            ->get();
        
        $sedesSheet->setCellValue('A1', 'Sede');
        $sedesSheet->setCellValue('B1', 'Empresa');
        $sedesSheet->getStyle('A1:B1')->getFont()->setBold(true);

        $row = 2;
        foreach ($sedes as $sede) {
            $sedesSheet->setCellValue('A' . $row, $sede->sede);
            $sedesSheet->setCellValue('B' . $row, $sede->empresa->razon_social);
            $row++;
        }

        // Agregar una hoja de instrucciones
        $instructionsSheet = $spreadsheet->createSheet();
        $instructionsSheet->setTitle('Instrucciones');
        $instructionsSheet->setCellValue('A1', 'Instrucciones para la importación de tarifas');
        $instructionsSheet->setCellValue('A3', '1. Utilice los nombres exactos de Empresas y Sedes que aparecen en las hojas de referencia.');
        $instructionsSheet->setCellValue('A4', '2. El Régimen debe ser uno de los siguientes: CONTRIBUTIVO, SUBSIDIADO o PARTICULAR.');
        $instructionsSheet->setCellValue('A5', '3. Los valores deben ser numéricos y mayores a 0.');
        $instructionsSheet->setCellValue('A6', '4. Todos los campos son obligatorios excepto CUPS Facturados y Tarifas de Manual.');
        $instructionsSheet->getStyle('A1')->getFont()->setBold(true);

        // Ajustar el ancho de las columnas automáticamente
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach (range('A', $sheet->getHighestDataColumn()) as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        }

        // Volver a la primera hoja
        $spreadsheet->setActiveSheetIndex(0);

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        $filename = 'plantilla_tarifas_' . date('Y-m-d') . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    public function downloadErrorReport($filename)
    {
        try {
            $filepath = storage_path('app/public/reportes/' . $filename);
            
            if (!file_exists($filepath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El archivo de reporte no existe.'
                ], 404);
            }
            
            return response()->download($filepath, 'reporte_errores_importacion.xlsx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename=reporte_errores_importacion.xlsx'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el reporte: ' . $e->getMessage()
            ], 500);
        }
    }

    public function historial(Tarifa $tarifa)
    {
        try {
            $historial = $tarifa->historial()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json($historial);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar el historial: ' . $e->getMessage()
            ], 500);
        }
    }
}