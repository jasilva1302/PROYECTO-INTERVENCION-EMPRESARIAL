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
        Schema::create('respuestas_usuarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_usuario_id')->constrained('evaluaciones_usuarios')->onDelete('cascade');
            $table->foreignId('pregunta_id')->constrained('preguntas')->onDelete('cascade');
            $table->text('respuesta_texto')->nullable();
            $table->foreignId('opcion_seleccionada_id')->nullable()->constrained('opciones_respuesta')->onDelete('cascade');
            $table->decimal('puntaje_obtenido', 8, 2)->nullable();
            $table->text('retroalimentacion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respuestas_usuarios');
    }
};
