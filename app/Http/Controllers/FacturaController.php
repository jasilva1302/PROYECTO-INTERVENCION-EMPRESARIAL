<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Factura;
use App\Exports\FacturasExport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Empresa;

class FacturaController extends Controller
{
    public function exportarFacturas(Request $request)
    {
        $estado = $request->input('estado', 'pendiente');
        $fechaInicio = $request->input('fecha_inicio');
        $fechaFin = $request->input('fecha_fin');
        $empresa_id = $request->input('empresa_id');
        $sede_id = $request->input('sede_id');
        $mes = $request->input('mes');
        $anio = $request->input('anio');

        $export = new FacturasExport($estado, $fechaInicio, $fechaFin, $empresa_id, $sede_id, $mes, $anio);
        
        $filename = $estado === 'pendiente' ? 'facturas_pendientes.xlsx' : 'facturas_radicadas.xlsx';
        
        return Excel::download($export, $filename);
    }

    public function index()
    {
        $empresas = Empresa::where('is_active', true)->get();
        return view('facturas.index', compact('empresas'));
    }
} 