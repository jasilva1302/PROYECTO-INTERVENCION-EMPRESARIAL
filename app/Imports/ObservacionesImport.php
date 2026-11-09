<?php

namespace App\Imports;

use App\Models\Factura;
use App\Models\ObservacionRadicacion;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Facades\Log;

class ObservacionesImport implements ToModel, WithHeadingRow
{
    protected $userId;
    protected $procesadas = 0;
    protected $errores = [];

    public function __construct()
    {
        $this->userId = auth()->id();
    }

    public function model(array $row)
    {
        try {
            if (empty($row['factura']) || empty($row['observacion'])) {
                $this->errores[] = "Fila " . ($this->procesadas + 2) . ": Factura u observación vacía";
                return null;
            }

            $factura = Factura::where('factura', $row['factura'])->first();
            
            if (!$factura) {
                $this->errores[] = "Fila " . ($this->procesadas + 2) . ": Factura no encontrada: " . $row['factura'];
                return null;
            }

            if (!$factura->isPendienteRadicar()) {
                $this->errores[] = "Fila " . ($this->procesadas + 2) . ": La factura " . $row['factura'] . " no está pendiente por radicar";
                return null;
            }

            ObservacionRadicacion::create([
                'factura_id' => $factura->id,
                'user_id' => $this->userId,
                'observacion' => $row['observacion']
            ]);

            $this->procesadas++;
        } catch (\Exception $e) {
            Log::error("Error procesando observación: " . $e->getMessage(), [
                'factura' => $row['factura'] ?? 'N/A',
                'error' => $e->getMessage()
            ]);
            $this->errores[] = "Error procesando factura " . ($row['factura'] ?? 'N/A') . ": " . $e->getMessage();
        }

        return null;
    }

    public function getResultados()
    {
        return [
            'procesadas' => $this->procesadas,
            'errores' => $this->errores
        ];
    }
} 