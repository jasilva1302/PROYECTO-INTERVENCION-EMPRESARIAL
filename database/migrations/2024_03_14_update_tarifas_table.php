<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('tarifas', function (Blueprint $table) {
            // Campos existentes que deben mantenerse si ya existen
            if (!Schema::hasColumn('tarifas', 'empresa_id')) {
                $table->unsignedBigInteger('empresa_id');
                $table->foreign('empresa_id')->references('id')->on('empresas');
            }
            
            if (!Schema::hasColumn('tarifas', 'sede_id')) {
                $table->unsignedBigInteger('sede_id');
                $table->foreign('sede_id')->references('id')->on('sedes');
            }

            // Nuevos campos basados en el archivo de importación
            if (!Schema::hasColumn('tarifas', 'regimen')) {
                $table->string('regimen')->nullable(); // CONTRIBUTIVO, SUBSIDIADO
            }

            if (!Schema::hasColumn('tarifas', 'tercero')) {
                $table->string('tercero')->nullable(); // Nombre de la EPS o aseguradora
            }

            if (!Schema::hasColumn('tarifas', 'descripcion_del_servicio')) {
                $table->text('descripcion_del_servicio')->nullable();
            }

            if (!Schema::hasColumn('tarifas', 'cups')) {
                $table->string('cups')->nullable(); // Código CUPS base
            }

            if (!Schema::hasColumn('tarifas', 'cups_facturados')) {
                $table->string('cups_facturados')->nullable(); // CUPS con sufijo (ej: 890268-CM)
            }

            if (!Schema::hasColumn('tarifas', 'tarifas_de_manual')) {
                $table->decimal('tarifas_de_manual', 12, 2)->nullable();
            }

            if (!Schema::hasColumn('tarifas', 'valor')) {
                $table->decimal('valor', 12, 2)->nullable();
            }

            if (!Schema::hasColumn('tarifas', 'year')) {
                $table->integer('year')->nullable();
            }

            if (!Schema::hasColumn('tarifas', 'activo')) {
                $table->boolean('activo')->default(true);
            }

            // Campos para el control de vigencia
            if (!Schema::hasColumn('tarifas', 'inicio_vigencia')) {
                $table->date('inicio_vigencia')->nullable();
            }

            if (!Schema::hasColumn('tarifas', 'fin_vigencia')) {
                $table->date('fin_vigencia')->nullable();
            }

            // Índices para mejorar el rendimiento de las búsquedas
            $table->index(['empresa_id', 'sede_id', 'cups']);
            $table->index(['regimen', 'tercero']);
            $table->index('cups_facturados');
        });
    }

    public function down()
    {
        Schema::table('tarifas', function (Blueprint $table) {
            // Eliminar índices
            $table->dropIndex(['empresa_id', 'sede_id', 'cups']);
            $table->dropIndex(['regimen', 'tercero']);
            $table->dropIndex('tarifas_cups_facturados_index');

            // Eliminar columnas nuevas
            $table->dropColumn([
                'regimen',
                'tercero',
                'descripcion_del_servicio',
                'cups',
                'cups_facturados',
                'tarifas_de_manual',
                'valor',
                'year',
                'activo',
                'inicio_vigencia',
                'fin_vigencia'
            ]);
        });
    }
}; 