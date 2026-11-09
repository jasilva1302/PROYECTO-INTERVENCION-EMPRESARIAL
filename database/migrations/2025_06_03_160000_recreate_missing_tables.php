<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // Crear tabla tarifas si no existe
        if (!Schema::hasTable('tarifas')) {
            Schema::create('tarifas', function (Blueprint $table) {
                $table->id();
                $table->string('cups')->unique();
                $table->string('descripcion');
                $table->decimal('valor', 15, 2);
                $table->boolean('is_active')->default(true);
                $table->date('vigencia')->nullable();
                $table->timestamps();
            });
        }

        // Crear tabla roles si no existe
        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // Crear tabla permissions si no existe
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        // Crear tabla role_permission si no existe
        if (!Schema::hasTable('role_permission')) {
            Schema::create('role_permission', function (Blueprint $table) {
                $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
                $table->foreignId('permission_id')->constrained('permissions')->onDelete('cascade');
                $table->primary(['role_id', 'permission_id']);
            });
        }

        // Crear tabla evaluaciones si no existe
        if (!Schema::hasTable('evaluaciones')) {
            Schema::create('evaluaciones', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->text('descripcion')->nullable();
                $table->boolean('activa')->default(true);
                $table->timestamps();
            });
        }

        // Crear tabla evaluaciones_usuarios si no existe
        if (!Schema::hasTable('evaluaciones_usuarios')) {
            Schema::create('evaluaciones_usuarios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('evaluacion_id')->constrained('evaluaciones')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->timestamp('fecha_inicio')->nullable();
                $table->timestamp('fecha_fin')->nullable();
                $table->string('estado')->default('pendiente');
                $table->decimal('calificacion', 5, 2)->nullable();
                $table->timestamps();

                $table->index(['evaluacion_id', 'user_id']);
            });
        }

        // Crear tabla preguntas si no existe
        if (!Schema::hasTable('preguntas')) {
            Schema::create('preguntas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('evaluacion_id')->constrained('evaluaciones')->onDelete('cascade');
                $table->text('pregunta');
                $table->string('tipo')->default('multiple');
                $table->json('opciones')->nullable();
                $table->boolean('obligatoria')->default(true);
                $table->integer('orden')->default(0);
                $table->timestamps();

                $table->index('evaluacion_id');
            });
        }

        // Crear tabla respuestas_usuarios si no existe
        if (!Schema::hasTable('respuestas_usuarios')) {
            Schema::create('respuestas_usuarios', function (Blueprint $table) {
                $table->id();
                $table->foreignId('evaluacion_usuario_id')->constrained('evaluaciones_usuarios')->onDelete('cascade');
                $table->foreignId('pregunta_id')->constrained('preguntas')->onDelete('cascade');
                $table->text('respuesta');
                $table->timestamps();

                $table->index(['evaluacion_usuario_id', 'pregunta_id']);
            });
        }

        // Crear tabla audit_facturas si no existe
        if (!Schema::hasTable('audit_facturas')) {
            Schema::create('audit_facturas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('auditoria_id')->constrained('auditorias')->onDelete('cascade');
                $table->foreignId('factura_id')->constrained('facturas')->onDelete('cascade');
                $table->timestamps();

                $table->unique(['auditoria_id', 'factura_id']);
            });
        }
    }

    public function down()
    {
        // No hacemos nada en down() para evitar pérdida de datos
    }
}; 