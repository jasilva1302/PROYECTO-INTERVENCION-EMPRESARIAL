<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('factura_servicios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->string('cups_servicio')->nullable();
            $table->string('cargo_servicio')->nullable();
            $table->text('descripcion_servicio')->nullable();
            $table->integer('cantidad');
            $table->string('autorizacion_servicio')->nullable();
            $table->decimal('valor_unitario_servicio', 15, 2)->nullable();
            $table->decimal('valor_total_servicio', 15, 2)->nullable();
            $table->timestamps();

            // Índice para búsquedas rápidas por factura
            $table->index('factura_id');
            // Índice para búsquedas por CUPS
            $table->index('cups_servicio');
        });

        // Modificar la tabla facturas para eliminar las columnas que se movieron
        Schema::table('facturas', function (Blueprint $table) {
            $table->dropColumn([
                'cups_servicio',
                'cargo_servicio',
                'descripcion_servicio',
                'cantidad',
                'autorizacion_servicio',
                'valor_unitario_servicio',
                'valor_total_servicio'
            ]);
        });
    }

    public function down()
    {
        // Primero, restaurar las columnas en la tabla facturas
        Schema::table('facturas', function (Blueprint $table) {
            $table->string('cups_servicio')->nullable();
            $table->string('cargo_servicio')->nullable();
            $table->text('descripcion_servicio')->nullable();
            $table->integer('cantidad');
            $table->string('autorizacion_servicio')->nullable();
            $table->decimal('valor_unitario_servicio', 15, 2)->nullable();
            $table->decimal('valor_total_servicio', 15, 2)->nullable();
        });

        // Luego, eliminar la tabla factura_servicios
        Schema::dropIfExists('factura_servicios');
    }
}; 