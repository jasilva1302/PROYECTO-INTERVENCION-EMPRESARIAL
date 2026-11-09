<?php

namespace App\Http\Controllers;

use App\Models\Audit;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\FacturaStorageService;

class FacturaPdfController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function showStructure(Audit $audit)
    {
        // Verificar permisos
        if (!$this->hasPermission('ver_auditorias')) {
            abort(403, 'No tienes permiso para ver documentos de auditoría');
        }

        try {
            $disk = Storage::disk('facturas');
            $structure = [];
            
            // Obtener todas las aseguradoras
            $aseguradoras = $disk->directories();
            
            foreach ($aseguradoras as $aseguradora) {
                $facturas = $disk->directories($aseguradora);
                $structure[$aseguradora] = [];
                
                foreach ($facturas as $factura) {
                    $pdfs = $disk->files($factura);
                    if (!empty($pdfs)) {
                        $structure[$aseguradora][] = basename($factura);
                    }
                }
            }
            
            if (empty($structure)) {
                return back()->with('warning', 'No se encontraron documentos');
            }

            return view('auditoria.pdf-structure', [
                'audit' => $audit,
                'structure' => $structure
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar estructura de PDFs', [
                'error' => $e->getMessage(),
                'audit' => $audit->id
            ]);
            return back()->with('error', 'Error al cargar la estructura de documentos');
        }
    }

    public function showFactura(Audit $audit, $aseguradora, $factura)
    {
        // Verificar permisos
        if (!$this->hasPermission('ver_auditorias')) {
            abort(403, 'No tienes permiso para ver documentos de auditoría');
        }

        try {
            $disk = Storage::disk('facturas');
            $path = "$aseguradora/$factura";
            
            if (!$disk->exists($path)) {
                return back()->with('error', 'No se encontró la factura especificada');
            }
            
            $pdfs = $disk->files($path);
            
            return view('auditoria.pdf-viewer', [
                'audit' => $audit,
                'aseguradora' => $aseguradora,
                'factura' => $factura,
                'pdfs' => $pdfs
            ]);
        } catch (\Exception $e) {
            Log::error('Error al mostrar factura', [
                'error' => $e->getMessage(),
                'audit' => $audit->id,
                'aseguradora' => $aseguradora,
                'factura' => $factura
            ]);
            return back()->with('error', 'Error al cargar los documentos de la factura');
        }
    }

    public function getPdf(Audit $audit, $aseguradora, $factura, $filename)
    {
        try {
            $disk = Storage::disk('facturas');
            $path = $aseguradora . '/' . $factura . '/' . $filename;

            if (!$disk->exists($path)) {
                abort(404, 'Archivo no encontrado');
            }

            $fullPath = $disk->path($path);
            return response()->file($fullPath, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Cache-Control' => 'private, no-store, no-cache, must-revalidate, max-age=0'
            ]);
        } catch (\Exception $e) {
            Log::error('Error al servir PDF', [
                'error' => $e->getMessage(),
                'path' => $path ?? 'no_path'
            ]);
            abort(404, 'Error al acceder al archivo');
        }
    }

    private function getPdfType($prefix)
    {
        $types = [
            'CRC' => 'Carta de recobro',
            'FVS' => 'Factura de venta',
            'HEV' => 'Historia clínica',
            'OTR' => 'Otros soportes',
            'PDX' => 'Prescripción'
        ];

        return $types[$prefix] ?? 'Otro';
    }
}