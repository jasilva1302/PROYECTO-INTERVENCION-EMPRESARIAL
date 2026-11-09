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
            // Modificar columnas para permitir valores nulos
            $table->string('tipo_cliente')->nullable()->change();
            $table->string('documento_aseguradora')->nullable()->change();
            $table->string('plan')->nullable()->change();
            $table->string('numero_cuenta')->nullable()->change();
            $table->string('cups_servicio')->nullable()->change();
            $table->string('cargo_servicio')->nullable()->change();
            $table->string('descripcion_servicio')->nullable()->change();
            $table->string('autorizacion_servicio')->nullable()->change();
            $table->decimal('valor_unitario_servicio', 15, 2)->nullable()->change();
            $table->decimal('valor_total_servicio', 15, 2)->nullable()->change();
            $table->string('prefijo_documento')->nullable()->change();
            $table->string('numero_documento')->nullable()->change();
            $table->date('periodo_facturacion_inicio')->nullable()->change();
            $table->date('periodo_facturacion_final')->nullable()->change();
            $table->string('contabilizacion_documento')->nullable()->change();
            $table->string('interface_documento')->nullable()->change();
            $table->string('adquiriente')->nullable()->change();
            $table->decimal('total_documento', 15, 2)->nullable()->change();
            $table->decimal('saldo', 15, 2)->nullable()->change();
            $table->text('observacion_documento')->nullable()->change();
            $table->string('estado_documento_dian')->nullable()->change();
            $table->string('estado_transaccion_dian')->nullable()->change();
            $table->date('fecha_validacion_dian')->nullable()->change();
            $table->string('resultado_dian')->nullable()->change();
            $table->string('cufe')->nullable()->change();
            $table->date('fecha_radicacion')->nullable()->change();
            $table->string('numero_radicacion')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('facturas', function (Blueprint $table) {
            // Revertir columnas a no nulas
            $table->string('tipo_cliente')->nullable(false)->change();
            $table->string('documento_aseguradora')->nullable(false)->change();
            $table->string('plan')->nullable(false)->change();
            $table->string('numero_cuenta')->nullable(false)->change();
            $table->string('cups_servicio')->nullable(false)->change();
            $table->string('cargo_servicio')->nullable(false)->change();
            $table->string('descripcion_servicio')->nullable(false)->change();
            $table->string('autorizacion_servicio')->nullable(false)->change();
            $table->decimal('valor_unitario_servicio', 15, 2)->nullable(false)->change();
            $table->decimal('valor_total_servicio', 15, 2)->nullable(false)->change();
            $table->string('prefijo_documento')->nullable(false)->change();
            $table->string('numero_documento')->nullable(false)->change();
            $table->date('periodo_facturacion_inicio')->nullable(false)->change();
            $table->date('periodo_facturacion_final')->nullable(false)->change();
            $table->date('contabilizacion_documento')->nullable(false)->change();
            $table->string('interface_documento')->nullable(false)->change();
            $table->string('adquiriente')->nullable(false)->change();
            $table->decimal('total_documento', 15, 2)->nullable(false)->change();
            $table->decimal('saldo', 15, 2)->nullable(false)->change();
            $table->text('observacion_documento')->nullable(false)->change();
            $table->string('estado_documento_dian')->nullable(false)->change();
            $table->string('estado_transaccion_dian')->nullable(false)->change();
            $table->date('fecha_validacion_dian')->nullable(false)->change();
            $table->string('resultado_dian')->nullable(false)->change();
            $table->string('cufe')->nullable(false)->change();
            $table->date('fecha_radicacion')->nullable(false)->change();
            $table->string('numero_radicacion')->nullable(false)->change();
        });
    }
}; 