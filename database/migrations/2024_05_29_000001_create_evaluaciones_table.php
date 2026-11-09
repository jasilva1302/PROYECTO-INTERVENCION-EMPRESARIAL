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
        // Primero eliminamos la tabla si existe
        Schema::dropIfExists('evaluaciones');

        // Luego creamos la tabla con la estructura correcta
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->foreignId('creador_id')->constrained('users')->onDelete('cascade');
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_limite');
            $table->integer('tiempo_limite')->nullable(); // en minutos
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Índices
            $table->index('fecha_inicio');
            $table->index('fecha_limite');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluaciones');
    }
}; 