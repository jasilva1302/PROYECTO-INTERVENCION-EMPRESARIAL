<?php

namespace App\Imports;

use App\Models\Factura;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Carbon\Carbon;

class RadicadosImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        $factura = Factura::where('factura', $row['factura'])->first();
        
        if ($factura) {
            $fecha_radicacion = null;
            if (!empty($row['fecha_de_radicacion'])) {
                try {
                    // Intentar convertir la fecha desde el formato DD/MM/YYYY
                    $fecha_radicacion = Carbon::createFromFormat('d/m/Y', $row['fecha_de_radicacion'])->format('Y-m-d');
                } catch (\Exception $e) {
                    // Si falla, intentar con el formato que Excel usa internamente
                    try {
                        $fecha_radicacion = Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['fecha_de_radicacion']))->format('Y-m-d');
                    } catch (\Exception $e) {
                        // Si ambos intentos fallan, dejar la fecha como null
                        $fecha_radicacion = null;
                    }
                }
            }

            $factura->update([
                'numero_radicacion' => $row['numero_de_radicacion'],
                'fecha_radicacion' => $fecha_radicacion,
            ]);
        }

        return null;
    }
} 