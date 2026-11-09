<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use App\Models\Audit;

class AuditFilesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getFacturaFiles($facturaNumber, $auditId)
    {
        // Verificar permisos
        if (!$this->hasPermission('ver_auditorias')) {
            return response()->json([
                'error' => 'No tienes permiso para ver archivos de auditoría'
            ], 403);
        }

        // Obtener la auditoría para conseguir la ruta base
        $audit = Audit::findOrFail($auditId);
        if (!$audit || !$audit->link) {
            return response()->json([
                'error' => 'Auditoría no válida o sin ruta configurada'
            ], 404);
        }

        // Normalizar la ruta y configurar el disco facturas
        $basePath = str_replace(['"', "'"], '', trim($audit->link));
        $basePath = rtrim($basePath, '\\');
        Config::set('filesystems.disks.facturas.root', $basePath);

        try {
            // Buscar archivos PDF que coincidan con el número de factura
            $allFiles = Storage::disk('facturas')->allFiles();
            
            // Filtrar archivos que coincidan con el patrón de la factura
            $facturaFiles = array_filter($allFiles, function($file) use ($facturaNumber) {
                // Extraer el número de factura del nombre del archivo
                return stripos(basename($file), $facturaNumber) !== false;
            });

            $filesInfo = array_map(function($file) {
                $fullPath = Storage::disk('facturas')->path($file);
                return [
                    'name' => basename($file),
                    'type' => 'pdf',
                    'path' => $fullPath,
                    'url' => route('ver.pdf.simple', ['path' => base64_encode($fullPath)]),
                    'size' => Storage::disk('facturas')->size($file),
                    'last_modified' => Storage::disk('facturas')->lastModified($file)
                ];
            }, $facturaFiles);

            return response()->json([
                'success' => true,
                'files' => $filesInfo
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Error al obtener los archivos: ' . $e->getMessage()
            ], 500);
        }
    }
}