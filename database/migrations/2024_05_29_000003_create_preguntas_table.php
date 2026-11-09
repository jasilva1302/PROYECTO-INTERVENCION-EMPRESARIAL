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
        // Primero eliminamos las tablas si existen (en orden inverso por las dependencias)
        Schema::dropIfExists('respuestas_usuario');
        Schema::dropIfExists('opciones_respuesta');
        Schema::dropIfExists('preguntas');

        // Luego creamos las tablas con la estructura correcta
        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('evaluaciones')->onDelete('cascade');
            $table->text('pregunta');
            $table->enum('tipo', ['opcion_multiple', 'texto_libre', 'verdadero_falso']);
            $table->integer('puntaje');
            $table->integer('orden');
            $table->timestamps();

            // Índices
            $table->index('evaluacion_id');
            $table->index('orden');
        });

        Schema::create('opciones_respuesta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pregunta_id')->constrained()->onDelete('cascade');
            $table->text('opcion');
            $table->boolean('es_correcta')->default(false);
            $table->integer('orden');
            $table->timestamps();

            // Índices
            $table->index('pregunta_id');
            $table->index('orden');
        });

        Schema::create('respuestas_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_usuario_id')->constrained('evaluaciones_usuarios')->onDelete('cascade');
            $table->foreignId('pregunta_id')->constrained()->onDelete('cascade');
            $table->text('respuesta_texto')->nullable();
            $table->foreignId('opcion_seleccionada_id')->nullable()->constrained('opciones_respuesta')->onDelete('set null');
            $table->decimal('puntaje_obtenido', 5, 2)->nullable();
            $table->text('retroalimentacion')->nullable();
            $table->timestamps();

            // Índices
            $table->index('evaluacion_usuario_id');
            $table->index('pregunta_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respuestas_usuario');
        Schema::dropIfExists('opciones_respuesta');
        Schema::dropIfExists('preguntas');
    }
}; 