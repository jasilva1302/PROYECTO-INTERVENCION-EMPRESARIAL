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
        Schema::dropIfExists('evaluaciones_usuarios');

        // Luego creamos la tabla con la estructura correcta
        Schema::create('evaluaciones_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('evaluaciones')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('estado', ['pendiente', 'en_progreso', 'completada', 'calificada'])->default('pendiente');
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->decimal('calificacion', 5, 2)->nullable();
            $table->text('retroalimentacion')->nullable();
            $table->timestamps();

            // Índices
            $table->unique(['evaluacion_id', 'user_id']);
            $table->index('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_usuarios');
    }
}; 