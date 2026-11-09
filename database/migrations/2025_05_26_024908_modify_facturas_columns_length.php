<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Modificar columnas para aceptar texto largo
            $table->text('tipo_cliente')->nullable()->change();
            $table->text('documento_aseguradora')->nullable()->change();
            $table->text('plan')->nullable()->change();
            $table->text('numero_cuenta')->nullable()->change();
            $table->text('cups_servicio')->nullable()->change();
            $table->text('cargo_servicio')->nullable()->change();
            $table->text('descripcion_servicio')->nullable()->change();
            $table->text('autorizacion_servicio')->nullable()->change();
            $table->text('prefijo_documento')->nullable()->change();
            $table->text('numero_documento')->nullable()->change();
            $table->text('interface_documento')->nullable()->change();
            $table->text('adquiriente')->nullable()->change();
            $table->text('observacion_documento')->nullable()->change();
            $table->text('estado_documento_dian')->nullable()->change();
            $table->text('estado_transaccion_dian')->nullable()->change();
            $table->text('resultado_dian')->nullable()->change();
            $table->text('cufe')->nullable()->change();
            $table->text('numero_radicacion')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Revertir columnas a string
            $table->string('tipo_cliente')->nullable()->change();
            $table->string('documento_aseguradora')->nullable()->change();
            $table->string('plan')->nullable()->change();
            $table->string('numero_cuenta')->nullable()->change();
            $table->string('cups_servicio')->nullable()->change();
            $table->string('cargo_servicio')->nullable()->change();
            $table->string('descripcion_servicio')->nullable()->change();
            $table->string('autorizacion_servicio')->nullable()->change();
            $table->string('prefijo_documento')->nullable()->change();
            $table->string('numero_documento')->nullable()->change();
            $table->string('interface_documento')->nullable()->change();
            $table->string('adquiriente')->nullable()->change();
            $table->string('observacion_documento')->nullable()->change();
            $table->string('estado_documento_dian')->nullable()->change();
            $table->string('estado_transaccion_dian')->nullable()->change();
            $table->string('resultado_dian')->nullable()->change();
            $table->string('cufe')->nullable()->change();
            $table->string('numero_radicacion')->nullable()->change();
        });
    }
}; 