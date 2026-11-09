<?php

namespace App\Imports;

use App\Models\Tarifa;
use App\Models\TarifaHistorial;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TarifasUpdateImport implements ToModel, WithHeadingRow
{
    protected $defaultYear;

    public function __construct($defaultYear = null)
    {
        $this->defaultYear = $defaultYear;
    }

    public function model(array $row)
    {
        // Buscar la tarifa por CUPS
        $tarifa = Tarifa::where('cups', $row['cups'])->first();

        if ($tarifa) {
            $year = $row['ano_aplicacion'] ?? $this->defaultYear;

            // Registrar en el historial
            TarifaHistorial::create([
                'tarifa_id' => $tarifa->id,
                'tarifa_anterior' => $tarifa->valor,
                'tarifa_nueva' => $row['nueva_tarifa'],
                'year' => $year,
                'user_id' => Auth::id()
            ]);

            // Actualizar la tarifa
            $tarifa->update(['valor' => $row['nueva_tarifa']]);
        }

        return null;
    }
}