<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Verificar si el índice existe y eliminarlo si es necesario
        $indexExists = DB::select("SHOW INDEX FROM tarifas WHERE Key_name = 'tarifa_unique_new'");
        if (!empty($indexExists)) {
            DB::statement('ALTER TABLE tarifas DROP INDEX tarifa_unique_new');
        }

        // Crear el nuevo índice único
        DB::statement('ALTER TABLE tarifas ADD UNIQUE INDEX tarifa_unique_new (empresa_id, sede_id, cups, cups_facturados, year, regimen)');
        
        // Verificar si existe el índice antiguo antes de intentar eliminarlo
        $oldIndexExists = DB::select("SHOW INDEX FROM tarifas WHERE Key_name = 'tarifa_unique'");
        if (!empty($oldIndexExists)) {
            DB::statement('ALTER TABLE tarifas DROP INDEX tarifa_unique');
        }
    }

    public function down()
    {
        // Verificar si el índice original existe y eliminarlo si es necesario
        $indexExists = DB::select("SHOW INDEX FROM tarifas WHERE Key_name = 'tarifa_unique'");
        if (!empty($indexExists)) {
            DB::statement('ALTER TABLE tarifas DROP INDEX tarifa_unique');
        }

        // Restaurar el índice original
        DB::statement('ALTER TABLE tarifas ADD UNIQUE INDEX tarifa_unique (empresa_id, sede_id, cups, year, regimen)');
        
        // Eliminar el nuevo índice si existe
        $newIndexExists = DB::select("SHOW INDEX FROM tarifas WHERE Key_name = 'tarifa_unique_new'");
        if (!empty($newIndexExists)) {
            DB::statement('ALTER TABLE tarifas DROP INDEX tarifa_unique_new');
        }
    }
}; 