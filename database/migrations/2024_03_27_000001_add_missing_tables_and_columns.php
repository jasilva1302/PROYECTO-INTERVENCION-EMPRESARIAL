<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Verificar y agregar columnas faltantes en la tabla modules
        if (Schema::hasTable('modules')) {
            Schema::table('modules', function (Blueprint $table) {
                if (!Schema::hasColumn('modules', 'route')) {
                    $table->string('route')->nullable()->after('description');
                }
                if (!Schema::hasColumn('modules', 'icon')) {
                    $table->string('icon')->nullable()->after('route');
                }
                if (!Schema::hasColumn('modules', 'order')) {
                    $table->integer('order')->default(0)->after('is_active');
                }
            });
        }

        // Verificar y agregar columnas faltantes en la tabla permissions
        if (Schema::hasTable('permissions')) {
            Schema::table('permissions', function (Blueprint $table) {
                if (!Schema::hasColumn('permissions', 'module_id')) {
                    $table->unsignedBigInteger('module_id')->after('description');
                    $table->foreign('module_id')->references('id')->on('modules')->onDelete('cascade');
                }
            });
        }

        // Verificar y agregar columnas faltantes en la tabla roles
        if (Schema::hasTable('roles')) {
            Schema::table('roles', function (Blueprint $table) {
                if (!Schema::hasColumn('roles', 'description')) {
                    $table->string('description')->nullable()->after('name');
                }
            });
        }

        // Verificar y crear la tabla permission_role si no existe
        if (!Schema::hasTable('permission_role')) {
            Schema::create('permission_role', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('permission_id');
                $table->unsignedBigInteger('role_id');
                $table->timestamps();
                $table->unique(['permission_id', 'role_id']);
                $table->foreign('permission_id')->references('id')->on('permissions')->onDelete('cascade');
                $table->foreign('role_id')->references('id')->on('roles')->onDelete('cascade');
            });
        }

        // Verificar y agregar columnas faltantes en la tabla users
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                if (!Schema::hasColumn('users', 'role_id')) {
                    $table->unsignedBigInteger('role_id')->nullable()->after('password');
                    $table->foreign('role_id')->references('id')->on('roles')->onDelete('set null');
                }
                if (!Schema::hasColumn('users', 'is_active')) {
                    $table->boolean('is_active')->default(true)->after('role_id');
                }
            });
        }

        // Verificar y crear la tabla sessions si no existe
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down()
    {
        // No implementamos down() ya que queremos mantener los datos existentes
    }
}; 