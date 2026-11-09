<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanTablesSeeder extends Seeder
{
    public function run()
    {
        // Desactivar verificación de claves foráneas
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            // Lista de tablas principales a limpiar
            $tables = [
                'facturas',
                'cargue_facturacion',
                'hallazgos',
                'respuestas',
                'evaluaciones',
                'audits',
                'users',
                'permission_role',
                'permissions',
                'roles',
                'modules',
                'sedes',
                'empresas'
            ];

            // Limpiar cada tabla
            foreach ($tables as $table) {
                if (Schema::hasTable($table)) {
                    try {
                        DB::table($table)->truncate();
                        $this->command->info("Tabla {$table} limpiada.");
                    } catch (\Exception $e) {
                        $this->command->warn("No se pudo limpiar la tabla {$table}: " . $e->getMessage());
                    }
                } else {
                    $this->command->warn("La tabla {$table} no existe.");
                }
            }
        } finally {
            // Reactivar verificación de claves foráneas
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }
} 