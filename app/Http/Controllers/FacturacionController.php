<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CargueFacturacion;
use App\Models\Factura;
use App\Imports\FacturacionImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasPermissionChecks;
use App\Models\FacturaServicio;
use Illuminate\Support\Facades\DB;
use App\Models\FacturaServicioAdicional;

class FacturacionController extends Controller
{
    use HasPermissionChecks;

    public function index()
    {
        $this->checkPermission('ver Facturación');
        
        $cargues = CargueFacturacion::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('facturacion.index', compact('cargues'));
    }

    public function create()
    {
        $this->checkPermission('crear Facturación');
        return view('facturacion.create');
    }

    public function store(Request $request)
    {
        $this->checkPermission('crear Facturación');
        
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls'
        ]);

        try {
            $cargue = new CargueFacturacion([
                'user_id' => auth()->id(),
                'estado' => 'pendiente'
            ]);
            $cargue->save();

            Excel::import(new FacturacionImport($cargue), $request->file('archivo'));

            return redirect()->route('facturacion.index')
                ->with('success', 'Archivo cargado correctamente');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al cargar el archivo: ' . $e->getMessage());
        }
    }

    public function show(CargueFacturacion $cargue)
    {
        $this->checkPermission('ver Facturación');
        
        $facturas = $cargue->facturas()
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('facturacion.show', compact('cargue', 'facturas'));
    }

    public function edit(CargueFacturacion $cargue)
    {
        $this->checkPermission('editar Facturación');
        return view('facturacion.edit', compact('cargue'));
    }

    public function update(Request $request, CargueFacturacion $cargue)
    {
        $this->checkPermission('editar Facturación');
        // ... lógica de actualización
    }

    public function cargar()
    {
        $this->checkPermission('cargar Facturación');
        return view('facturacion.cargar');
    }

    public function procesarCargue(Request $request)
    {
        $this->checkPermission('cargar Facturación');
        // ... lógica de procesamiento de cargue
    }

    public function exportar(Request $request)
    {
        $this->checkPermission('exportar Facturación');
        
        $request->validate([
            'cargue_id' => 'required|exists:cargue_facturacion,id'
        ]);

        $cargue = CargueFacturacion::findOrFail($request->cargue_id);
        
        return Excel::download(
            new FacturacionExport($cargue),
            'facturacion_' . $cargue->created_at->format('Y-m-d') . '.xlsx'
        );
    }

    public function cargarFacturas(Request $request)
    {
        $this->checkPermission('cargar Facturación');

        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls,csv'
        ]);

        try {
            DB::beginTransaction();

            $cargue = CargueFacturacion::create([
                'user_id' => auth()->id(),
                'nombre_archivo' => $request->file('archivo')->getClientOriginalName(),
                'estado' => 'procesando'
            ]);

            // Procesar archivo Excel
            $datos = Excel::toCollection(null, $request->file('archivo'))->first();
            
            // Agrupar por número de factura
            $facturasPorNumero = $datos->groupBy('factura');

            foreach ($facturasPorNumero as $numeroFactura => $serviciosFactura) {
                $primerServicio = $serviciosFactura->first();
                
                // Crear la factura con los datos generales
                $factura = Factura::create([
                    'cargue_id' => $cargue->id,
                    'empresa_id' => $primerServicio['empresa_id'],
                    'sede_id' => $primerServicio['sede_id'],
                    'departamento' => $primerServicio['departamento'],
                    'tipo' => $primerServicio['tipo'],
                    'identificacion' => $primerServicio['identificacion'],
                    'paciente' => $primerServicio['paciente'],
                    'tipo_cliente' => $primerServicio['tipo_cliente'],
                    'documento_aseguradora' => $primerServicio['documento_aseguradora'],
                    'aseguradora' => $primerServicio['aseguradora'],
                    'plan' => $primerServicio['plan'],
                    'numero_cuenta' => $primerServicio['numero_cuenta'],
                    'factura' => $numeroFactura,
                    'periodo_facturacion_inicio' => $primerServicio['periodo_facturacion_inicio'],
                    'periodo_facturacion_final' => $primerServicio['periodo_facturacion_final'],
                    'contabilizacion_documento' => $primerServicio['contabilizacion_documento'],
                    'interface_documento' => $primerServicio['interface_documento'],
                    'adquiriente' => $primerServicio['adquiriente'],
                    'total_documento' => $primerServicio['total_documento'],
                    'saldo' => $primerServicio['saldo'],
                    'observacion_documento' => $primerServicio['observacion_documento'],
                    'estado_documento_dian' => $primerServicio['estado_documento_dian'],
                    'estado_transaccion_dian' => $primerServicio['estado_transaccion_dian'],
                    'fecha_validacion_dian' => $primerServicio['fecha_validacion_dian'],
                    'resultado_dian' => $primerServicio['resultado_dian'],
                    'cufe' => $primerServicio['cufe'],
                    'fecha_radicacion' => $primerServicio['fecha_radicacion'],
                    'numero_radicacion' => $primerServicio['numero_radicacion'],
                    'usuario' => $primerServicio['usuario']
                ]);

                // Crear todos los servicios asociados a la factura
                foreach ($serviciosFactura as $servicio) {
                    FacturaServicio::create([
                        'factura_id' => $factura->id,
                        'cups_servicio' => $servicio['cups_servicio'],
                        'cargo_servicio' => $servicio['cargo_servicio'],
                        'descripcion_servicio' => $servicio['descripcion_servicio'],
                        'cantidad' => $servicio['cantidad'],
                        'autorizacion_servicio' => $servicio['autorizacion_servicio'],
                        'valor_unitario_servicio' => $servicio['valor_unitario_servicio'],
                        'valor_total_servicio' => $servicio['valor_total_servicio']
                    ]);
                }
            }

            $cargue->update(['estado' => 'completado']);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Facturas cargadas exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            if (isset($cargue)) {
                $cargue->update([
                    'estado' => 'fallido',
                    'error' => $e->getMessage()
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al cargar las facturas: ' . $e->getMessage()
            ], 500);
        }
    }

    public function obtenerServiciosFactura($facturaId)
    {
        $factura = Factura::with('servicios')->findOrFail($facturaId);
        
        return response()->json([
            'success' => true,
            'factura' => $factura,
            'servicios' => $factura->servicios,
            'total_servicios' => $factura->getTotalServicios()
        ]);
    }
} 