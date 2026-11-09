<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('creador_id'); // ID del usuario que crea la evaluación
            $table->datetime('fecha_inicio');
            $table->datetime('fecha_limite');
            $table->integer('tiempo_limite')->nullable(); // Tiempo límite en minutos
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('creador_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('preguntas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluacion_id');
            $table->text('pregunta');
            $table->enum('tipo', ['opcion_multiple', 'texto_libre', 'verdadero_falso']);
            $table->integer('puntaje')->default(1);
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->foreign('evaluacion_id')->references('id')->on('evaluaciones')->onDelete('cascade');
        });

        Schema::create('opciones_respuesta', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pregunta_id');
            $table->text('opcion');
            $table->boolean('es_correcta')->default(false);
            $table->integer('orden')->default(0);
            $table->timestamps();

            $table->foreign('pregunta_id')->references('id')->on('preguntas')->onDelete('cascade');
        });

        Schema::create('evaluaciones_usuarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluacion_id');
            $table->unsignedBigInteger('user_id');
            $table->datetime('fecha_inicio')->nullable();
            $table->datetime('fecha_fin')->nullable();
            $table->decimal('calificacion', 5, 2)->nullable();
            $table->enum('estado', ['pendiente', 'en_progreso', 'completada', 'calificada'])->default('pendiente');
            $table->text('retroalimentacion')->nullable();
            $table->timestamps();

            $table->foreign('evaluacion_id')->references('id')->on('evaluaciones')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->unique(['evaluacion_id', 'user_id']);
        });

        Schema::create('respuestas_usuarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('evaluacion_usuario_id');
            $table->unsignedBigInteger('pregunta_id');
            $table->text('respuesta_texto')->nullable();
            $table->unsignedBigInteger('opcion_seleccionada_id')->nullable();
            $table->decimal('puntaje_obtenido', 5, 2)->nullable();
            $table->text('retroalimentacion')->nullable();
            $table->timestamps();

            $table->foreign('evaluacion_usuario_id')->references('id')->on('evaluaciones_usuarios')->onDelete('cascade');
            $table->foreign('pregunta_id')->references('id')->on('preguntas')->onDelete('cascade');
            $table->foreign('opcion_seleccionada_id')->references('id')->on('opciones_respuesta')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('respuestas_usuarios');
        Schema::dropIfExists('evaluaciones_usuarios');
        Schema::dropIfExists('opciones_respuesta');
        Schema::dropIfExists('preguntas');
        Schema::dropIfExists('evaluaciones');
    }
}; 