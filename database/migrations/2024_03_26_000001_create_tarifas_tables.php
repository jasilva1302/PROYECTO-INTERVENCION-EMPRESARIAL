<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Empresas table
        if (!Schema::hasTable('empresas')) {
            Schema::create('empresas', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->string('nit')->nullable();
                $table->string('direccion')->nullable();
                $table->string('telefono')->nullable();
                $table->string('email')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // Sedes table
        if (!Schema::hasTable('sedes')) {
            Schema::create('sedes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas');
                $table->string('nombre');
                $table->string('direccion')->nullable();
                $table->string('telefono')->nullable();
                $table->string('email')->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // Tarifas table
        if (!Schema::hasTable('tarifas')) {
            Schema::create('tarifas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas');
                $table->foreignId('sede_id')->constrained('sedes');
                $table->string('tercero');
                $table->string('regimen');
                $table->string('descripcion_del_servicio');
                $table->string('cups');
                $table->string('cups_facturados')->nullable();
                $table->decimal('tarifas_de_manual', 12, 2)->nullable();
                $table->decimal('valor', 12, 2);
                $table->year('year');
                $table->boolean('activo')->default(true);
                $table->timestamps();
            });
        }

        // Tarifa Historial table
        if (!Schema::hasTable('tarifa_historial')) {
            Schema::create('tarifa_historial', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tarifa_id')->constrained('tarifas')->onDelete('cascade');
                $table->foreignId('user_id')->constrained('users');
                $table->decimal('valor_anterior', 12, 2);
                $table->decimal('valor_nuevo', 12, 2);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('tarifa_historial');
        Schema::dropIfExists('tarifas');
        Schema::dropIfExists('sedes');
        Schema::dropIfExists('empresas');
    }
}; 