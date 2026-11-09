<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Config;
use App\Models\Audit;

class AuditFileService
{
    private $audit;
    private $basePath;
    private $isNetworkPath;

    public function __construct(Audit $audit)
    {
        $this->audit = $audit;
        $this->setupDisk();
    }

    private function setupDisk()
    {
        if (!$this->audit->link) {
            throw new \Exception('La auditoría no tiene una ruta base configurada');
        }

        // Normalizar la ruta y configurar el disco facturas
        $this->basePath = str_replace(['"', "'"], '', trim($this->audit->link));
        $this->basePath = rtrim($this->basePath, '\\');
        
        // Determinar si es una ruta de red o local
        $this->isNetworkPath = substr($this->basePath, 0, 2) === '\\\\';
        
        if (!$this->isNetworkPath) {
            // Si es una ruta local, asegurarse de que no tenga formato de red
            $this->basePath = str_replace('\\\\', '\\', $this->basePath);
            // Convertir barras normales a barras invertidas para Windows
            $this->basePath = str_replace('/', '\\', $this->basePath);
        }

        // Asegurarse de que el directorio base exista
        if (!file_exists($this->basePath)) {
            if (!mkdir($this->basePath, 0755, true)) {
                throw new \Exception("No se pudo crear el directorio base: {$this->basePath}");
            }
        }
        
        Config::set('filesystems.disks.facturas.root', $this->basePath);
        
        \Log::info('Disco facturas configurado', [
            'base_path' => $this->basePath,
            'is_network_path' => $this->isNetworkPath,
            'exists' => file_exists($this->basePath),
            'is_dir' => is_dir($this->basePath),
            'is_readable' => is_readable($this->basePath),
            'is_writable' => is_writable($this->basePath)
        ]);
    }

    private function normalizePath($path)
    {
        // Convertir todas las barras a barras invertidas para Windows
        $path = str_replace('/', '\\', $path);
        
        // Si es una ruta absoluta, devolverla tal cual
        if (substr($path, 0, 2) === '\\\\' || substr($path, 1, 1) === ':') {
            return $path;
        }
        
        // Combinar con la ruta base
        return $this->basePath . '\\' . ltrim($path, '\\');
    }

    private function ensureDirectoryExists($path)
    {
        $normalizedPath = $this->normalizePath($path);
        
        if (!file_exists($normalizedPath)) {
            \Log::info('Creando directorio', [
                'path' => $normalizedPath,
                'base_path' => $this->basePath
            ]);
            
            if (!mkdir($normalizedPath, 0755, true)) {
                throw new \Exception("No se pudo crear el directorio: {$normalizedPath}");
            }
        }
        
        return $normalizedPath;
    }

    public function getFullFileStructure()
    {
        $structure = [];

        try {
            // Asegurarse de que el directorio base existe
            $this->ensureDirectoryExists('');

            // Obtener subdirectorios (aseguradoras)
            $aseguradoras = array_filter(glob($this->basePath . '\\*'), 'is_dir');

            foreach ($aseguradoras as $aseguradoraPath) {
                $aseguradora = basename($aseguradoraPath);
                $facturas = array_filter(glob($aseguradoraPath . '\\*'), 'is_dir');
                
                $aseguradoraData = [
                    'nombre' => $aseguradora,
                    'facturas' => []
                ];

                foreach ($facturas as $facturaPath) {
                    $factura = basename($facturaPath);
                    $pdfFiles = glob($facturaPath . '\\*.pdf');

                    if (!empty($pdfFiles)) {
                        $aseguradoraData['facturas'][] = [
                            'nombre' => $factura,
                            'pdfs' => array_map('basename', $pdfFiles),
                            'count' => count($pdfFiles)
                        ];
                    }
                }

                if (!empty($aseguradoraData['facturas'])) {
                    $structure[] = $aseguradoraData;
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error al obtener estructura de archivos', [
                'audit_id' => $this->audit->id,
                'error' => $e->getMessage(),
                'base_path' => $this->basePath
            ]);
        }

        return $structure;
    }

    public function getPdfFiles($aseguradora, $factura)
    {
        $pdfFiles = [];

        try {
            $path = $aseguradora . '\\' . $factura;
            $fullPath = $this->normalizePath($path);
            
            // Asegurarse de que el directorio existe
            $this->ensureDirectoryExists($path);
            
            // Buscar archivos PDF
            $files = glob($fullPath . '\\*.pdf');

            foreach ($files as $file) {
                $filename = basename($file);
                $tipoArchivo = substr($filename, 0, 3);

                $pdfFiles[] = [
                    'name' => $filename,
                    'path' => $file,
                    'url' => route('ver.pdf.simple', ['path' => base64_encode($file)]),
                    'type' => $tipoArchivo
                ];
            }

            \Log::info('PDFs encontrados', [
                'audit_id' => $this->audit->id,
                'aseguradora' => $aseguradora,
                'factura' => $factura,
                'path' => $fullPath,
                'total_pdfs' => count($pdfFiles)
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener archivos PDF', [
                'audit_id' => $this->audit->id,
                'aseguradora' => $aseguradora,
                'factura' => $factura,
                'error' => $e->getMessage()
            ]);
        }

        return $pdfFiles;
    }

    public function saveFile($file, $aseguradora, $factura)
    {
        try {
            $path = $aseguradora . '\\' . $factura;
            $fullPath = $this->ensureDirectoryExists($path);
            
            $filename = $file->getClientOriginalName();
            $filePath = $fullPath . '\\' . $filename;

            if ($file->move($fullPath, $filename)) {
                \Log::info('Archivo guardado exitosamente', [
                    'path' => $filePath,
                    'size' => filesize($filePath)
                ]);
                return $path . '\\' . $filename;
            }

            throw new \Exception("No se pudo mover el archivo a: {$filePath}");

        } catch (\Exception $e) {
            \Log::error('Error al guardar archivo', [
                'audit_id' => $this->audit->id,
                'aseguradora' => $aseguradora,
                'factura' => $factura,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleteDirectory($path)
    {
        $fullPath = $this->normalizePath($path);
        
        if (file_exists($fullPath)) {
            $it = new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);
            
            foreach ($files as $file) {
                if ($file->isDir()) {
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
            
            return rmdir($fullPath);
        }
        
        return true;
    }

    public function testFileAccess($path)
    {
        try {
            $fullPath = $this->normalizePath($path);

            $result = [
                'path' => $fullPath,
                'exists' => file_exists($fullPath),
                'is_file' => is_file($fullPath),
                'is_readable' => is_readable($fullPath),
                'size' => file_exists($fullPath) ? filesize($fullPath) : null,
                'permissions' => file_exists($fullPath) ? decoct(fileperms($fullPath) & 0777) : null,
                'error' => null,
                'base_path' => $this->basePath,
                'is_network_path' => $this->isNetworkPath
            ];

            if (file_exists($fullPath)) {
                try {
                    $handle = fopen($fullPath, 'r');
                    if ($handle) {
                        fclose($handle);
                        $result['can_open'] = true;
                    } else {
                        $result['can_open'] = false;
                        $result['open_error'] = error_get_last();
                    }
                } catch (\Exception $e) {
                    $result['can_open'] = false;
                    $result['open_error'] = $e->getMessage();
                }
            }

            return $result;

        } catch (\Exception $e) {
            return [
                'path' => $fullPath ?? $path,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'base_path' => $this->basePath,
                'is_network_path' => $this->isNetworkPath
            ];
        }
    }
} 