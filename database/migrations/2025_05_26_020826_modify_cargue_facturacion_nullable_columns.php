<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('cargue_facturacion', function (Blueprint $table) {
            // Primero eliminamos las restricciones de clave foránea existentes
            $table->dropForeign(['empresa_id']);
            $table->dropForeign(['sede_id']);

            // Modificamos las columnas para que sean nullable
            $table->unsignedBigInteger('empresa_id')->nullable()->change();
            $table->unsignedBigInteger('sede_id')->nullable()->change();

            // Volvemos a agregar las restricciones de clave foránea
            $table->foreign('empresa_id')->references('id')->on('empresas');
            $table->foreign('sede_id')->references('id')->on('sedes');
        });
    }

    public function down()
    {
        Schema::table('cargue_facturacion', function (Blueprint $table) {
            // Primero eliminamos las restricciones de clave foránea
            $table->dropForeign(['empresa_id']);
            $table->dropForeign(['sede_id']);

            // Revertimos las columnas a not nullable
            $table->unsignedBigInteger('empresa_id')->nullable(false)->change();
            $table->unsignedBigInteger('sede_id')->nullable(false)->change();

            // Volvemos a agregar las restricciones de clave foránea
            $table->foreign('empresa_id')->references('id')->on('empresas');
            $table->foreign('sede_id')->references('id')->on('sedes');
        });
    }
}; 