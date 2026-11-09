<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;

class FacturaStorageService
{
    public function findFacturaPdfs($numeroFactura, $audit = null)
    {
        $pdfFiles = [];
        
        try {
            // Usar el método estándar para todas las rutas
            $pdfFiles = $this->searchWithPHP($numeroFactura);

            Log::info("PDFs encontrados para factura", [
                'numero_factura' => $numeroFactura,
                'total_pdfs' => count($pdfFiles),
                'pdfs_encontrados' => $pdfFiles
            ]);

        } catch (\Exception $e) {
            Log::error("Error buscando PDFs para factura {$numeroFactura}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $pdfFiles;
    }

    private function searchWithPHP($numeroFactura)
    {
        $pdfFiles = [];
        
        try {
            $disk = Storage::disk('facturas');
            
            // Verificar si el directorio base existe
            if (!$disk->exists('')) {
                Log::warning("Directorio base no encontrado", [
                    'disk_root' => Config::get('filesystems.disks.facturas.root'),
                    'disk_exists' => $disk->exists('')
                ]);
                return [];
            }

            // Obtener todos los archivos recursivamente
            $allFiles = $disk->allFiles();
            
            // Filtrar solo archivos PDF que contengan el número de factura
            foreach ($allFiles as $file) {
                $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $basename = basename($file);
                $containsNumber = stripos($basename, $numeroFactura) !== false;

                if ($extension === 'pdf' && $containsNumber) {
                    $fullPath = $disk->path($file);
                    $pathParts = explode('/', $file);
                    $aseguradora = $this->findAseguradora($pathParts);

                    $pdfFiles[] = [
                        'name' => $basename,
                        'path' => $fullPath,
                        'url' => route('ver.pdf.simple', ['path' => base64_encode($fullPath)]),
                        'type' => $this->getPdfType($basename),
                        'aseguradora' => $aseguradora
                    ];

                    Log::info("PDF encontrado", [
                        'nombre' => $basename,
                        'ruta' => $fullPath,
                        'aseguradora' => $aseguradora
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            Log::error("Error al buscar con PHP", [
                'error' => $e->getMessage()
            ]);
        }
        
        return $pdfFiles;
    }

    private function findAseguradora($pathParts)
    {
        // Buscar la aseguradora en las partes de la ruta
        foreach ($pathParts as $part) {
            if (strlen($part) > 3 && !str_contains(strtolower($part), '.pdf')) {
                return $part;
            }
        }
        return 'Desconocida';
    }

    private function getPdfType($filename)
    {
        $types = [
            'CRC' => 'Carta de recobro',
            'FVS' => 'Factura de venta',
            'HEV' => 'Historia clínica',
            'OTR' => 'Otros soportes',
            'PDX' => 'Prescripción'
        ];

        $prefix = strtoupper(substr($filename, 0, 3));
        return $types[$prefix] ?? 'Otro';
    }
}