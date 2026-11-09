<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Primero, crear una nueva columna observacion
        Schema::table('hallazgos', function (Blueprint $table) {
            $table->text('observacion')->nullable();
        });

        // Copiar los datos de descripcion a observacion
        DB::statement('UPDATE hallazgos SET observacion = descripcion');

        // Eliminar la columna antigua y agregar la nueva relación
        Schema::table('hallazgos', function (Blueprint $table) {
            // Eliminar la columna audit_id y su clave foránea
            $table->dropForeign(['audit_id']);
            $table->dropColumn(['audit_id', 'descripcion']);
            
            // Agregar la nueva columna audit_factura_id
            $table->unsignedBigInteger('audit_factura_id');
            $table->foreign('audit_factura_id')->references('id')->on('audit_facturas')->onDelete('cascade');

            // Asegurarse de que observacion no sea nullable después de la migración
            $table->text('observacion')->nullable(false)->change();
        });
    }

    public function down()
    {
        Schema::table('hallazgos', function (Blueprint $table) {
            // Crear una columna temporal para descripcion
            $table->text('descripcion')->nullable();
        });

        // Copiar los datos de observacion a descripcion
        DB::statement('UPDATE hallazgos SET descripcion = observacion');

        Schema::table('hallazgos', function (Blueprint $table) {
            // Eliminar la nueva estructura
            $table->dropForeign(['audit_factura_id']);
            $table->dropColumn(['audit_factura_id', 'observacion']);
            
            // Asegurarse de que descripcion no sea nullable
            $table->text('descripcion')->nullable(false)->change();
            
            // Recrear la columna audit_id
            $table->unsignedBigInteger('audit_id');
            $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');
        });
    }
}; 