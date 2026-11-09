<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Crear tabla de módulos si no existe
        if (!Schema::hasTable('modules')) {
            Schema::create('modules', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Crear tabla de permisos si no existe
        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->string('description')->nullable();
                $table->unsignedBigInteger('module_id');
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
            });
        }

        // Crear tabla pivote permission_role si no existe
        if (!Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamps();

                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');

                $table->unique(['permission_id', 'role_id']);
            });
        }
    }

    public function down()
    {
        // Solo eliminar las tablas si existen
        Schema::dropIfExists('permission_role');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('modules');
    }
}; 