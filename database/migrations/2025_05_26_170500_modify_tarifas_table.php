<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tarifas', function (Blueprint $table) {
            // Renombrar columnas existentes para mantener consistencia
            $table->renameColumn('descripcion', 'descripcion_del_servicio');
            
            // Agregar nuevas columnas
            $table->string('tercero')->after('sede_id')->nullable();
            $table->string('cups_facturados')->after('cups')->nullable();
            $table->decimal('tarifas_de_manual', 12, 2)->after('cups_facturados')->nullable();
            
            // Modificar la restricción única para incluir los nuevos campos relevantes
            $table->dropUnique('tarifa_unique');
            $table->unique([
                'empresa_id', 
                'sede_id', 
                'tercero',
                'cups', 
                'cups_facturados',
                'year', 
                'regimen'
            ], 'tarifa_unique_new');
        });
    }

    public function down()
    {
        Schema::table('tarifas', function (Blueprint $table) {
            // Revertir las columnas renombradas
            $table->renameColumn('descripcion_del_servicio', 'descripcion');
            
            // Eliminar las nuevas columnas
            $table->dropColumn([
                'tercero',
                'cups_facturados',
                'tarifas_de_manual'
            ]);
            
            // Restaurar la restricción única original
            $table->dropUnique('tarifa_unique_new');
            $table->unique([
                'empresa_id', 
                'sede_id', 
                'cups', 
                'year', 
                'regimen'
            ], 'tarifa_unique');
        });
    }
}; 