<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('facturacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empresa_id')->constrained('empresas');
            $table->foreignId('sede_id')->constrained('sedes');
            $table->foreignId('aseguradora_id')->constrained('aseguradoras');
            $table->string('numero_factura');
            $table->date('periodo_facturacion_inicio');
            $table->date('periodo_facturacion_final');
            $table->date('fecha_radicacion')->nullable();
            $table->string('numero_radicacion')->nullable();
            $table->timestamps();
            
            // Índices para mejorar el rendimiento de las consultas
            $table->index('periodo_facturacion_inicio');
            $table->index('periodo_facturacion_final');
            $table->index('fecha_radicacion');
            $table->index(['empresa_id', 'sede_id', 'aseguradora_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('facturacion');
    }
}; 