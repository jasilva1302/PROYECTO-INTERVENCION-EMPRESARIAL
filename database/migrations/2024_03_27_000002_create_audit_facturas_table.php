<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('audit_facturas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_id');
            $table->unsignedBigInteger('factura_id');
            $table->timestamps();

            $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');
            $table->foreign('factura_id')->references('id')->on('facturas')->onDelete('cascade');
            
            $table->unique(['audit_id', 'factura_id']);
        });

        // Actualizar la tabla hallazgos para usar audit_facturas
        Schema::table('hallazgos', function (Blueprint $table) {
            // Eliminar la foreign key existente si existe
            if (Schema::hasColumn('hallazgos', 'audit_id')) {
                $table->dropForeign(['audit_id']);
                $table->dropColumn('audit_id');
            }

            // Agregar la nueva relación con audit_facturas
            $table->unsignedBigInteger('audit_factura_id')->after('id');
            $table->foreign('audit_factura_id')->references('id')->on('audit_facturas')->onDelete('cascade');
        });
    }

    public function down()
    {
        // Revertir los cambios en la tabla hallazgos
        Schema::table('hallazgos', function (Blueprint $table) {
            $table->dropForeign(['audit_factura_id']);
            $table->dropColumn('audit_factura_id');
            
            // Restaurar la columna original
            $table->unsignedBigInteger('audit_id')->after('id');
            $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');
        });

        Schema::dropIfExists('audit_facturas');
    }
}; 