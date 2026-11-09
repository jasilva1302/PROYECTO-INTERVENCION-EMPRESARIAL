<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('observaciones_radicacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->text('observacion');
            $table->timestamps();

            // Índices para mejorar el rendimiento
            $table->index(['factura_id', 'created_at']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('observaciones_radicacion');
    }
}; 