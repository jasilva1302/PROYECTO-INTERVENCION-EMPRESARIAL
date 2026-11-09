<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class UpdateRolePermissionsSeeder extends Seeder
{
    public function run()
    {
        // Permisos para ADMINISTRADOR
        $adminRole = Role::where('name', 'ADMINISTRADOR')->first();
        if ($adminRole) {
            $adminRole->permissions = [
                "Ver Auditorías",
                "Crear Auditorías",
                "Editar Auditorías",
                "Eliminar Auditorías",
                "Asignar Auditorías",
                "Cerrar Auditorías",
                "Ver facturación",
                "Crear facturación",
                "Editar facturación",
                "Eliminar facturación",
                "Cargar facturación",
                "Exportar facturación",
                "Ver estadísticas",
                "Ver tarifas",
                "Crear tarifas",
                "Editar tarifas",
                "Eliminar tarifas",
                "Ver evaluaciones",
                "Crear evaluaciones",
                "Editar evaluaciones",
                "Eliminar evaluaciones",
                "Realizar evaluaciones",
                "Calificar evaluaciones",
                "Ver configuración",
                "Editar configuración"
            ];
            $adminRole->save();
        }

        // Permisos para COORDINADOR/DIRECTOR
        $coordinadorRoles = Role::whereIn('name', ['COORDINADOR', 'DIRECTOR'])->get();
        foreach ($coordinadorRoles as $role) {
            $role->permissions = [
                "Ver Auditorías",
                "Crear Auditorías",
                "Editar Auditorías",
                "Ver facturación",
                "Crear facturación",
                "Editar facturación",
                "Ver evaluaciones",
                "Crear evaluaciones",
                "Editar evaluaciones",
                "Realizar evaluaciones"
            ];
            $role->save();
        }

        // Permisos para ASISTENTE
        $asistenteRole = Role::where('name', 'ASISTENTE')->first();
        if ($asistenteRole) {
            $asistenteRole->permissions = [
                "Ver Auditorías",
                "Ver facturación",
                "Ver evaluaciones",
                "Realizar evaluaciones"
            ];
            $asistenteRole->save();
        }
    }
} 