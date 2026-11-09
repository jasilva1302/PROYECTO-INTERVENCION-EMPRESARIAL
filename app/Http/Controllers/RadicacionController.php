<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Factura;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RadicadosImport;
use App\Imports\ObservacionesImport;
use App\Exports\FacturasExport;

class RadicacionController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $mesSeleccionado = $request->get('mes', date('m'));
        $anioSeleccionado = $request->get('anio', date('Y'));
        $estadoRadicacion = $request->get('estado_radicacion', 'todos');

        $query = Factura::with(['observacionesRadicacion' => function($query) {
            $query->orderBy('created_at', 'desc');
        }, 'observacionesRadicacion.user'])
        ->whereYear('periodo_facturacion_inicio', $anioSeleccionado)
        ->whereMonth('periodo_facturacion_inicio', $mesSeleccionado);

        // Aplicar filtro de estado de radicación
        if ($estadoRadicacion !== 'todos') {
            if ($estadoRadicacion === 'radicado') {
                $query->whereNotNull('fecha_radicacion');
            } else if ($estadoRadicacion === 'pendiente') {
                $query->whereNull('fecha_radicacion')
                      ->whereNull('numero_radicacion');
            }
        }

        $facturas = $query->orderBy('periodo_facturacion_inicio', 'desc')
                         ->get();

        return view('radicacion.index', compact('facturas', 'mesSeleccionado', 'anioSeleccionado', 'estadoRadicacion'));
    }

    public function cargarMasivo(Request $request)
    {
        $request->validate([
            'archivo_excel' => 'required|mimes:xlsx,xls|max:2048'
        ], [
            'archivo_excel.required' => 'Debe seleccionar un archivo Excel',
            'archivo_excel.mimes' => 'El archivo debe ser un Excel (.xlsx o .xls)',
            'archivo_excel.max' => 'El archivo no debe pesar más de 2MB'
        ]);

        try {
            Excel::import(new RadicadosImport, $request->file('archivo_excel'));

            return redirect()->route('radicacion.index')
                ->with('success', 'Los radicados se han actualizado correctamente');
        } catch (\Exception $e) {
            return redirect()->route('radicacion.index')
                ->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    public function cargarObservacionesMasivo(Request $request)
    {
        $request->validate([
            'archivo_excel' => 'required|mimes:xlsx,xls|max:2048'
        ], [
            'archivo_excel.required' => 'Debe seleccionar un archivo Excel',
            'archivo_excel.mimes' => 'El archivo debe ser un Excel (.xlsx o .xls)',
            'archivo_excel.max' => 'El archivo no debe pesar más de 2MB'
        ]);

        try {
            $import = new ObservacionesImport();
            Excel::import($import, $request->file('archivo_excel'));
            
            $resultados = $import->getResultados();
            
            if (!empty($resultados['errores'])) {
                return redirect()->route('radicacion.index')
                    ->with('warning', 'Se procesaron ' . $resultados['procesadas'] . ' observaciones con algunos errores:<br>' . 
                           implode('<br>', $resultados['errores']));
            }

            return redirect()->route('radicacion.index')
                ->with('success', 'Se agregaron ' . $resultados['procesadas'] . ' observaciones correctamente');
        } catch (\Exception $e) {
            return redirect()->route('radicacion.index')
                ->with('error', 'Error al procesar el archivo: ' . $e->getMessage());
        }
    }

    public function descargarPlantilla()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Establecer los encabezados
        $headers = ['Factura', 'Fecha de Radicación', 'Número de Radicación'];
        $sheet->fromArray([$headers], NULL, 'A1');

        // Dar formato a los encabezados
        $sheet->getStyle('A1:C1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50'],
            ],
        ]);

        // Agregar una fila de ejemplo
        $exampleData = [
            'FAC-001',
            '01/01/2024',
            'RAD-2024-001'
        ];
        $sheet->fromArray([$exampleData], NULL, 'A2');

        // Dar formato a la fila de ejemplo
        $sheet->getStyle('A2:C2')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA'],
            ],
            'font' => [
                'italic' => true,
            ],
        ]);

        // Ajustar el ancho de las columnas
        foreach (range('A', 'C') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Agregar validación para la fecha
        $validation = $sheet->getCell('B2')->getDataValidation();
        $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_DATE);
        $validation->setErrorStyle(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setErrorTitle('Error de fecha');
        $validation->setError('Por favor ingrese una fecha válida en formato DD/MM/YYYY');
        $validation->setPromptTitle('Fecha de radicación');
        $validation->setPrompt('Ingrese la fecha en formato DD/MM/YYYY');

        // Crear el writer para descargar el archivo
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Configurar headers para la descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="plantilla_radicados.xlsx"');
        header('Cache-Control: max-age=0');

        // Guardar el archivo al output
        $writer->save('php://output');
        exit;
    }

    public function descargarPlantillaObservaciones()
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Establecer los encabezados
        $headers = ['FACTURA', 'OBSERVACION'];
        $sheet->fromArray([$headers], NULL, 'A1');

        // Dar formato a los encabezados
        $sheet->getStyle('A1:B1')->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '2C3E50'],
            ],
        ]);

        // Agregar una fila de ejemplo
        $exampleData = [
            'FAC-001',
            'Pendiente documentación del paciente'
        ];
        $sheet->fromArray([$exampleData], NULL, 'A2');

        // Dar formato a la fila de ejemplo
        $sheet->getStyle('A2:B2')->applyFromArray([
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E2EFDA'],
            ],
            'font' => [
                'italic' => true,
            ],
        ]);

        // Ajustar el ancho de las columnas
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        // Crear el writer para descargar el archivo
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        
        // Configurar headers para la descarga
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="plantilla_observaciones.xlsx"');
        header('Cache-Control: max-age=0');

        // Guardar el archivo al output
        $writer->save('php://output');
        exit;
    }

    public function agregarObservacion(Request $request)
    {
        $request->validate([
            'factura_id' => 'required|exists:facturas,id',
            'observacion' => 'required|string|max:1000'
        ], [
            'factura_id.required' => 'La factura es requerida',
            'factura_id.exists' => 'La factura seleccionada no existe',
            'observacion.required' => 'La observación es requerida',
            'observacion.max' => 'La observación no puede exceder los 1000 caracteres'
        ]);

        $factura = Factura::findOrFail($request->factura_id);

        // Verificar que la factura esté pendiente por radicar
        if (!$factura->isPendienteRadicar()) {
            return redirect()->route('radicacion.index')
                ->with('error', 'Solo se pueden agregar observaciones a facturas pendientes por radicar');
        }

        // Agregar la observación
        $factura->agregarObservacionRadicacion($request->observacion, auth()->id());

        return redirect()->route('radicacion.index')
            ->with('success', 'Observación agregada correctamente');
    }

    public function exportarFacturas($estado, Request $request)
    {
        $filename = $estado === 'pendiente' ? 'facturas_pendientes.xlsx' : 'facturas_radicadas.xlsx';
        
        if ($estado === 'radicado' && $request->has('fecha_inicio') && $request->has('fecha_fin')) {
            $filename = 'facturas_radicadas_' . $request->fecha_inicio . '_' . $request->fecha_fin . '.xlsx';
            return Excel::download(
                new FacturasExport($estado, $request->fecha_inicio, $request->fecha_fin), 
                $filename
            );
        }
        
        return Excel::download(new FacturasExport($estado), $filename);
    }
} 