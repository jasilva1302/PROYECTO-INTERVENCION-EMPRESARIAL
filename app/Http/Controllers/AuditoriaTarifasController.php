<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Factura;
use App\Models\Tarifa;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\HasPermissionChecks;

class AuditoriaTarifasController extends Controller
{
    use HasPermissionChecks;

    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $this->checkPermissionAndRespond('Ver Tarifas');

        try {
            $auditorias = DB::table('facturas as f')
                ->join('factura_servicios as fs', 'f.id', '=', 'fs.factura_id')
                ->join('empresas as e', 'f.empresa_id', '=', 'e.id')
                ->join('sedes as s', 'f.sede_id', '=', 's.id')
                ->leftJoin('tarifas as t', function($join) {
                    $join->on('f.empresa_id', '=', 't.empresa_id')
                        ->on('f.sede_id', '=', 't.sede_id')
                        ->on('fs.cargo_servicio', '=', 't.cups_facturados')
                        ->on('f.aseguradora', '=', 't.tercero')
                        ->whereRaw('f.periodo_facturacion_inicio BETWEEN t.inicio_vigencia AND t.fin_vigencia')
                        ->where('t.activo', '=', 1);
                })
                ->select([
                    'e.razon_social as empresa',
                    's.sede',
                    'f.factura',
                    'f.aseguradora',
                    'f.tipo_cliente as regimen',
                    'fs.descripcion_servicio',
                    'fs.cups_servicio',
                    'fs.valor_unitario_servicio',
                    'f.periodo_facturacion_inicio',
                    't.valor as tarifa_valor',
                    't.inicio_vigencia',
                    't.fin_vigencia',
                    DB::raw('(
                        CASE 
                            WHEN t.valor IS NULL THEN "SIN TARIFA CONFIGURADA"
                            WHEN t.valor = fs.valor_unitario_servicio THEN "TARIFA CORRECTA"
                            ELSE "DIFERENCIA DE TARIFAS"
                        END
                    ) as estado'),
                    DB::raw('(
                        CASE 
                            WHEN t.valor IS NULL THEN "No hay tarifa configurada para este servicio"
                            WHEN t.valor = fs.valor_unitario_servicio THEN "OK"
                            ELSE CONCAT("Tarifa correcta: $", FORMAT(t.valor, 2))
                        END
                    ) as observaciones')
                ])
                ->groupBy([
                    'f.id',
                    'fs.id',
                    'e.razon_social',
                    's.sede',
                    'f.factura',
                    'f.aseguradora',
                    'f.tipo_cliente',
                    'fs.descripcion_servicio',
                    'fs.cups_servicio',
                    'fs.valor_unitario_servicio',
                    'f.periodo_facturacion_inicio',
                    't.valor',
                    't.inicio_vigencia',
                    't.fin_vigencia'
                ])
                ->orderBy('f.periodo_facturacion_inicio', 'desc')
                ->orderBy('f.factura')
                ->orderBy('fs.cups_servicio')
                ->paginate(50);

            return view('tarifas.auditoria.auditoria_tarifas', compact('auditorias'));
        } catch (\Exception $e) {
            Log::error('Error en auditoría de tarifas: ' . $e->getMessage());
            return back()->with('error', 'Error al cargar la auditoría de tarifas: ' . $e->getMessage());
        }
    }
} 