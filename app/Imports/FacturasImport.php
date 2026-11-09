<?php

namespace App\Imports;

use App\Models\Factura;
use App\Models\AuditFactura;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FacturasImport implements ToModel, WithHeadingRow
{
    protected $auditId;

    public function __construct($auditId)
    {
        $this->auditId = $auditId;
    }

    public function model(array $row)
    {
        $factura = Factura::firstOrCreate([
            'factura' => $row['factura'],
            'periodo' => $row['periodo']
        ], [
            'sede' => $row['sede'],
            'aseguradora' => $row['aseguradora'],
            'saldo' => $row['saldo']
        ]);
        
        AuditFactura::firstOrCreate([
            'audit_id' => $this->auditId,
            'factura_id' => $factura->id
        ]);
        
        return null;
    }
}